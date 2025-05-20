#!/bin/bash

# Get PHP executable path
PHP_PATH=$(which php)
echo "PHP executable path: $PHP_PATH" > /var/www/html/logs/notifications.log

# Ensure logs directory has proper permissions
mkdir -p /var/www/html/logs
chmod -R 777 /var/www/html/logs

# Clear existing log file or create it
echo "Service started at $(date)" >> /var/www/html/logs/notifications.log
chmod 666 /var/www/html/logs/notifications.log

# Test PHP availability
PHP_VERSION=$($PHP_PATH -v | head -n 1)
echo "PHP Version: $PHP_VERSION" >> /var/www/html/logs/notifications.log

# Print environment variables
echo "Environment PATH: $PATH" >> /var/www/html/logs/notifications.log

# Test Telegram Notify script exists
if [ -f /var/www/html/telegram_notify.php ]; then
    echo "telegram_notify.php found." >> /var/www/html/logs/notifications.log
else
    echo "ERROR: telegram_notify.php not found!" >> /var/www/html/logs/notifications.log
fi

# Test database connection
$PHP_PATH -r '
require_once "/var/www/html/config/database.php";
$database = new Database();
try {
    $db = $database->getConnection();
    echo "Database connection successful\n";
} catch (Exception $e) {
    echo "Database connection failed: " . $e->getMessage() . "\n";
}
' >> /var/www/html/logs/notifications.log 2>&1

# Run a single manual test of the notification script
echo "Running initial test of telegram_notify.php..." >> /var/www/html/logs/notifications.log
cd /var/www/html && $PHP_PATH telegram_notify.php >> /var/www/html/logs/notifications.log 2>&1
echo "Initial test completed." >> /var/www/html/logs/notifications.log

# Start supervisor
echo "Starting supervisor service..." >> /var/www/html/logs/notifications.log
exec /usr/bin/supervisord -n -c /etc/supervisor/conf.d/supervisord.conf