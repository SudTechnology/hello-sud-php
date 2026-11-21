# SudGIP Auth Server Demo

A lightweight PHP HTTP server demo for SudGIP authentication services. This project provides a basic implementation of authentication APIs using the SudGIP Auth PHP SDK without relying on third-party frameworks.

## Features

- Three core authentication APIs:
    - `getCode`: Generate authentication code by UID
    - `getSSToken`: Exchange code for SSToken and user information
    - `updateSSToken`: Refresh SSToken for extended access
- Built-in error handling and response formatting
- Environment configuration management
- Modular architecture for easy extension
- PSR-4 autoloading compliant
- No third-party HTTP frameworks

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

### Production Environment
It is recommended to use a production-grade web server like Nginx or Apache:
### Example Nginx Configuration:
```bash
    server {
        listen 80;
        server_name your-domain.com;
        root /path/to/project/public;
        
        location / {
            try_files $uri $uri/ /index.php?$query_string;
        }
        
        location ~ \.php$ {
            fastcgi_pass unix:/run/php/php8.0-fpm.sock;
            fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
            include fastcgi_params;
        }
    }
```

## Local SDK 2.0.0 smoke test

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

## API Documentation

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
    "data": {
        "code": "generated_code_here"
    }
}
```

### 2. getSSToken (POST)
#### Endpoint:
**/api/getSSToken**
#### Request Body (JSON)::
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
#### Request Body (JSON)::
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

## Project Structure
```text
sud-gip-auth-server/
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

## Error Handling
All API responses follow a standardized format:
```json
{
    "ret_code": 0,          // 0 for success, non-zero for errors
    "ret_msg": "",          // Error message (empty for success)
    "sdk_error_code": 0,    // SDK-specific error code
    "data": {}              // Response data payload
}
```
Common error codes:
- ret_code: 1: General error
- sdk_error_code: 1002: Token verification failed
- sdk_error_code: 1005: Token expired


## License
This project is licensed under the MIT License - see the LICENSE file for details.

## Acknowledgments
- SudGIP Auth PHP SDK: https://github.com/SudTechnology/sud-gip-auth-php
- PHP Dotenv: https://github.com/vlucas/phpdotenv