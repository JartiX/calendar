<?php
/**
 * Контроллер для работы с пользователями
 * 
 * Управляет регистрацией, авторизацией и управлением аккаунтом
 */
class UserController {
    private $user;
    private $db;
    
    /**
     * Конструктор класса
     * 
     * @param PDO $db Соединение с базой данных
     */
    public function __construct($db) {
        $this->db = $db;
        $this->user = new User($db);
    }
    
    /**
     * Отображение формы регистрации
     */
    public function register() {
        if (isset($_SESSION['user_id'])) {
            // Пользователь уже авторизован, перенаправляем на главную
            header('Location: index.php?action=index');
            exit;
        }

        include_once 'views/users/register.php';
    }
    
    /**
     * Обработка формы регистрации
     */
    public function store() {
        if (isset($_SESSION['user_id'])) {
            // Пользователь уже авторизован, перенаправляем на главную
            header('Location: index.php?action=index');
            exit;
        }

        if($_SERVER['REQUEST_METHOD'] == 'POST') {
            // Валидация данных
            if(empty($_POST['username']) || empty($_POST['password']) || empty($_POST['email'])) {
                $_SESSION['error'] = "Все поля обязательны для заполнения";
                include_once 'views/users/register.php';
                return;
            }
            
            // Проверка минимальной длины пароля
            if(strlen($_POST['password']) < PASSWORD_MIN_LENGTH) {
                $_SESSION['error'] = "Пароль должен содержать не менее " . PASSWORD_MIN_LENGTH . " символов";
                include_once 'views/users/register.php';
                return;
            }
            
            // Проверка паролей
            if($_POST['password'] !== $_POST['confirm_password']) {
                $_SESSION['error'] = "Пароли не совпадают";
                include_once 'views/users/register.php';
                return;
            }
            
            // Установка значений
            $this->user->username = htmlspecialchars(strip_tags($_POST['username']));
            $this->user->password = $_POST['password'];
            $this->user->email = htmlspecialchars(strip_tags($_POST['email']));
            
            // Создание пользователя
            if($this->user->create()) {                
                // Создание сессии для нового пользователя
                $_SESSION['user_id'] = $this->user->id;
                $_SESSION['username'] = $this->user->username;
                $_SESSION['last_activity'] = time();
                
                // Создаем уведомление об успешной регистрации
                header('Location: index.php?action=index&notification=registration_success');
                exit;
            } else {
                $_SESSION['error'] = "Произошла ошибка при регистрации или пользователь с таким именем/email уже существует";
                include_once 'views/users/register.php';
            }
        }
    }
    
    /**
     * Отображение формы входа
     */
    public function login() {
        if (isset($_SESSION['user_id'])) {
            // Пользователь уже авторизован, перенаправляем на главную
            header('Location: index.php?action=index');
            exit;
        }
        
        include_once 'views/users/login.php';
    }
    
    /**
     * Аутентификация пользователя
     */
    public function authenticate() {
        if (isset($_SESSION['user_id'])) {
            // Пользователь уже авторизован, перенаправляем на главную
            header('Location: index.php?action=index');
            exit;
        }

        if($_SERVER['REQUEST_METHOD'] == 'POST') {
            // Валидация данных
            if(empty($_POST['username']) || empty($_POST['password'])) {
                $_SESSION['error'] = "Введите имя пользователя и пароль";
                include_once 'views/users/login.php';
                return;
            }
            
            // Установка значений
            $this->user->username = htmlspecialchars(strip_tags($_POST['username']));
            $this->user->password = $_POST['password'];
            
            // Авторизация
            if($this->user->login()) {
                // Создание сессии
                $_SESSION['user_id'] = $this->user->id;
                $_SESSION['username'] = $this->user->username;
                $_SESSION['last_activity'] = time(); // Добавление времени последней активности
                
                // Устанавливаем флаг для проверки просроченных задач при загрузке страницы
                $_SESSION['check_overdue'] = true;
                
                // Перенаправление на сохраненный URL или на главную
                if(isset($_SESSION['redirect_after_login']) && !empty($_SESSION['redirect_after_login'])) {
                    // Для отладки
                    error_log("Перенаправление после логина на: " . $_SESSION['redirect_after_login']);
                    
                    $redirect = $_SESSION['redirect_after_login'];
                    unset($_SESSION['redirect_after_login']);
                    
                    // Убедимся, что мы не перенаправляем на страницу логина снова
                    if(strpos($redirect, 'action=login') !== false || 
                       strpos($redirect, 'action=authenticate') !== false || 
                       strpos($redirect, 'action=register') !== false) {
                        $redirect = 'index.php?action=index';
                    }
                    
                    header("Location: $redirect");
                } else {
                    // Для отладки
                    error_log("Перенаправление на главную страницу");
                    
                    header('Location: index.php?action=index');
                }
                exit;
            } else {
                $_SESSION['error'] = "Неверное имя пользователя или пароль";
                include_once 'views/users/login.php';
            }
        } else {
            // Если запрос не POST, перенаправляем на форму входа
            header('Location: index.php?action=login');
            exit;
        }
    }
    
    /**
     * Выход из системы
     */
    public function logout() {
        // Запускаем сессию, если она не активна
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
        
        // Сохраняем сообщение перед уничтожением сессии
        $message = "Вы успешно вышли из системы";

        // Удаление всех данных сессии
        $_SESSION = array();
        
        // Уничтожаем куки сессии при наличии
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }

        // Удаление сессии
        session_destroy();
        
        // Запускаем новую сессию для сообщения
        session_start();
        
        // Устанавливаем сообщение об успешном выходе
        $_SESSION['success'] = $message;
        
        // Перенаправляем на страницу входа
        header('Location: index.php?action=login');
        exit;
    }
    
    /**
     * Отображение профиля пользователя
     */
    public function profile() {
        // Получаем информацию о пользователе
        $this->user->id = $_SESSION['user_id'];
        $user_data = $this->user->readOne();
        
        if($user_data) {
            // Устанавливаем заголовок страницы
            $pageTitle = 'Мой профиль';
            
            // Проверяем, запрошен ли JSON-формат (для AJAX-запросов)
            if (isset($_GET['format']) && $_GET['format'] === 'json') {
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => true,
                    'user' => [
                        'id' => $user_data['id'],
                        'username' => $user_data['username'],
                        'email' => $user_data['email'],
                        'created_at' => $user_data['created_at']
                    ]
                ]);
                exit;
            }
            
            // Загрузка представления
            include_once 'views/users/profile.php';
        } else {
            // Проверяем, запрошен ли JSON-формат (для AJAX-запросов)
            if (isset($_GET['format']) && $_GET['format'] === 'json') {
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => false,
                    'message' => 'Невозможно загрузить информацию о профиле'
                ]);
                exit;
            }
            
            $_SESSION['error'] = "Невозможно загрузить информацию о профиле";
            header('Location: index.php?action=index');
            exit;
        }
    }
    
    /**
     * Обновление профиля пользователя
     */
    public function updateProfile() {
        if($_SERVER['REQUEST_METHOD'] == 'POST') {
            // Установка ID пользователя
            $this->user->id = $_SESSION['user_id'];
            
            // Валидация данных
            if(empty($_POST['username']) || empty($_POST['email'])) {
                // Проверяем, является ли запрос AJAX
                if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
                    header('Content-Type: application/json');
                    echo json_encode([
                        'success' => false,
                        'message' => 'Имя пользователя и Email обязательны для заполнения'
                    ]);
                    exit;
                } else {
                    $_SESSION['error'] = "Имя пользователя и Email обязательны для заполнения";
                    header('Location: index.php?action=profile');
                    exit;
                }
            }
            
            // Установка значений
            $this->user->username = htmlspecialchars(strip_tags($_POST['username']));
            $this->user->email = htmlspecialchars(strip_tags($_POST['email']));
            
            // Обновление профиля
            if($this->user->update()) {
                // Обновляем имя пользователя в сессии
                $_SESSION['username'] = $this->user->username;
                
                // Проверяем, является ли запрос AJAX
                if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
                    header('Content-Type: application/json');
                    echo json_encode([
                        'success' => true,
                        'message' => 'Профиль успешно обновлен',
                        'redirect_to' => isset($_POST['redirect_to']) && $_POST['redirect_to'] === 'index' 
                            ? 'index.php?tab=profile' 
                            : 'index.php?action=profile'
                    ]);
                    exit;
                } else {
                    $_SESSION['success'] = "Профиль успешно обновлен";
                    
                    // Определяем, куда перенаправить пользователя
                    $redirect_to = isset($_POST['redirect_to']) && $_POST['redirect_to'] === 'index' 
                        ? 'index.php?tab=profile' // Перенаправление на главную с активной вкладкой профиля
                        : 'index.php?action=profile';
                    
                    header("Location: $redirect_to");
                    exit;
                }
            } else {
                if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
                    header('Content-Type: application/json');
                    echo json_encode([
                        'success' => false,
                        'message' => 'Не удалось обновить профиль'
                    ]);
                    exit;
                } else {
                    $_SESSION['error'] = "Не удалось обновить профиль";
                    header('Location: index.php?action=profile');
                    exit;
                }
            }
        } else {
            header('Location: index.php?action=profile');
            exit;
        }
    }

    /**
     * Изменение пароля пользователя
     */
    public function changePassword() {
        if($_SERVER['REQUEST_METHOD'] == 'POST') {
            // Установка ID пользователя
            $this->user->id = $_SESSION['user_id'];
            
            // Валидация данных
            if(empty($_POST['current_password']) || empty($_POST['new_password']) || empty($_POST['confirm_password'])) {
                if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
                    header('Content-Type: application/json');
                    echo json_encode([
                        'success' => false,
                        'message' => 'Все поля обязательны для заполнения'
                    ]);
                    exit;
                } else {
                    $_SESSION['error'] = "Все поля обязательны для заполнения";
                    header('Location: index.php?action=profile');
                    exit;
                }
            }
            
            // Проверка минимальной длины пароля
            if(strlen($_POST['new_password']) < PASSWORD_MIN_LENGTH) {
                if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
                    header('Content-Type: application/json');
                    echo json_encode([
                        'success' => false,
                        'message' => 'Новый пароль должен содержать не менее ' . PASSWORD_MIN_LENGTH . ' символов'
                    ]);
                    exit;
                } else {
                    $_SESSION['error'] = "Новый пароль должен содержать не менее " . PASSWORD_MIN_LENGTH . " символов";
                    header('Location: index.php?action=profile');
                    exit;
                }
            }
            
            // Проверка совпадения новых паролей
            if($_POST['new_password'] !== $_POST['confirm_password']) {
                if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
                    header('Content-Type: application/json');
                    echo json_encode([
                        'success' => false,
                        'message' => 'Новые пароли не совпадают'
                    ]);
                    exit;
                } else {
                    $_SESSION['error'] = "Новые пароли не совпадают";
                    header('Location: index.php?action=profile');
                    exit;
                }
            }
            
            // Изменение пароля
            if($this->user->changePassword($_POST['current_password'], $_POST['new_password'])) {
                if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
                    header('Content-Type: application/json');
                    echo json_encode([
                        'success' => true,
                        'message' => 'Пароль успешно изменен',
                        'redirect_to' => isset($_POST['redirect_to']) && $_POST['redirect_to'] === 'index' 
                            ? 'index.php?tab=profile' 
                            : 'index.php?action=profile'
                    ]);
                    exit;
                } else {
                    $_SESSION['success'] = "Пароль успешно изменен";
                    
                    // Определяем, куда перенаправить пользователя
                    $redirect_to = isset($_POST['redirect_to']) && $_POST['redirect_to'] === 'index' 
                        ? 'index.php?tab=profile' // Перенаправление на главную с активной вкладкой профиля 
                        : 'index.php?action=profile';
                    
                    header("Location: $redirect_to");
                    exit;
                }
            } else {
                if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
                    header('Content-Type: application/json');
                    echo json_encode([
                        'success' => false,
                        'message' => 'Не удалось изменить пароль. Проверьте правильность текущего пароля'
                    ]);
                    exit;
                } else {
                    $_SESSION['error'] = "Не удалось изменить пароль. Проверьте правильность текущего пароля";
                    header('Location: index.php?action=profile');
                    exit;
                }
            }
        } else {
            header('Location: index.php?action=profile');
            exit;
        }
    }
}