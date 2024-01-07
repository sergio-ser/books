# Use the official PHP image with FPM (FastCGI Process Manager)
FROM php:8.1-fpm

# Increase PHP memory limit
RUN echo "memory_limit = 256M" > /usr/local/etc/php/conf.d/docker-php-memory-limit.ini

# Install required PHP extensions
RUN docker-php-ext-install pdo_mysql opcache sockets

# Install Nginx
RUN apt-get update && apt-get install -y nginx && apt-get install -y unzip && apt-get install -y nano && apt-get install -y cron

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

# Create a script to run commands and start services
COPY start.sh /usr/local/bin/start.sh
RUN chmod +x /usr/local/bin/start.sh

# Add a cron job to run Symfony command every 5 minutes
RUN echo "*/1 * * * * www-data /usr/local/bin/php /var/www/html/bin/console app:consume-books" > /etc/crontab

# Start PHP-FPM and Nginx
CMD /usr/local/bin/start.sh