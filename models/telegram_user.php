<?php
/**
 * Модель для работы с подключениями Telegram
 */
class TelegramUser {
    private $conn;
    private $table_name = "telegram_users";
    
    public $id;
    public $user_id;
    public $telegram_chat_id;
    public $telegram_username;
    public $is_active;
    public $created_at;
    
    public function __construct($db) {
        $this->conn = $db;
    }
    
    public function create() {
        if (empty($this->user_id) || empty($this->telegram_chat_id)) {
            return false;
        }
        
        $query = "INSERT INTO " . $this->table_name . " 
                    (user_id, telegram_chat_id, telegram_username, is_active) 
                VALUES 
                    (:user_id, :telegram_chat_id, :telegram_username, :is_active)
                ON DUPLICATE KEY UPDATE 
                    telegram_chat_id = VALUES(telegram_chat_id),
                    telegram_username = VALUES(telegram_username),
                    is_active = VALUES(is_active)";
        
        try {
            $stmt = $this->conn->prepare($query);
            
            $stmt->bindParam(":user_id", $this->user_id);
            $stmt->bindParam(":telegram_chat_id", $this->telegram_chat_id);
            $stmt->bindParam(":telegram_username", $this->telegram_username);
            
            $this->is_active = isset($this->is_active) ? $this->is_active : true;
            $stmt->bindParam(":is_active", $this->is_active);
            
            if($stmt->execute()) {
                $this->id = $this->conn->lastInsertId();
                return true;
            }
        } catch (PDOException $e) {
            error_log("Ошибка создания подключения Telegram: " . $e->getMessage());
        }
        
        return false;
    }
    
    public function readByUserId($user_id) {
        $query = "SELECT * FROM " . $this->table_name . " 
                WHERE user_id = :user_id AND is_active = TRUE 
                ORDER BY created_at DESC LIMIT 1";
        
        try {
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":user_id", $user_id);
            $stmt->execute();
            
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if($row) {
                $this->id = $row['id'];
                $this->user_id = $row['user_id'];
                $this->telegram_chat_id = $row['telegram_chat_id'];
                $this->telegram_username = $row['telegram_username'];
                $this->is_active = $row['is_active'];
                $this->created_at = $row['created_at'];
                
                return $row;
            }
        } catch (PDOException $e) {
            error_log("Ошибка получения подключения Telegram: " . $e->getMessage());
        }
        
        return null;
    }
    
    public function deactivate() {
        $this->is_active = false;
        
        $query = "UPDATE " . $this->table_name . "
                SET is_active = :is_active
                WHERE id = :id";
        
        try {
            $stmt = $this->conn->prepare($query);

            $activeValue = 0;
            $stmt->bindParam(":is_active", $activeValue, PDO::PARAM_INT);
            $stmt->bindParam(":id", $this->id);
            
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Ошибка деактивации подключения Telegram: " . $e->getMessage());
        }
        
        return false;
    }
}
?>