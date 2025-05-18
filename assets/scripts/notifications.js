/**
 * Скрипт для работы с уведомлениями
 * 
 * Предоставляет функции для отображения уведомлений пользователю
 */

/**
 * Показывает уведомление пользователю
 * 
 * @param {string} title - Заголовок уведомления
 * @param {string} message - Текст уведомления
 * @param {string} type - Тип уведомления (success, danger, warning, info)
 * @param {number} duration - Продолжительность отображения в мс (по умолчанию 5000)
 */
function showNotification(title, message, type = 'info', duration = 5000) {
    // Проверка доступности Bootstrap 5 toast
    if (typeof bootstrap !== 'undefined' && bootstrap.Toast) {
        // Иконка в зависимости от типа уведомления
        let icon = '';
        switch (type) {
            case 'success':
                icon = '<i class="fas fa-check-circle me-2"></i>';
                break;
            case 'danger':
                icon = '<i class="fas fa-exclamation-circle me-2"></i>';
                break;
            case 'warning':
                icon = '<i class="fas fa-exclamation-triangle me-2"></i>';
                break;
            case 'info':
                icon = '<i class="fas fa-info-circle me-2"></i>';
                break;
        }
        
        // Создаем элемент уведомления
        const toastEl = document.createElement('div');
        toastEl.className = `toast align-items-center text-white bg-${type} border-0`;
        toastEl.setAttribute('role', 'alert');
        toastEl.setAttribute('aria-live', 'assertive');
        toastEl.setAttribute('aria-atomic', 'true');
        toastEl.setAttribute('data-bs-delay', duration);
        
        toastEl.innerHTML = `
            <div class="d-flex">
                <div class="toast-body">
                    ${icon}<strong>${title}:</strong> ${message}
                </div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Закрыть"></button>
            </div>
        `;
        
        // Добавляем уведомление на страницу
        let toastContainer = document.querySelector('.toast-container');
        if (!toastContainer) {
            toastContainer = document.createElement('div');
            toastContainer.className = 'toast-container position-fixed bottom-0 end-0 p-3';
            document.body.appendChild(toastContainer);
        }
        
        toastContainer.appendChild(toastEl);
        
        // Добавляем анимацию появления
        toastEl.style.opacity = '0';
        toastEl.style.transform = 'translateY(20px)';
        toastEl.style.transition = 'opacity 0.3s, transform 0.3s';
        
        // Инициализируем и показываем уведомление
        const toast = new bootstrap.Toast(toastEl, {
            autohide: true,
            delay: duration
        });
        
        // Показываем с анимацией
        setTimeout(() => {
            toastEl.style.opacity = '1';
            toastEl.style.transform = 'translateY(0)';
        }, 10);
        
        toast.show();
        
        // Удаляем уведомление после закрытия
        toastEl.addEventListener('hidden.bs.toast', function() {
            // Анимация исчезновения
            toastEl.style.opacity = '0';
            toastEl.style.transform = 'translateY(20px)';
            
            // Удаляем элемент после завершения анимации
            setTimeout(() => {
                toastEl.remove();
                
                // Проверяем, остались ли еще уведомления, и если нет, удаляем контейнер
                if (toastContainer.childNodes.length === 0) {
                    toastContainer.remove();
                }
            }, 300);
        });
    } else {
        // Запасной вариант - обычный alert
        alert(`${title}: ${message}`);
    }
}

/**
 * Показывает подтверждение с настраиваемыми кнопками
 * 
 * @param {string} title - Заголовок подтверждения
 * @param {string} message - Текст подтверждения
 * @param {Function} onConfirm - Функция, вызываемая при подтверждении
 * @param {Function} onCancel - Функция, вызываемая при отмене
 * @param {string} confirmText - Текст кнопки подтверждения
 * @param {string} cancelText - Текст кнопки отмены
 */
function showConfirmation(title, message, onConfirm, onCancel = null, confirmText = 'Да', cancelText = 'Отмена') {
    // Проверка доступности Bootstrap modal
    if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
        // Создаем уникальный ID для модального окна
        const modalId = 'confirmModal' + Date.now();
        
        // Создаем модальное окно
        const modalEl = document.createElement('div');
        modalEl.className = 'modal fade';
        modalEl.id = modalId;
        modalEl.setAttribute('tabindex', '-1');
        modalEl.setAttribute('aria-labelledby', `${modalId}Label`);
        modalEl.setAttribute('aria-hidden', 'true');
        
        modalEl.innerHTML = `
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="${modalId}Label">${title}</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Закрыть"></button>
                    </div>
                    <div class="modal-body">
                        ${message}
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">${cancelText}</button>
                        <button type="button" class="btn btn-primary" id="${modalId}ConfirmBtn">${confirmText}</button>
                    </div>
                </div>
            </div>
        `;
        
        document.body.appendChild(modalEl);
        
        const modal = new bootstrap.Modal(modalEl);
        
        // Добавляем обработчики событий
        const confirmBtn = document.getElementById(`${modalId}ConfirmBtn`);
        confirmBtn.addEventListener('click', function() {
            modal.hide();
            if (typeof onConfirm === 'function') {
                onConfirm();
            }
        });
        
        modalEl.addEventListener('hidden.bs.modal', function() {
            if (modalEl.getAttribute('data-confirm') !== 'true' && typeof onCancel === 'function') {
                onCancel();
            }
            modalEl.remove();
        });
        
        modal.show();
    } else {
        // Запасной вариант - стандартное подтверждение
        if (confirm(message)) {
            if (typeof onConfirm === 'function') {
                onConfirm();
            }
        } else if (typeof onCancel === 'function') {
            onCancel();
        }
    }
}

/**
 * Инициализация компонента уведомлений при загрузке страницы
 */
document.addEventListener('DOMContentLoaded', function() {
    if (!document.querySelector('.toast-container')) {
        const container = document.createElement('div');
        container.className = 'toast-container position-fixed bottom-0 end-0 p-3';
        document.body.appendChild(container);
    }
});