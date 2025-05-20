#!/bin/bash

if [ -f /var/www/html/config/db_credentials.example.php ]; then
    sed -i 's/127.0.0.1/db/g' /var/www/html/config/db_credentials.example.php
    sed -i 's/db/task_calendar/g' /var/www/html/config/db_credentials.example.php
    sed -i 's/password/task_password/g' /var/www/html/config/db_credentials.example.php
    cp /var/www/html/config/db_credentials.example.php /var/www/html/config/db_credentials.php
fi