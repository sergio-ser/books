# Use the official PHP image with FPM (FastCGI Process Manager)
FROM php:8.1-fpm

# Install required PHP extensions
RUN docker-php-ext-install pdo_mysql opcache

# Install Nginx
RUN apt-get update && apt-get install -y nginx && apt-get install -y nano

# Copy Nginx configuration
COPY nginx.conf /etc/nginx/sites-available/default

# Set the working directory
WORKDIR /var/www/html

# Copy the Symfony application files to the container
COPY . /var/www/html

# Set permissions for the Symfony application files
RUN chown -R www-data:www-data /var/www/html

# Install Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer --version=2.2.19

# Install Symfony dependencies
RUN composer install --no-scripts --no-autoloader

# Run Symfony console commands (e.g., migrations)
RUN php bin/console doctrine:migrations:migrate --no-interaction

# Start PHP-FPM and Nginx
CMD service nginx start && php-fpm -R