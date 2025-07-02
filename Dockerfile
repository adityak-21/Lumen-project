FROM php:7.4-fpm-alpine

WORKDIR /var/www

RUN apk add --no-cache \
    libzip-dev \
    libpng-dev \
    libjpeg-turbo-dev \
    libwebp-dev \
    libxpm-dev \
    zlib-dev \
    libxml2-dev \
    oniguruma-dev \
    curl-dev \
    openssl-dev \
    bash \
    git \
    supervisor \
    && docker-php-ext-configure zip \
    && docker-php-ext-install pdo pdo_mysql zip gd xml mbstring

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

COPY composer.json composer.lock ./

RUN composer install --no-dev --optimize-autoloader

COPY . .

RUN chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache || true

RUN chown -R www-data:www-data /var/www \
    && chmod -R 755 /var/www

COPY supervisord.conf /etc/supervisord.conf

EXPOSE 9000

CMD ["/usr/bin/supervisord", "-c", "/etc/supervisord.conf"]