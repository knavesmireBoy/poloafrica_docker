FROM php:fpm-alpine

RUN apk add --no-cache $PHPIZE_DEPS
RUN apk add --no-cache linux-headers
RUN pecl install xdebug 
RUN docker-php-ext-enable xdebug 

RUN apk add --no-cache mysql-client msmtp perl wget procps shadow libzip libpng libjpeg-turbo libwebp freetype icu icu-data-full

RUN apk add --no-cache --virtual build-essentials \
    icu-dev icu-libs zlib-dev g++ make automake autoconf libzip-dev \
    libpng-dev libwebp-dev libjpeg-turbo-dev freetype-dev && \
    docker-php-ext-configure gd --enable-gd --with-freetype --with-jpeg --with-webp && \
    docker-php-ext-install gd

RUN docker-php-ext-install pdo pdo_mysql

#RUN sed -i '/#!\/bin\/sh/aecho "$(hostname -i)\t$(hostname) $(hostname).localhost" >> /etc/hosts' /usr/local/bin/docker-php-entrypoint

#RUN sed -i "s/post_max_size =.*/post_max_size = 200M/g" /etc/php5/fpm/php.ini
#RUN sed -i "s/upload_max_filesize =.*/upload_max_filesize = 200M/g" /etc/php5/fpm/php.ini

RUN echo "post_max_size=200M" > /usr/local/etc/php/conf.d/php-uploadsize.ini
RUN echo "upload_max_filesize=200M" >> /usr/local/etc/php/conf.d/php-uploadsize.ini
#below optional MAKE SURE the below line is in server block of nginx.conf
#client_max_body_size 200M;
RUN echo "client_max_body_size=200M" >> /usr/local/etc/php/conf.d/php-uploadsize.ini
RUN echo "memory_limit=200M" >> /usr/local/etc/php/conf.d/php-uploadsize.ini