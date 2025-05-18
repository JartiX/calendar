<?php
/**
 * Шаблон нижней части страницы
 * 
 * Включает в себя футер сайта, подключение скриптов и закрывающие теги HTML
 */
?>
        <!-- Футер сайта -->
        <footer class="mt-5 pt-3 pb-3 text-center text-muted border-top">
            <div class="container">
                <p>&copy; 2025 Пилявин Артём. Все права защищены.</p>
                <p class="mb-0">Версия 1.0.0</p>
            </div>
        </footer>
    </div>

    <!-- Модальное окно подтверждения удаления задачи -->
    <div class="modal fade" id="deleteTaskModal" tabindex="-1" aria-labelledby="deleteTaskModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="deleteTaskModalLabel">Подтверждение удаления</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Закрыть"></button>
                </div>
                <div class="modal-body">
                    <p>Вы действительно хотите удалить задачу <strong id="deleteTaskTitle"></strong>?</p>
                    <p class="text-danger">Это действие невозможно отменить.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Отмена</button>
                    <button type="button" class="btn btn-danger" id="confirmDeleteButton">
                        <i class="fas fa-trash"></i> Удалить
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Подключение скриптов -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.1.3/js/bootstrap.bundle.min.js"></script>
    <script src="assets/scripts/validator.js"></script>
    <script src="assets/scripts/notifications.js"></script>
    <script src="assets/scripts/task.js"></script>
    <script src="assets/scripts/user_profile.js"></script>
    <script src="assets/scripts/ajax_forms.js"></script>
    
    <?php if (isset($includeCalendarScript) && $includeCalendarScript): ?>
    <script src="assets/scripts/calendar.js"></script>
    <?php endif; ?>
    
    <!-- Обработчик URL-параметров для уведомлений -->
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        <?php if(isset($_SESSION['check_overdue']) && $_SESSION['check_overdue']): 
            // Используем один раз
            unset($_SESSION['check_overdue']); 
        ?>
            // Немедленно проверяем просроченные задачи после входа
            setTimeout(checkOverdueTasks, 500);
        <?php endif; ?>
        
        // Функция для получения параметра из URL
        function getUrlParameter(name) {
            name = name.replace(/[\[]/, '\\[').replace(/[\]]/, '\\]');
            var regex = new RegExp('[\\?&]' + name + '=([^&#]*)');
            var results = regex.exec(location.search);
            return results === null ? '' : decodeURIComponent(results[1].replace(/\+/g, ' '));
        }
        
        // Настройка глобального модального окна подтверждения удаления
        const deleteTaskModal = document.getElementById('deleteTaskModal');
        
        if (deleteTaskModal) {
            // Предотвращаем закрытие модального окна при клике за его пределами
            deleteTaskModal.setAttribute('data-bs-backdrop', 'static');
            deleteTaskModal.setAttribute('data-bs-keyboard', 'false');
            
            // При закрытии модального окна удаляем обработчики событий с кнопки подтверждения
            deleteTaskModal.addEventListener('hidden.bs.modal', function() {
                const confirmBtn = deleteTaskModal.querySelector('#confirmDeleteButton');
                if (confirmBtn) {
                    const newConfirmBtn = confirmBtn.cloneNode(true);
                    confirmBtn.parentNode.replaceChild(newConfirmBtn, confirmBtn);
                }
            });
        }

        // Проверяем, нужно ли активировать вкладку профиля
        <?php if(isset($_GET['profile_tab']) && $_GET['profile_tab'] === 'active'): ?>
            // Находим и активируем вкладку профиля
            const profileTab = document.getElementById('profile-tab');
            if (profileTab) {
                const tabTrigger = new bootstrap.Tab(profileTab);
                tabTrigger.show();
            }
        <?php endif; ?>
        
        // Проверяем наличие параметра notification
        const notification = getUrlParameter('notification');
        
        if (notification) {
            // В зависимости от типа уведомления показываем соответствующее сообщение
            switch(notification) {
                case 'task_created':
                    showNotification('Успех', 'Задача успешно создана', 'success');
                    break;
                case 'task_updated':
                    showNotification('Успех', 'Задача успешно обновлена', 'success');
                    break;
                case 'task_deleted':
                    showNotification('Успех', 'Задача успешно удалена', 'success');
                    break;
                case 'registration_success':
                    showNotification('Успех', 'Регистрация успешно завершена! Добро пожаловать!', 'success');
                    break;
            }
            
            // Удаляем параметр из URL без перезагрузки страницы
            if (window.history && window.history.replaceState) {
                var url = window.location.href;
                url = url.replace(/&?notification=[^&]*/, '');
                url = url.replace(/\?notification=[^&]*&/, '?');
                url = url.replace(/\?notification=[^&]*$/, '');
                window.history.replaceState({}, document.title, url);
            }
        }
    });
    </script>
    
    <?php if (isset($extraScripts) && $extraScripts): ?>
    <?php echo $extraScripts; ?>
    <?php endif; ?>
</body>
</html>