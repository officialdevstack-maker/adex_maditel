<?php

namespace App\Console\Commands;

use App\Services\ParentSync\ParentFundingService;
use App\Services\ParentSync\SyncClient;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Pull the credit events the parent raised when this child's parent-issued
 * virtual accounts were funded, credit the local customer's wallet for each,
 * and ack the outcome. The mirror of the parent's ChildFundingController credit
 * outbox; the same reliable pull/ack pattern as ParentSyncPullDirectives.
 *
 * Applying a credit is idempotent on the parent's funding reference (see
 * ParentFundingService::applyCredit), so a re-delivered event never
 * double-credits a wallet.
 */
class ParentSyncPullCreditEvents extends Command
{
    protected $signature = 'parent-sync:pull-credit-events';

    protected $description = 'Pull and apply parent-relayed wallet credits for aggregated funding';

    public function handle(SyncClient $client, ParentFundingService $funding): int
    {
        if (!config('parent_sync.enabled')) {
            $this->info('Parent sync disabled — nothing to do.');
            return self::SUCCESS;
        }

        if (!$funding->isEnabled()) {
            // Aggregation off — the parent isn't managing this child's funding.
            return self::SUCCESS;
        }

        $events = $client->fetchCreditEvents();
        if (empty($events)) {
            return self::SUCCESS;
        }

        $applied = 0;
        $failed = 0;

        foreach ($events as $event) {
            if (empty($event['id'])) {
                continue;
            }

            try {
                $result = $funding->applyCredit($event);
            } catch (\Throwable $e) {
                Log::channel('parent-sync')->error('Apply credit event threw', [
                    'credit_event_id' => $event['id'],
                    'error' => $e->getMessage(),
                ]);
                // Don't ack — let the parent redeliver so a transient local
                // failure is retried instead of being lost.
                continue;
            }

            $note = $result === 'failed'
                ? 'no local customer for external_customer_id ' . ($event['external_customer_id'] ?? '?')
                : null;

            if ($client->ackCreditEvent((int) $event['id'], $result, $note)) {
                $result === 'credited' ? $applied++ : $failed++;
            }
        }

        $this->info("Credit events: {$applied} applied, {$failed} unmatched.");

        return self::SUCCESS;
    }
}
