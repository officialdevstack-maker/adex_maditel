<?php

namespace Tests\Unit;

use App\Http\Controllers\Controller;
use Tests\TestCase;

/**
 * The server-side enforcement of a parent migration: any login/session
 * endpoint that calls parentMigrationBlock() must get a blocking 403 for a
 * migrated user and null for everyone else. Uses plain stdClass rows like
 * the legacy DB::table('user') queries return.
 */
class ParentMigrationBlockTest extends TestCase
{
    protected Controller $controller;

    protected function setUp(): void
    {
        parent::setUp();
        $this->controller = new Controller();
    }

    public function test_migrated_user_gets_a_blocking_response_with_the_redirect_url(): void
    {
        $user = (object) [
            'username' => 'alice',
            'migrated_to_parent_at' => '2026-07-11 09:00:00',
            'parent_redirect_url' => 'https://parent.test/app',
        ];

        $response = $this->controller->parentMigrationBlock($user);

        $this->assertNotNull($response);
        $this->assertSame(403, $response->getStatusCode());

        $data = $response->getData(true);
        $this->assertSame('migrated', $data['status']);
        $this->assertSame('https://parent.test/app', $data['redirect_url']);
        // Legacy frontends only display `message` on a 403 — the URL must be
        // in there so the user is told where to go even without new frontend
        // support.
        $this->assertStringContainsString('https://parent.test/app', $data['message']);
    }

    public function test_non_migrated_user_is_not_blocked(): void
    {
        $user = (object) [
            'username' => 'bob',
            'migrated_to_parent_at' => null,
            'parent_redirect_url' => null,
        ];

        $this->assertNull($this->controller->parentMigrationBlock($user));
    }

    public function test_migrated_flag_without_a_url_is_not_blocked(): void
    {
        // A redirect_all_users disable (enabled=false) nulls the URL; a row
        // with a stale timestamp but no URL must not lock the user out with
        // nowhere to go.
        $user = (object) [
            'username' => 'carol',
            'migrated_to_parent_at' => '2026-07-11 09:00:00',
            'parent_redirect_url' => null,
        ];

        $this->assertNull($this->controller->parentMigrationBlock($user));
    }

    public function test_rows_without_the_migration_columns_are_not_blocked(): void
    {
        // Child DB where the migration hasn't run yet — the columns simply
        // don't exist on the row object.
        $user = (object) ['username' => 'dave'];

        $this->assertNull($this->controller->parentMigrationBlock($user));
    }
}
