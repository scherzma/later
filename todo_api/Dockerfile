# Use the official PHP runtime as a base image
FROM php:8.2-cli

# Set working directory
WORKDIR /var/www/todo-api

# Expose port 8000
EXPOSE 8000

# Start the PHP built-in server
CMD ["php", "-S", "0.0.0.0:8000", "-t", "."]