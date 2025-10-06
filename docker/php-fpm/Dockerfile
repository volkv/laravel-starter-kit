FROM php:8.4.13-fpm-alpine

# Install dependencies
RUN apk add --no-cache \
    build-base \
    autoconf \
    libpng-dev \
    libjpeg-turbo-dev \
    freetype-dev \
    zip \
    jpegoptim optipng pngquant gifsicle \
    vim \
    unzip \
    git \
    curl \
    mpc1-dev \
    oniguruma-dev \
    libzip-dev \
    libxml2-dev \
    curl-dev \
    libxpm-dev \
    libvpx-dev \
    icu-dev \
    gmp-dev \
    bash

#postgres
RUN apk add --no-cache postgresql-dev
RUN docker-php-ext-configure pgsql -with-pgsql=/usr/local/pgsql
RUN docker-php-ext-install pdo pdo_pgsql pgsql

# NodeJS
RUN apk add --no-cache nodejs npm

RUN docker-php-ext-configure gd --with-freetype --with-jpeg

# Create user with UID/GID 1000:1000  
RUN addgroup -g 1000 appuser && \
    adduser -u 1000 -G appuser -D -s /bin/bash appuser

# Create directories for PHP and logs
RUN mkdir -p /var/log/php /composer /npm
RUN chown -R appuser:appuser /var/log/php /npm /composer
RUN chmod -R 755 /var/log/php

RUN npm set cache "/npm" -g

RUN npm install -g npm

# Install composer
ENV XDG_CONFIG_HOME /
ENV COMPOSER_HOME /composer
ENV COMPOSER_MEMORY_LIMIT -1
ENV COMPOSER_ALLOW_SUPERUSER 1
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin/ --filename=composer
# Install extensions

RUN docker-php-ext-install zip exif gd intl bcmath opcache pcntl gmp
RUN pecl install redis && docker-php-ext-enable redis

COPY "php.ini" "/usr/local/etc/php/php.ini"
COPY "www.conf" "/usr/local/etc/php-fpm.d/www.conf"

WORKDIR /var/www

USER appuser

# Expose port 9000 and start php-fpm server
EXPOSE 9000
