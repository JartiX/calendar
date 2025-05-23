<?php
/**
 * Класс для работы с базой данных
 * 
 * Управляет подключением к базе данных и предоставляет соединение
 */

class Database {
    // Параметры подключения
    private $host;
    private $db_name;
    private $username;
    private $password;
    private $conn;
    private static $instance = null;

    public function __construct() {
        $this->host = getenv('DB_HOST') ?: 'db';
        $this->db_name = getenv('DB_NAME') ?: 'task_calendar';
        $this->username = getenv('DB_USER') ?: 'task_user';
        $this->password = getenv('DB_PASSWORD') ?: 'task_password';
    }
    
    /**
     * Получает соединение с базой данных
     * 
     * @return PDO Объект соединения с базой данных
     */
    public function getConnection() {
        if ($this->conn === null) {
            try {
                $this->conn = new PDO(
                    'mysql:host=' . $this->host . ';dbname=' . $this->db_name . ';charset=utf8mb4',
                    $this->username,
                    $this->password,
                    [
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                        PDO::ATTR_EMULATE_PREPARES => false
                    ]
                );
            } catch(PDOException $exception) {
                // Записываем ошибку в журнал
                error_log('Ошибка подключения к БД: ' . $exception->getMessage());
                
                // Более безопасное сообщение пользователю
                echo 'Произошла ошибка при подключении к базе данных. Пожалуйста, свяжитесь с администратором.';
                exit;
            }
        }

        return $this->conn;
    }
    
    /**
     * Получает экземпляр класса Database (реализация Singleton)
     * 
     * @return Database Экземпляр класса Database
     */
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        
        return self::$instance;
    }
    
    /**
     * Закрывает соединение с базой данных
     */
    public function closeConnection() {
        $this->conn = null;
    }
}