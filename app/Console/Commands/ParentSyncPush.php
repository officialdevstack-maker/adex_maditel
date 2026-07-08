<?php

namespace App\Console\Commands;

use App\Services\ParentSync\SyncClient;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * Generic diff/push engine — reads everything from config/parent_sync.php,
 * no adex_maditel-specific column names hardcoded. Copy verbatim into a
 * future child app; only config/parent_sync.php should need editing.
 *
 * For each configured resource: WHERE {pk} > {cursor} ORDER BY {pk} LIMIT
 * {batch_size}, map columns, sign, POST to the parent. The local cursor
 * (parent_sync_state) only advances on a confirmed 2xx, so a failed push
 * is retried next run rather than silently dropped.
 */
class ParentSyncPush extends Command
{
    protected $signature = 'parent-sync:push {--dry-run : Log what would be sent without actually calling the parent}';

    protected $description = 'Push customer/transaction deltas to the parent platform';

    public function handle(SyncClient $client)
    {
        $config = config('parent_sync');

        if (!$config['enabled'] && !$this->option('dry-run')) {
            $this->warn('parent_sync.enabled is false — set PARENT_SYNC_ENABLED=true or pass --dry-run.');
            return self::SUCCESS;
        }

        $forceDryRun = $this->option('dry-run');

        foreach ($config['resources'] as $resource => $resourceConfig) {
            $this->syncResource($client, $resource, $resourceConfig, $forceDryRun);
        }

        return self::SUCCESS;
    }

    protected function syncResource(SyncClient $client, string $resource, array $resourceConfig, bool $forceDryRun): void
    {
        $table = $resourceConfig['table'];
        $pk = $resourceConfig['pk'];
        $externalIdColumn = $resourceConfig['external_id_column'];

        // Step-0 safety net: refuse to run against a table/column that
        // doesn't actually exist rather than assume the reverse-engineered
        // config is correct — see config/parent_sync.php's header comment.
        if (!Schema::hasTable($table) || !Schema::hasColumn($table, $pk) || !Schema::hasColumn($table, $externalIdColumn)) {
            $this->error("[{$resource}] table/column mismatch — check config/parent_sync.php against the real schema (table={$table}, pk={$pk}, external_id_column={$externalIdColumn}).");
            Log::channel('parent-sync')->error("Schema mismatch for resource [{$resource}]", $resourceConfig);
            return;
        }

        $state = DB::table('parent_sync_state')->where('resource', $resource)->first();
        $lastId = $state->last_id ?? 0;

        $rows = DB::table($table)
            ->where($pk, '>', $lastId)
            ->orderBy($pk)
            ->limit($resourceConfig['batch_size'] ?? config('parent_sync.batch_size'))
            ->get();

        if ($rows->isEmpty()) {
            $this->line("[{$resource}] nothing new since id > {$lastId}.");
            return;
        }

        $records = $rows->map(fn ($row) => $this->mapRow($row, $resourceConfig, $externalIdColumn))->all();
        $firstId = $rows->first()->{$pk};
        $lastRowId = $rows->last()->{$pk};

        // Deterministic per batch range so a retry of the SAME unadvanced
        // cursor reuses the same event_id — the parent's idempotency check
        // is keyed on event_id, so this is what makes "failed push, retry
        // next run" safe against double-ingestion if the parent actually
        // processed it but we never saw the response.
        $eventId = md5("{$resource}:{$firstId}:{$lastRowId}");

        if ($forceDryRun) {
            config(['parent_sync.dry_run' => true]);
        }

        $success = $client->pushBatch($eventId, $resource, $records);

        if (!$success) {
            $this->error("[{$resource}] push failed — cursor not advanced, will retry next run.");
            return;
        }

        DB::table('parent_sync_state')->updateOrInsert(
            ['resource' => $resource],
            ['last_id' => $lastRowId, 'last_synced_at' => now(), 'updated_at' => now(), 'created_at' => now()]
        );

        $this->info("[{$resource}] pushed " . count($records) . " record(s), cursor now {$lastRowId}.");
    }

    protected function mapRow($row, array $resourceConfig, string $externalIdColumn): array
    {
        $row = (array) $row;
        $record = ['external_id' => $row[$externalIdColumn] ?? null];

        if (!empty($resourceConfig['external_customer_id_column'])) {
            $record['external_customer_id'] = $row[$resourceConfig['external_customer_id_column']] ?? null;
        }

        foreach ($resourceConfig['columns'] as $localColumn => $payloadKey) {
            $record[$payloadKey] = $row[$localColumn] ?? null;
        }

        return $record;
    }
}
