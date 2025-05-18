<?php
/**
 * Представление списка задач
 * 
 * Отображает список задач с возможностью фильтрации по дате и статусу
 */

// Подключаем шапку сайта
include_once 'views/layouts/header.php';
?>

<div class="row mb-3">
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
                                <i class="fas fa-calendar"></i> <span class="d-none d-md-inline">Показать по дате</span>
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
                                            // Проверяем, что у каждого статуса есть цвет, если нет - добавляем
                                            foreach ($statuses as &$status) {
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
                                        } catch (Exception $e) {
                                            error_log("Ошибка при получении статусов задач: " . $e->getMessage());
                                            // Используем статические данные при ошибке
                                            $statuses = [
                                                ['id' => 1, 'name' => 'Активная', 'color' => '#0d6efd'],
                                                ['id' => 2, 'name' => 'Просроченная', 'color' => '#dc3545'],
                                                ['id' => 3, 'name' => 'Выполненная', 'color' => '#198754']
                                            ];
                                        }
                                    } else {
                                        $statuses = [
                                            ['id' => 1, 'name' => 'Активная', 'color' => '#0d6efd'],
                                            ['id' => 2, 'name' => 'Просроченная', 'color' => '#dc3545'],
                                            ['id' => 3, 'name' => 'Выполненная', 'color' => '#198754']
                                        ];
                                    }
                                    
                                    foreach ($statuses as $status): 
                                    ?>
                                        <option value="<?php echo $status['id']; ?>" 
                                            <?php echo (isset($_GET['status_id']) && $_GET['status_id'] == $status['id']) ? 'selected' : ''; ?>
                                            style="background-color: <?php echo isset($status['color']) ? htmlspecialchars($status['color']) : '#6c757d'; ?>; color: white;">
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

<div class="row task-list">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h3 id="taskListTitle">
                    <?php 
                    if(isset($_GET['action'])) {
                        switch($_GET['action']) {
                            case 'active':
                                echo 'Текущие задачи';
                                break;
                            case 'overdue':
                                echo 'Просроченные задачи';
                                break;
                            case 'completed':
                                echo 'Выполненные задачи';
                                break;
                            case 'index':
                                echo 'Список задач';
                                break;
                            default:
                                echo isset($taskListTitle) ? $taskListTitle : 'Результаты поиска: ';
                        }
                    } else {
                        echo 'Список задач';
                    }
                    ?>
                </h3>
                <div class="date-selector">
                    <form id="dateFilterForm" class="d-flex" data-status-id="<?php echo isset($currentStatus) ? $currentStatus : (isset($_GET['action']) && in_array($_GET['action'], ['active', 'overdue', 'completed']) ? ['active' => 1, 'overdue' => 2, 'completed' => 3][$_GET['action']] : ''); ?>">
                        <input type="hidden" name="action" value="byDate">
                        <input type="hidden" name="status_context" id="status_context" value="<?php 
                            echo isset($currentStatus) ? $currentStatus : (
                                isset($_GET['action']) && in_array($_GET['action'], ['active', 'overdue', 'completed']) ? 
                                ['active' => 1, 'overdue' => 2, 'completed' => 3][$_GET['action']] : ''
                            ); 
                        ?>">
                    </form>
                </div>
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
                            if (isset($tasks) && $tasks !== false):
                                if ($tasks->rowCount() > 0):
                                    while($row = $tasks->fetch(PDO::FETCH_ASSOC)): 
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
                                            
                                            <?php if($row['status_id'] == 1): // Если задача активная ?>
                                                <button type="button" class="btn btn-sm btn-outline-success" onclick="event.stopPropagation(); markTaskStatus(<?php echo $row['id']; ?>, 3);" title="Отметить как выполненную" data-bs-toggle="tooltip">
                                                    <i class="fas fa-check"></i>
                                                </button>
                                                <button type="button" class="btn btn-sm btn-outline-danger" onclick="event.stopPropagation(); markTaskStatus(<?php echo $row['id']; ?>, 2);" title="Отметить как просроченную" data-bs-toggle="tooltip">
                                                    <i class="fas fa-clock"></i>
                                                </button>
                                            <?php elseif($row['status_id'] == 2): // Если задача просроченная ?>
                                                <button type="button" class="btn btn-sm btn-outline-success" onclick="event.stopPropagation(); markTaskStatus(<?php echo $row['id']; ?>, 3);" title="Отметить как выполненную" data-bs-toggle="tooltip">
                                                    <i class="fas fa-check"></i>
                                                </button>
                                                <button type="button" class="btn btn-sm btn-outline-primary" onclick="event.stopPropagation(); markTaskStatus(<?php echo $row['id']; ?>, 1);" title="Отметить как активную" data-bs-toggle="tooltip">
                                                    <i class="fas fa-play"></i>
                                                </button>
                                            <?php elseif($row['status_id'] == 3): // Если задача выполненная ?>
                                                <button type="button" class="btn btn-sm btn-outline-primary" onclick="event.stopPropagation(); markTaskStatus(<?php echo $row['id']; ?>, 1);" title="Отметить как активную" data-bs-toggle="tooltip">
                                                    <i class="fas fa-play"></i>
                                                </button>
                                                <button type="button" class="btn btn-sm btn-outline-danger" onclick="event.stopPropagation(); markTaskStatus(<?php echo $row['id']; ?>, 2);" title="Отметить как просроченную" data-bs-toggle="tooltip">
                                                    <i class="fas fa-clock"></i>
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php 
                                endwhile; 
                            else:
                            ?>
                                <tr>
                                    <td colspan="8" class="text-center">Задачи не найдены</td>
                                </tr>
                            <?php endif; 
                            else: 
                            ?>
                                <tr>
                                    <td colspan="8" class="text-center">Ошибка при выполнении запроса</td>
                                </tr>
                            ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                
                <div class="mt-3">
                    <a href="index.php?action=create" class="btn btn-success">
                        <i class="fas fa-plus"></i> Добавить новую задачу
                    </a>
                    <a href="index.php?action=index" class="btn btn-secondary ms-2">
                        <i class="fas fa-calendar"></i> Вернуться к календарю
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
$extraScripts = '
<script>
document.addEventListener("DOMContentLoaded", function() {
    // Инициализация подсказок
    const tooltipTriggerList = [].slice.call(document.querySelectorAll("[data-bs-toggle=\'tooltip\']"));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
});
</script>
';

// Подключаем футер сайта
include_once 'views/layouts/footer.php';
?>