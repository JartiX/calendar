<?php
/**
 * Модель для работы с уведомлениями
 */
class Notification {
    private $conn;
    private $table_name = "notifications";
    
    public $id;
    public $task_id;
    public $notification_type;
    public $sent_at;
    
    public function __construct($db) {
        $this->conn = $db;
    }
    
    public function create() {
        if (empty($this->task_id) || empty($this->notification_type)) {
            return false;
        }
        
        $query = "INSERT INTO " . $this->table_name . " 
                SET task_id = :task_id, 
                    notification_type = :notification_type, 
                    sent_at = :sent_at";
        
        try {
            $stmt = $this->conn->prepare($query);
            
            $stmt->bindParam(":task_id", $this->task_id);
            $stmt->bindParam(":notification_type", $this->notification_type);
            
            if (empty($this->sent_at)) {
                $this->sent_at = date('Y-m-d H:i:s');
            }
            $stmt->bindParam(":sent_at", $this->sent_at);
            
            if($stmt->execute()) {
                $this->id = $this->conn->lastInsertId();
                return true;
            }
        } catch (PDOException $e) {
            error_log("Ошибка создания уведомления: " . $e->getMessage());
        }
        
        return false;
    }
}
?>