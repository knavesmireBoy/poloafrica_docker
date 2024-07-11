FROM php:fpm-alpine
RUN apk add --no-cache $PHPIZE_DEPS
RUN apk add --no-cache linux-headers
RUN docker-php-ext-install pdo pdo_mysql