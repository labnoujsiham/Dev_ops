# Use official PHP with Apache
FROM php:8.2-apache

# Install PHP MySQL extension
RUN docker-php-ext-install mysqli pdo pdo_mysql

# Copy all your project files into the container
COPY . /var/www/html/

# Give Apache the right permissions
RUN chown -R www-data:www-data /var/www/html/

# Expose port 80
EXPOSE 80