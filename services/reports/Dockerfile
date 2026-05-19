FROM php:8.4-apache

# Install PostgreSQL extensions
RUN apt-get update && apt-get install -y libpq-dev \
    && docker-php-ext-install pdo pdo_pgsql

# Enable Apache mod_rewrite for API routing
RUN a2enmod rewrite

# Copy local code to the container
COPY . /var/www/html/
RUN chown -R www-data:www-data /var/www/html

# Use a custom entrypoint to set the port at runtime
CMD sed -i "s/Listen 80/Listen ${PORT:-80}/g" /etc/apache2/ports.conf && \
    sed -i "s/:80/:${PORT:-80}/g" /etc/apache2/sites-available/000-default.conf && \
    apache2-foreground
