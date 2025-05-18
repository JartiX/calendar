<?php
/**
 * Представление страницы входа
 * 
 * Отображает форму входа в систему
 */

// Загружаем настройки сессии
require_once __DIR__ . '/../../config/settings.php';


// Запускаем сессию, если она еще не запущена
if (session_status() == PHP_SESSION_NONE) {
    // Настраиваем имя сессии перед ее запуском
    session_name(SESSION_NAME);
    session_start();
}

$pageTitle = 'Вход в систему';

$extraStyles = '
<style>
    body {
        padding-top: 40px;
        padding-bottom: 40px;
        background-color: #f5f5f5;
    }
    .form-signin {
        max-width: 330px;
        padding: 15px;
        margin: 0 auto;
    }
    .form-signin .form-floating:focus-within {
        z-index: 2;
    }
    .form-signin input[type="text"] {
        margin-bottom: -1px;
        border-bottom-right-radius: 0;
        border-bottom-left-radius: 0;
    }
    .form-signin input[type="password"] {
        margin-bottom: 10px;
        border-top-left-radius: 0;
        border-top-right-radius: 0;
    }
</style>
';
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Страница входа в приложение Мой календарь">
    <title><?php echo $pageTitle; ?> - Мой календарь</title>
    
    <!-- Подключение стилей -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.1.3/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="assets/css/main.css">
    <?php echo $extraStyles; ?>
    
    <!-- Favicon -->
    <link rel="shortcut icon" href="assets/images/favicon.ico" type="image/x-icon">
</head>
<body class="text-center">
    <main class="form-signin">
        <form action="index.php?action=authenticate" method="POST">
            <h1 class="h3 mb-3 fw-normal">Мой календарь</h1>
            <h2 class="h5 mb-3 fw-normal">Вход в систему</h2>
            
            <?php if(isset($_SESSION['error'])): ?>
            <div class="alert alert-danger">
                <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
            </div>
            <?php endif; ?>
            
            <?php if(isset($_SESSION['success'])): ?>
            <div class="alert alert-success">
                <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
            </div>
            <?php endif; ?>
            
            <div class="form-floating">
                <input type="text" class="form-control" id="username" name="username" placeholder="Имя пользователя" required>
                <label for="username">Имя пользователя</label>
            </div>
            <div class="form-floating">
                <input type="password" class="form-control" id="password" name="password" placeholder="Пароль" required>
                <label for="password">Пароль</label>
            </div>
            
            <button class="w-100 btn btn-lg btn-primary" type="submit">Войти</button>
            <div class="mt-3">
                <a href="index.php?action=register">Зарегистрироваться</a>
            </div>
            <p class="mt-5 mb-3 text-muted">&copy; 2025 Пилявин Артём. Все права защищены.</p>
        </form>
    </main>
    
    <!-- Подключение скриптов -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.1.3/js/bootstrap.bundle.min.js"></script>
    <script src="assets/scripts/notifications.js"></script>
</body>
</html>