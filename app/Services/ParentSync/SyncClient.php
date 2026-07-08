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
            return $this->signedRequest()->post($url)->successful();
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
