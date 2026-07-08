<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\ParentSync\SyncClient;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Phase 2: fetches pending directives and dispatches each to a per-type
 * handler, acking only after the handler succeeds. A handler failure
 * leaves the directive pending so it's retried next run — which means
 * every handler must be idempotent (an acked-but-lost 2xx, or a failed
 * ack after a successful handle, both replay the directive).
 */
class ParentSyncPullDirectives extends Command
{
    protected $signature = 'parent-sync:pull-directives';

    protected $description = 'Fetch pending directives from the parent and execute them';

    public function handle(SyncClient $client)
    {
        $config = config('parent_sync');

        if (!$config['enabled']) {
            $this->warn('parent_sync.enabled is false — nothing to do.');
            return self::SUCCESS;
        }

        $directives = $client->fetchDirectives();

        if (empty($directives)) {
            $this->line('No pending directives.');
            return self::SUCCESS;
        }

        foreach ($directives as $directive) {
            try {
                $this->dispatchDirective($directive);
            } catch (\Throwable $e) {
                // Left pending on the parent — retried next run.
                $this->error("Directive #{$directive['id']} failed: {$e->getMessage()}");
                Log::channel('parent-sync')->error('Directive handling failed — left pending for retry', [
                    'directive' => $directive,
                    'error' => $e->getMessage(),
                ]);
                continue;
            }

            if (!$client->ackDirective((int) $directive['id'])) {
                // Handled but not acked: it will be re-fetched and re-handled
                // next run, which idempotent handlers absorb harmlessly.
                Log::channel('parent-sync')->warning('Directive handled but ack failed', [
                    'directive_id' => $directive['id'],
                ]);
            }

            $this->info("Directive #{$directive['id']} type={$directive['type']} handled.");
        }

        return self::SUCCESS;
    }

    protected function dispatchDirective(array $directive): void
    {
        $payload = (array) ($directive['payload'] ?? []);

        switch ($directive['type']) {
            case 'redirect_user':
                $this->handleRedirectUser($payload);
                break;

            case 'message':
                // A note for whoever operates this child — no machine action.
                Log::channel('parent-sync')->info('Message from parent', [
                    'text' => $payload['text'] ?? '',
                    'directive_id' => $directive['id'],
                ]);
                break;

            default:
                // Ack unknown types rather than leaving them to clog the
                // queue forever — the parent keeps the directive row either
                // way, so nothing is lost.
                Log::channel('parent-sync')->warning('Unknown directive type — acked without action', [
                    'directive' => $directive,
                ]);
                break;
        }
    }

    /**
     * The parent has promoted this customer to a real parent account:
     * stamp the local row so the frontend (via the login response) tells
     * them their account has moved.
     */
    protected function handleRedirectUser(array $payload): void
    {
        $externalId = $payload['external_id'] ?? null;
        $targetUrl = $payload['target_url'] ?? null;

        if (!$externalId || !$targetUrl) {
            throw new \RuntimeException('redirect_user payload missing external_id or target_url');
        }

        $user = User::find($externalId);
        if (!$user) {
            // Permanent condition — retrying won't conjure the user. Ack it
            // (by returning normally) and leave the trail in the log.
            Log::channel('parent-sync')->warning('redirect_user: no local user for external_id', [
                'external_id' => $externalId,
            ]);
            return;
        }

        DB::table('user')->where('id', $user->id)->update([
            'parent_redirect_url' => $targetUrl,
            'migrated_to_parent_at' => now(),
        ]);
    }
}
