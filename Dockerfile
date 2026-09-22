FROM php:8.2-apache

# Install FFmpeg, image libraries, and PostgreSQL driver (for Supabase)
RUN apt-get update && apt-get install -y \
    ffmpeg \
    libfreetype6-dev \
    libjpeg62-turbo-dev \
    libpng-dev \
    libpq-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) gd pdo pdo_pgsql \
    && rm -rf /var/lib/apt/lists/*

# Enable Apache mod_rewrite & allow .htaccess overrides
RUN a2enmod rewrite \
    && sed -i 's/AllowOverride None/AllowOverride All/g' /etc/apache2/apache2.conf


# Copy all project files (Root homepage + countNumber tool)
COPY . /var/www/html/

# Setup working directories and permissions for the tool
RUN mkdir -p /var/www/html/countNumber/output /var/www/html/countNumber/temp /var/www/html/countNumber/data \
    && chown -R www-data:www-data /var/www/html \
    && chmod -R 775 /var/www/html/countNumber/output /var/www/html/countNumber/temp /var/www/html/countNumber/data

EXPOSE 80
CMD ["apache2-foreground"]

