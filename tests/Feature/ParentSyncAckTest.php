<?php

namespace Tests\Feature;

use App\Services\ParentSync\SyncClient;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ParentSyncAckTest extends TestCase
{
    public function test_it_sends_a_signed_result_body_when_acknowledging_a_directive(): void
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

        $this->assertTrue($client->ackDirective(42, 'failed', 'no local user'));

        Http::assertSent(function ($request) {
            $expectedBody = json_encode(['result' => 'failed', 'note' => 'no local user']);

            // The signature must cover the exact bytes sent, or the parent's
            // ChildAuthenticator 401s the ack.
            $expectedSignature = hash_hmac(
                'sha256',
                $request->header('X-Timestamp')[0] . '.' . $expectedBody,
                'secret'
            );

            return $request->url() === 'https://example.com/api/child/demo-child/directives/42/ack'
                && $request->body() === $expectedBody
                && $request->header('X-Signature')[0] === $expectedSignature
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
