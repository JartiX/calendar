<?php
/**
 * Представление страницы создания задачи
 * 
 * Отображает форму для создания новой задачи
 */

$pageTitle = 'Создание задачи';

require_once 'helpers/auth_helper.php';
require_once 'helpers/date_helper.php';

require_once 'models/telegram_user.php';
require_once 'controllers/telegram_controller.php';

// Подключаем шапку сайта
include_once 'views/layouts/header.php';

require_once 'controllers/telegram_controller.php';

if (!isset($db) || !$db) {
    $database = new Database();
    $db = $database->getConnection();
}

?>

<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <h3>Новая задача</h3>
            </div>
            <div class="card-body">
                <form action="index.php?action=store" method="POST">
                    <!-- Защита от CSRF -->
                    <?php echo AuthHelper::csrfField(); ?>
                    
                    <div class="row mb-3">
                        <div class="col-md-4">
                            <label for="title" class="form-label">Тема:</label>
                            <input type="text" class="form-control" id="title" name="title" required>
                        </div>
                        <div class="col-md-4">
                            <label for="type_id" class="form-label">Тип:</label>
                            <select class="form-select" id="type_id" name="type_id" required>
                                <?php while($row = $taskTypes->fetch(PDO::FETCH_ASSOC)): ?>
                                    <option value="<?php echo $row['id']; ?>"><?php echo htmlspecialchars($row['name']); ?></option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label for="location" class="form-label">Место:</label>
                            <input type="text" class="form-control" id="location" name="location">
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-4">
                            <label for="date" class="form-label">Дата:</label>
                            <input type="date" class="form-control" id="date" name="date" 
                                   value="<?php echo isset($_GET['date']) ? htmlspecialchars($_GET['date']) : date('Y-m-d'); ?>" required>
                        </div>
                        <div class="col-md-4">
                            <label for="time" class="form-label">Время:</label>
                            <input type="time" class="form-control" id="time" name="time" value="<?php echo date('H:i'); ?>" required>
                        </div>
                        <div class="col-md-4">
                            <label for="duration" class="form-label">Длительность:</label>
                            <div class="input-group">
                                <input type="number" class="form-control" id="duration" name="duration" min="1" value="1">
                                <select class="form-select" id="duration_unit_id" name="duration_unit_id">
                                    <?php while($row = $durationUnits->fetch(PDO::FETCH_ASSOC)): ?>
                                        <option value="<?php echo $row['id']; ?>"><?php echo htmlspecialchars($row['name']); ?></option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-4">
                            <label for="priority_id" class="form-label">Приоритет:</label>
                            <select class="form-select" id="priority_id" name="priority_id">
                                <option value="">Не указан</option>
                                <?php foreach($taskPriorities as $row): ?>
                                    <option value="<?php echo $row['id']; ?>" 
                                            style="background-color: <?php echo htmlspecialchars($row['color']); ?>">
                                        <?php echo htmlspecialchars($row['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="col-md-8">
                            <label for="comments" class="form-label">Комментарий:</label>
                            <textarea class="form-control" id="comments" name="comments" rows="3"></textarea>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-4">
                            <label for="notification_time" class="form-label">Уведомление:</label>
                            <select class="form-select" id="notification_time" name="notification_time">
                                <option value="">Без уведомления</option>
                                <option value="5" <?php echo (isset($task_data) && isset($task_data['notification_time']) && $task_data['notification_time'] == 5) ? 'selected' : ''; ?>>За 5 минут</option>
                                <option value="10" <?php echo (isset($task_data) && isset($task_data['notification_time']) && $task_data['notification_time'] == 10) ? 'selected' : ''; ?>>За 10 минут</option>
                                <option value="15" <?php echo (isset($task_data) && isset($task_data['notification_time']) && $task_data['notification_time'] == 15) ? 'selected' : ''; ?>>За 15 минут</option>
                                <option value="30" <?php echo (isset($task_data) && isset($task_data['notification_time']) && $task_data['notification_time'] == 30) ? 'selected' : ''; ?>>За 30 минут</option>
                                <option value="60" <?php echo (isset($task_data) && isset($task_data['notification_time']) && $task_data['notification_time'] == 60) ? 'selected' : ''; ?>>За 1 час</option>
                                <option value="120" <?php echo (isset($task_data) && isset($task_data['notification_time']) && $task_data['notification_time'] == 120) ? 'selected' : ''; ?>>За 2 часа</option>
                                <option value="180" <?php echo (isset($task_data) && isset($task_data['notification_time']) && $task_data['notification_time'] == 180) ? 'selected' : ''; ?>>За 3 часа</option>
                                <option value="360" <?php echo (isset($task_data) && isset($task_data['notification_time']) && $task_data['notification_time'] == 360) ? 'selected' : ''; ?>>За 6 часов</option>
                                <option value="720" <?php echo (isset($task_data) && isset($task_data['notification_time']) && $task_data['notification_time'] == 720) ? 'selected' : ''; ?>>За 12 часов</option>
                                <option value="1440" <?php echo (isset($task_data) && isset($task_data['notification_time']) && $task_data['notification_time'] == 1440) ? 'selected' : ''; ?>>За 1 день</option>
                            </select>
                            <?php 
                            // Проверяем подключение к Telegram
                            $telegramController = new TelegramController($db);
                            $isConnected = $telegramController->isConnected($_SESSION['user_id']);
                            
                            if (!$isConnected) {
                                echo '<div class="form-text text-warning">';
                                echo '<i class="fas fa-exclamation-triangle"></i> ';
                                echo 'Для получения уведомлений <a href="index.php?action=profile">подключите Telegram</a>.';
                                echo '</div>';
                            } else {
                                echo '<div class="form-text">';
                                echo '<i class="fab fa-telegram text-primary"></i> ';
                                echo 'Уведомления будут отправлены в Telegram.';
                                echo '</div>';
                            }
                            ?>
                        </div>
                    </div>

                    <div class="mb-3 d-flex justify-content-between">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-plus"></i> Добавить
                        </button>
                        <a href="index.php?action=index" class="btn btn-secondary">
                            <i class="fas fa-times"></i> Отмена
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php
// Подключаем футер сайта
include_once 'views/layouts/footer.php';
?>