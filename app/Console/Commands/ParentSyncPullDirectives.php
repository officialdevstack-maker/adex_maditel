<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\ParentSync\SyncClient;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

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

        // external_id can be the local numeric id or a stable string key
        // (e.g. username). Try numeric id first, then fall back to
        // username lookup.
        $user = null;
        if (is_numeric($externalId)) {
            $user = User::find((int) $externalId);
        }
        if (!$user) {
            $user = User::where('username', (string) $externalId)->first();
        }
        if (!$user) {
            // Permanent condition — retrying won't conjure the user. Ack it
            // (by returning normally) and leave the trail in the log.
            Log::channel('parent-sync')->warning('redirect_user: no local user for external_id', [
                'external_id' => $externalId,
            ]);
            return;
        }

        // The redirect columns come from
        // 2026_07_08_120000_add_parent_migration_fields_to_user_table. If that
        // migration hasn't run on this child, the UPDATE below throws a raw
        // "unknown column" error that reads like a mystery in the log and
        // leaves the directive stuck Pending forever. Fail with a clear,
        // actionable message instead (still left pending, so it applies as
        // soon as the migration is run).
        foreach (['parent_redirect_url', 'migrated_to_parent_at'] as $column) {
            if (!Schema::hasColumn('user', $column)) {
                throw new \RuntimeException(
                    "user.{$column} column is missing — run `php artisan migrate` on this child so redirect_user directives can apply."
                );
            }
        }

        DB::table('user')->where('id', $user->id)->update([
            'parent_redirect_url' => $targetUrl,
            'migrated_to_parent_at' => now(),
        ]);
    }
}
