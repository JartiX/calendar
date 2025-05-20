<?php
/**
 * Скрипт для обработки запросов от Telegram Bot API
 */

require_once 'config/settings.php';
require_once 'config/database.php';
require_once 'models/telegram_user.php';
require_once 'models/telegram_code.php';
require_once 'controllers/telegram_controller.php';
require_once 'config/tg_credentials.php';

// Получаем данные от Telegram
$update_json = file_get_contents('php://input');
$update = json_decode($update_json, true);
error_log('Telegram update: ' . $update_json);

$database = new Database();
$db = $database->getConnection();

$telegramController = new TelegramController($db);

if (isset($update['message'])) {
    $message = $update['message'];
    $chatId = $message['chat']['id'];
    $username = isset($message['from']['username']) ? $message['from']['username'] : null;
    
    // Если сообщение содержит команду
    if (isset($message['text']) && strpos($message['text'], '/') === 0) {
        $command = explode(' ', $message['text'])[0];
        
        switch ($command) {
            case '/start':
                $reply = "Привет! Я бот для уведомлений о задачах.\n\n";
                $reply .= "Для подключения используйте команду /connect [код_подключения]";
                sendTelegramMessage($chatId, $reply);
                break;
                
            case '/connect':
                $parts = explode(' ', $message['text'], 2);
                if (count($parts) < 2) {
                    sendTelegramMessage($chatId, "Пожалуйста, укажите код подключения: /connect [код_подключения]");
                    break;
                }
                
                $connectionCode = trim($parts[1]);
                error_log("Попытка валидации кода подключения: " . $connectionCode);
                
                $userId = $telegramController->validateConnectionCode($connectionCode);
                
                if ($userId) {
                    error_log("Валидация кода прошла успешно для пользователя с ID: " . $userId);
                    if ($telegramController->connectTelegram($userId, $chatId, $username)) {
                        sendTelegramMessage($chatId, "Подключение успешно установлено! Теперь вы будете получать уведомления о задачах.");
                    } else {
                        error_log("Failed to connect Telegram for user ID: " . $userId);
                        sendTelegramMessage($chatId, "Не удалось установить подключение. Пожалуйста, попробуйте позже.");
                    }
                } else {
                    error_log("Неверный код подключения: " . $connectionCode);
                    sendTelegramMessage($chatId, "Недействительный или истекший код подключения. Пожалуйста, получите новый код в веб-интерфейсе.");
                }
                break;
                
            case '/help':
                $reply = "Доступные команды:\n";
                $reply .= "/start - Начало работы с ботом\n";
                $reply .= "/connect [код] - Подключение к аккаунту\n";
                $reply .= "/disconnect - Отключение от системы\n";
                $reply .= "/help - Показать эту справку";
                sendTelegramMessage($chatId, $reply);
                break;
                
            default:
                sendTelegramMessage($chatId, "Неизвестная команда. Используйте /help для просмотра доступных команд.");
                break;
        }
    }
}

/**
 * Отправляет сообщение в Telegram используя cURL вместо file_get_contents
 *
 * @param int $chatId ID чата
 * @param string $text Текст сообщения
 * @return bool Результат отправки
 */
function sendTelegramMessage($chatId, $text) {
    $data = [
        'chat_id' => $chatId,
        'text' => $text,
        'parse_mode' => 'HTML'
    ];
    
    $url = 'https://api.telegram.org/bot' . TELEGRAM_BOT_TOKEN . '/sendMessage';
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    
    // SSL опции для решения проблем с сертификатами
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
    
    if (DEBUG_MODE) {
        error_log("Отправка запроса в Telegram API: " . json_encode($data));
    }
    
    $response = curl_exec($ch);
    
    if ($response === false) {
        $error = curl_error($ch);
        $errno = curl_errno($ch);
        error_log("cURL Error ($errno): " . $error);
        curl_close($ch);
        return false;
    }
    
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    
    if (DEBUG_MODE) {
        error_log("Код ответа Telegram API: " . $httpCode);
        error_log("Ответ Telegram API: " . $response);
    }
    
    curl_close($ch);
    
    $result = json_decode($response, true);
    
    return isset($result['ok']) && $result['ok'] === true;
}
?>