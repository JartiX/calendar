FROM php:8.0-cli

# Install cron and dependencies
RUN apt-get update && apt-get install -y \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libzip-dev \
    zip \
    unzip \
    curl \
    cron \
    openssl \
    ca-certificates \
    supervisor \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) gd pdo pdo_mysql mysqli zip

# Update CA certificates
RUN update-ca-certificates

# Configure PHP for SSL
RUN { \
    echo 'openssl.cafile = /etc/ssl/certs/ca-certificates.crt'; \
    echo 'curl.cainfo = /etc/ssl/certs/ca-certificates.crt'; \
} > /usr/local/etc/php/conf.d/ssl-cainfo.ini

# Set the working directory
WORKDIR /var/www/html

# Configure PHP
COPY docker/php/php.ini /usr/local/etc/php/conf.d/custom.ini

# Create logs directory with correct permissions
RUN mkdir -p /var/www/html/logs

# Create a script to run notification
RUN echo '#!/bin/bash\n\
PHP_PATH=$(which php)\n\
cd /var/www/html\n\
echo "[$(date)] Running notification script" >> /var/www/html/logs/notifications.log\n\
$PHP_PATH /var/www/html/telegram_notify.php >> /var/www/html/logs/notifications.log 2>&1\n\
echo "[$(date)] Notification script completed" >> /var/www/html/logs/notifications.log\n\
' > /usr/local/bin/run-notifications

# Make the script executable
RUN chmod +x /usr/local/bin/run-notifications

# Set up supervisor configuration for cron-like behavior
RUN mkdir -p /var/log/supervisor
COPY docker/cron/supervisord.conf /etc/supervisor/conf.d/supervisord.conf

# Create entry point script
COPY docker/cron/entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh

# Start supervisor as the entry point
CMD ["/entrypoint.sh"]