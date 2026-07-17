<?php

namespace App\Services\ParentSync;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * Child side of parent-managed funding.
 *
 * When the parent turns on aggregation (set_funding_mode directive), this child
 * stops issuing its own virtual accounts. Instead it asks the parent to issue
 * accounts for each customer, stores them in the customer's existing hard-coded
 * bank columns so the funding page shows them unchanged, and mirrors the credits
 * the parent relays onto local wallets.
 */
class ParentFundingService
{
    public function __construct(private SyncClient $client)
    {
    }

    /**
     * Parent bank name → the local `user` column the funding page reads.
     * A bank with no column here is stored nowhere and simply not shown.
     */
    private const BANK_COLUMNS = [
        'PALMPAY' => 'paypalmpay',
        'MONIEPOINT' => 'rolex',
        'MONIEPOINT MFB' => 'rolex',
        'WEMA' => 'wema',
        'WEMA BANK' => 'wema',
        'STERLING' => 'sterlen',
        'STERLING BANK' => 'sterlen',
        '9PSB' => 'fed',
        '9 PSB' => 'fed',
        '9MOBILE 9PAYMENT SERVICE BANK' => 'fed',
    ];

    public function isEnabled(): bool
    {
        if (!Schema::hasTable('parent_funding_config')) {
            return false;
        }

        return (bool) optional(DB::table('parent_funding_config')->first())->aggregate;
    }

    /** Persist the parent's desired funding mode (set_funding_mode directive). */
    public function setMode(bool $aggregate, ?string $parentUrl): void
    {
        DB::table('parent_funding_config')->updateOrInsert(
            ['id' => 1],
            ['aggregate' => $aggregate, 'parent_url' => $parentUrl, 'updated_at' => now(), 'created_at' => now()],
        );
    }

    /** The local column for a parent bank name, or null when unmapped. */
    public function bankColumnFor(?string $bankName): ?string
    {
        if (!$bankName) {
            return null;
        }

        return self::BANK_COLUMNS[strtoupper(trim($bankName))] ?? null;
    }

    /**
     * Ensure a customer has parent-issued accounts, storing each in its bank
     * column. Safe to call often — the parent is idempotent per customer, so
     * only pass regenerate=true on login/register. Returns the accounts, or []
     * when aggregation is off or generation failed.
     *
     * @param object $user A `user` row with at least id, username, email; phone optional.
     */
    public function ensureAccounts($user, bool $regenerate = false): array
    {
        if (!$this->isEnabled()) {
            return [];
        }

        $accounts = $this->client->requestVirtualAccounts([
            'external_customer_id' => (string) $user->username,
            'email' => $user->email ?? ($user->username . '@noemail.local'),
            'name' => trim(($user->name ?? '') ?: $user->username),
            'phone' => $user->phone ?? null,
            'regenerate' => $regenerate,
            'reason' => $regenerate ? 'login' : 'ensure',
        ]);

        if (empty($accounts)) {
            return [];
        }

        // Map each returned bank to its local column and write them in one go.
        $update = [];
        foreach ($accounts as $account) {
            $column = $this->bankColumnFor($account['bank_name'] ?? null);
            if ($column && Schema::hasColumn('user', $column)) {
                $update[$column] = $account['account_number'] ?? null;
            }
        }

        if (!empty($update)) {
            DB::table('user')->where('id', $user->id)->update($update);
            Log::channel('parent-sync')->info('Stored parent virtual accounts', [
                'username' => $user->username,
                'banks' => array_keys($update),
            ]);
        }

        return $accounts;
    }

    /**
     * Apply one relayed credit to a local wallet, idempotently. Returns the
     * ack result: 'credited' | 'failed'. A reference already applied is treated
     * as success without crediting again.
     *
     * @param array{external_customer_id:?string,amount:mixed,reference:?string,provider:?string,gross_amount?:mixed,fee?:mixed} $event
     */
    public function applyCredit(array $event): string
    {
        $reference = (string) ($event['reference'] ?? '');
        $username = $event['external_customer_id'] ?? null;
        $amount = (float) ($event['amount'] ?? 0);

        if ($reference === '' || !$username || $amount <= 0) {
            return 'failed';
        }

        // Idempotency claim: the unique `reference` insert IS the lock. A
        // duplicate delivery (before the ack lands) fails the insert and is
        // acked as success without crediting a second time. Claiming before
        // crediting means a retry can never double-credit even under a race.
        try {
            DB::table('parent_credit_events')->insert([
                'reference' => $reference,
                'username' => $username,
                'amount' => $amount,
                'gross_amount' => (float) ($event['gross_amount'] ?? $amount),
                'fee' => (float) ($event['fee'] ?? 0),
                'provider' => $event['provider'] ?? null,
                'status' => 'pending',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } catch (\Throwable $e) {
            // Almost certainly the unique-reference constraint — already handled.
            return 'credited';
        }

        return DB::transaction(function () use ($reference, $username, $amount) {
            $user = DB::table('user')->where('username', $username)->lockForUpdate()->first();

            if (!$user) {
                DB::table('parent_credit_events')->where('reference', $reference)
                    ->update(['status' => 'failed', 'applied_at' => now(), 'updated_at' => now()]);

                return 'failed';
            }

            DB::table('user')->where('id', $user->id)->update(['bal' => $user->bal + $amount]);
            DB::table('parent_credit_events')->where('reference', $reference)
                ->update(['status' => 'applied', 'applied_at' => now(), 'updated_at' => now()]);

            Log::channel('parent-sync')->info('Applied parent-relayed credit', [
                'username' => $username,
                'amount' => $amount,
                'reference' => $reference,
                'new_bal' => $user->bal + $amount,
            ]);

            return 'credited';
        });
    }
}
