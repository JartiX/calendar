<?php
/**
 * Контент страницы профиля пользователя
 * 
 * Содержит только контент профиля пользователя для возможности включения
 * как на отдельной странице, так и в виде вкладки
 */

// Проверяем, был ли передан флаг о том, что включение происходит во вкладке
$isIncludedInTab = isset($includeProfileInTab) && $includeProfileInTab === true;

// Всегда проверяем, инициализирована ли база данных
if (!isset($db) || !$db) {
    $database = new Database();
    $db = $database->getConnection();
}

// Проверяем, переданы ли данные пользователя
if (!isset($user_data) || !$user_data) {
    if (!isset($user) || !$user) {
        $user = new User($db);
        $user->id = $_SESSION['user_id'];
    }
    $user_data = $user->readOne();
}
?>

<div class="row">
    <div class="col-md-12">
        <div class="card mb-4">
            <div class="card-header">
                <h3><i class="fas fa-user"></i> Личный кабинет</h3>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <h4>Информация профиля</h4>
                        <div class="table-responsive">
                            <table class="table">
                                <tbody>
                                    <tr>
                                        <th style="width: 40%">Имя пользователя:</th>
                                        <td><?php echo htmlspecialchars($user_data['username']); ?></td>
                                    </tr>
                                    <tr>
                                        <th>Email:</th>
                                        <td><?php echo htmlspecialchars($user_data['email']); ?></td>
                                    </tr>
                                    <tr>
                                        <th>Дата регистрации:</th>
                                        <td><?php echo date('d.m.Y H:i', strtotime($user_data['created_at'])); ?></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <h4>Статистика</h4>
                        <div class="table-responsive">
                            <table class="table">
                                <tbody>
                                    <?php
                                    // Запрос для получения количества задач по статусам
                                    $task = new Task($db);
                                    $task->user_id = $_SESSION['user_id'];
                                    
                                    $activeCount = $task->countByStatus(1);
                                    $overdueCount = $task->countByStatus(2);
                                    $completedCount = $task->countByStatus(3);
                                    $totalCount = $activeCount + $overdueCount + $completedCount;
                                    ?>
                                    <tr>
                                        <th style="width: 40%">Активные задачи:</th>
                                        <td><span class="badge bg-primary"><?php echo $activeCount; ?></span></td>
                                    </tr>
                                    <tr>
                                        <th>Просроченные задачи:</th>
                                        <td><span class="badge bg-danger"><?php echo $overdueCount; ?></span></td>
                                    </tr>
                                    <tr>
                                        <th>Выполненные задачи:</th>
                                        <td><span class="badge bg-success"><?php echo $completedCount; ?></span></td>
                                    </tr>
                                    <tr>
                                        <th>Всего задач:</th>
                                        <td><span class="badge bg-secondary"><?php echo $totalCount; ?></span></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Редактирование профиля -->
        <div class="card mb-4">
            <div class="card-header">
                <h3><i class="fas fa-edit"></i> Редактирование профиля</h3>
            </div>
            <div class="card-body">
                <form action="index.php?action=updateProfile" method="POST">
                    <?php echo AuthHelper::csrfField(); ?>
                    
                    <!-- Скрытое поле для определения источника вызова -->
                    <input type="hidden" name="redirect_to" value="<?php echo isset($includeProfileInTab) && $includeProfileInTab ? 'index' : 'profile'; ?>">
                    
                    <div class="mb-3">
                        <label for="username" class="form-label">Имя пользователя:</label>
                        <input type="text" class="form-control" id="username" name="username" value="<?php echo htmlspecialchars($user_data['username']); ?>" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="email" class="form-label">Email:</label>
                        <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($user_data['email']); ?>" required>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Сохранить изменения
                    </button>
                </form>
            </div>
        </div>

        <!-- Смена пароля -->
        <div class="card">
            <div class="card-header">
                <h3><i class="fas fa-key"></i> Смена пароля</h3>
            </div>
            <div class="card-body">
                <form action="index.php?action=changePassword" method="POST">
                    <?php echo AuthHelper::csrfField(); ?>
                    
                    <!-- Скрытое поле для определения источника вызова -->
                    <input type="hidden" name="redirect_to" value="<?php echo isset($includeProfileInTab) && $includeProfileInTab ? 'index' : 'profile'; ?>">
                    
                    <div class="mb-3">
                        <label for="current_password" class="form-label">Текущий пароль:</label>
                        <input type="password" class="form-control" id="current_password" name="current_password" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="new_password" class="form-label">Новый пароль:</label>
                        <input type="password" class="form-control" id="new_password" name="new_password" required>
                        <div class="form-text">Пароль должен содержать не менее 8 символов.</div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="confirm_password" class="form-label">Подтверждение пароля:</label>
                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Изменить пароль
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>