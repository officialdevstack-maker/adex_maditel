<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Parent-managed funding on the child side.
 *
 *  - parent_funding_config: single-row switch set by the parent's
 *    `set_funding_mode` directive. When aggregate is on, this child stops
 *    issuing its own virtual accounts and instead asks the parent for them and
 *    mirrors parent-received credits onto local wallets.
 *
 *  - parent_credit_events: local ledger of credits the parent relayed, keyed on
 *    the parent's funding reference. Makes applying a credit idempotent — a
 *    re-delivered event (before the ack lands) can never double-credit a wallet.
 */
class CreateParentFundingTables extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('parent_funding_config')) {
            Schema::create('parent_funding_config', function (Blueprint $table) {
                $table->id();
                $table->boolean('aggregate')->default(false);
                $table->string('parent_url')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('parent_credit_events')) {
            Schema::create('parent_credit_events', function (Blueprint $table) {
                $table->id();
                $table->string('reference')->unique();
                $table->string('username')->nullable();
                $table->decimal('amount', 15, 2)->default(0);
                $table->decimal('gross_amount', 15, 2)->default(0);
                $table->decimal('fee', 15, 2)->default(0);
                $table->string('provider')->nullable();
                // applied = credited locally; failed = no local user to credit.
                $table->string('status', 20)->default('applied');
                $table->timestamp('applied_at')->nullable();
                $table->timestamps();

                $table->index('username');
            });
        }
    }

    public function down()
    {
        Schema::dropIfExists('parent_credit_events');
        Schema::dropIfExists('parent_funding_config');
    }
}
