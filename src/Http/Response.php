<?php
namespace SudGipAuth\Http;

/** Shared response contract for routing, callback authentication, and SDK results. */
class Response
{
    public static function success($data): void
    {
        self::send(200, 0, '', 0, $data);
    }

    public static function error(int $status, string $message, int $sdkCode = 0): void
    {
        self::send($status, 1, $message, $sdkCode, (object) []);
    }

    private static function send(int $status, int $code, string $message, int $sdkCode, $data): void
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-store');
        echo json_encode([
            'ret_code' => $code,
            'ret_msg' => $message,
            'sdk_error_code' => $sdkCode,
            'data' => $data
        ], JSON_THROW_ON_ERROR);
    }
}
