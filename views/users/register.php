<?php
/**
 * Представление страницы регистрации
 * 
 * Отображает форму регистрации нового пользователя
 */

// Загружаем настройки сессии
require_once __DIR__ . '/../../config/settings.php';


// Запускаем сессию, если она еще не запущена
if (session_status() == PHP_SESSION_NONE) {
    // Настраиваем имя сессии перед ее запуском
    session_name(SESSION_NAME);
    session_start();
}

$pageTitle = 'Регистрация';

$extraStyles = '
<style>
    body {
        padding-top: 40px;
        padding-bottom: 40px;
        background-color: #f5f5f5;
    }
    .form-signup {
        max-width: 330px;
        padding: 15px;
        margin: 0 auto;
    }
    .form-signup .form-floating:focus-within {
        z-index: 2;
    }
    .form-signup input {
        margin-bottom: 10px;
    }
</style>
';
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Страница регистрации в приложении Мой календарь">
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
    <main class="form-signup">
        <form action="index.php?action=store" method="POST" id="registerForm">
            <h1 class="h3 mb-3 fw-normal">Мой календарь</h1>
            <h2 class="h5 mb-3 fw-normal">Регистрация</h2>
            
            <?php if(isset($_SESSION['error'])): ?>
            <div class="alert alert-danger">
                <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
            </div>
            <?php endif; ?>
            
            <div class="form-floating">
                <input type="text" class="form-control" id="username" name="username" placeholder="Имя пользователя" required>
                <label for="username">Имя пользователя</label>
            </div>
            <div class="form-floating">
                <input type="email" class="form-control" id="email" name="email" placeholder="Email" required>
                <label for="email">Email</label>
            </div>
            <div class="form-floating">
                <input type="password" class="form-control" id="password" name="password" placeholder="Пароль" required minlength="8">
                <label for="password">Пароль</label>
                <div class="invalid-feedback" id="password-length-error">
                    Пароль должен содержать не менее 8 символов
                </div>
            </div>
            <div class="form-floating">
                <input type="password" class="form-control" id="confirm_password" name="confirm_password" placeholder="Подтверждение пароля" required>
                <label for="confirm_password">Подтверждение пароля</label>
                <div class="invalid-feedback" id="password-error">
                    Пароли не совпадают
                </div>
            </div>
            
            <button class="w-100 btn btn-lg btn-primary mt-3" type="submit">Зарегистрироваться</button>
            <div class="mt-3">
                <a href="index.php?action=login">Уже есть аккаунт? Войти</a>
            </div>
            <p class="mt-5 mb-3 text-muted">&copy; 2025 Пилявин Артём. Все права защищены.</p>
        </form>
    </main>
    
    <!-- Подключение скриптов -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.1.3/js/bootstrap.bundle.min.js"></script>
    <script src="assets/scripts/notifications.js"></script>

    <script>
        // Получение элементов формы
        const passwordField = document.getElementById('password');
        const confirmPasswordField = document.getElementById('confirm_password');
        const passwordLengthError = document.getElementById('password-length-error');
        const passwordError = document.getElementById('password-error');
        
        // Функция для проверки длины пароля
        function checkPasswordLength() {
            if (passwordField.value.length < 8) {
                passwordField.classList.add('is-invalid');
                passwordLengthError.style.display = 'block';
                return false;
            } else {
                passwordField.classList.remove('is-invalid');
                passwordLengthError.style.display = 'none';
                return true;
            }
        }
        
        // Функция для проверки совпадения паролей
        function checkPasswordsMatch() {
            if (passwordField.value !== confirmPasswordField.value) {
                confirmPasswordField.classList.add('is-invalid');
                passwordError.style.display = 'block';
                return false;
            } else {
                confirmPasswordField.classList.remove('is-invalid');
                passwordError.style.display = 'none';
                return true;
            }
        }
        
        // Проверка формы перед отправкой
        document.getElementById('registerForm').addEventListener('submit', function(e) {
            let isValid = true;
            
            // Проверка длины пароля
            if (!checkPasswordLength()) {
                isValid = false;
            }
            
            // Проверка совпадения паролей
            if (!checkPasswordsMatch()) {
                isValid = false;
            }
            
            // Предотвращаем отправку формы, если есть ошибки
            if (!isValid) {
                e.preventDefault();
            }
        });
        
        // Обработчик изменения пароля
        passwordField.addEventListener('input', function() {
            checkPasswordLength();
            
            // Если поле подтверждения не пустое, проверяем совпадение
            if (confirmPasswordField.value.length > 0) {
                checkPasswordsMatch();
            }
        });
        
        // Обработчик изменения подтверждения пароля
        confirmPasswordField.addEventListener('input', function() {
            checkPasswordsMatch();
        });
    </script>
</body>
</html>