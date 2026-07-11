<?php

namespace Tests\Feature;

use App\Services\ParentSync\TransactionRetryService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Hermetic (in-memory sqlite) tests of the retry_transaction executor. The
 * real DataSend call is stubbed via the send() seam — everything else
 * (guards, plan matching, status writes, refunds) runs for real.
 */
class TransactionRetryServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('database.connections.parent_sync_testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);
        config()->set('database.default', 'parent_sync_testing');
        DB::purge('parent_sync_testing');

        Schema::create('user', function (Blueprint $table) {
            $table->increments('id');
            $table->string('username');
            $table->decimal('bal', 12, 2)->default(0);
        });
        Schema::create('data', function (Blueprint $table) {
            $table->increments('id');
            $table->string('username');
            $table->string('transid');
            $table->string('network');
            $table->string('network_type');
            $table->string('plan_name');
            $table->decimal('amount', 12, 2);
            $table->integer('plan_status')->default(0);
            $table->string('wallet')->nullable();
        });
        Schema::create('message', function (Blueprint $table) {
            $table->increments('id');
            $table->string('transid');
            $table->integer('plan_status')->default(0);
        });
        Schema::create('data_plan', function (Blueprint $table) {
            $table->increments('id');
            $table->string('plan_id');
            $table->string('plan_name');
            $table->string('plan_size');
            $table->string('plan_type');
        });
        Schema::create('data_sel', function (Blueprint $table) {
            $table->increments('id');
            $table->string('mtn_sme')->nullable();
        });

        DB::table('user')->insert(['username' => 'alice', 'bal' => 100]);
        DB::table('data_plan')->insert(['plan_id' => 'P77', 'plan_name' => '1', 'plan_size' => 'GB', 'plan_type' => 'SME']);
        DB::table('data_sel')->insert(['mtn_sme' => 'Adex1']);
    }

    protected function stuckTransaction(array $overrides = []): void
    {
        DB::table('data')->insert(array_merge([
            'username' => 'alice',
            'transid' => 'TX1',
            'network' => 'MTN',
            'network_type' => 'SME',
            'plan_name' => '1GB',
            'amount' => 250,
            'plan_status' => 0,
            'wallet' => 'wallet',
        ], $overrides));
        DB::table('message')->insert(['transid' => 'TX1', 'plan_status' => 0]);
    }

    protected function serviceReturning(?string $status, array &$calls = []): TransactionRetryService
    {
        return new class($status, $calls) extends TransactionRetryService {
            private ?string $status;
            private array $callsRef;

            public function __construct(?string $status, array &$calls)
            {
                $this->status = $status;
                $this->callsRef = &$calls;
            }

            protected function send(string $method, array $data): ?string
            {
                $this->callsRef[] = ['method' => $method, 'data' => $data];
                return $this->status;
            }
        };
    }

    public function test_successful_retry_marks_the_transaction_successful(): void
    {
        $this->stuckTransaction();
        $calls = [];

        $outcome = $this->serviceReturning('success', $calls)->retry('TX1');

        $this->assertSame('executed', $outcome['result']);
        $this->assertSame(1, (int) DB::table('data')->where('transid', 'TX1')->value('plan_status'));
        $this->assertSame(1, (int) DB::table('message')->where('transid', 'TX1')->value('plan_status'));
        // Dispatched through the data_sel-configured route with the original
        // transid (provider-side dedupe) and the recovered plan id.
        $this->assertSame('Adex1', $calls[0]['method']);
        $this->assertSame('TX1', $calls[0]['data']['transid']);
        $this->assertSame('P77', $calls[0]['data']['purchase_plan']);
    }

    public function test_failed_retry_refunds_the_user_and_marks_failed(): void
    {
        $this->stuckTransaction();

        $outcome = $this->serviceReturning('fail')->retry('TX1');

        $this->assertSame('executed', $outcome['result']);
        $this->assertStringContainsString('refunded', $outcome['note']);
        $this->assertSame(2, (int) DB::table('data')->where('transid', 'TX1')->value('plan_status'));
        $this->assertSame(350.0, (float) DB::table('user')->where('username', 'alice')->value('bal'));
    }

    public function test_processing_retry_leaves_the_transaction_pending(): void
    {
        $this->stuckTransaction();

        $outcome = $this->serviceReturning('process')->retry('TX1');

        $this->assertSame('executed', $outcome['result']);
        $this->assertSame(0, (int) DB::table('data')->where('transid', 'TX1')->value('plan_status'));
        $this->assertSame(100.0, (float) DB::table('user')->where('username', 'alice')->value('bal'));
    }

    public function test_already_successful_transaction_is_never_retried(): void
    {
        $this->stuckTransaction(['plan_status' => 1]);
        $calls = [];

        $outcome = $this->serviceReturning('success', $calls)->retry('TX1');

        $this->assertSame('failed', $outcome['result']);
        $this->assertCount(0, $calls);
    }

    public function test_already_refunded_transaction_is_never_retried(): void
    {
        // Re-vending after a refund would hand out free data.
        $this->stuckTransaction(['plan_status' => 2]);
        $calls = [];

        $outcome = $this->serviceReturning('success', $calls)->retry('TX1');

        $this->assertSame('failed', $outcome['result']);
        $this->assertStringContainsString('refunded', $outcome['note']);
        $this->assertCount(0, $calls);
    }

    public function test_ambiguous_plan_match_refuses_to_retry(): void
    {
        // Two plans render as "1GB" SME — guessing could vend the wrong bundle.
        DB::table('data_plan')->insert(['plan_id' => 'P88', 'plan_name' => '1', 'plan_size' => 'GB', 'plan_type' => 'SME']);
        $this->stuckTransaction();
        $calls = [];

        $outcome = $this->serviceReturning('success', $calls)->retry('TX1');

        $this->assertSame('failed', $outcome['result']);
        $this->assertCount(0, $calls);
        $this->assertSame(0, (int) DB::table('data')->where('transid', 'TX1')->value('plan_status'));
    }

    public function test_unknown_transid_fails_with_note(): void
    {
        $outcome = $this->serviceReturning('success')->retry('NOPE');

        $this->assertSame('failed', $outcome['result']);
        $this->assertStringContainsString('NOPE', $outcome['note']);
    }
}
