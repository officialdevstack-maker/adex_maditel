<?php

namespace Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Exercises the full pull -> execute -> ack-with-result loop against an
 * in-memory sqlite copy of the legacy tables (user / settings / web_api) so
 * nothing here ever touches the real child database.
 */
class ParentSyncPullDirectivesTest extends TestCase
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

        config()->set('parent_sync', [
            'enabled' => true,
            'dry_run' => false,
            'parent_base_url' => 'https://parent.test',
            'child_slug' => 'demo-child',
            'shared_secret' => 'secret',
            'batch_size' => 10,
            'http_timeout' => 15,
            'resources' => [
                'customers' => [
                    'table' => 'user',
                    'pk' => 'id',
                    'external_id_column' => 'username',
                    'columns' => [],
                ],
            ],
        ]);
    }

    protected function createUserTable(bool $withRedirectColumns = true): void
    {
        Schema::create('user', function (Blueprint $table) use ($withRedirectColumns) {
            $table->increments('id');
            $table->string('username');
            if ($withRedirectColumns) {
                $table->string('parent_redirect_url')->nullable();
                $table->timestamp('migrated_to_parent_at')->nullable();
            }
        });

        DB::table('user')->insert([
            ['username' => 'alice'],
            ['username' => 'bob'],
        ]);
    }

    protected function fakeParent(array $directives): void
    {
        Http::fake([
            'https://parent.test/api/child/demo-child/directives' => Http::response(['data' => $directives], 200),
            'https://parent.test/api/child/demo-child/directives/*/ack' => Http::response(['message' => 'Acknowledged'], 200),
        ]);
    }

    protected function ackBodyFor(int $directiveId): ?array
    {
        $body = null;
        Http::assertSent(function ($request) use ($directiveId, &$body) {
            if ($request->url() === "https://parent.test/api/child/demo-child/directives/{$directiveId}/ack") {
                $body = json_decode($request->body(), true);
                return true;
            }
            return false;
        });

        return $body;
    }

    public function test_redirect_user_updates_only_the_matching_user(): void
    {
        $this->createUserTable();
        $this->fakeParent([[
            'id' => 1,
            'type' => 'redirect_user',
            'payload' => ['external_id' => 'alice', 'target_url' => 'https://parent.test/app'],
        ]]);

        $this->artisan('parent-sync:pull-directives')->assertExitCode(0);

        $alice = DB::table('user')->where('username', 'alice')->first();
        $bob = DB::table('user')->where('username', 'bob')->first();

        $this->assertSame('https://parent.test/app', $alice->parent_redirect_url);
        $this->assertNotNull($alice->migrated_to_parent_at);
        $this->assertNull($bob->parent_redirect_url);
        $this->assertNull($bob->migrated_to_parent_at);

        $ack = $this->ackBodyFor(1);
        $this->assertSame('executed', $ack['result']);
    }

    public function test_redirect_user_for_unknown_user_acks_failed_with_note(): void
    {
        $this->createUserTable();
        $this->fakeParent([[
            'id' => 2,
            'type' => 'redirect_user',
            'payload' => ['external_id' => 'nobody', 'target_url' => 'https://parent.test/app'],
        ]]);

        $this->artisan('parent-sync:pull-directives')->assertExitCode(0);

        $this->assertSame(0, DB::table('user')->whereNotNull('parent_redirect_url')->count());

        $ack = $this->ackBodyFor(2);
        $this->assertSame('failed', $ack['result']);
        $this->assertStringContainsString('nobody', $ack['note']);
    }

    public function test_redirect_all_users_updates_every_user(): void
    {
        $this->createUserTable();
        $this->fakeParent([[
            'id' => 3,
            'type' => 'redirect_all_users',
            'payload' => ['enabled' => true, 'target_url' => 'https://parent.test/app'],
        ]]);

        $this->artisan('parent-sync:pull-directives')->assertExitCode(0);

        $this->assertSame(2, DB::table('user')->where('parent_redirect_url', 'https://parent.test/app')->count());
        $this->assertSame('executed', $this->ackBodyFor(3)['result']);
    }

    public function test_update_settings_applies_allowlisted_flags_and_reports_ignored_keys(): void
    {
        Schema::create('settings', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('monnify')->default(0);
            $table->integer('wema')->default(0);
        });
        DB::table('settings')->insert(['monnify' => 0, 'wema' => 1]);

        $this->fakeParent([[
            'id' => 4,
            'type' => 'update_settings',
            'payload' => ['settings' => ['monnify' => true, 'wema' => false, 'not_a_flag' => true]],
        ]]);

        $this->artisan('parent-sync:pull-directives')->assertExitCode(0);

        $row = DB::table('settings')->first();
        $this->assertSame(1, (int) $row->monnify);
        $this->assertSame(0, (int) $row->wema);

        $ack = $this->ackBodyFor(4);
        $this->assertSame('executed', $ack['result']);
        $this->assertStringContainsString('ignored: not_a_flag', $ack['note']);
    }

    public function test_reroute_provider_updates_the_slot_column(): void
    {
        Schema::create('web_api', function (Blueprint $table) {
            $table->increments('id');
            $table->string('adex_website1')->nullable();
            $table->string('adex_website2')->nullable();
        });
        DB::table('web_api')->insert(['adex_website1' => 'https://old.test', 'adex_website2' => 'https://old2.test']);

        $this->fakeParent([[
            'id' => 5,
            'type' => 'reroute_provider',
            'payload' => ['slot' => '2', 'website_url' => 'https://new-provider.test/', 'username' => 'reseller1'],
        ]]);

        $this->artisan('parent-sync:pull-directives')->assertExitCode(0);

        $row = DB::table('web_api')->first();
        $this->assertSame('https://old.test', $row->adex_website1);
        $this->assertSame('https://new-provider.test', $row->adex_website2);

        $ack = $this->ackBodyFor(5);
        $this->assertSame('executed', $ack['result']);
        // No adex_username2 column exists — the note must say the username
        // was ignored instead of silently dropping it.
        $this->assertStringContainsString('username ignored', $ack['note']);
    }

    public function test_unknown_directive_type_is_acked_as_skipped(): void
    {
        $this->fakeParent([[
            'id' => 6,
            'type' => 'do_something_new',
            'payload' => [],
        ]]);

        $this->artisan('parent-sync:pull-directives')->assertExitCode(0);

        $ack = $this->ackBodyFor(6);
        $this->assertSame('skipped', $ack['result']);
        $this->assertStringContainsString('do_something_new', $ack['note']);
    }

    public function test_transient_failure_is_not_acked_so_the_parent_retries(): void
    {
        // user table WITHOUT the redirect columns: the handler throws (fixable
        // by running migrate), so the directive must stay pending — no ack.
        $this->createUserTable(withRedirectColumns: false);
        $this->fakeParent([[
            'id' => 7,
            'type' => 'redirect_user',
            'payload' => ['external_id' => 'alice', 'target_url' => 'https://parent.test/app'],
        ]]);

        $this->artisan('parent-sync:pull-directives')->assertExitCode(0);

        Http::assertNotSent(function ($request) {
            return str_contains($request->url(), '/directives/7/ack');
        });
    }
}
