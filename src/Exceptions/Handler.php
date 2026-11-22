<?php
namespace SudGipAuth\Exceptions;

use SudGipAuth\Http\Response;

class Handler
{
    public static function handleException($exception): void
    {
        // Never return internal exception details to the caller.
        Response::error(500, 'Internal Server Error');
    }
}
