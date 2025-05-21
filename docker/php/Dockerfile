FROM php:8.0-apache

# Install dependencies
RUN apt-get update && apt-get install -y \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libzip-dev \
    zip \
    unzip \
    curl \
    openssl \
    ca-certificates \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) gd pdo pdo_mysql mysqli zip

RUN apt-get update && apt-get install -y locales && \
    sed -i 's/# ru_RU.UTF-8 UTF-8/ru_RU.UTF-8 UTF-8/' /etc/locale.gen && \
    locale-gen ru_RU.UTF-8

ENV LANG ru_RU.UTF-8
ENV LC_ALL ru_RU.UTF-8

# Update CA certificates
RUN update-ca-certificates

# Configure PHP for SSL
RUN { \
    echo 'openssl.cafile = /etc/ssl/certs/ca-certificates.crt'; \
    echo 'curl.cainfo = /etc/ssl/certs/ca-certificates.crt'; \
} > /usr/local/etc/php/conf.d/ssl-cainfo.ini

# Установка кодировки для Apache
RUN echo 'AddDefaultCharset UTF-8' > /etc/apache2/conf-available/charset.conf && \
    a2enconf charset

# Enable Apache modules
RUN a2enmod rewrite

# Set the working directory
WORKDIR /var/www/html

# Configure PHP
COPY docker/php/php.ini /usr/local/etc/php/conf.d/custom.ini

# Create logs directory with correct permissions
RUN mkdir -p /var/www/html/logs && \
    chown -R www-data:www-data /var/www/html && \
    chmod -R 755 /var/www/html

# Copy application files
COPY . /var/www/html/

# Set permissions
RUN chown -R www-data:www-data /var/www/html

# Expose port 80
EXPOSE 80

# Start Apache
CMD ["apache2-foreground"]