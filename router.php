<?php
/**
 * Маршрутизатор приложения
 * 
 * Обрабатывает все входящие запросы и направляет их к соответствующим контроллерам
 */

require_once 'session_start.php';


require_once 'config/database.php';
require_once 'models/task.php';
require_once 'models/user.php';
require_once 'controllers/task_controller.php';
require_once 'controllers/user_controller.php';
require_once 'helpers/auth_helper.php';

// Создание соединения с базой данных
$database = new Database();
$db = $database->getConnection();

// Создание контроллеров
$taskController = new TaskController($db);
$userController = new UserController($db);

// Получение действия из запроса
$action = isset($_GET['action']) ? $_GET['action'] : 'index';

// Список действий, которые доступны без авторизации
$publicActions = ['login', 'register', 'authenticate', 'store'];

// Обработка маршрутов, которые не требуют авторизации
if (in_array($action, $publicActions)) {
    switch($action) {
        case 'login':
            $userController->login();
            break;
            
        case 'authenticate':
            $userController->authenticate();
            break;
            
        case 'register':
            $userController->register();
            break;
            
        case 'store':
            // Проверяем, для чего используется store (для регистрации или для задач)
            if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['username']) && !isset($_SESSION['user_id'])) {
                $userController->store();
            } else if (isset($_SESSION['user_id'])) {
                $taskController->store();
            } else {
                header('Location: index.php?action=login');
                exit;
            }
            break;
            
        default:
            // Если запрошенное действие не публичное, перенаправляем на страницу входа
            header('Location: index.php?action=login');
            exit;
    }
    exit; // Завершаем выполнение после обработки публичных маршрутов
}

// Проверка авторизации для остальных действий
if (!AuthHelper::isLoggedIn()) {
    // Сохраняем текущий URL для перенаправления после логина
    $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
    
    // Отладочная информация
    error_log("Сохранен URL для перенаправления: " . $_SERVER['REQUEST_URI']);
    
    // Перенаправляем на страницу входа
    header('Location: index.php?action=login');
    exit;
}

// Проверка тайм-аута сессии
if (!AuthHelper::checkSessionTimeout(SESSION_TIMEOUT)) {
    $_SESSION['error'] = 'Время сессии истекло. Пожалуйста, войдите снова.';
    header('Location: index.php?action=login');
    exit;
}

// Маршрутизация для авторизованных пользователей
switch($action) {
    case 'index':
        $taskController->index();
        break;
        
    case 'create':
        $taskController->create();
        break;
        
    case 'show':
        if(isset($_GET['id'])) {
            $taskController->show($_GET['id']);
        } else {
            $taskController->index();
        }
        break;
        
    case 'edit':
        if(isset($_GET['id'])) {
            $taskController->edit($_GET['id']);
        } else {
            $taskController->index();
        }
        break;
        
    case 'update':
        if(isset($_GET['id'])) {
            $taskController->update($_GET['id']);
        } else {
            $taskController->index();
        }
        break;
        
    case 'delete':
        if(isset($_GET['id'])) {
            $taskController->delete($_GET['id']);
        } else {
            $taskController->index();
        }
        break;
        
    case 'active':
        $taskController->active();
        break;
        
    case 'overdue':
        $taskController->overdue();
        break;
        
    case 'completed':
        $taskController->completed();
        break;
        
    case 'byDate':
        $taskController->byDate();
        break;
        
    case 'search':
        $taskController->search();
        break;

    case 'combinedSearch':
        $taskController->combinedSearch();
        break;
        
    case 'profile':
        $userController->profile();
        break;
        
    case 'updateProfile':
        $userController->updateProfile();
        break;
        
    case 'changePassword':
        $userController->changePassword();
        break;
        
    case 'logout':
        $userController->logout();
        break;
        
    default:
        $taskController->index();
        break;
}

// Закрываем соединение с базой данных
$database->closeConnection();