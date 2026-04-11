FROM php:8.3-apache

# Arguments for user/group IDs
ARG USER_ID=1000
ARG GROUP_ID=1000

# Enable Apache mod_rewrite
RUN a2enmod rewrite

# Install system dependencies and PHP extensions
RUN apt-get update && apt-get install -y \
    libsqlite3-dev \
    git \
    unzip \
    gosu \
    && docker-php-ext-install pdo pdo_sqlite \
    && pecl install xdebug \
    && docker-php-ext-enable xdebug \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

COPY docker/xdebug.ini /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini

# Create 'sail' user with same UID/GID as host user (like Laravel Sail)
RUN groupadd --force -g ${GROUP_ID} sail \
    && useradd -ms /bin/bash --no-user-group -g ${GROUP_ID} -u ${USER_ID} sail

# Configure Apache to run as sail user (Laravel Sail approach)
ENV APACHE_RUN_USER=sail
ENV APACHE_RUN_GROUP=sail

# Install Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Set document root to public/
ENV APACHE_DOCUMENT_ROOT=/var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf \
    && sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

# Allow .htaccess overrides
RUN sed -ri -e 's/AllowOverride None/AllowOverride All/g' /etc/apache2/apache2.conf

# Set working directory
WORKDIR /var/www/html

# Copy composer files and install dependencies
COPY composer.json composer.lock ./
RUN composer install --no-dev --optimize-autoloader

# Copy application code
COPY . .

# Ensure database directory is writable
RUN mkdir -p /var/www/html/database \
    && chown -R sail:sail /var/www/html/database

# Create startup script to fix permissions
RUN echo '#!/bin/bash\n\
chown -R sail:sail /var/www/html/database /var/www/html/public/uploads 2>/dev/null || true\n\
exec apache2-foreground\n\
' > /usr/local/bin/start-container \
    && chmod +x /usr/local/bin/start-container

EXPOSE 80
