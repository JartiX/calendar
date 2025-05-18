<?php
/**
 * Представление страницы редактирования задачи
 * 
 * Отображает форму для редактирования существующей задачи
 */

$pageTitle = 'Редактирование задачи: ' . $task_data['title'];

require_once 'helpers/auth_helper.php';
require_once 'helpers/date_helper.php';

// Подключаем шапку сайта
include_once 'views/layouts/header.php';
?>

<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h3>Редактирование задачи</h3>
                <a href="index.php?action=show&id=<?php echo $task_data['id']; ?>" class="btn btn-outline-primary">
                    <i class="fas fa-eye"></i> Просмотр задачи
                </a>
            </div>
            <div class="card-body">
                <form action="index.php?action=update&id=<?php echo $task_data['id']; ?>" method="POST">
                    <!-- Защита от CSRF -->
                    <?php echo AuthHelper::csrfField(); ?>
                    
                    <!-- Скрытое поле с URL возврата -->
                    <input type="hidden" name="return_url" value="<?php echo isset($_SERVER['HTTP_REFERER']) ? htmlspecialchars($_SERVER['HTTP_REFERER']) : ''; ?>">
    
                    <div class="row mb-3">
                        <div class="col-md-4">
                            <label for="title" class="form-label">Тема:</label>
                            <input type="text" class="form-control" id="title" name="title" 
                                   value="<?php echo htmlspecialchars($task_data['title']); ?>" required>
                        </div>
                        <div class="col-md-4">
                            <label for="type_id" class="form-label">Тип:</label>
                            <select class="form-select" id="type_id" name="type_id" required>
                                <?php while($row = $taskTypes->fetch(PDO::FETCH_ASSOC)): ?>
                                    <option value="<?php echo $row['id']; ?>" 
                                            <?php echo ($row['id'] == $task_data['type_id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($row['name']); ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label for="location" class="form-label">Место:</label>
                            <input type="text" class="form-control" id="location" name="location" 
                                   value="<?php echo htmlspecialchars($task_data['location']); ?>">
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-4">
                            <label for="date" class="form-label">Дата:</label>
                            <input type="date" class="form-control" id="date" name="date" 
                                   value="<?php echo DateHelper::formatDateForInput($task_data['scheduled_date']); ?>" required>
                        </div>
                        <div class="col-md-4">
                            <label for="time" class="form-label">Время:</label>
                            <input type="time" class="form-control" id="time" name="time" 
                                   value="<?php echo DateHelper::formatTimeForInput($task_data['scheduled_date']); ?>" required>
                        </div>
                        <div class="col-md-4">
                            <label for="duration" class="form-label">Длительность:</label>
                            <div class="input-group">
                                <input type="number" class="form-control" id="duration" name="duration" min="1" 
                                       value="<?php echo htmlspecialchars($task_data['duration']); ?>">
                                <select class="form-select" id="duration_unit_id" name="duration_unit_id">
                                    <?php while($row = $durationUnits->fetch(PDO::FETCH_ASSOC)): ?>
                                        <option value="<?php echo $row['id']; ?>" 
                                                <?php echo ($row['id'] == $task_data['duration_unit_id']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($row['name']); ?>
                                        </option>
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
                                            <?php echo (!empty($task_data['priority_id']) && $row['id'] == $task_data['priority_id']) ? 'selected' : ''; ?> 
                                            style="background-color: <?php echo isset($row['color']) ? htmlspecialchars($row['color']) : '#6c757d'; ?>">
                                        <?php echo htmlspecialchars($row['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="col-md-4">
                            <label for="status_id" class="form-label">Статус:</label>
                            <select class="form-select" id="status_id" name="status_id" required>
                                <?php
                                // Проверяем, что у каждого статуса есть цвет, если нет - добавляем
                                foreach ($taskStatuses as &$status) {
                                    if (!isset($status['color'])) {
                                        // Устанавливаем цвета по умолчанию в зависимости от ID
                                        if ($status['id'] == 1) {
                                            $status['color'] = '#0d6efd'; // Синий для активных
                                        } elseif ($status['id'] == 2) {
                                            $status['color'] = '#dc3545'; // Красный для просроченных
                                        } elseif ($status['id'] == 3) {
                                            $status['color'] = '#198754'; // Зелёный для выполненных
                                        } else {
                                            $status['color'] = '#6c757d'; // Серый для остальных
                                        }
                                    }
                                }
                                unset($status);
                                ?>

                                <?php foreach($taskStatuses as $row): ?>
                                    <option value="<?php echo $row['id']; ?>" 
                                            <?php echo ($row['id'] == $task_data['status_id']) ? 'selected' : ''; ?>
                                            style="background-color: <?php echo isset($row['color']) ? htmlspecialchars($row['color']) : '#6c757d'; ?>; color: white;">
                                        <?php echo htmlspecialchars($row['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="col-md-4">
                            <label for="comments" class="form-label">Комментарий:</label>
                            <textarea class="form-control" id="comments" name="comments" rows="3"><?php echo htmlspecialchars($task_data['comments']); ?></textarea>
                        </div>
                    </div>
                    
                    <div class="mb-3 d-flex justify-content-between">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Сохранить
                        </button>
                        <a href="index.php?action=show&id=<?php echo $task_data['id']; ?>" class="btn btn-secondary">
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