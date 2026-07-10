<?php

namespace Tests\Feature;

use App\Services\ParentSync\SyncClient;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ParentSyncAckTest extends TestCase
{
    public function test_it_sends_an_explicit_empty_body_when_acknowledging_a_directive(): void
    {
        config()->set('parent_sync', [
            'enabled' => true,
            'dry_run' => false,
            'parent_base_url' => 'https://example.com',
            'child_slug' => 'demo-child',
            'shared_secret' => 'secret',
            'batch_size' => 10,
            'http_timeout' => 15,
        ]);

        Http::fake([
            'https://example.com/api/child/demo-child/directives/42/ack' => Http::response(['message' => 'Acknowledged'], 200),
        ]);

        $client = new SyncClient();

        $this->assertTrue($client->ackDirective(42));

        Http::assertSent(function ($request) {
            return $request->url() === 'https://example.com/api/child/demo-child/directives/42/ack'
                && $request->body() === ''
                && $request->hasHeader('X-Signature')
                && $request->header('Content-Type')[0] === 'application/json';
        });
    }

    public function test_it_treats_a_missing_directive_as_a_successful_ack(): void
    {
        config()->set('parent_sync', [
            'enabled' => true,
            'dry_run' => false,
            'parent_base_url' => 'https://example.com',
            'child_slug' => 'demo-child',
            'shared_secret' => 'secret',
            'batch_size' => 10,
            'http_timeout' => 15,
        ]);

        Http::fake([
            'https://example.com/api/child/demo-child/directives/99/ack' => Http::response(['message' => 'Directive not found'], 404),
        ]);

        $client = new SyncClient();

        $this->assertTrue($client->ackDirective(99));
    }
}
