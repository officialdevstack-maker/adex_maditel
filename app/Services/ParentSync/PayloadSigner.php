<?php

namespace App\Services\ParentSync;

/**
 * Same HMAC scheme as the parent's App\Class\ChildSync\PayloadSigner — no
 * child-specific knowledge lives here, so this file can be copied verbatim
 * into any future child app.
 */
class PayloadSigner
{
    public static function sign(string $secret, string $timestamp, string $rawBody): string
    {
        return hash_hmac('sha256', "{$timestamp}.{$rawBody}", $secret);
    }
}
