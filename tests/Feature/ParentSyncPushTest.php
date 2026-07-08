<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ParentSyncPushTest extends TestCase
{
    public function test_it_initializes_sync_state_on_the_first_push_run(): void
    {
        config()->set('database.default', 'sqlite');
        config()->set('database.connections.sqlite', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);

        DB::purge('sqlite');
        DB::reconnect('sqlite');

        Schema::dropAllTables();

        Schema::create('user', function ($table) {
            $table->id();
            $table->string('username')->nullable();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->decimal('bal', 15, 2)->default(0);
            $table->string('status')->nullable();
        });

        DB::table('user')->insert([
            'username' => 'demo-user',
            'email' => 'demo@example.com',
            'phone' => '08012345678',
            'bal' => 100.50,
            'status' => 'active',
        ]);

        config()->set('parent_sync', [
            'enabled' => true,
            'dry_run' => true,
            'parent_base_url' => 'https://example.com',
            'child_slug' => 'demo-child',
            'shared_secret' => 'secret',
            'batch_size' => 10,
            'http_timeout' => 15,
            'resources' => [
                'customers' => [
                    'table' => 'user',
                    'pk' => 'id',
                    'external_id_column' => 'username',
                    'columns' => [
                        'username' => 'username',
                        'email' => 'email',
                    ],
                ],
            ],
        ]);

        $this->artisan('parent-sync:push', ['--dry-run' => true])->assertExitCode(0);

        $state = DB::table('parent_sync_state')->where('resource', 'customers')->first();

        $this->assertNotNull($state);
        $this->assertSame(0, (int) $state->last_id);
    }
}
