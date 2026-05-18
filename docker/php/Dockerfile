FROM php:8.4-fpm

RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip \
    libpq-dev \
    libicu-dev \
    libzip-dev

RUN apt-get clean && rm -rf /var/lib/apt/lists/*

RUN docker-php-ext-install \
    pdo \
    pdo_pgsql \
    pgsql \
    mbstring \
    exif \
    pcntl \
    bcmath \
    gd \
    intl \
    zip

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www

COPY composer.json composer.lock ./

RUN composer install --no-scripts --no-autoloader

COPY . .

RUN composer dump-autoload

RUN mkdir -p /var/www/storage/framework/cache/data \
    && mkdir -p /var/www/storage/framework/sessions \
    && mkdir -p /var/www/storage/framework/views \
    && mkdir -p /var/www/storage/logs \
    && mkdir -p /var/www/bootstrap/cache

RUN chown -R www-data:www-data /var/www \
    && chmod -R 775 /var/www/

USER www-data

EXPOSE 9000
CMD ["php-fpm"]
