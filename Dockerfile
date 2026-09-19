FROM php:8.2-apache

# Install FFmpeg and required image libraries
RUN apt-get update && apt-get install -y \
    ffmpeg \
    libfreetype6-dev \
    libjpeg62-turbo-dev \
    libpng-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) gd \
    && rm -rf /var/lib/apt/lists/*

# Enable Apache mod_rewrite
RUN a2enmod rewrite

# Copy project files
COPY . /var/www/html/

# Setup working directories and permissions
RUN mkdir -p /var/www/html/output /var/www/html/temp \
    && chown -R www-data:www-data /var/www/html \
    && chmod -R 775 /var/www/html/output /var/www/html/temp

EXPOSE 80
CMD ["apache2-foreground"]
