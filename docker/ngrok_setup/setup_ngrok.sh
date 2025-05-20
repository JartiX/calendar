#!/bin/bash

echo "Waiting for ngrok to start..."
sleep 10

NGROK_PUBLIC_URL=$(curl -s http://ngrok:4040/api/tunnels | grep -o '"public_url":"[^"]*' | grep -o 'http[^"]*')

if [ -z "$NGROK_PUBLIC_URL" ]; then
    echo "Error: Could not get ngrok public URL. Make sure ngrok is running properly."
    exit 1
fi

echo "Ngrok public URL: $NGROK_PUBLIC_URL"

TELEGRAM_BOT_TOKEN=$(grep -o "TELEGRAM_BOT_TOKEN', '[^']*" /var/www/html/config/tg_credentials.php | cut -d "'" -f 3)

if [ -z "$TELEGRAM_BOT_TOKEN" ]; then
    echo "Error: Could not get Telegram bot token from config file."
    exit 1
fi

echo "Setting Telegram webhook to $NGROK_PUBLIC_URL/telegram_bot.php"

RESPONSE=$(curl -s -X POST "https://api.telegram.org/bot$TELEGRAM_BOT_TOKEN/setWebhook" \
    -H "Content-Type: application/json" \
    -d "{\"url\": \"$NGROK_PUBLIC_URL/telegram_bot.php\", \"drop_pending_updates\": true}")

echo "Telegram API response: $RESPONSE"

echo "<?php
/**
 * Автоматически сгенерированный файл с текущим URL ngrok
 * Создан: $(date)
 */

define('NGROK_PUBLIC_URL', '$NGROK_PUBLIC_URL');
" > /var/www/html/config/ngrok_url.php

echo "Created ngrok_url.php with current URL"

echo "<?php
require_once 'config/ngrok_url.php';
?>
<!DOCTYPE html>
<html>
<head>
    <title>ngrok Status</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 40px; }
        .container { max-width: 800px; margin: 0 auto; }
        .url-box { 
            background: #f5f5f5; 
            padding: 15px; 
            border-radius: 5px; 
            margin: 20px 0;
            word-break: break-all;
        }
        h1 { color: #333; }
    </style>
</head>
<body>
    <div class=\"container\">
        <h1>ngrok Status</h1>
        <p>Your application is currently accessible at:</p>
        <div class=\"url-box\">
            <a href=\"<?php echo NGROK_PUBLIC_URL; ?>\" target=\"_blank\"><?php echo NGROK_PUBLIC_URL; ?></a>
        </div>
        <p>Telegram webhook is set to:</p>
        <div class=\"url-box\">
            <?php echo NGROK_PUBLIC_URL; ?>/telegram_bot.php
        </div>
        <p><small>Last updated: <?php echo date('Y-m-d H:i:s'); ?></small></p>
    </div>
</body>
</html>
" > /var/www/html/ngrok_status.php

echo "Created ngrok_status.php"
echo "Setup complete. You can view the ngrok status page at $NGROK_PUBLIC_URL/ngrok_status.php"