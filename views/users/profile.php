<?php
/**
 * Представление страницы профиля пользователя
 * 
 * Отображает информацию о пользователе и формы для её редактирования
 */

// Подключаем шапку сайта
include_once 'views/layouts/header.php';

// Убедимся, что у нас есть соединение с БД
$database = new Database();
$db = $database->getConnection();

// Запрашиваем данные пользователя
$user = new User($db);
$user->id = $_SESSION['user_id'];
$user_data = $user->readOne();

// Загружаем содержимое профиля
include_once 'views/users/profile_content.php';

// Подключаем футер сайта
include_once 'views/layouts/footer.php';
?>