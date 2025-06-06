FROM  php:7.4-fpm-alpine
WORKDIR /var/www
# RUN apk add --no-cache \
#     libzip-dev \
#     libpng-dev \
#     libjpeg-turbo-dev \
#     libwebp-dev \
#     libxpm-dev \
#     zlib-dev \
#     libxml2-dev \
#     oniguruma-dev \
#     curl-dev \
#     openssl-dev \
#     bash \
#     git
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer
COPY . /var/www
RUN composer install
EXPOSE 9000
CMD ["php", "-S", "0.0.0:9000", "-t", "/var/www/public"]
