# Use PHP with Apache for a classic PHP/MySQL stack
FROM php:8.2-apache

# 1. Install system dependencies (git, zip, unzip) needed by Composer
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    libzip-dev \
    && docker-php-ext-install mysqli pdo pdo_mysql zip

# 2. Enable Apache mod_rewrite
RUN a2enmod rewrite

# 3. Configure DocumentRoot to /public
ENV APACHE_DOCUMENT_ROOT /var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

# 4. Copy your website files
COPY . /var/www/html/

# 5. Set permissions
RUN chown -R www-data:www-data /var/www/html

# 6. Install Composer from official image
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# 7. Run composer install
# We use --allow-plugins because some packages require it
RUN composer install --no-interaction --optimize-autoloader --no-dev

# Health check
HEALTHCHECK --interval=30s --timeout=3s --retries=3 \
  CMD curl -f http://localhost/ || exit 1

EXPOSE 80