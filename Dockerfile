# 基于 PHP 8.0 官方镜像（含内置服务器）
FROM php:8.0-cli-alpine

# 安装依赖和 Composer
RUN apk add --no-cache git curl \
    && curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# 设置工作目录
WORKDIR /app

# 复制项目文件
COPY . /app

# 安装项目依赖
RUN composer install --no-dev --optimize-autoloader

# 暴露 PHP 内置服务器端口（默认 8000）
EXPOSE 8000

# 启动命令：使用 PHP 内置服务器运行
CMD ["php", "-S", "0.0.0.0:8000", "-t", "public"]