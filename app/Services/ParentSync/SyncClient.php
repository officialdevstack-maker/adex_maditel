<?php

namespace App\Services\ParentSync;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Thin HTTP client for the parent<->child channel. Generic — reads
 * everything from config/parent_sync.php, no per-child knowledge here.
 * Copy verbatim into a future child app.
 */
class SyncClient
{
    protected function config(): array
    {
        return config('parent_sync');
    }

    /**
     * POSTs a signed batch to the parent's webhook endpoint. Returns true
     * only on a genuine 2xx — callers must not advance their sync cursor
     * otherwise, so a failed/timed-out push is retried next run rather
     * than silently dropped.
     */
    public function pushBatch(string $eventId, string $resource, array $records): bool
    {
        $config = $this->config();
        $body = ['event_id' => $eventId, 'resource' => $resource, 'records' => $records];

        if ($config['dry_run']) {
            Log::channel('parent-sync')->info('[dry-run] would push batch', $body + ['count' => count($records)]);
            return true;
        }

        $url = rtrim($config['parent_base_url'], '/') . '/api/webhook/child/' . $config['child_slug'];

        // The signature must cover the exact bytes sent — encode once here
        // and send via withBody() rather than post($url, $body), which
        // would re-encode internally and could byte-diff from what we
        // signed (key order, whitespace, float formatting, etc.).
        $raw = json_encode($body);

        try {
            $response = $this->signedRequest($raw)
                ->withBody($raw, 'application/json')
                ->post($url);

            if ($response->successful()) {
                return true;
            }

            Log::channel('parent-sync')->warning('Push batch rejected by parent', [
                'status' => $response->status(),
                'body' => $response->body(),
                'event_id' => $eventId,
            ]);
            return false;
        } catch (\Throwable $e) {
            Log::channel('parent-sync')->error('Push batch failed', [
                'error' => $e->getMessage(),
                'event_id' => $eventId,
            ]);
            return false;
        }
    }

    /**
     * GETs pending directives for this child instance.
     */
    public function fetchDirectives(): array
    {
        $config = $this->config();
        $url = rtrim($config['parent_base_url'], '/') . '/api/child/' . $config['child_slug'] . '/directives';

        try {
            $response = $this->signedRequest()->get($url);
            if (!$response->successful()) {
                Log::channel('parent-sync')->warning('Fetch directives failed', ['status' => $response->status()]);
                return [];
            }
            return $response->json('data', []);
        } catch (\Throwable $e) {
            Log::channel('parent-sync')->error('Fetch directives error', ['error' => $e->getMessage()]);
            return [];
        }
    }

    public function ackDirective(int $id): bool
    {
        $config = $this->config();
        $url = rtrim($config['parent_base_url'], '/') . '/api/child/' . $config['child_slug'] . "/directives/{$id}/ack";

        try {
            // Send an EXPLICIT empty body. signedRequest() signs over '' (a
            // bodyless POST), but a plain ->post($url) lets the JSON client
            // encode the empty payload to "[]" and send that as the body — the
            // parent then verifies the signature against getContent() = "[]",
            // which never matches sign(''), 401-ing every ack. withBody('')
            // pins the sent bytes to exactly what was signed (same fix
            // pushBatch already relies on).
            $response = $this->signedRequest()
                ->withBody('', 'application/json')
                ->post($url);

            if ($response->successful() || $response->status() === 404) {
                return true;
            }

            // Surface WHY the parent rejected the ack. Without this the caller
            // only logs "handled but ack failed" with no status, leaving a 401
            // (bad signature/unknown instance), a 404 (directive not found), and
            // a 5xx/parent-down all indistinguishable. Mirrors pushBatch.
            Log::channel('parent-sync')->warning('Ack rejected by parent', [
                'directive_id' => $id,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            return false;
        } catch (\Throwable $e) {
            Log::channel('parent-sync')->error('Ack directive error', ['error' => $e->getMessage(), 'directive_id' => $id]);
            return false;
        }
    }

    // $raw must be the exact bytes of the request body (empty string for
    // GETs / bodyless POSTs like ackDirective) — must match what the
    // parent's ChildAuthenticator::verify() reads via $request->getContent().
    protected function signedRequest(string $raw = '')
    {
        $config = $this->config();
        $timestamp = (string) time();

        return Http::timeout($config['http_timeout'])
            ->withHeaders([
                'X-Child-Instance' => $config['child_slug'],
                'X-Timestamp' => $timestamp,
                'X-Signature' => PayloadSigner::sign($config['shared_secret'], $timestamp, $raw),
            ]);
    }
}
