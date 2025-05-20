<?php
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
    <div class="container">
        <h1>ngrok Status</h1>
        <p>Your application is currently accessible at:</p>
        <div class="url-box">
            <a href="<?php echo NGROK_PUBLIC_URL; ?>" target="_blank"><?php echo NGROK_PUBLIC_URL; ?></a>
        </div>
        <p>Telegram webhook is set to:</p>
        <div class="url-box">
            <?php echo NGROK_PUBLIC_URL; ?>/telegram_bot.php
        </div>
        <p><small>Last updated: <?php echo date('Y-m-d H:i:s'); ?></small></p>
    </div>
</body>
</html>

