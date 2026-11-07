# # Stage 1: Node.js build
# FROM node:23.7.0 AS node-builder

# WORKDIR /app

# # Copy only necessary files for frontend build
# COPY package.json package-lock.json ./
# RUN npm install --legacy-peer-deps

# COPY . ./
# RUN npm run build

# # Stage 2: PHP Apache
# FROM php:8.3-apache

# # Install system dependencies
# RUN apt-get update && apt-get install -y \
#     git zip unzip sudo nano curl \
#     libpng-dev libjpeg-dev libfreetype6-dev
#     # libicu-dev libbz2-dev libpng-dev libjpeg-dev libpq-dev libmcrypt-dev libreadline-dev libfreetype6-dev g++

# # Enable Apache modules
# RUN a2enmod rewrite headers

# # Install PHP extensions
# RUN docker-php-ext-install mysqli pdo_mysql pdo # pdo_pgsql
# RUN docker-php-ext-configure gd --with-freetype --with-jpeg
# RUN docker-php-ext-install gd

# # Install Composer
# RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
# ENV COMPOSER_ALLOW_SUPERUSER=1

# # Copy Apache config and start script
# COPY .docker/apache/000-default.conf /etc/apache2/sites-available/000-default.conf
# COPY .docker/apache/start-apache /usr/local/bin/start-apache
# RUN chmod +x /usr/local/bin/start-apache

# # Copy application source code (excluding node_modules)
# COPY . /var/www/

# # Copy built frontend from node-builder stage
# COPY --from=node-builder /app/public /var/www/public

# # Set permissions
# RUN chown -R www-data:www-data /var/www
# # Install PHP dependencies
# RUN composer install --prefer-dist --working-dir=/var/www

# CMD ["start-apache"]
