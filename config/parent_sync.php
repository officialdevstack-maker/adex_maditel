<?php

// This is the ONLY file meant to change meaningfully when this connector
// bundle (config/parent_sync.php + app/Services/ParentSync/* +
// app/Console/Commands/ParentSync*.php) is dropped into a different child
// app — everything else is generic and reads its table/column knowledge
// from here.
//
// IMPORTANT — before setting parent_sync.enabled=true / running the push
// command for real: the `pk`/columns below for each resource are
// reverse-engineered from controller code (app/Http/Controllers/Purchase/
// DataPurchase.php, app/Http/Controllers/API/AdminController.php etc.),
// NOT confirmed against the live database schema (no migration exists for
// these tables — the real schema only lives in production MySQL). Run
// `DESCRIBE user;` / `DESCRIBE data;` (or `SHOW CREATE TABLE`) against the
// live DB and correct `pk`/`columns` below if anything doesn't match —
// ParentSyncPush checks the configured pk column actually exists before
// syncing and will refuse to run otherwise, but it can't verify the
// column actually behaves as a reliable monotonic/append-only id for
// diffing — that's a judgment call only you can make against real data.

return [

    'enabled' => env('PARENT_SYNC_ENABLED', false),
    'dry_run' => env('PARENT_SYNC_DRY_RUN', true),

    'parent_base_url' => env('PARENT_SYNC_BASE_URL'),
    'child_slug' => env('PARENT_SYNC_CHILD_SLUG'),
    'shared_secret' => env('PARENT_SYNC_SECRET'),

    'batch_size' => env('PARENT_SYNC_BATCH_SIZE', 200),
    'http_timeout' => env('PARENT_SYNC_HTTP_TIMEOUT', 15),

    'resources' => [
        'customers' => [
            'table' => 'user',
            // Column diffing (`WHERE {pk} > {cursor}`) is keyed on this —
            // separate from the identity column below.
            'pk' => 'id',
            // The value sent as `external_id` — the stable key the parent
            // uses to upsert this record. `username` (not `id`) is used
            // here because the `data` table below only carries a
            // `username` string, not a numeric user_id FK, so username is
            // the one column confirmed to join both tables. Switch both
            // this and transactions' `external_customer_id_column` to `id`
            // together if you confirm `data` actually has a real user_id
            // column.
            'external_id_column' => 'username',
            // local column => extra payload key (external_id is handled above,
            // don't repeat it here)
            'columns' => [
                'username' => 'username',
                'email' => 'email',
                'phone' => 'phone',
                'bal' => 'wallet_balance',
                'status' => 'status',
            ],
        ],
        'transactions' => [
            'table' => 'data',
            // UNCONFIRMED — reverse-engineered from controller code only, no
            // migration exists for this table. Verify `data` actually has an
            // autoincrement `id` column before enabling for real; if it
            // doesn't, this whole resource needs a different diff strategy.
            'pk' => 'id',
            'external_id_column' => 'transid',
            // Matched against customers' external_id_column above so the
            // parent can link a transaction to the right ChildCustomer.
            'external_customer_id_column' => 'username',
            'columns' => [
                'network' => 'transaction_type',
                'amount' => 'amount',
                'plan_status' => 'status',
            ],
        ],
    ],

];
