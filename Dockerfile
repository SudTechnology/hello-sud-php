# PHP runtime for local demo development.
FROM php:8.0-cli-alpine

# Install Composer and dependencies.
RUN apk add --no-cache git curl \
    && curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Application directory.
WORKDIR /app

# .dockerignore excludes local credentials.
COPY . /app

# Install the published SDK.
RUN composer install --no-dev --optimize-autoloader

# Local HTTP port.
EXPOSE 8000

# Route all demo requests through the entry point.
CMD ["php", "-S", "0.0.0.0:8000", "-t", "public", "public/index.php"]