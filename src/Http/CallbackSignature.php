<?php

declare(strict_types=1);

namespace SudGipAuth\Http;

use Sud\Gip\Api\RequestSigner;

/** HTTP header adapter; signature computation and comparison belong to the SDK. */
final class CallbackSignature
{
    public static function verify(array $headers, string $originalBody, RequestSigner $signer): bool
    {
        $values = [];
        foreach (['HTTP_SUD_APPID', 'HTTP_SUD_TIMESTAMP', 'HTTP_SUD_NONCE', 'HTTP_SUD_SIGNATURE'] as $name) {
            $value = $headers[$name] ?? '';
            // PHP servers may combine duplicate headers with commas. Reject such
            // ambiguous metadata rather than selecting an arbitrary header value.
            if (!is_string($value) || trim($value) === '' || strpos($value, ',') !== false) {
                return false;
            }
            $values[] = $value;
        }

        // SECURITY: pass the ORIGINAL php://input bytes unchanged to the SDK.
        // Never json_decode/json_encode, trim, normalize line endings, convert
        // encodings, or decompress first. Only parse after successful verification.
        return $signer->verifySignature($values[0], $values[1], $values[2], $originalBody, $values[3]);
    }
}
