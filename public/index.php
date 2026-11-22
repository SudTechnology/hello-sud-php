<?php
require_once __DIR__ . '/../vendor/autoload.php';

use SudGipAuth\App;
use SudGipAuth\Http\Response;

try {
    // Environment variables take precedence; a local .env is optional.
    Dotenv\Dotenv::createImmutable(__DIR__ . '/../')->safeLoad();
    (new App())->run();
} catch (Throwable $exception) {
    // Never expose exception messages, stack traces, credentials, or tokens to callers.
    Response::error(500, 'Internal Server Error');
}
