<?php
/**
 * Шаблон верхней части страницы
 * 
 * Включает в себя заголовок HTML, мета-теги, стили и шапку сайта
 */

// Проверка наличия сессии
if (!session_id()) {
    session_start();
}

// Получаем текущее действие
$currentAction = isset($_GET['action']) ? $_GET['action'] : 'index';
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Приложение для управления задачами и событиями в календаре">
    <meta name="keywords" content="календарь, задачи, планирование, расписание">
    <meta name="author" content="Мой календарь">
    <title><?php echo isset($pageTitle) ? htmlspecialchars($pageTitle) . ' - Мой календарь' : 'Мой календарь'; ?></title>
    
    <!-- Подключение стилей -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.1.3/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="assets/css/main.css">
    <link rel="stylesheet" href="assets/css/calendar.css">
    <link rel="stylesheet" href="assets/css/responsive.css">
    
    <!-- Favicon -->
    <link rel="shortcut icon" href="assets/images/favicon.ico" type="image/x-icon">
</head>
<body>
    <div class="container">
        <!-- Шапка сайта -->
        <div class="header d-flex justify-content-between align-items-center">
            <h1>Мой календарь</h1>
            
            <?php if (isset($_SESSION['user_id'])): ?>
                <div class="btn-group">
                    <a href="index.php?action=index" class="btn btn-outline-primary <?php echo ($currentAction == 'index') ? 'active' : ''; ?>">Главная</a>
                    <a href="index.php?action=active" class="btn btn-outline-primary <?php echo ($currentAction == 'active') ? 'active' : ''; ?>">Текущие задачи</a>
                    <a href="index.php?action=overdue" class="btn btn-outline-primary <?php echo ($currentAction == 'overdue') ? 'active' : ''; ?>">Просроченные задачи</a>
                    <a href="index.php?action=completed" class="btn btn-outline-primary <?php echo ($currentAction == 'completed') ? 'active' : ''; ?>">Выполненные задачи</a>
                    <a href="index.php?action=logout" class="btn btn-outline-danger" title="Выйти">
                        <i class="fas fa-sign-out-alt"></i>
                        <span class="d-none d-md-inline">Выйти</span>
                    </a>
                </div>
            <?php endif; ?>
        </div>
        
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Закрыть"></button>
            </div>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Закрыть"></button>
            </div>
        <?php endif; ?>