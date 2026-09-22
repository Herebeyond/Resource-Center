FROM php:8.2-apache

RUN apt-get update && apt-get install -y \
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

RUN composer install --no-interaction --prefer-dist --no-progress

EXPOSE 8000

CMD ["php", "-S", "0.0.0.0:8000", "-t", "public"]
