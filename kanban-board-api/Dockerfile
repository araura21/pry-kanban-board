FROM php:8.4-cli
RUN apt-get update && apt-get install -y libpq-dev libicu-dev git unzip libzip-dev \
    && docker-php-ext-install pdo_pgsql intl zip pcntl \
    && rm -rf /var/lib/apt/lists/*
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
ENV COMPOSER_ALLOW_SUPERUSER=1
WORKDIR /var/www/html
