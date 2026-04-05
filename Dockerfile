FROM php:8.2-apache

# Install PHP extensions + composer
RUN docker-php-ext-install mysqli pdo pdo_mysql \
    && curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

COPY . /var/www/html/

# Install PHP dependencies (vendor/ is gitignored, must be built here)
RUN cd /var/www/html && composer install --no-dev --optimize-autoloader --no-interaction

EXPOSE 80
