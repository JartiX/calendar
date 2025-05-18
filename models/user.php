<?php
/**
 * Модель для работы с пользователями
 * 
 * Предоставляет методы для регистрации, авторизации и управления пользователями
 */
class User {
    // Подключение к базе данных и название таблицы
    private $conn;
    private $table_name = "users";
    
    // Свойства объекта
    public $id;
    public $username;
    public $password;
    public $email;
    public $created_at;
    
    // Константы для проверки паролей
    const MIN_PASSWORD_LENGTH = 8;
    
    /**
     * Конструктор класса
     * 
     * @param PDO $db Соединение с базой данных
     */
    public function __construct($db) {
        $this->conn = $db;
    }
    
    /**
     * Регистрация нового пользователя
     * 
     * @return bool Результат операции
     */
    public function create() {
        // Проверка входных данных
        if (empty($this->username) || empty($this->password) || empty($this->email)) {
            return false;
        }
        
        // Проверка email
        if (!filter_var($this->email, FILTER_VALIDATE_EMAIL)) {
            return false;
        }
        
        // Проверка длины пароля
        if (strlen($this->password) < self::MIN_PASSWORD_LENGTH) {
            return false;
        }
        
        // Проверка, существует ли пользователь с таким именем или email
        if ($this->usernameExists() || $this->emailExists()) {
            return false;
        }
        
        // SQL-запрос для создания нового пользователя
        $query = "INSERT INTO " . $this->table_name . " 
                SET username = :username, 
                    password = :password, 
                    email = :email";
        
        try {
            // Подготовка запроса
            $stmt = $this->conn->prepare($query);
            
            // Очистка данных
            $this->username = htmlspecialchars(strip_tags($this->username));
            $this->email = htmlspecialchars(strip_tags($this->email));
            
            // Хеширование пароля
            $password_hash = password_hash($this->password, PASSWORD_BCRYPT, ['cost' => 12]);
            
            // Привязка значений
            $stmt->bindParam(":username", $this->username);
            $stmt->bindParam(":password", $password_hash);
            $stmt->bindParam(":email", $this->email);
            
            // Выполнение запроса
            if($stmt->execute()) {
                // Получаем ID вставленной записи
                $this->id = $this->conn->lastInsertId();
                return true;
            }
        } catch (PDOException $e) {
            // Записываем ошибку в лог
            error_log("Ошибка создания пользователя: " . $e->getMessage());
        }
        
        return false;
    }
    
    /**
     * Авторизация пользователя
     * 
     * @return bool Результат операции
     */
    public function login() {
        // Проверка входных данных
        if (empty($this->username) || empty($this->password)) {
            return false;
        }
        
        // SQL-запрос для получения данных пользователя
        $query = "SELECT id, username, password FROM " . $this->table_name . " 
                WHERE username = :username LIMIT 1";
        
        try {
            // Подготовка запроса
            $stmt = $this->conn->prepare($query);
            
            // Очистка данных
            $this->username = htmlspecialchars(strip_tags($this->username));
            
            // Привязка значений
            $stmt->bindParam(":username", $this->username);
            
            // Выполнение запроса
            $stmt->execute();
            
            // Если найден пользователь с таким именем
            if($stmt->rowCount() > 0) {
                $row = $stmt->fetch(PDO::FETCH_ASSOC);
                
                // Проверка пароля
                if(password_verify($this->password, $row['password'])) {
                    // Установка ID пользователя
                    $this->id = $row['id'];
                    
                    // Обновляем хеш пароля, если нужно
                    $this->updatePasswordHashIfNeeded($row['password']);
                    
                    // Записываем в лог успешный вход
                    error_log("Успешный вход пользователя: {$this->username} (ID: {$this->id})");
                    
                    return true;
                }
                
                // Записываем в лог неудачную попытку входа
                error_log("Неверный пароль для пользователя: {$this->username}");
            } else {
                // Записываем в лог неудачную попытку входа
                error_log("Пользователь не найден: {$this->username}");
            }
        } catch (PDOException $e) {
            // Записываем ошибку в лог
            error_log("Ошибка входа: " . $e->getMessage());
        }
        
        return false;
    }
    
    /**
     * Проверяет, существует ли пользователь с таким именем
     * 
     * @param string|null $username Имя пользователя для проверки (если null, используется текущее)
     * @return bool Результат проверки
     */
    private function usernameExists($username = null) {
        // Используем переданное имя или текущее
        $username_to_check = $username !== null ? $username : $this->username;
        
        // SQL-запрос для проверки
        $query = "SELECT id FROM " . $this->table_name . " WHERE username = :username LIMIT 1";
        
        try {
            // Подготовка запроса
            $stmt = $this->conn->prepare($query);
            
            // Очистка данных
            $username_to_check = htmlspecialchars(strip_tags($username_to_check));
            
            // Привязка значений
            $stmt->bindParam(":username", $username_to_check);
            
            // Выполнение запроса
            $stmt->execute();
            
            // Возвращаем true, если найден пользователь
            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            // Записываем ошибку в лог
            error_log("Ошибка проверки имени пользователя: " . $e->getMessage());
            
            // Предполагаем, что пользователь существует, чтобы избежать дубликатов
            return true;
        }
    }
    
    /**
     * Проверяет, существует ли пользователь с таким email
     * 
     * @param string|null $email Email для проверки (если null, используется текущий)
     * @return bool Результат проверки
     */
    private function emailExists($email = null) {
        // Используем переданный email или текущий
        $email_to_check = $email !== null ? $email : $this->email;
        
        // SQL-запрос для проверки
        $query = "SELECT id FROM " . $this->table_name . " WHERE email = :email LIMIT 1";
        
        try {
            // Подготовка запроса
            $stmt = $this->conn->prepare($query);
            
            // Очистка данных
            $email_to_check = htmlspecialchars(strip_tags($email_to_check));
            
            // Привязка значений
            $stmt->bindParam(":email", $email_to_check);
            
            // Выполнение запроса
            $stmt->execute();
            
            // Возвращаем true, если найден пользователь
            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            // Записываем ошибку в лог
            error_log("Ошибка проверки email: " . $e->getMessage());
            
            // Предполагаем, что пользователь существует, чтобы избежать дубликатов
            return true;
        }
    }
    
    /**
     * Обновляет хеш пароля, если он использует устаревший алгоритм
     * 
     * @param string $currentHash Текущий хеш пароля
     */
    private function updatePasswordHashIfNeeded($currentHash) {
        // Проверяем, нужно ли обновлять хеш
        if (password_needs_rehash($currentHash, PASSWORD_BCRYPT, ['cost' => 12])) {
            // Создаем новый хеш
            $newHash = password_hash($this->password, PASSWORD_BCRYPT, ['cost' => 12]);
            
            // SQL-запрос для обновления хеша
            $query = "UPDATE " . $this->table_name . " SET password = :password WHERE id = :id";
            
            try {
                // Подготовка запроса
                $stmt = $this->conn->prepare($query);
                
                // Привязка значений
                $stmt->bindParam(":password", $newHash);
                $stmt->bindParam(":id", $this->id, PDO::PARAM_INT);
                
                // Выполнение запроса
                $stmt->execute();
                
                // Записываем в лог обновление хеша
                error_log("Обновлен хеш пароля для пользователя ID: {$this->id}");
            } catch (PDOException $e) {
                // Записываем ошибку в лог
                error_log("Ошибка обновления хеша пароля: " . $e->getMessage());
            }
        }
    }
    
    /**
     * Получает данные пользователя по ID
     * 
     * @return array|null Данные пользователя или null, если пользователь не найден
     */
    public function readOne() {
        // Проверка ID
        if (empty($this->id) || !is_numeric($this->id)) {
            return null;
        }
        
        // SQL-запрос для получения данных пользователя
        $query = "SELECT id, username, email, created_at FROM " . $this->table_name . " 
                WHERE id = :id LIMIT 1";
        
        try {
            // Подготовка запроса
            $stmt = $this->conn->prepare($query);
            
            // Привязка ID пользователя
            $stmt->bindParam(":id", $this->id, PDO::PARAM_INT);
            
            // Выполнение запроса
            $stmt->execute();
            
            // Получение данных
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Если пользователь найден, заполняем свойства объекта
            if($row) {
                $this->username = $row['username'];
                $this->email = $row['email'];
                $this->created_at = $row['created_at'];
                
                return $row;
            }
        } catch (PDOException $e) {
            // Записываем ошибку в лог
            error_log("Ошибка получения данных пользователя: " . $e->getMessage());
        }
        
        return null;
    }
    
    /**
     * Обновляет данные пользователя
     * 
     * @return bool Результат операции
     */
    public function update() {
        // Проверка ID
        if (empty($this->id) || !is_numeric($this->id)) {
            return false;
        }
        
        // SQL-запрос для обновления данных пользователя
        $query = "UPDATE " . $this->table_name . " 
                SET username = :username, 
                    email = :email";
        
        // Если указан новый пароль, добавляем его в запрос
        if (!empty($this->password)) {
            $query .= ", password = :password";
        }
        
        $query .= " WHERE id = :id";
        
        try {
            // Подготовка запроса
            $stmt = $this->conn->prepare($query);
            
            // Очистка данных
            $this->username = htmlspecialchars(strip_tags($this->username));
            $this->email = htmlspecialchars(strip_tags($this->email));
            
            // Привязка значений
            $stmt->bindParam(":username", $this->username);
            $stmt->bindParam(":email", $this->email);
            $stmt->bindParam(":id", $this->id, PDO::PARAM_INT);
            
            // Если указан новый пароль, хешируем его и добавляем в привязки
            if (!empty($this->password)) {
                $password_hash = password_hash($this->password, PASSWORD_BCRYPT, ['cost' => 12]);
                $stmt->bindParam(":password", $password_hash);
            }
            
            // Выполнение запроса
            return $stmt->execute();
        } catch (PDOException $e) {
            // Записываем ошибку в лог
            error_log("Ошибка обновления данных пользователя: " . $e->getMessage());
            
            return false;
        }
    }
    
    /**
     * Изменяет пароль пользователя
     * 
     * @param string $currentPassword Текущий пароль
     * @param string $newPassword Новый пароль
     * @return bool Результат операции
     */
    public function changePassword($currentPassword, $newPassword) {
        // Проверка ID
        if (empty($this->id) || !is_numeric($this->id)) {
            return false;
        }
        
        // Проверка длины нового пароля
        if (strlen($newPassword) < self::MIN_PASSWORD_LENGTH) {
            return false;
        }
        
        // SQL-запрос для получения текущего хеша пароля
        $query = "SELECT password FROM " . $this->table_name . " WHERE id = :id LIMIT 1";
        
        try {
            // Подготовка запроса
            $stmt = $this->conn->prepare($query);
            
            // Привязка ID пользователя
            $stmt->bindParam(":id", $this->id, PDO::PARAM_INT);
            
            // Выполнение запроса
            $stmt->execute();
            
            // Получение данных
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Проверка текущего пароля
            if ($row && password_verify($currentPassword, $row['password'])) {
                // Хеширование нового пароля
                $newPasswordHash = password_hash($newPassword, PASSWORD_BCRYPT, ['cost' => 12]);
                
                // SQL-запрос для обновления пароля
                $updateQuery = "UPDATE " . $this->table_name . " SET password = :password WHERE id = :id";
                
                // Подготовка запроса
                $updateStmt = $this->conn->prepare($updateQuery);
                
                // Привязка значений
                $updateStmt->bindParam(":password", $newPasswordHash);
                $updateStmt->bindParam(":id", $this->id, PDO::PARAM_INT);
                
                // Выполнение запроса
                return $updateStmt->execute();
            }
        } catch (PDOException $e) {
            // Записываем ошибку в лог
            error_log("Ошибка изменения пароля: " . $e->getMessage());
        }
        
        return false;
    }
}