# Use official PHP with Apache
FROM php:8.2-apache

# Install PHP extensions and Composer for the test stage
RUN apt-get update \
	&& apt-get install -y --no-install-recommends git unzip \
	&& docker-php-ext-install mysqli pdo pdo_mysql \
	&& rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Copy all your project files into the container
WORKDIR /var/www/html
COPY . /var/www/html/

# Install PHP test dependencies when composer.json is available
RUN if [ -f composer.json ]; then composer install --no-interaction --no-progress --prefer-dist; fi

# Give Apache the right permissions
RUN chown -R www-data:www-data /var/www/html/

# Expose port 80
EXPOSE 80