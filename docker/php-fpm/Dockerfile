FROM php:8.5.4-fpm-alpine

# Runtime packages: tools + shared libraries required by PHP extensions
RUN apk add --no-cache \
    bash \
    git \
    curl \
    vim \
    zip \
    unzip \
    jpegoptim optipng pngquant gifsicle \
    nodejs \
    npm \
    freetype \
    libjpeg-turbo \
    libpng \
    libzip \
    icu-libs \
    icu-data-full \
    gmp \
    libpq \
    sqlite-libs

# Build PHP extensions with throwaway build dependencies
RUN apk add --no-cache --virtual .build-deps \
        $PHPIZE_DEPS \
        freetype-dev \
        libjpeg-turbo-dev \
        libpng-dev \
        libzip-dev \
        icu-dev \
        gmp-dev \
        postgresql-dev \
        sqlite-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
        pdo_pgsql \
        pgsql \
        pdo_sqlite \
        zip \
        exif \
        gd \
        intl \
        bcmath \
        pcntl \
        gmp \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && apk del .build-deps

# Create user with UID/GID 1000:1000
RUN addgroup -g 1000 appuser && \
    adduser -u 1000 -G appuser -D -s /bin/bash appuser

# Directories for logs and package caches
RUN mkdir -p /var/log/php /composer /npm && \
    chown -R appuser:appuser /var/log/php /npm /composer

RUN npm set cache "/npm" -g

ENV XDG_CONFIG_HOME=/
ENV COMPOSER_HOME=/composer
ENV COMPOSER_MEMORY_LIMIT=-1
COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer

COPY "php.ini" "/usr/local/etc/php/php.ini"
COPY "www.conf" "/usr/local/etc/php-fpm.d/www.conf"

WORKDIR /var/www

USER appuser

EXPOSE 9000
