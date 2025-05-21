<?php
/**
 * Главная страница приложения
 * 
 * Содержит календарь, список задач и форму добавления новой задачи
 */

$pageTitle = 'Главная';

// Подключаем шапку сайта
include_once 'views/layouts/header.php';

include_once 'controllers/telegram_controller.php';
?>

<!-- Секция поиска -->
<div class="row search-section">
    <div class="col-md-12">
        <!-- Поиск задач -->
        <div class="card">
            <div class="card-header">
                <h3>Фильтрация задач</h3>
                    <div>
                        <a href="index.php?action=active" class="btn btn-outline-primary me-2">
                            <i class="fas fa-play"></i> Активные
                        </a>
                        <a href="index.php?action=overdue" class="btn btn-outline-danger me-2">
                            <i class="fas fa-clock"></i> Просроченные
                        </a>
                        <a href="index.php?action=completed" class="btn btn-outline-success">
                            <i class="fas fa-check"></i> Выполненные
                        </a>
                    </div>
            </div>
            <div class="card-body">
                <div class="row">
                    <!-- Поиск по ключевому слову -->
                    <div class="col-md-6 mb-3">
                        <form action="index.php" method="GET" class="d-flex" id="keywordSearchForm">
                            <input type="hidden" name="action" value="combinedSearch">
                            
                            <!-- Сохраняем текущие параметры фильтрации -->
                            <?php if (!empty($_GET['date'])): ?>
                            <input type="hidden" name="date" value="<?php echo htmlspecialchars($_GET['date']); ?>">
                            <?php endif; ?>
                            
                            <?php if (!empty($_GET['status_id'])): ?>
                            <input type="hidden" name="status_id" value="<?php echo htmlspecialchars($_GET['status_id']); ?>">
                            <?php endif; ?>
                            
                            <?php if (!empty($_GET['priority_id'])): ?>
                            <input type="hidden" name="priority_id" value="<?php echo htmlspecialchars($_GET['priority_id']); ?>">
                            <?php endif; ?>
                            
                            <input type="text" name="keyword" class="form-control me-2" placeholder="Поиск по названию..."
                                value="<?php echo isset($_GET['keyword']) ? htmlspecialchars($_GET['keyword']) : ''; ?>">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-search"></i> <span class="d-none d-md-inline">Найти</span>
                            </button>
                        </form>
                    </div>
                    
                    <!-- Поиск по дате -->
                    <div class="col-md-6 mb-3">
                        <form action="index.php" method="GET" class="d-flex" id="dateSearchForm">
                            <input type="hidden" name="action" value="combinedSearch">
                            
                            <!-- Сохраняем текущие параметры фильтрации -->
                            <?php if (!empty($_GET['keyword'])): ?>
                            <input type="hidden" name="keyword" value="<?php echo htmlspecialchars($_GET['keyword']); ?>">
                            <?php endif; ?>
                            
                            <?php if (!empty($_GET['status_id'])): ?>
                            <input type="hidden" name="status_id" value="<?php echo htmlspecialchars($_GET['status_id']); ?>">
                            <?php endif; ?>
                            
                            <?php if (!empty($_GET['priority_id'])): ?>
                            <input type="hidden" name="priority_id" value="<?php echo htmlspecialchars($_GET['priority_id']); ?>">
                            <?php endif; ?>
                            
                            <input type="date" class="form-control me-2" name="date" 
                                value="<?php echo !empty($_GET['date']) ? htmlspecialchars($_GET['date']) : date('Y-m-d'); ?>">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-calendar"></i> <span class="d-none d-md-inline">Фильтр по дате</span>
                            </button>
                        </form>
                    </div>
                </div>
                
                <div class="row mt-3">
                    <!-- Фильтр по статусу -->
                    <div class="col-md-6 mb-3">
                        <form action="index.php" method="GET" id="statusFilterForm">
                            <input type="hidden" name="action" value="combinedSearch">
                            
                            <!-- Сохраняем текущие параметры фильтрации -->
                            <?php if (!empty($_GET['keyword'])): ?>
                            <input type="hidden" name="keyword" value="<?php echo htmlspecialchars($_GET['keyword']); ?>">
                            <?php endif; ?>
                            
                            <?php if (!empty($_GET['date'])): ?>
                            <input type="hidden" name="date" value="<?php echo htmlspecialchars($_GET['date']); ?>">
                            <?php endif; ?>
                            
                            <?php if (!empty($_GET['priority_id'])): ?>
                            <input type="hidden" name="priority_id" value="<?php echo htmlspecialchars($_GET['priority_id']); ?>">
                            <?php endif; ?>
                            
                            <div class="input-group">
                                <select class="form-select" name="status_id" id="statusFilter">
                                    <option value="">Все статусы</option>
                                    <?php
                                    $statuses = [];
                                    if (isset($task) && is_object($task)) {
                                        try {
                                            $statuses = $task->getTaskStatuses();
                                        } catch (Exception $e) {
                                            error_log("Ошибка при получении статусов задач: " . $e->getMessage());
                                            // Используем статические данные при ошибке
                                            $statuses = [
                                                ['id' => 1, 'name' => 'Активная'],
                                                ['id' => 2, 'name' => 'Просроченная'],
                                                ['id' => 3, 'name' => 'Выполненная']
                                            ];
                                        }
                                    } else {
                                        $statuses = [
                                            ['id' => 1, 'name' => 'Активная'],
                                            ['id' => 2, 'name' => 'Просроченная'],
                                            ['id' => 3, 'name' => 'Выполненная']
                                        ];
                                    }
                                    
                                    foreach ($statuses as $status): 
                                    ?>
                                        <option value="<?php echo $status['id']; ?>" 
                                            <?php echo (isset($_GET['status_id']) && $_GET['status_id'] == $status['id']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($status['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-filter"></i> <span class="d-none d-md-inline">Фильтр по статусу</span>
                                </button>
                            </div>
                        </form>
                    </div>

                    <!-- Фильтр приоритетов -->
                    <div class="col-md-6 mb-3">
                        <form action="index.php" method="GET" id="priorityFilterForm">
                            <input type="hidden" name="action" value="combinedSearch">
                            
                            <!-- Сохраняем текущие параметры фильтрации -->
                            <?php if (!empty($_GET['keyword'])): ?>
                            <input type="hidden" name="keyword" value="<?php echo htmlspecialchars($_GET['keyword']); ?>">
                            <?php endif; ?>
                            
                            <?php if (!empty($_GET['date'])): ?>
                            <input type="hidden" name="date" value="<?php echo htmlspecialchars($_GET['date']); ?>">
                            <?php endif; ?>
                            
                            <?php if (!empty($_GET['status_id'])): ?>
                            <input type="hidden" name="status_id" value="<?php echo htmlspecialchars($_GET['status_id']); ?>">
                            <?php endif; ?>
                            
                            <div class="input-group">
                                <select class="form-select" name="priority_id" id="priorityFilter">
                                    <option value="">Все приоритеты</option>
                                    <?php
                                    $priorities = [];
                                    if (isset($task) && is_object($task)) {
                                        try {
                                            $priorities = $task->getTaskPriorities();
                                        } catch (Exception $e) {
                                            error_log("Ошибка при получении приоритетов задач: " . $e->getMessage());
                                            // Используем статические данные при ошибке
                                            $priorities = [
                                                ['id' => 1, 'name' => 'Высокий', 'color' => '#dc3545'],
                                                ['id' => 2, 'name' => 'Средний', 'color' => '#ffc107'],
                                                ['id' => 3, 'name' => 'Низкий', 'color' => '#28a745']
                                            ];
                                        }
                                    } else {
                                        $priorities = [
                                            ['id' => 1, 'name' => 'Высокий', 'color' => '#dc3545'],
                                            ['id' => 2, 'name' => 'Средний', 'color' => '#ffc107'],
                                            ['id' => 3, 'name' => 'Низкий', 'color' => '#28a745']
                                        ];
                                    }
                                    
                                    foreach ($priorities as $priority): 
                                    ?>
                                        <option value="<?php echo $priority['id']; ?>" 
                                            <?php echo (isset($_GET['priority_id']) && $_GET['priority_id'] == $priority['id']) ? 'selected' : ''; ?>
                                            style="background-color: <?php echo htmlspecialchars($priority['color']); ?>">
                                            <?php echo htmlspecialchars($priority['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-filter"></i> <span class="d-none d-md-inline">Фильтр по приоритету</span>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
                
                <!-- Отображение текущих параметров фильтрации -->
                <?php if (!empty($_GET['keyword']) || !empty($_GET['date']) || !empty($_GET['status_id']) || !empty($_GET['priority_id'])): ?>
                <div class="row mt-2">
                    <div class="col-12">
                        <div class="active-filters">
                            <span class="text-muted">Текущие фильтры:</span>
                            
                            <?php if (!empty($_GET['keyword'])): ?>
                            <span class="badge bg-info me-2">
                                Поиск: <?php echo htmlspecialchars($_GET['keyword']); ?>
                                <a href="#" onclick="removeFilter('keyword')" class="text-white ms-1"><i class="fas fa-times"></i></a>
                            </span>
                            <?php endif; ?>
                            
                            <?php if (!empty($_GET['date'])): ?>
                            <span class="badge bg-info me-2">
                                Дата: <?php echo date('d.m.Y', strtotime($_GET['date'])); ?>
                                <a href="#" onclick="removeFilter('date')" class="text-white ms-1"><i class="fas fa-times"></i></a>
                            </span>
                            <?php endif; ?>
                            
                            <?php if (!empty($_GET['status_id'])): ?>
                            <span class="badge bg-info me-2">
                                Статус: 
                                <?php 
                                    // Извлекаем название статуса по ID
                                    $statusName = "Выбранный";
                                    foreach ($statuses as $status) {
                                        if ($status['id'] == $_GET['status_id']) {
                                            $statusName = $status['name'];
                                            break;
                                        }
                                    }
                                    echo htmlspecialchars($statusName);
                                ?>
                                <a href="#" onclick="removeFilter('status_id')" class="text-white ms-1"><i class="fas fa-times"></i></a>
                            </span>
                            <?php endif; ?>
                            
                            <?php if (!empty($_GET['priority_id'])): ?>
                            <span class="badge bg-info me-2">
                                Приоритет: 
                                <?php 
                                    // Извлекаем название приоритета по ID
                                    $priorityName = "Выбранный";
                                    foreach ($priorities as $priority) {
                                        if ($priority['id'] == $_GET['priority_id']) {
                                            $priorityName = $priority['name'];
                                            break;
                                        }
                                    }
                                    echo htmlspecialchars($priorityName);
                                ?>
                                <a href="#" onclick="removeFilter('priority_id')" class="text-white ms-1"><i class="fas fa-times"></i></a>
                            </span>
                            <?php endif; ?>
                            
                            <?php if (!empty($_GET['keyword']) || !empty($_GET['date']) || !empty($_GET['status_id']) || !empty($_GET['priority_id'])): ?>
                            <a href="index.php?action=<?php echo isset($_GET['tab']) ? 'index&tab=' . htmlspecialchars($_GET['tab']) : 'index'; ?>" 
                            class="btn btn-sm btn-outline-secondary">
                                <i class="fas fa-times"></i> Сбросить все фильтры
                            </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php
// Определяем активную вкладку
$activeTab = 'calendar'; // По умолчанию - вкладка календаря

// Проверяем, есть ли параметр tab в URL
if (isset($_GET['tab'])) {
    $validTabs = ['calendar', 'list', 'create', 'profile'];
    if (in_array($_GET['tab'], $validTabs)) {
        $activeTab = $_GET['tab'];
    }
}
?>

<!-- Вкладки для переключения представлений -->
<ul class="nav nav-tabs" id="viewTabs" role="tablist">
    <li class="nav-item" role="presentation">
        <button class="nav-link <?php echo ($activeTab == 'calendar') ? 'active' : ''; ?>" 
                id="calendar-tab" data-bs-toggle="tab" data-bs-target="#calendar-view" 
                type="button" role="tab" aria-controls="calendar-view" 
                aria-selected="<?php echo ($activeTab == 'calendar') ? 'true' : 'false'; ?>">
            <i class="fas fa-calendar-alt"></i> Календарь
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link <?php echo ($activeTab == 'list') ? 'active' : ''; ?>" 
                id="list-tab" data-bs-toggle="tab" data-bs-target="#list-view" 
                type="button" role="tab" aria-controls="list-view" 
                aria-selected="<?php echo ($activeTab == 'list') ? 'true' : 'false'; ?>">
            <i class="fas fa-list"></i> Список активных задач
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link <?php echo ($activeTab == 'create') ? 'active' : ''; ?>" 
                id="create-tab" data-bs-toggle="tab" data-bs-target="#create-view" 
                type="button" role="tab" aria-controls="create-view" 
                aria-selected="<?php echo ($activeTab == 'create') ? 'true' : 'false'; ?>">
            <i class="fas fa-plus-circle"></i> Добавить задачу
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link <?php echo ($activeTab == 'profile') ? 'active' : ''; ?>" 
                id="profile-tab" data-bs-toggle="tab" data-bs-target="#profile-view" 
                type="button" role="tab" aria-controls="profile-view" 
                aria-selected="<?php echo ($activeTab == 'profile') ? 'true' : 'false'; ?>">
            <i class="fas fa-user"></i> Личный кабинет
        </button>
    </li>
</ul>

<div class="tab-content" id="viewTabsContent">
    <!-- Представление календаря -->
    <div class="tab-pane fade <?php echo ($activeTab == 'calendar') ? 'show active' : ''; ?>" 
         id="calendar-view" role="tabpanel" aria-labelledby="calendar-tab">
        <?php include_once 'views/tasks/calendar.php'; ?>
    </div>
    
    <!-- Представление списка задач -->
    <div class="tab-pane fade <?php echo ($activeTab == 'list') ? 'show active' : ''; ?>" 
        id="list-view" role="tabpanel" aria-labelledby="list-tab">
        <div class="row task-list">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h3>Активные задачи</h3>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Тип</th>
                                        <th>Задача</th>
                                        <th>Место</th>
                                        <th>Дата и время</th>
                                        <th>Длительность</th>
                                        <th>Приоритет</th>
                                        <th>Статус</th>
                                        <th>Действия</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    if(is_object($activeTasks) && method_exists($activeTasks, 'rowCount') && $activeTasks->rowCount() > 0):
                                        while($row = $activeTasks->fetch(PDO::FETCH_ASSOC)): 
                                    ?>
                                        <tr class="task-item" onclick="window.location='index.php?action=show&id=<?php echo $row['id']; ?>'">
                                            <td data-label="Тип"><?php echo htmlspecialchars($row['type_name']); ?></td>
                                            <td data-label="Задача"><?php echo htmlspecialchars($row['title']); ?></td>
                                            <td data-label="Место"><?php echo $row['location'] ? htmlspecialchars($row['location']) : '-'; ?></td>
                                            <td data-label="Дата и время"><?php echo date('d.m.Y H:i', strtotime($row['scheduled_date'])); ?></td>
                                            <td data-label="Длительность"><?php echo $row['duration'] ? htmlspecialchars($row['duration'] . ' ' . $row['duration_unit_name']) : '-'; ?></td>
                                            <td data-label="Приоритет">
                                                <?php if(isset($row['priority_name']) && !empty($row['priority_name'])): ?>
                                                    <span class="badge" style="background-color: <?php echo htmlspecialchars($row['priority_color']); ?>">
                                                        <?php echo htmlspecialchars($row['priority_name']); ?>
                                                    </span>
                                                <?php else: ?>
                                                    <span class="badge bg-secondary">Не указан</span>
                                                <?php endif; ?>
                                            </td>
                                            <td data-label="Статус">
                                                <?php 
                                                switch($row['status_id']) {
                                                    case 1:
                                                        echo '<span class="badge bg-primary">' . htmlspecialchars($row['status_name']) . '</span>';
                                                        break;
                                                    case 2:
                                                        echo '<span class="badge bg-danger">' . htmlspecialchars($row['status_name']) . '</span>';
                                                        break;
                                                    case 3:
                                                        echo '<span class="badge bg-success">' . htmlspecialchars($row['status_name']) . '</span>';
                                                        break;
                                                    default:
                                                        echo htmlspecialchars($row['status_name']);
                                                }
                                                ?>
                                            </td>
                                            <td data-label="Действия">
                                                <div class="action-buttons">
                                                    <a href="index.php?action=edit&id=<?php echo $row['id']; ?>" class="btn btn-sm btn-outline-primary" onclick="event.stopPropagation();" title="Редактировать" data-bs-toggle="tooltip">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <button type="button" class="btn btn-sm btn-outline-danger" onclick="event.stopPropagation(); deleteTask(<?php echo $row['id']; ?>, this.closest('tr'));" title="Удалить" data-bs-toggle="tooltip">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                    <!-- Кнопки изменения статуса -->
                                                    <button type="button" class="btn btn-sm btn-outline-success" onclick="event.stopPropagation(); markTaskStatus(<?php echo $row['id']; ?>, 3);" title="Отметить как выполненную" data-bs-toggle="tooltip">
                                                        <i class="fas fa-check"></i>
                                                    </button>
                                                    <button type="button" class="btn btn-sm btn-outline-danger" onclick="event.stopPropagation(); markTaskStatus(<?php echo $row['id']; ?>, 2);" title="Отметить как просроченную" data-bs-toggle="tooltip">
                                                        <i class="fas fa-clock"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php 
                                        endwhile; 
                                    elseif(is_array($activeTasks) && !empty($activeTasks)):
                                        foreach($activeTasks as $row):
                                    ?>
                                        <tr class="task-item" onclick="window.location='index.php?action=show&id=<?php echo $row['id']; ?>'">
                                            <!-- Тот же HTML-код, что и выше -->
                                            <td data-label="Тип"><?php echo htmlspecialchars($row['type_name']); ?></td>
                                            <td data-label="Задача"><?php echo htmlspecialchars($row['title']); ?></td>
                                            <td data-label="Место"><?php echo $row['location'] ? htmlspecialchars($row['location']) : '-'; ?></td>
                                            <td data-label="Дата и время"><?php echo date('d.m.Y H:i', strtotime($row['scheduled_date'])); ?></td>
                                            <td data-label="Длительность"><?php echo $row['duration'] ? htmlspecialchars($row['duration'] . ' ' . $row['duration_unit_name']) : '-'; ?></td>
                                            <td data-label="Приоритет">
                                                <?php if(isset($row['priority_name']) && !empty($row['priority_name'])): ?>
                                                    <span class="badge" style="background-color: <?php echo htmlspecialchars($row['priority_color']); ?>">
                                                        <?php echo htmlspecialchars($row['priority_name']); ?>
                                                    </span>
                                                <?php else: ?>
                                                    <span class="badge bg-secondary">Не указан</span>
                                                <?php endif; ?>
                                            </td>
                                            <td data-label="Действия">
                                                <div class="action-buttons">
                                                    <a href="index.php?action=edit&id=<?php echo $row['id']; ?>" class="btn btn-sm btn-outline-primary" onclick="event.stopPropagation();" title="Редактировать" data-bs-toggle="tooltip">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <button type="button" class="btn btn-sm btn-outline-danger" onclick="event.stopPropagation(); deleteTask(<?php echo $row['id']; ?>, this.closest('tr'));" title="Удалить" data-bs-toggle="tooltip">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                    <!-- Кнопки изменения статуса -->
                                                    <button type="button" class="btn btn-sm btn-outline-success" onclick="event.stopPropagation(); markTaskStatus(<?php echo $row['id']; ?>, 3);" title="Отметить как выполненную" data-bs-toggle="tooltip">
                                                        <i class="fas fa-check"></i>
                                                    </button>
                                                    <button type="button" class="btn btn-sm btn-outline-danger" onclick="event.stopPropagation(); markTaskStatus(<?php echo $row['id']; ?>, 2);" title="Отметить как просроченную" data-bs-toggle="tooltip">
                                                        <i class="fas fa-clock"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php 
                                        endforeach;
                                    else:
                                    ?>
                                        <tr>
                                            <td colspan="7" class="text-center">Активных задач не найдено</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Представление создания задачи -->
    <div class="tab-pane fade <?php echo ($activeTab == 'create') ? 'show active' : ''; ?>" 
        id="create-view" role="tabpanel" aria-labelledby="create-tab">
        <div class="row add-task-form">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <h3>Добавить новую задачу</h3>
                    </div>
                    <div class="card-body">
                        <form action="index.php?action=store" method="POST">
                            <!-- Скрытое поле для указания источника формы -->
                            <input type="hidden" name="tab_source" value="main_page">

                            <div class="row mb-3">
                                <div class="col-md-4">
                                    <label for="title" class="form-label">Тема:</label>
                                    <input type="text" class="form-control" id="title" name="title" required>
                                </div>
                                <div class="col-md-4">
                                    <label for="type_id" class="form-label">Тип:</label>
                                    <select class="form-select" id="type_id" name="type_id" required>
                                        <?php foreach($taskTypes as $row): ?>
                                            <option value="<?php echo $row['id']; ?>"><?php echo htmlspecialchars($row['name']); ?></option>
                                        <?php endforeach; ?>
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
                                    <input type="date" class="form-control" id="date" name="date" value="<?php echo date('Y-m-d'); ?>" required>
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
                                            <?php foreach($durationUnits as $row): ?>
                                                <option value="<?php echo $row['id']; ?>"><?php echo htmlspecialchars($row['name']); ?></option>
                                            <?php endforeach; ?>
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
                                            <option value="<?php echo $row['id']; ?>" style="background-color: <?php echo htmlspecialchars($row['color']); ?>">
                                                <?php echo htmlspecialchars($row['name']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
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
                                        //  Проверяем подключение к Telegram
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
                                
                                <div class="col-md-8">
                                    <label for="comments" class="form-label">Комментарий:</label>
                                    <textarea class="form-control" id="comments" name="comments" rows="3"></textarea>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-12">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-plus"></i> Добавить задачу
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Представление личного кабинета -->
    <div class="tab-pane fade <?php echo ($activeTab == 'profile') ? 'show active' : ''; ?>" 
         id="profile-view" role="tabpanel" aria-labelledby="profile-tab">
        <?php 
        // Включаем шаблон профиля без хедера и футера
        $includeProfileInTab = true; // флаг для profile_content.php
        include_once 'views/users/profile_content.php';
        ?>
    </div>
</div>

<?php
// Подключаем футер сайта
include_once 'views/layouts/footer.php';
?>