# SudGIP Auth Server Demo

A lightweight PHP HTTP server demo for SudGIP authentication services. Use this project to learn how to initialize the SDK and implement Code generation, SSToken exchange, and refresh in your own server. This project provides a basic implementation of authentication APIs using the SudGIP Auth PHP SDK without relying on third-party frameworks.

## Prerequisites

- PHP 8.0+ (PHP 7.4 is no longer supported)
- Composer (https://getcomposer.org/)
- SudGIP Auth PHP SDK 2.0.0 (automatically installed via Composer; uses firebase/php-jwt ^7.0)
- Alternatively, use Docker with its engine running; local PHP and Composer are not required.

## Installation

1. Clone the repository:
   ```bash
   git clone https://github.com/SudTechnology/hello-sud-php.git
   cd hello-sud-php
   ```
2. For local PHP development, install dependencies (skip this step when using Docker):
   ```bash
   composer install
    ```
3.  Edit the .env file and set your APP_ID and APP_SECRET:
    ```bash
    APP_ID=your_actual_app_id
    APP_SECRET=your_actual_app_secret
    ```
    
The HS256 `APP_SECRET` must contain at least **32 bytes**. The example value
`your_actual_app_secret` is a placeholder and cannot be used to run SDK 2.0.0.
Keep an existing Sud-issued secret unchanged if it meets the requirement. If it
is shorter, coordinate rotation with the credential issuer; do not pad or hash it
locally. Both signing and verifying services must use the same key.

For isolated local testing only, generate a random test secret with
`openssl rand -hex 32` and copy its output into `.env`. This does not provision a
Sud credential and cannot establish interoperability with Sud services. Do not
commit real credentials or generated tokens.

## Running the Server

### Docker (no local PHP or Composer required)
Run these commands from the project root:

```bash
docker build -t hello-sud-php:test .
docker run --rm hello-sud-php:test composer show sudtechnology/sud-gip-auth-php
```

Confirm that the installed version is `v2.0.0`, then start the server:

```bash
docker run --rm --name hello-sud-test \
  -p 127.0.0.1:8000:8000 \
  --mount "type=bind,source=$PWD/.env,target=/app/.env,readonly" \
  hello-sud-php:test
```

Keep this terminal running. Press Ctrl+C to stop the container. The bind mount
uses your current local `.env` instead of the copy embedded in the image. After
editing `.env`, restart with the same command. Code or dependency changes require
a new build and a new container.

The current Dockerfile copies the project, including `.env`, into the image.
Keep this image local if it contains credentials; do not publish it. The built-in
PHP server is intended for local development only.

If pulling the base image times out, check Docker Desktop network/proxy settings
and retry `docker pull php:8.0-cli-alpine` before building again.

### Development Environment
Start the PHP built-in server:
```bash
  php -S localhost:8000 -t public
```

## Implementing the Demo Server

The server receives HTTP requests, calls the SDK, and wraps its results in JSON.
The SDK handles token signing, verification, and UID extraction. Routing, request
validation, HTTP status codes, user profiles, and the `ret_code` response envelope
belong to your application.

### 1. Initialize the SDK

The entry point loads Composer and `.env`; `src/App.php` creates the shared
SDK instance. The essential initialization is:

```php
require 'vendor/autoload.php';

use Sud\Gip\Api\SudGIPAuth;

Dotenv\Dotenv::createImmutable(__DIR__)->load();
$auth = new SudGIPAuth($_ENV['APP_ID'], $_ENV['APP_SECRET']);
```

This snippet assumes a script in the project root. The supplied entry point is
`public/index.php` and resolves paths relative to its parent directory.

### 2. Generate a Code

The `/api/getCode` handler reads a UID and calls:

```php
$code = $auth->getCode($uid);
$data = ['code' => $code->code];
```

For demonstration, UID is a query parameter. In your own server, derive it from
the authenticated user's session or validated access token. The SDK does not log
users in or decide whether the caller may act as that UID.

### 3. Exchange a Code for an SSToken

Validate the Code before using its UID. In a handler, the core sequence is:

```php
$uidResponse = $auth->getUidByCode($code);
if (!$uidResponse->isSuccess) {
    // Return a failure response with $uidResponse->errorCode.
    return;
}

$uid = $uidResponse->uid;
$ssToken = $auth->getSSToken($uid);
$data = [
    'ss_token' => $ssToken->token,
    'expire_date' => $ssToken->expireDate,
    'user_info' => ['uid' => $uid]
];
```

The demo adds fixed nickname, avatar, and other profile values. Replace those
with a lookup in your own user service or database. Only the UID comes from the
verified token; the SDK does not supply user profiles.

### 4. Refresh an SSToken

```php
$uidResponse = $auth->getUidBySSToken($ssToken);
if (!$uidResponse->isSuccess) {
    // Return a failure response with $uidResponse->errorCode.
    return;
}

$newToken = $auth->getSSToken($uidResponse->uid);
$data = [
    'ss_token' => $newToken->token,
    'expire_date' => $newToken->expireDate
];
```

Refreshing validates the existing token and signs a new one for the same UID.
It does not revoke the old token. Expired tokens fail verification.

### 5. Wrap SDK results in HTTP responses

The controller's `sendSuccessResponse` and `sendErrorResponse` methods implement
the demo's response envelope. For example, a successful handler returns:

```php
echo json_encode([
    'ret_code' => 0,
    'ret_msg' => '',
    'sdk_error_code' => 0,
    'data' => $data
]);
```

See `src/Controllers/AuthController.php` for complete handlers and
`src/Routes/routes.php` for route registration. The snippets above focus on SDK
calls; they are not standalone HTTP endpoints.

## API Reference

### 1. getCode (GET)
#### Endpoint:
**/api/getCode?uid={uid}**
#### Parameters:
- uid (required): User identifier
#### Response:
```json
{
    "ret_code": 0,
    "ret_msg": "",
    "sdk_error_code": 0,
    "data": {
        "code": "generated_code_here"
    }
}
```

### 2. getSSToken (POST)
#### Endpoint:
**/api/getSSToken**
#### Request Body (JSON):
```json
{
  "code": "code_from_getCode"
}
```
#### Response:
```json
{
  "ret_code": 0,
  "ret_msg": "",
  "sdk_error_code": 0,
  "data": {
    "ss_token": "generated_sstoken_here",
    "expire_date": 1630417861359,
    "user_info": {
      "uid": "xxxxxxx",
      "nick_name": "萌萌",
      "avatar_url": "https://icon.png?128*128",
      "gender": "female",
      "is_ai": 0,
      "ai_level": 0
    }
  }
}
```

### 3. updateSSToken (POST)
#### Endpoint:
**/api/updateSSToken**
#### Request Body (JSON):
```json
{
  "ss_token": "current_sstoken_here"
}
```
#### Response:
```json
{
  "ret_code": 0,
  "ret_msg": "",
  "sdk_error_code": 0,
  "data": {
    "ss_token": "updated_sstoken_here",
    "expire_date": 1630580156184
  }
}
```

## Error Handling

The demo controller returns HTTP 200 on success and HTTP 400 for missing
parameters or failed token verification. For example, an invalid Code produces:

```json
{
  "ret_code": 1,
  "ret_msg": "",
  "sdk_error_code": 1002,
  "data": []
}
```

`ret_code` is the server's result (0 for success, 1 for failure).
`sdk_error_code` carries SDK verification failures, including 1002 for invalid
tokens and 1005 for expired tokens. Successful data is an object; the controller
uses an empty array for failure data. Other unexpected exceptions are handled
at the application entry point when they propagate there.

## Testing

With the Docker server above running, execute the following in a second terminal.
It uses PHP inside the container, so no local PHP, Composer, or jq is required.
Tokens are passed automatically between requests and are not printed.

```bash
docker exec -i hello-sud-test php <<'PHP'
<?php
function request($path, $body = null) {
    $options = ['http' => ['method' => $body === null ? 'GET' : 'POST',
        'ignore_errors' => true, 'timeout' => 10]];
    if ($body !== null) {
        $options['http']['header'] = "Content-Type: application/json";
        $options['http']['content'] = json_encode($body);
    }
    $raw = file_get_contents('http://127.0.0.1:8000' . $path, false, stream_context_create($options));
    if ($raw === false) { throw new RuntimeException('HTTP request failed: ' . $path); }
    return json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
}
function check($condition, $name) {
    if (!$condition) { throw new RuntimeException('FAIL: ' . $name); }
    echo 'PASS: ', $name, PHP_EOL;
}
$code = request('/api/getCode?uid=test-user-001');
check(($code['ret_code'] ?? null) === 0 && !empty($code['data']['code']), 'Generate Code');
$token = request('/api/getSSToken', ['code' => $code['data']['code']]);
check(($token['ret_code'] ?? null) === 0 && !empty($token['data']['ss_token'])
    && ($token['data']['user_info']['uid'] ?? null) === 'test-user-001', 'Exchange Code and read UID');
sleep(1);
$refreshed = request('/api/updateSSToken', ['ss_token' => $token['data']['ss_token']]);
check(($refreshed['ret_code'] ?? null) === 0 && !empty($refreshed['data']['ss_token'])
    && ($refreshed['data']['expire_date'] ?? 0) > ($token['data']['expire_date'] ?? 0), 'Refresh SSToken');
$invalid = request('/api/getSSToken', ['code' => 'invalid-token']);
check(($invalid['ret_code'] ?? null) === 1 && ($invalid['sdk_error_code'] ?? null) === 1002,
    'Reject invalid Code');
PHP
```

Expected output: four `PASS` lines and exit status 0. This verifies local token
creation, verification, UID retrieval, refresh, and malformed-token rejection.
It does not replace integration testing with Sud services using issued credentials.

## Project Structure
```text
hello-sud-php/
├── public/                 # Web root directory
│   └── index.php           # Main entry point
├── src/                    # Application source code
│   ├── Controllers/        # API controllers
│   │   └── AuthController.php
│   ├── Routes/             # Routing system
│   │   ├── Router.php
│   │   └── routes.php
│   ├── Exceptions/         # Exception handling
│   │   └── Handler.php
│   └── App.php             # Application bootstrap
├── vendor/                 # Composer dependencies
├── .env                    # Environment configuration
├── composer.json           # Composer configuration
└── README.md               # Project documentation
```

## Integration Notes

Code defaults to two hours and has a minimum duration of 30 minutes. SSToken
defaults to two hours and has a minimum duration of two hours. Optional SDK
durations and returned expiration timestamps use milliseconds.

Code and SSToken share a token format. The SDK does not automatically compare
`app_id` with your configured application ID. Add any required application and
authorization checks in your service. The demo does not implement login,
persistence, or token revocation. Local tests do not establish remote-service
interoperability; use issued credentials for that integration.

## License
This project is licensed under the MIT License - see the LICENSE file for details.

## Acknowledgments
- SudGIP Auth PHP SDK: https://github.com/SudTechnology/sud-gip-auth-php
- PHP Dotenv: https://github.com/vlucas/phpdotenv