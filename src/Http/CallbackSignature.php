<?php
namespace SudGipAuth\Http;

/** Optional HTTP callback authentication, independent from SDK token verification. */
class CallbackSignature
{
    public static function verify(array $headers, string $originalBody, string $appId, string $secret): bool
    {
        $id = $headers['HTTP_SUD_APPID'] ?? '';
        $timestamp = $headers['HTTP_SUD_TIMESTAMP'] ?? '';
        $nonce = $headers['HTTP_SUD_NONCE'] ?? '';
        $signature = $headers['HTTP_SUD_SIGNATURE'] ?? '';
        if ($id !== $appId || trim($timestamp) === '' || trim($nonce) === ''
            || !preg_match('/\A[0-9a-f]{40}\z/', $signature)) {
            return false;
        }

        // SECURITY: $originalBody must be the exact bytes from php://input. Never json_decode,
        // json_encode, trim, reorder fields, convert encodings, or normalize line endings first.
        // The purpose is to detect body tampering, including changes to whitespace. PHP strings
        // preserve bytes. Append the protocol newline without changing any existing body bytes.
        $content = $id . "\n" . $timestamp . "\n" . $nonce . "\n" . $originalBody . "\n";
        return hash_equals(hash_hmac('sha1', $content, $secret), $signature);
    }
}
