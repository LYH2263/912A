# 构建阶段 - 安装依赖
FROM php:8.2-fpm AS dependencies

# 安装系统依赖
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libxml2-dev \
    libzip-dev \
    libonig-dev \
    default-mysql-client \
    && rm -rf /var/lib/apt/lists/*

# 安装 PHP 扩展
RUN docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath zip

# 安装 Composer 2.9（2.10+ 会因安全公告阻止 Laravel 10 依赖解析）
RUN php -r "copy('https://getcomposer.org/download/2.9.3/composer.phar', '/usr/local/bin/composer');" \
    && chmod +x /usr/local/bin/composer

WORKDIR /var/www/html

# 复制 composer 文件（利用 Docker 缓存）
COPY composer.json composer.lock* ./

# 安装 PHP 依赖（使用 --no-scripts 加速）
RUN composer install --no-interaction --prefer-dist --optimize-autoloader --no-scripts --no-dev

# 运行阶段
FROM php:8.2-fpm

# 安装运行时依赖
RUN apt-get update && apt-get install -y \
    default-mysql-client \
    libonig5 \
    curl \
    && rm -rf /var/lib/apt/lists/*

# 安装 PHP 扩展（仅运行时需要的）
RUN apt-get update && apt-get install -y \
    $PHPIZE_DEPS \
    libpng-dev \
    libxml2-dev \
    libzip-dev \
    libonig-dev \
    default-mysql-client \
    && docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath zip \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

WORKDIR /var/www/html

# 从构建阶段复制依赖
COPY --from=dependencies /var/www/html/vendor ./vendor

# 复制应用代码
COPY . .

# 复制健康检查脚本并设置权限
COPY healthcheck.sh /usr/local/bin/healthcheck.sh
RUN chmod +x /usr/local/bin/healthcheck.sh

# 创建必要的目录并设置权限
RUN mkdir -p storage/framework/cache storage/framework/sessions storage/framework/views storage/logs \
    && mkdir -p bootstrap/cache \
    && chown -R www-data:www-data /var/www/html \
    && chmod -R 775 storage bootstrap/cache

EXPOSE 8000

# 使用 artisan serve（FPM 镜像也包含 CLI）
CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8000"]
