<?php
namespace SudGipAuth\Exceptions;

class Handler
{
    public static function handleException($e)
    {
        http_response_code(500);
        echo json_encode([
            "ret_code" => 1,
            "ret_msg" => "Internal Server Error",
            "error" => $e->getMessage(),
            "trace" => $e->getTraceAsString()
        ]);
    }
}    