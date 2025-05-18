/**
 * Скрипт для работы с задачами
 * 
 * Обеспечивает функциональность работы со списком задач, включая:
 * - Изменение статуса задач
 * - Фильтрацию задач по дате
 * - Проверку просроченных задач
 */

/**
 * Изменяет статус задачи
 * 
 * @param {number} taskId - ID задачи
 * @param {number} statusId - ID нового статуса
 * @param {boolean} refreshCalendar - Обновлять ли календарь после изменения
 * @param {string} redirectTo - URL для перенаправления после обновления
 */
function markTaskStatus(taskId, statusId, refreshCalendar = true, redirectTo = '') {
    // Определение названия статуса для подтверждения
    let statusName = '';
    switch(statusId) {
        case 1: statusName = 'активная'; break;
        case 2: statusName = 'просроченная'; break;
        case 3: statusName = 'выполненная'; break;
        default: 
            console.error('Неизвестный статус:', statusId);
            return;
    }
    
    // Кодируем URL для передачи в запросе
    const encodedRedirectTo = encodeURIComponent(redirectTo);
    
    // Запрос подтверждения у пользователя
    showConfirmation(
        'Изменение статуса', 
        `Отметить задачу как ${statusName}?`,
        function() {
            // Отправка запроса на сервер после подтверждения
            updateTaskStatus(taskId, statusId, refreshCalendar, encodedRedirectTo);
        }
    );
}

/**
 * Выполняет AJAX-запрос к серверу для изменения статуса задачи
 * 
 * @param {number} taskId - ID задачи
 * @param {number} statusId - ID нового статуса
 * @param {boolean} refreshCalendar - Обновлять ли календарь после изменения
 * @param {string} redirectTo - URL для перенаправления после обновления
 */
function updateTaskStatus(taskId, statusId, refreshCalendar, redirectTo) {
    // Показываем прогресс-бар или спиннер
    // (в будущем можно добавить)
    
    // Определение названия статуса для уведомления
    let statusName = '';
    switch(statusId) {
        case 1: statusName = 'активную'; break;
        case 2: statusName = 'просроченную'; break;
        case 3: statusName = 'выполненную'; break;
    }
    
    // Отправка запроса на сервер
    fetch('api/handler.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: `action=change_status&task_id=${taskId}&status_id=${statusId}&redirect_to=${redirectTo}`
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('Ошибка сети');
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            showNotification('Успех', `Задача отмечена как ${statusName}`, 'success');
            
            // Обновляем страницу или перенаправляем после небольшой задержки
            setTimeout(() => {
                if (data.redirect_to && data.redirect_to !== '') {
                    window.location.href = data.redirect_to;
                } else if (refreshCalendar) {
                    window.location.reload();
                }
            }, 1000);
        } else {
            showNotification('Ошибка', data.message || 'Неизвестная ошибка', 'danger');
        }
    })
    .catch(error => {
        console.error('Ошибка:', error);
        showNotification('Ошибка', 'Произошла ошибка при обновлении задачи', 'danger');
    });
}

/**
 * Функция для проверки просроченных задач
 */
function checkOverdueTasks() {
    fetch('api/handler.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: 'action=check_overdue'
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('Ошибка сети');
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            // Если есть просроченные задачи, обновляем счетчик
            if (data.overdue_count > 0) {
                const overdueLink = document.querySelector('a[href="index.php?action=overdue"]');
                if (overdueLink) {
                    // Добавляем бейдж с количеством просроченных задач или обновляем существующий
                    const existingBadge = overdueLink.querySelector('.badge');
                    if (!existingBadge) {
                        overdueLink.innerHTML += ` <span class="badge bg-danger">${data.overdue_count}</span>`;
                    } else {
                        existingBadge.textContent = data.overdue_count;
                    }
                }
                
                // Показываем уведомление
                showNotification('Внимание', 'У вас есть просроченные задачи', 'warning');
            }
        } else {
            console.error('Ошибка при проверке просроченных задач:', data.message);
        }
    })
    .catch(error => {
        console.error('Ошибка при проверке просроченных задач:', error);
    });
}

// Обработка запросов изменения статуса задачи
function handleStatusChange(event, taskId, statusId, redirectTo) {
    // Предотвращаем действия по умолчанию (например, переход по ссылке)
    if (event) {
        event.preventDefault();
        event.stopPropagation();
    }
    
    // Показываем индикатор загрузки
    const target = event ? event.currentTarget : null;
    if (target) {
        const originalHtml = target.innerHTML;
        target.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>';
        target.disabled = true;
    }
    
    // Отправляем AJAX-запрос
    fetch('api/handler.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: `action=change_status&task_id=${taskId}&status_id=${statusId}&redirect_to=${redirectTo}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Показываем уведомление об успехе
            showNotification('Успех', 'Статус задачи успешно обновлен', 'success');
            
            // Перезагрузка страницы или обновление списка задач
            setTimeout(() => {
                if (data.redirect_to) {
                    window.location.href = data.redirect_to;
                } else {
                    location.reload();
                }
            }, 1000);
        } else {
            // Восстанавливаем кнопку
            if (target) {
                target.innerHTML = originalHtml;
                target.disabled = false;
            }
            
            // Показываем уведомление об ошибке
            showNotification('Ошибка', data.message || 'Произошла ошибка при обновлении статуса', 'danger');
        }
    })
    .catch(error => {
        console.error('Ошибка:', error);
        
        // Восстанавливаем кнопку
        if (target) {
            target.innerHTML = originalHtml;
            target.disabled = false;
        }
        
        // Показываем уведомление об ошибке
        showNotification('Ошибка', 'Произошла ошибка при обновлении задачи', 'danger');
    });
}

/**
 * Удаляет фильтр из URL и перезагружает страницу
 * 
 * @param {string} filterName - Имя параметра фильтра для удаления
 */
function removeFilter(filterName) {
    // Получаем текущие параметры URL
    const url = new URL(window.location.href);
    const params = new URLSearchParams(url.search);
    
    // Удаляем указанный фильтр
    params.delete(filterName);
    
    // Если удалены все фильтры, но остался параметр action=combinedSearch, 
    // перенаправляем на главную или текущую вкладку
    if (!params.has('keyword') && !params.has('date') && !params.has('status_id') && 
        !params.has('priority_id') && params.get('action') === 'combinedSearch') {
        if (params.has('tab')) {
            window.location.href = 'index.php?action=index&tab=' + params.get('tab');
        } else {
            window.location.href = 'index.php?action=index';
        }
        return;
    }
    
    // Обновляем URL с оставшимися параметрами
    url.search = params.toString();
    window.location.href = url.toString();
}

/**
 * Инициализация при загрузке страницы
 */
document.addEventListener('DOMContentLoaded', function() {
    // Отслеживаем клики по вкладкам, чтобы сохранять параметр tab в URL без перезагрузки
    const tabButtons = document.querySelectorAll('#viewTabs .nav-link');
    tabButtons.forEach(button => {
        button.addEventListener('click', function() {
            const tabId = this.id.replace('-tab', '');
            const url = new URL(window.location.href);
            url.searchParams.set('tab', tabId);
            history.pushState({}, '', url);
        });
    });

    // Фильтры
    
    // Применяем стили к селектам фильтров
    styleFilterSelects();

    const statusFilterForm = document.getElementById('statusFilterForm');
    const priorityFilterForm = document.getElementById('priorityFilterForm');
    const keywordSearchForm = document.getElementById('keywordSearchForm');
    
    // Предотвращение отправки пустых форм фильтров статуса
    if (statusFilterForm) {
        statusFilterForm.addEventListener('submit', function(e) {
            const statusFilter = this.querySelector('[name="status_id"]');
            if (!statusFilter || !statusFilter.value) {
                e.preventDefault(); // Останавливаем отправку формы
                showNotification('Предупреждение', 'Выберите статус', 'warning');
                return false;
            }
        });
    }
    
    // Предотвращение отправки пустых форм фильтров приоритета
    if (priorityFilterForm) {
        priorityFilterForm.addEventListener('submit', function(e) {
            const priorityFilter = this.querySelector('[name="priority_id"]');
            if (!priorityFilter || !priorityFilter.value) {
                e.preventDefault(); // Останавливаем отправку формы
                showNotification('Предупреждение', 'Выберите приоритет', 'warning');
                return false;
            }
        });
    }

    // Предотвращение отправки пустых форм поиска по ключевому слову
    if (keywordSearchForm) {
        keywordSearchForm.addEventListener('submit', function(e) {
            const keywordInput = this.querySelector('[name="keyword"]');
            if (!keywordInput || !keywordInput.value.trim()) {
                e.preventDefault(); // Останавливаем отправку формы
                showNotification('Предупреждение', 'Введите текст для поиска', 'warning');
                return false;
            }
        });
    }

    // Обработчик кнопки фильтра по дате
    const filterDateBtn = document.getElementById('filterDateBtn');
    if (filterDateBtn) {
        filterDateBtn.addEventListener('click', function() {
            const dateForm = document.getElementById('dateSearchForm');
            if (dateForm) {
                const dateInput = dateForm.querySelector('input[name="date"]');
                if (dateInput && dateInput.value) {
                    try {
                        dateForm.submit();
                    } catch (e) {
                        console.error('Ошибка при отправке формы даты:', e);
                        window.location.href = 'index.php?action=index';
                    }
                } else {
                    showNotification('Предупреждение', 'Выберите дату для фильтрации', 'warning');
                }
            } else {
                // Если форма не найдена, редиректим на главную
                console.error('Форма dateSearchForm не найдена');
                window.location.href = 'index.php?action=index';
            }
        });
    }
    
    if (!document.querySelector('.toast-container')) {
        const container = document.createElement('div');
        container.className = 'toast-container position-fixed bottom-0 end-0 p-3';
        document.body.appendChild(container);
    }
    
    checkOverdueTasks();
    
    // Настраиваем периодическую проверку просроченных задач (каждые 5 минут)
    setInterval(checkOverdueTasks, 300000);
});

/**
 * Стилизует выпадающие списки фильтров
 */
function styleFilterSelects() {
    // Стилизация фильтра приоритетов
    const priorityFilter = document.getElementById('priorityFilter');
    if (priorityFilter) {
        const applyPriorityStyle = function() {
            priorityFilter.classList.add('has-value');
            const selectedOption = priorityFilter.options[priorityFilter.selectedIndex];
            if (selectedOption && selectedOption.style.backgroundColor) {
                priorityFilter.style.borderLeft = `4px solid ${selectedOption.style.backgroundColor}`;
            } else {
                priorityFilter.style.borderLeft = '';
            }
        };
        
        // Применяем стили сразу при загрузке
        applyPriorityStyle();
        
        // Добавляем обработчик события без автоматической отправки формы
        priorityFilter.addEventListener('change', function(e) {
            applyPriorityStyle();
            
            // Отправляем форму только если есть значение
            const form = document.getElementById('priorityFilterForm');
            if (form) {
                if (!this.value) {
                    removeFilter('priority_id');
                }
            }
        });
    }
    
    // Стилизация фильтра статусов
    const statusFilter = document.getElementById('statusFilter');
    if (statusFilter) {
        const statusColors = {
            '1': '#007bff',  // Активная - синий
            '2': '#dc3545',  // Просроченная - красный
            '3': '#28a745'   // Выполненная - зеленый
        };
        
        const applyStatusStyle = function() {
            statusFilter.classList.add('has-value');
            const selectedValue = statusFilter.value;
            if (selectedValue && statusColors[selectedValue]) {
                statusFilter.style.borderLeft = `4px solid ${statusColors[selectedValue]}`;
            } else {
                statusFilter.style.borderLeft = '';
            }
        };
        
        // Применяем стили сразу при загрузке
        applyStatusStyle();
        
        // Добавляем обработчик события без автоматической отправки формы
        statusFilter.addEventListener('change', function(e) {
            applyStatusStyle();
            
            // Отправляем форму только если есть значение
            const form = document.getElementById('statusFilterForm');
            if (form) {
                if (!this.value) {
                    removeFilter('status_id');
                }
            }
        });
    }
}


/**
 * Функция для удаления задачи через AJAX
 * 
 * @param {number} taskId - ID задачи для удаления
 * @param {Element} taskElement - HTML-элемент строки задачи, который нужно удалить из DOM
 * @param {boolean} showConfirmation - Показывать ли диалог подтверждения
 */
function deleteTask(taskId, taskElement, showConfirmation = true) {
    // Функция для выполнения удаления
    const performDelete = () => {
        // Показываем индикатор загрузки
        if (taskElement) {
            taskElement.style.opacity = '0.5';
        }
        
        // Создаем объект для AJAX-запроса
        const xhr = new XMLHttpRequest();
        xhr.open('GET', `index.php?action=delete&id=${taskId}`, true);
        xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
        
        xhr.onload = function() {
            if (xhr.status === 200) {
                try {
                    const response = JSON.parse(xhr.responseText);
                    
                    if (response.success) {
                        // Плавно удаляем элемент из DOM
                        if (taskElement) {
                            taskElement.style.transition = 'all 0.3s ease';
                            taskElement.style.opacity = '0';
                            taskElement.style.maxHeight = '0';
                            
                            setTimeout(() => {
                                taskElement.remove();
                                
                                // Проверяем, не осталось ли задач в таблице
                                const tableBody = document.querySelector('.table tbody');
                                if (tableBody && tableBody.children.length === 0) {
                                    // Добавляем строку "Задачи не найдены"
                                    const noTasksRow = document.createElement('tr');
                                    noTasksRow.innerHTML = '<td colspan="8" class="text-center">Задачи не найдены</td>';
                                    tableBody.appendChild(noTasksRow);
                                }
                            }, 300);
                        }
                        
                        // Показываем уведомление
                        showNotification('Успех', 'Задача успешно удалена', 'success');
                        
                        // Если мы на странице просмотра задачи, перенаправляем на календарь
                        if (window.location.href.includes('action=show')) {
                            setTimeout(() => {
                                window.location.href = 'index.php?action=index';
                            }, 1000);
                        }
                    } else {
                        // Восстанавливаем прозрачность элемента
                        if (taskElement) {
                            taskElement.style.opacity = '1';
                        }
                        
                        // Показываем ошибку
                        showNotification('Ошибка', response.message || 'Ошибка при удалении задачи', 'danger');
                    }
                } catch (e) {
                    console.error('Ошибка при обработке ответа:', e);
                    
                    // Восстанавливаем прозрачность элемента
                    if (taskElement) {
                        taskElement.style.opacity = '1';
                    }
                    
                    // Показываем ошибку
                    showNotification('Ошибка', 'Произошла ошибка при удалении задачи', 'danger');
                }
            } else {
                // Восстанавливаем прозрачность элемента
                if (taskElement) {
                    taskElement.style.opacity = '1';
                }
                
                // Показываем ошибку
                showNotification('Ошибка', 'Произошла ошибка при удалении задачи', 'danger');
            }
        };
        
        xhr.onerror = function() {
            // Восстанавливаем прозрачность элемента
            if (taskElement) {
                taskElement.style.opacity = '1';
            }
            
            // Показываем ошибку
            showNotification('Ошибка', 'Произошла ошибка сети', 'danger');
        };
        
        xhr.send();
    };
    
    // Проверяем, нужно ли показать подтверждение
    if (showConfirmation) {
        // Получаем название задачи, если возможно
        let taskTitle = '';
        if (taskElement) {
            // Пытаемся найти название задачи в DOM-элементе
            const titleCell = taskElement.querySelector('[data-label="Задача"]');
            if (titleCell) {
                taskTitle = titleCell.textContent.trim();
            }
        }
        
        // Используем глобальное модальное окно
        const modalEl = document.getElementById('deleteTaskModal');
        if (modalEl) {
            // Обновляем содержимое модального окна
            const taskTitleEl = modalEl.querySelector('#deleteTaskTitle');
            if (taskTitleEl) {
                taskTitleEl.textContent = taskTitle || `#${taskId}`;
            }
            
            // Настраиваем кнопку подтверждения
            const confirmBtn = modalEl.querySelector('#confirmDeleteButton');
            if (confirmBtn) {
                // Удаляем предыдущие обработчики
                const newConfirmBtn = confirmBtn.cloneNode(true);
                confirmBtn.parentNode.replaceChild(newConfirmBtn, confirmBtn);
                
                // Добавляем новый обработчик
                newConfirmBtn.addEventListener('click', function() {
                    performDelete();
                    // Закрываем модальное окно
                    const bootstrapModal = bootstrap.Modal.getInstance(modalEl);
                    if (bootstrapModal) {
                        bootstrapModal.hide();
                    }
                });
            }
            
            // Показываем модальное окно
            const modal = new bootstrap.Modal(modalEl);
            modal.show();
        } else {
            // Если модальное окно не найдено, используем showConfirmation
            showConfirmation(
                'Подтверждение удаления',
                `Вы действительно хотите удалить задачу${taskTitle ? ` "${taskTitle}"` : ''}? Это действие невозможно отменить.`,
                performDelete,
                null,
                'Удалить',
                'Отмена'
            );
        }
    } else {
        performDelete();
    }
}