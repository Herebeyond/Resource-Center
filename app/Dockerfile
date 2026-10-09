FROM php:8.2-apache

RUN apt-get update && apt-get install -y --no-install-recommends \
    libicu-dev \
    libpq-dev \
    libzip-dev \
    unzip \
    git \
    && docker-php-ext-configure intl \
    && docker-php-ext-install intl pdo pdo_pgsql zip \
    && a2enmod rewrite \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer

WORKDIR /var/www/html

COPY . /var/www/html

RUN printf '%s\n' \
    'APP_ENV=prod' \
    'APP_SECRET=build-only-placeholder' \
    'DATABASE_URL=postgresql://build:build@127.0.0.1:5432/build?serverVersion=16&charset=utf8' \
    'DEFAULT_URI=http://localhost' \
    > .env \
    && composer install --no-interaction --prefer-dist --no-progress \
    && rm .env

RUN mkdir -p var && chown -R www-data:www-data var

COPY --chown=www-data:www-data .env.example .env

USER www-data

EXPOSE 8000

CMD ["php", "-S", "0.0.0.0:8000", "-t", "public"]
