<?php
/**
 * Главный входной файл приложения
 * 
 * Инициализирует необходимые настройки и запускает маршрутизатор
 */

// Подключаем файл с настройками
require_once 'config/settings.php';

// Запускаем маршрутизатор
require_once 'router.php';