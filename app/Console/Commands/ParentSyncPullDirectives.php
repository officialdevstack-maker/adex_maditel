<?php

namespace App\Console\Commands;

use App\Services\ParentSync\SyncClient;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 2: fetches pending directives and dispatches each to a per-type
 * handler, then acks with the actual outcome so the parent can show
 * executed / failed / skipped instead of a blanket "delivered".
 *
 * Failure semantics:
 *  - a handler THROWS            -> transient; no ack, the directive stays
 *                                   pending on the parent and is retried
 *                                   next run (handlers must be idempotent)
 *  - a handler returns 'failed'  -> permanent; acked as failed with a note
 *                                   so it stops retrying and the admin sees
 *                                   why it could not be executed
 *  - a handler returns 'skipped' -> this child doesn't support the type;
 *                                   acked as skipped so the parent UI stops
 *                                   pretending it was applied
 */
class ParentSyncPullDirectives extends Command
{
    protected $signature = 'parent-sync:pull-directives';

    protected $description = 'Fetch pending directives from the parent and execute them';

    // Columns of the child `settings` table the parent's update_settings
    // directive may write. Must stay in sync with ProcessFlagKey in the
    // parent admin UI (vtu_2 affiliates/service.ts).
    protected const SETTINGS_ALLOWLIST = [
        'is_verify_email',
        'flutterwave',
        'monnify',
        'monnify_atm',
        'wema',
        'earning',
        'referral',
    ];

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
                $outcome = $this->dispatchDirective($directive);
            } catch (\Throwable $e) {
                // Transient — left pending on the parent, retried next run.
                $this->error("Directive #{$directive['id']} failed: {$e->getMessage()}");
                Log::channel('parent-sync')->error('Directive handling failed — left pending for retry', [
                    'directive' => $directive,
                    'error' => $e->getMessage(),
                ]);
                continue;
            }

            $result = $outcome['result'];
            $note = $outcome['note'] ?? null;

            if (!$client->ackDirective((int) $directive['id'], $result, $note)) {
                // Handled but not acked: it will be re-fetched and re-handled
                // next run, which idempotent handlers absorb harmlessly.
                Log::channel('parent-sync')->warning('Directive handled but ack failed', [
                    'directive_id' => $directive['id'],
                    'result' => $result,
                ]);
            }

            $this->info("Directive #{$directive['id']} type={$directive['type']} result={$result}" . ($note ? " ({$note})" : ''));
        }

        return self::SUCCESS;
    }

    /**
     * @return array{result: string, note: ?string}
     */
    protected function dispatchDirective(array $directive): array
    {
        $payload = (array) ($directive['payload'] ?? []);

        switch ($directive['type']) {
            case 'redirect_user':
                return $this->handleRedirectUser($payload);

            case 'redirect_all_users':
                return $this->handleRedirectAllUsers($payload);

            case 'update_settings':
                return $this->handleUpdateSettings($payload);

            case 'reroute_provider':
                return $this->handleRerouteProvider($payload);

            case 'message':
                // A note for whoever operates this child — no machine action.
                Log::channel('parent-sync')->info('Message from parent', [
                    'text' => $payload['text'] ?? '',
                    'directive_id' => $directive['id'],
                ]);
                return ['result' => 'executed', 'note' => null];

            case 'retry_transaction':
                $transid = $payload['external_id'] ?? null;
                if (!$transid) {
                    return ['result' => 'failed', 'note' => 'retry_transaction payload missing external_id'];
                }
                // Data purchases only — the sole transaction resource synced
                // to the parent. See TransactionRetryService for the safety
                // rules (stuck txns only, same transid, refund on hard fail).
                return app(\App\Services\ParentSync\TransactionRetryService::class)
                    ->retry((string) $transid);

            default:
                Log::channel('parent-sync')->warning('Unknown directive type — acked as skipped', [
                    'directive' => $directive,
                ]);
                return ['result' => 'skipped', 'note' => "unknown directive type '{$directive['type']}'"];
        }
    }

    /**
     * Redirect ONE customer to the parent platform, matched by the same
     * external id the push sync reports for customers (see
     * parent_sync.resources.customers.external_id_column — username here).
     *
     * @return array{result: string, note: ?string}
     */
    protected function handleRedirectUser(array $payload): array
    {
        $targetUrl = $payload['target_url'] ?? null;
        $externalId = $payload['external_id'] ?? null;
        $enabled = (bool) ($payload['enabled'] ?? true);

        if (!$targetUrl) {
            return ['result' => 'failed', 'note' => 'redirect_user payload missing target_url'];
        }
        if (!$externalId) {
            return ['result' => 'failed', 'note' => 'redirect_user payload missing external_id'];
        }

        $this->assertRedirectColumnsExist();

        $idColumn = config('parent_sync.resources.customers.external_id_column', 'username');

        $updated = DB::table('user')
            ->where($idColumn, $externalId)
            ->update([
                'parent_redirect_url' => $enabled ? $targetUrl : null,
                'migrated_to_parent_at' => $enabled ? now() : null,
            ]);

        if ($updated === 0 && DB::table('user')->where($idColumn, $externalId)->doesntExist()) {
            return ['result' => 'failed', 'note' => "no local user with {$idColumn}='{$externalId}'"];
        }

        return ['result' => 'executed', 'note' => null];
    }

    /**
     * Apply (or clear, when enabled=false) a redirect for EVERY local user.
     *
     * @return array{result: string, note: ?string}
     */
    protected function handleRedirectAllUsers(array $payload): array
    {
        $targetUrl = $payload['target_url'] ?? null;
        $enabled = (bool) ($payload['enabled'] ?? true);

        if ($enabled && !$targetUrl) {
            return ['result' => 'failed', 'note' => 'redirect_all_users payload missing target_url'];
        }

        $this->assertRedirectColumnsExist();

        $query = DB::table('user');

        if (!$enabled) {
            $query->whereNotNull('parent_redirect_url');
        }

        $query->update([
            'parent_redirect_url' => $enabled ? $targetUrl : null,
            'migrated_to_parent_at' => $enabled ? now() : null,
        ]);

        return ['result' => 'executed', 'note' => null];
    }

    /**
     * Write allowlisted process flags into the single-row `settings` table.
     * Unknown keys and keys whose column doesn't exist locally are reported
     * back in the note rather than silently dropped.
     *
     * @return array{result: string, note: ?string}
     */
    protected function handleUpdateSettings(array $payload): array
    {
        $settings = (array) ($payload['settings'] ?? []);

        if (empty($settings)) {
            return ['result' => 'failed', 'note' => 'update_settings payload has no settings map'];
        }

        $applied = [];
        $rejected = [];

        foreach ($settings as $key => $value) {
            if (!in_array($key, self::SETTINGS_ALLOWLIST, true) || !Schema::hasColumn('settings', $key)) {
                $rejected[] = $key;
                continue;
            }
            // Legacy schema stores these flags as 0/1 ints.
            $applied[$key] = (int) filter_var($value, FILTER_VALIDATE_BOOLEAN);
        }

        if (empty($applied)) {
            return ['result' => 'failed', 'note' => 'no applicable settings keys: ' . implode(', ', $rejected)];
        }

        // Single-row table — same whole-table update the child's own admin
        // code uses (API\AdminController).
        DB::table('settings')->update($applied);

        $note = empty($rejected)
            ? 'applied: ' . implode(', ', array_keys($applied))
            : 'applied: ' . implode(', ', array_keys($applied)) . '; ignored: ' . implode(', ', $rejected);

        return ['result' => 'executed', 'note' => $note];
    }

    /**
     * Point one upstream provider slot (web_api.adex_website{slot}) at a new
     * base URL. Slots 1..5 exist in the legacy schema; the parent UI offers
     * 1..3.
     *
     * @return array{result: string, note: ?string}
     */
    protected function handleRerouteProvider(array $payload): array
    {
        $slot = (string) ($payload['slot'] ?? '');
        $websiteUrl = $payload['website_url'] ?? null;

        if (!in_array($slot, ['1', '2', '3', '4', '5'], true)) {
            return ['result' => 'failed', 'note' => "reroute_provider slot must be 1-5, got '{$slot}'"];
        }
        if (!$websiteUrl || !filter_var($websiteUrl, FILTER_VALIDATE_URL)) {
            return ['result' => 'failed', 'note' => 'reroute_provider payload missing a valid website_url'];
        }

        $column = "adex_website{$slot}";
        if (!Schema::hasColumn('web_api', $column)) {
            return ['result' => 'failed', 'note' => "web_api.{$column} column does not exist on this child"];
        }

        // Single-row table, same convention as `settings`.
        DB::table('web_api')->update([$column => rtrim($websiteUrl, '/')]);
        $note = "web_api.{$column} updated";

        // Slot credentials live in adex_api.adex{slot}_username/_password —
        // that's what DataSend/AirtimeSend actually read when building the
        // Basic auth header. Required when tunneling the slot to the parent
        // (the parent account whose wallet funds the tunneled transactions).
        $credentials = [];
        foreach (['username', 'password'] as $field) {
            $value = $payload[$field] ?? null;
            $credColumn = "adex{$slot}_{$field}";
            if ($value !== null && $value !== '') {
                if (Schema::hasColumn('adex_api', $credColumn)) {
                    $credentials[$credColumn] = $value;
                } else {
                    $note .= "; {$field} ignored (no adex_api.{$credColumn} column)";
                }
            }
        }
        if (!empty($credentials)) {
            DB::table('adex_api')->update($credentials);
            $note .= ', credentials updated';
        }

        return ['result' => 'executed', 'note' => $note];
    }

    protected function assertRedirectColumnsExist(): void
    {
        foreach (['parent_redirect_url', 'migrated_to_parent_at'] as $column) {
            if (!Schema::hasColumn('user', $column)) {
                // Throw (transient) rather than fail permanently — running
                // `php artisan migrate` on this child fixes it, and the
                // directive should then apply on the next poll.
                throw new \RuntimeException(
                    "user.{$column} column is missing — run `php artisan migrate` on this child so redirect directives can apply."
                );
            }
        }
    }
}
