<?php
/**
 * Контроллер для интеграции с Telegram
 */
require_once 'models/telegram_user.php';

if (!isset($db) || !$db) {
    $database = new Database();
    $db = $database->getConnection();
}

class TelegramController {
    private $db;
    private $telegramUser;
    
    public function __construct($db) {
        $this->db = $db;
        $this->telegramUser = new TelegramUser($db);
    }
    
    public function generateConnectionCode($userId) {
        require_once 'models/telegram_code.php';
        
        $telegramCode = new TelegramCode($this->db);
        $telegramCode->user_id = $userId;
        $telegramCode->connection_code = bin2hex(random_bytes(16));
        
        $telegramCode->expires_at = date('Y-m-d H:i:s', time() + 3600);
        
        if ($telegramCode->create()) {
            return $telegramCode->connection_code;
        }
        
        return false;
    }

    public function validateConnectionCode($code) {
        require_once 'models/telegram_code.php';
        
        $telegramCode = new TelegramCode($this->db);
        
        if ($telegramCode->findValidCode($code)) {
            $userId = $telegramCode->user_id;
            
            // Delete the used code
            $telegramCode->deleteCode();
            
            return $userId;
        }
        
        return false;
    }
    
    public function connectTelegram($userId, $chatId, $username = null) {
        if (empty($userId) || empty($chatId)) {
            return false;
        }
        
        $existingConnection = $this->telegramUser->readByUserId($userId);
        
        if ($existingConnection) {
            $this->telegramUser->id = $existingConnection['id'];
            $this->telegramUser->deactivate();
        }
        
        $this->telegramUser->user_id = $userId;
        $this->telegramUser->telegram_chat_id = $chatId;
        $this->telegramUser->telegram_username = $username;
        $this->telegramUser->is_active = true;
        
        return $this->telegramUser->create();
    }
    
    public function disconnectTelegram($userId) {
        $connection = $this->telegramUser->readByUserId($userId);
        
        if ($connection) {
            $this->telegramUser->id = $connection['id'];
            return $this->telegramUser->deactivate();
        }
        
        return false;
    }
    
    public function isConnected($userId) {
        $connection = $this->telegramUser->readByUserId($userId);
        return $connection !== null;
    }
    
    public function getConnectionInfo($userId) {
        return $this->telegramUser->readByUserId($userId);
    }

    public function setWebhook($url = null) {
        if ($url === null) {
            // Если URL не предоставлен, используем ngrok URL
            if (defined('NGROK_PUBLIC_URL')) {
                $url = NGROK_PUBLIC_URL . '/telegram_bot.php';
            } else {
                return [
                    'success' => false,
                    'message' => 'NGROK_PUBLIC_URL не определен'
                ];
            }
        }
        
        $data = [
            'url' => $url,
            'drop_pending_updates' => true
        ];
        
        $url = 'https://api.telegram.org/bot' . TELEGRAM_BOT_TOKEN . '/setWebhook';
        
        $options = [
            'http' => [
                'method' => 'POST',
                'header' => 'Content-Type: application/json',
                'content' => json_encode($data)
            ]
        ];
        
        $context = stream_context_create($options);
        $result = file_get_contents($url, false, $context);
        
        if ($result === false) {
            return [
                'success' => false,
                'message' => 'Ошибка при отправке запроса к API Telegram'
            ];
        }
        
        $result = json_decode($result, true);
        
        return [
            'success' => isset($result['ok']) && $result['ok'] === true,
            'message' => isset($result['description']) ? $result['description'] : 'Неизвестный ответ от API Telegram',
            'result' => $result
        ];
    }

    public function getWebhookInfo() {
        $url = 'https://api.telegram.org/bot' . TELEGRAM_BOT_TOKEN . '/getWebhookInfo';
        
        $result = file_get_contents($url);
        
        if ($result === false) {
            return [
                'success' => false,
                'message' => 'Ошибка при отправке запроса к API Telegram'
            ];
        }
        
        $result = json_decode($result, true);
        
        return [
            'success' => isset($result['ok']) && $result['ok'] === true,
            'message' => 'Информация получена успешно',
            'result' => $result
        ];
    }
}
?>