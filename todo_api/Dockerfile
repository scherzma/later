# Use the official PHP runtime as a base image
FROM php:8.2-cli

# Install MySQLi extension
RUN docker-php-ext-install mysqli pdo pdo_mysql
RUN apt-get update && apt-get install -y git zip unzip

# Install Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer


# Set working directory
WORKDIR /var/www/todo-api

COPY composer.json composer.lock ./
RUN composer install --no-dev --optimize-autoloader

# Expose port 8000
EXPOSE 8000

# Start the PHP built-in server
CMD ["php", "-S", "0.0.0.0:8000", "-t", "."]