FROM php:8.2-apache

# Install precise extensions and supervisor
RUN apt-get update && apt-get install -y supervisor zip unzip default-mysql-client \
    && docker-php-ext-install pdo pdo_mysql

# Copy the app files
COPY . /var/www/html/

# Optional: if you intend to use Composer deeply inside the container
# RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
# RUN cd /var/www/html && composer install --no-dev

# Ensure Apache listens on port 80 for normal HTTP requests
EXPOSE 80
# Ensure WebSockets listens on port 8080
EXPOSE 8080

# Overwrite default command with Supervisor
COPY supervisord.conf /etc/supervisor/conf.d/supervisord.conf
CMD ["supervisord", "-n"]
