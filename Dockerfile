FROM php:8.2-apache

# Install system deps, PHP extensions, and Composer
RUN apt-get update && apt-get install -y \
        unzip \
        git \
        curl \
    && docker-php-ext-install mysqli pdo pdo_mysql \
    && curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

COPY . /var/www/html/

# Install PHP dependencies inside the container
ENV COMPOSER_ALLOW_SUPERUSER=1
RUN cd /var/www/html && composer install --no-dev --optimize-autoloader --no-interaction --no-scripts

EXPOSE 80

