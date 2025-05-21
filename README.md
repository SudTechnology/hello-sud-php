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

- PHP 7.4+ or 8.0+
- Composer (https://getcomposer.org/)
- SudGIP Auth PHP SDK (automatically installed via Composer)

## Installation

1. Clone the repository:
   ```bash
   git clone https://github.com/your-username/sud-gip-auth-server.git
   cd sud-gip-auth-server
   ```
2. Install dependencies:
   ```bash
   composer install
    ```
3.  Edit the .env file and set your APP_ID and APP_SECRET:
    ```bash
    APP_ID=your_actual_app_id
    APP_SECRET=your_actual_app_secret
    ```
    
## Running the Server
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

### 2. updateSSToken (POST)
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
- sdk_error_code: 1005: Token validation failed


## License
This project is licensed under the MIT License - see the LICENSE file for details.

## Acknowledgments
- SudGIP Auth PHP SDK: https://github.com/SudTechnology/sud-gip-auth-php
- PHP Dotenv: https://github.com/vlucas/phpdotenv