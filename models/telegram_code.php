<?php
/**
 * Модель для работы с кодами подключения Telegram
 */
class TelegramCode {
    private $conn;
    private $table_name = "telegram_connection_codes";
    
    public $id;
    public $user_id;
    public $connection_code;
    public $expires_at;
    public $created_at;
    
    public function __construct($db) {
        $this->conn = $db;
    }
    
    public function create() {
        $this->deleteByUserId($this->user_id);
        
        $query = "INSERT INTO " . $this->table_name . " 
                SET user_id = :user_id, 
                    connection_code = :connection_code, 
                    expires_at = :expires_at";
        
        try {
            $stmt = $this->conn->prepare($query);
            
            $stmt->bindParam(":user_id", $this->user_id);
            $stmt->bindParam(":connection_code", $this->connection_code);
            $stmt->bindParam(":expires_at", $this->expires_at);
            
            if($stmt->execute()) {
                $this->id = $this->conn->lastInsertId();
                return true;
            }
        } catch (PDOException $e) {
            error_log("Ошибка создания кода подключения Telegram: " . $e->getMessage());
        }
        
        return false;
    }
    
    public function findValidCode($code) {
        $query = "SELECT * FROM " . $this->table_name . " 
                WHERE connection_code = :code 
                AND expires_at > NOW()";
        
        try {
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":code", $code);
            $stmt->execute();
            
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if($row) {
                $this->id = $row['id'];
                $this->user_id = $row['user_id'];
                $this->connection_code = $row['connection_code'];
                $this->expires_at = $row['expires_at'];
                $this->created_at = $row['created_at'];
                
                return true;
            }
        } catch (PDOException $e) {
            error_log("Ошибка поиска кода подключения: " . $e->getMessage());
        }
        
        return false;
    }
    
    public function deleteByUserId($userId) {
        $query = "DELETE FROM " . $this->table_name . " WHERE user_id = :user_id";
        
        try {
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":user_id", $userId);
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Ошибка удаления кода подключения: " . $e->getMessage());
            return false;
        }
    }
    
    public function deleteCode() {
        if (!$this->id) {
            return false;
        }
        
        $query = "DELETE FROM " . $this->table_name . " WHERE id = :id";
        
        try {
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":id", $this->id);
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Ошибка удаления кода подключения: " . $e->getMessage());
            return false;
        }
    }
}
?>