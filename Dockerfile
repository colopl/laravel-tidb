FROM php:8-cli-alpine

COPY --from=composer:latest "/usr/bin/composer" "/usr/local/bin/composer"

ENV TZ="Asia/Tokyo"

RUN docker-php-ext-install -j$(nproc) "pdo" "pdo_mysql"

COPY ./ "/project"

WORKDIR "/project"
