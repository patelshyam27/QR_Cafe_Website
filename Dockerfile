FROM php:8.3-apache

# Enable Apache mod_rewrite
RUN a2enmod rewrite

# Install SQLite and MySQL PDO extensions
RUN apt-get update && apt-get install -y libsqlite3-dev \
    && docker-php-ext-install pdo pdo_sqlite pdo_mysql

# Copy website files to web root
COPY QR_Cafe_PHP_Website/ /var/www/html/

# Set write permissions for cafe.db SQLite database
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 775 /var/www/html

EXPOSE 80
CMD ["apache2-foreground"]
