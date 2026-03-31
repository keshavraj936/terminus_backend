FROM php:8.2-apache

RUN apt-get update && apt-get install -y unzip git \
    && docker-php-ext-install pdo pdo_mysql

# Install Composer
COPY composer.json composer.lock ./
RUN php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');" \
    && php composer-setup.php \
    && php composer.phar install \
    && rm composer-setup.php

# Copy project files
COPY . /var/www/html/

# Supervisor
COPY supervisord.conf /etc/supervisor/conf.d/supervisord.conf

EXPOSE 80

CMD ["supervisord", "-n"]
