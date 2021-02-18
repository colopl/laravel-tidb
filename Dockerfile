FROM php:7.4-cli-alpine3.12

ENV TZ Asia/Tokyo

RUN apk add --no-cache --allow-untrusted \
    libxml2 \
    libstdc++ \
  && apk add --no-cache --virtual=.build-deps --allow-untrusted \
    tzdata \
    pcre-dev \
    libxml2-dev \
    gcc \
    g++ \
    make \
    autoconf \
    linux-headers \
  && pecl install -o -f \
    xdebug \
  && docker-php-ext-enable \
    opcache \
    xdebug \
  && apk del .build-deps \
  && apk del *-dev \
  && rm -rf /tmp/pear \
  && curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin/ --filename=composer \
  && mkdir -p /project/

WORKDIR /project
