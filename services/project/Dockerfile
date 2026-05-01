FROM php:8.4-apache

# Install PostgreSQL extensions
RUN apt-get update && apt-get install -y libpq-dev \
    && docker-php-ext-install pdo pdo_pgsql

# Enable Apache mod_rewrite for API routing
RUN a2enmod rewrite

# Copy local code to the container
COPY . /var/www/html/
RUN chown -R www-data:www-data /var/www/html
