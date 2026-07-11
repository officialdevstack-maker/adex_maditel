<?php

namespace App\Services\ParentSync;

use App\Http\Controllers\Purchase\DataSend;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * Executes the parent's retry_transaction directive for DATA purchases (the
 * only transaction resource synced to the parent — see
 * config/parent_sync.php).
 *
 * Only a transaction stuck at plan_status=0 (user debited, provider never
 * confirmed) is re-dispatched — with its ORIGINAL transid so providers that
 * dedupe on request-id absorb an accidental double send:
 *  - already successful (1): nothing to retry
 *  - already failed+refunded (2): re-vending would hand out free data
 *
 * A definitive 'fail' on retry settles the transaction the same way the
 * original purchase flow would have: refund the debited wallet and mark
 * plan_status=2.
 */
class TransactionRetryService
{
    /**
     * @return array{result: string, note: ?string}
     */
    public function retry(string $transid): array
    {
        $row = DB::table('data')->where('transid', $transid)->first();
        if (!$row) {
            return ['result' => 'failed', 'note' => "no data transaction with transid='{$transid}'"];
        }

        if ((int) $row->plan_status === 1) {
            return ['result' => 'failed', 'note' => 'transaction already successful — nothing to retry'];
        }
        if ((int) $row->plan_status === 2) {
            return ['result' => 'failed', 'note' => 'transaction already failed and refunded — the user must repurchase'];
        }

        $plan = $this->matchPlan($row);
        if (!$plan) {
            return ['result' => 'failed', 'note' => "could not identify the original data plan for '{$row->plan_name}' ({$row->network_type})"];
        }

        $method = $this->vendingMethod($row, $plan);
        if (!$method) {
            return ['result' => 'failed', 'note' => 'no vending route configured for this network/plan type'];
        }

        $status = $this->send($method, [
            'purchase_plan' => $plan->plan_id,
            'transid' => $transid,
            'username' => $row->username,
        ]);

        if ($status === 'success') {
            DB::table('data')->where('transid', $transid)->update(['plan_status' => 1]);
            DB::table('message')->where('transid', $transid)->update(['plan_status' => 1]);
            return ['result' => 'executed', 'note' => "retry succeeded via {$method}"];
        }

        if ($status === 'fail') {
            $this->refund($row);
            return ['result' => 'executed', 'note' => "retry via {$method} failed definitively — user refunded"];
        }

        // 'process' / null — provider still deciding; leave the transaction
        // as-is so a later retry (or the provider's own settlement) can
        // finish the job.
        return ['result' => 'executed', 'note' => "retry via {$method} still processing at the provider"];
    }

    /**
     * The `data` row doesn't store the purchased plan_id — recover it by
     * matching the stored display name (plan_name.plan_size concat at
     * purchase time) within the same plan_type. Ambiguity is a hard stop:
     * guessing between two plans risks vending the wrong bundle.
     */
    protected function matchPlan(object $row): ?object
    {
        $candidates = DB::table('data_plan')
            ->where('plan_type', $row->network_type)
            ->get()
            ->filter(fn ($plan) => ($plan->plan_name . $plan->plan_size) === $row->plan_name)
            ->values();

        if ($candidates->count() !== 1) {
            Log::channel('parent-sync')->warning('retry_transaction: plan match not unique', [
                'transid' => $row->transid,
                'plan_name' => $row->plan_name,
                'matches' => $candidates->count(),
            ]);
            return null;
        }

        return $candidates->first();
    }

    /**
     * Same vending-route derivation the original DataPurchase flow uses:
     * data_sel.{network}_{g|cg|sme} names the DataSend method to call.
     */
    protected function vendingMethod(object $row, object $plan): ?string
    {
        $suffixes = [
            'GIFTING' => '_g',
            'COOPERATE GIFTING' => '_cg',
            'SME' => '_sme',
        ];
        $suffix = $suffixes[$plan->plan_type] ?? null;
        if (!$suffix) {
            return null;
        }

        $network = strtoupper((string) $row->network) === '9MOBILE'
            ? 'mobile'
            : strtolower((string) $row->network);
        $vending = $network . $suffix;

        $dataSel = DB::table('data_sel')->first();
        $method = $dataSel->{$vending} ?? null;

        return ($method && method_exists(DataSend::class, $method)) ? $method : null;
    }

    /**
     * Seam for tests — real sends go out through the legacy DataSend
     * senders (raw curl, not fakeable via Http::fake).
     */
    protected function send(string $method, array $data): ?string
    {
        return DataSend::$method($data);
    }

    /**
     * Mirror of DataPurchase's fail path: return the debited amount to the
     * wallet that paid (main balance or a network sub-wallet) and mark the
     * transaction failed. The plan_status!=2 re-check keeps a concurrent
     * settlement from refunding twice.
     */
    protected function refund(object $row): void
    {
        $fresh = DB::table('data')->where('transid', $row->transid)->first();
        if (!$fresh || (int) $fresh->plan_status === 2) {
            return;
        }

        $amount = (float) $row->amount;
        $walletSystem = strtolower((string) ($row->wallet ?? 'wallet'));

        if ($walletSystem === 'wallet' || $walletSystem === '') {
            $user = DB::table('user')->where('username', $row->username)->first();
            if ($user) {
                DB::table('user')->where('username', $row->username)->update(['bal' => $user->bal + $amount]);
            }
        } else {
            $column = $walletSystem . '_bal';
            if (Schema::hasColumn('wallet_funding', $column)) {
                $funding = DB::table('wallet_funding')->where('username', $row->username)->first();
                if ($funding) {
                    DB::table('wallet_funding')->where('username', $row->username)
                        ->update([$column => $funding->{$column} + $amount]);
                }
            }
        }

        DB::table('data')->where('transid', $row->transid)->update(['plan_status' => 2]);
        DB::table('message')->where('transid', $row->transid)->update(['plan_status' => 2]);
    }
}
