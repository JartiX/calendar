<?php
/**
 * Представление страницы просмотра задачи
 * 
 * Отображает детальную информацию о задаче
 */

$pageTitle = 'Задача: ' . $task_data['title'];

require_once 'helpers/date_helper.php';

// Подключаем шапку сайта
include_once 'views/layouts/header.php';
?>

<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h3>Просмотр задачи</h3>
                <div>
                    <a href="index.php?action=edit&id=<?php echo $task_data['id']; ?>" class="btn btn-primary">
                        <i class="fas fa-edit"></i> Редактировать
                    </a>
                    <button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#deleteTaskModal">
                        <i class="fas fa-trash"></i> Удалить
                    </button>
                </div>
            </div>
            <div class="card-body">
                <div class="row mb-3">
                    <div class="col-md-4">
                        <h5>Тема:</h5>
                        <p><?php echo htmlspecialchars($task_data['title']); ?></p>
                    </div>
                    <div class="col-md-4">
                        <h5>Тип:</h5>
                        <p><?php echo htmlspecialchars($task_data['type_name']); ?></p>
                    </div>
                    <div class="col-md-4">
                        <h5>Место:</h5>
                        <p><?php echo $task_data['location'] ? htmlspecialchars($task_data['location']) : '-'; ?></p>
                    </div>
                </div>
                
                <div class="row mb-3">
                    <div class="col-md-4">
                        <h5>Дата и время:</h5>
                        <p><?php echo date('d.m.Y H:i', strtotime($task_data['scheduled_date'])); ?></p>
                    </div>
                    <div class="col-md-4">
                        <h5>Длительность:</h5>
                        <p><?php echo $task_data['duration'] ? htmlspecialchars($task_data['duration'] . ' ' . $task_data['duration_unit_name']) : '-'; ?></p>
                    </div>
                    <div class="col-md-4">
                        <h5>Статус:</h5>
                        <p>
                            <?php 
                            switch($task_data['status_id']) {
                                case 1:
                                    echo '<span class="badge bg-primary">' . htmlspecialchars($task_data['status_name']) . '</span>';
                                    break;
                                case 2:
                                    echo '<span class="badge bg-danger">' . htmlspecialchars($task_data['status_name']) . '</span>';
                                    break;
                                case 3:
                                    echo '<span class="badge bg-success">' . htmlspecialchars($task_data['status_name']) . '</span>';
                                    break;
                                default:
                                    echo htmlspecialchars($task_data['status_name']);
                            }
                            ?>
                        </p>
                    </div>
                </div>
                
                <div class="mb-3">
                    <h5>Комментарий:</h5>
                    <div class="p-3 bg-light rounded">
                        <?php echo $task_data['comments'] ? nl2br(htmlspecialchars($task_data['comments'])) : '<em>Комментарий отсутствует</em>'; ?>
                    </div>
                </div>
                
                <div class="row mb-3">
                    <div class="col-md-4">
                        <h5>Создано:</h5>
                        <p><?php echo date('d.m.Y H:i', strtotime($task_data['created_at'])); ?></p>
                    </div>
                    
                    <?php if($task_data['created_at'] != $task_data['updated_at']): ?>
                    <div class="col-md-4">
                        <h5>Обновлено:</h5>
                        <p><?php echo date('d.m.Y H:i', strtotime($task_data['updated_at'])); ?></p>
                    </div>
                    <?php endif; ?>

                    <div class="col-md-4">
                        <h5>Приоритет:</h5>
                        <p>
                            <?php if(isset($task_data['priority_name']) && !empty($task_data['priority_name'])): ?>
                                <span class="badge" style="background-color: <?php echo htmlspecialchars($task_data['priority_color']); ?>">
                                    <?php echo htmlspecialchars($task_data['priority_name']); ?>
                                </span>
                            <?php else: ?>
                                <span class="badge bg-secondary">Не указан</span>
                            <?php endif; ?>
                        </p>
                    </div>
                </div>
                
                <div class="mt-4">
                    <div class="btn-group">
                        <a href="index.php?action=index" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> К календарю
                        </a>
                        
                        <?php if($task_data['status_id'] == 1): // Если задача активная ?>
                            <button type="button" class="btn btn-success" onclick="markTaskStatus(<?php echo $task_data['id']; ?>, 3);">
                                <i class="fas fa-check"></i> Отметить как выполненную
                            </button>
                            <button type="button" class="btn btn-danger" onclick="markTaskStatus(<?php echo $task_data['id']; ?>, 2);">
                                <i class="fas fa-clock"></i> Отметить как просроченную
                            </button>
                        <?php elseif($task_data['status_id'] == 2): // Если задача просроченная ?>
                            <button type="button" class="btn btn-success" onclick="markTaskStatus(<?php echo $task_data['id']; ?>, 3);">
                                <i class="fas fa-check"></i> Отметить как выполненную
                            </button>
                            <button type="button" class="btn btn-primary" onclick="markTaskStatus(<?php echo $task_data['id']; ?>, 1);">
                                <i class="fas fa-play"></i> Отметить как активную
                            </button>
                        <?php elseif($task_data['status_id'] == 3): // Если задача выполненная ?>
                            <button type="button" class="btn btn-primary" onclick="markTaskStatus(<?php echo $task_data['id']; ?>, 1);">
                                <i class="fas fa-play"></i> Отметить как активную
                            </button>
                            <button type="button" class="btn btn-danger" onclick="markTaskStatus(<?php echo $task_data['id']; ?>, 2);">
                                <i class="fas fa-clock"></i> Отметить как просроченную
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Модальное окно подтверждения удаления -->
<div class="modal fade" id="deleteTaskModal" tabindex="-1" aria-labelledby="deleteTaskModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteTaskModalLabel">Подтверждение удаления</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Закрыть"></button>
            </div>
            <div class="modal-body">
                <p>Вы действительно хотите удалить задачу <strong><?php echo htmlspecialchars($task_data['title']); ?></strong>?</p>
                <p class="text-danger">Это действие невозможно отменить.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Отмена</button>
                <button type="button" class="btn btn-danger" onclick="deleteTask(<?php echo $task_data['id']; ?>, null, false);" data-bs-dismiss="modal">
                    <i class="fas fa-trash"></i> Удалить
                </button>
            </div>
        </div>
    </div>
</div>

<?php
// Подключаем футер сайта
include_once 'views/layouts/footer.php';
?>