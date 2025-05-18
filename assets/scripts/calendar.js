/**
 * Скрипт для работы с календарем
 * 
 * Обеспечивает функциональность календаря, включая:
 * - Управление отображением задач
 * - Обработку кликов по дням
 * - Отображение модального окна с задачами
 * - Обновление статусов задач
 * - Бесшовное переключение между месяцами
 */

// Объект для хранения всех задач по дням
const allTasks = window.calendarData ? window.calendarData.tasks : {};
// Текущий выбранный день для модального окна
let currentSelectedDate = null;

/**
 * Обрабатывает клик по дню в календаре
 * 
 * @param {Event} event - Событие клика
 * @param {string} date - Дата в формате YYYY-MM-DD
 */
function handleDayClick(event, date) {
    // Если клик на задаче или ссылке "еще задачи", даем их обработчикам работать
    if (event.target.closest('.task-item-calendar') || 
        event.target.closest('.more-tasks')) {
        return;
    }
    
    // Если клик на иконке добавления задачи, вызываем соответствующую функцию
    if (event.target.closest('.add-task-icon') || 
        event.target.classList.contains('fa-plus-circle')) {
        addTaskForDay(event, date);
        return;
    }
    
    // Для любого другого клика показываем модальное окно с задачами
    showDayTasks(event, date);
}

/**
 * Функция для добавления задачи на выбранный день
 * 
 * @param {Event|null} event - Событие клика или null
 * @param {string} date - Дата в формате YYYY-MM-DD
 */
function addTaskForDay(event, date) {
    // Останавливаем всплытие события, если вызвано из вложенного элемента
    if (event) {
        event.stopPropagation();
    }
    
    // Активируем вкладку создания задачи
    const createTab = document.getElementById('create-tab');
    if (createTab) {
        const tabTrigger = new bootstrap.Tab(createTab);
        tabTrigger.show();
        
        // Устанавливаем выбранную дату в форме
        const dateInput = document.getElementById('date');
        if (dateInput) {
            dateInput.value = date;
        }
        
        // Если был открыт модальный диалог, закрываем его
        const dayTasksModal = bootstrap.Modal.getInstance(document.getElementById('dayTasksModal'));
        if (dayTasksModal) {
            dayTasksModal.hide();
        }
        
        // Прокручиваем к форме создания задачи
        document.getElementById('create-view').scrollIntoView({ behavior: 'smooth' });
        
        // Устанавливаем фокус на поле "Тема"
        setTimeout(() => {
            const titleInput = document.getElementById('title');
            if (titleInput) {
                titleInput.focus();
            }
        }, 500);
    } else {
        // Если вкладки нет, перенаправляем на страницу создания
        window.location.href = `index.php?action=create&date=${date}`;
    }
}

/**
 * Функция просмотра задачи
 * 
 * @param {Event} event - Событие клика
 * @param {number} taskId - ID задачи
 */
function viewTask(event, taskId) {
    event.stopPropagation();
    window.location.href = `index.php?action=show&id=${taskId}`;
}

/**
 * Функция для отображения всех задач на выбранный день
 * 
 * @param {Event} event - Событие клика
 * @param {string} date - Дата в формате YYYY-MM-DD
 */
function showDayTasks(event, date) {
    if (event && typeof event.stopPropagation === 'function') {
        event.stopPropagation();
    }
    
    const modalElement = document.getElementById('dayTasksModal');
    
    // Создаем новый экземпляр или получаем существующий
    let modal = bootstrap.Modal.getInstance(modalElement);
    if (!modal) {
        modal = new bootstrap.Modal(modalElement, {
            backdrop: true,
            keyboard: true,
            focus: true
        });
    }
    
    const tasks = allTasks[date] || [];
    const dateFormatted = new Date(date).toLocaleDateString('ru-RU', {
        day: 'numeric', 
        month: 'long', 
        year: 'numeric'
    });
    
    // Сохраняем выбранную дату
    currentSelectedDate = date;
    
    // Устанавливаем дату в заголовке модального окна
    document.getElementById('modalDate').textContent = dateFormatted;
    
    // Очищаем предыдущий список задач
    const tasksList = document.getElementById('modalTasksList');
    
    if (tasksList) {
        tasksList.innerHTML = '';

        const taskDate = currentSelectedDate;
        const tasks = allTasks[taskDate] || [];
        
        // Добавляем задачи в список
        if(tasks.length > 0) {
            tasks.forEach(task => {
                const taskTime = new Date(task.scheduled_date).toLocaleTimeString('ru-RU', {
                    hour: '2-digit', 
                    minute: '2-digit'
                });
                const statusBadge = getStatusBadge(task);
                const priorityBadge = getPriorityBadge(task);
                
                // Создаем HTML для кнопок действий в зависимости от статуса
                let actionButtonsHtml = `
                    <a href="index.php?action=edit&id=${task.id}" class="btn btn-sm btn-outline-primary" onclick="event.stopPropagation();" title="Редактировать">
                        <i class="fas fa-edit"></i>
                    </a>
                    <button type="button" class="btn btn-sm btn-outline-danger" 
                           onclick="event.stopPropagation(); deleteCalendarTask(${task.id}, '${taskDate}');" title="Удалить">
                        <i class="fas fa-trash"></i>
                    </button>`;
                
                // Добавляем кнопки в зависимости от текущего статуса
                switch(parseInt(task.status_id)) {
                    case 1: // Активная
                        actionButtonsHtml += `
                            <button type="button" class="btn btn-sm btn-outline-success" 
                                    onclick="event.stopPropagation(); updateTaskStatusInModal(${task.id}, 3);" title="Отметить как выполненную">
                                <i class="fas fa-check"></i>
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-danger" 
                                    onclick="event.stopPropagation(); updateTaskStatusInModal(${task.id}, 2);" title="Отметить как просроченную">
                                <i class="fas fa-clock"></i>
                            </button>`;
                        break;
                    case 2: // Просроченная
                        actionButtonsHtml += `
                            <button type="button" class="btn btn-sm btn-outline-success" 
                                    onclick="event.stopPropagation(); updateTaskStatusInModal(${task.id}, 3);" title="Отметить как выполненную">
                                <i class="fas fa-check"></i>
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-primary" 
                                    onclick="event.stopPropagation(); updateTaskStatusInModal(${task.id}, 1);" title="Отметить как активную">
                                <i class="fas fa-play"></i>
                            </button>`;
                        break;
                    case 3: // Выполненная
                        actionButtonsHtml += `
                            <button type="button" class="btn btn-sm btn-outline-primary" 
                                    onclick="event.stopPropagation(); updateTaskStatusInModal(${task.id}, 1);" title="Отметить как активную">
                                <i class="fas fa-play"></i>
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-danger" 
                                    onclick="event.stopPropagation(); updateTaskStatusInModal(${task.id}, 2);" title="Отметить как просроченную">
                                <i class="fas fa-clock"></i>
                            </button>`;
                        break;
                }
                
                const row = document.createElement('tr');
                row.className = 'task-item';
                row.id = `task-row-${task.id}`;
                row.setAttribute('onclick', `window.location='index.php?action=show&id=${task.id}'`);
                row.setAttribute('data-task-id', task.id);
                row.setAttribute('data-status-id', task.status_id);
                
                // Формируем всю строку таблицы сразу
                row.innerHTML = `
                    <td data-label="Время">${taskTime}</td>
                    <td data-label="Тема">${task.title}</td>
                    <td data-label="Тип">${task.type_name || '-'}</td>
                    <td data-label="Статус">${statusBadge}</td>
                    <td data-label="Приоритет">${priorityBadge}</td>
                    <td data-label="Действия">
                        <div class="action-buttons">
                            ${actionButtonsHtml}
                        </div>
                    </td>
                `;
                
                tasksList.appendChild(row);
            });
        } else {
            // Если задач нет, показываем сообщение
            const row = document.createElement('tr');
            row.innerHTML = '<td colspan="6" class="text-center">На этот день нет задач</td>';
            tasksList.appendChild(row);
        }
    }
    
    // Настраиваем кнопку "Добавить задачу" в модальном окне
    const addTaskBtn = document.getElementById('addTaskModalBtn');
    if (addTaskBtn) {
        addTaskBtn.onclick = function() {
            addTaskForDay(null, currentSelectedDate);
        };
    }
    
    modal.show();
}

/**
 * Функция для удаления задачи в модальном окне календаря
 * 
 * @param {number} taskId - ID задачи
 * @param {string} taskDate - Дата задачи в формате YYYY-MM-DD
 */
function deleteCalendarTask(taskId, taskDate) {    
    // Получаем информацию о задаче
    let taskTitle = '';
    if (allTasks && allTasks[taskDate]) {
        const task = allTasks[taskDate].find(t => t.id == taskId);
        if (task) {
            taskTitle = task.title || `#${taskId}`;
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
                performCalendarTaskDelete(taskId, taskDate);
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
            () => performCalendarTaskDelete(taskId, taskDate),
            null,
            'Удалить',
            'Отмена'
        );
    }
}

/**
 * Выполняет удаление задачи из календаря
 * 
 * @param {number} taskId - ID задачи
 * @param {string} taskDate - Дата задачи в формате YYYY-MM-DD
 */
function performCalendarTaskDelete(taskId, taskDate) {
    // Показываем индикатор загрузки на затронутой строке
    const taskRow = document.getElementById(`task-row-${taskId}`);
    if (taskRow) {
        taskRow.style.opacity = '0.5';
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
                    if (taskRow) {
                        taskRow.style.transition = 'all 0.3s ease';
                        taskRow.style.opacity = '0';
                        taskRow.style.maxHeight = '0';
                        
                        setTimeout(() => {
                            taskRow.remove();
                            
                            // Проверяем, не осталось ли задач в таблице
                            const tableBody = document.querySelector('#modalTasksList');
                            if (tableBody && tableBody.children.length === 0) {
                                // Добавляем строку "Задачи не найдены"
                                const noTasksRow = document.createElement('tr');
                                noTasksRow.innerHTML = '<td colspan="6" class="text-center">На этот день нет задач</td>';
                                tableBody.appendChild(noTasksRow);
                            }
                        }, 300);
                    }
                    
                    // Удаляем задачу из локального массива задач
                    if (allTasks[taskDate]) {
                        const taskIndex = allTasks[taskDate].findIndex(t => t.id == taskId);
                        if (taskIndex !== -1) {
                            allTasks[taskDate].splice(taskIndex, 1);
                            
                            // Обновляем ячейку календаря без перезагрузки страницы
                            updateCalendarDayCell(taskDate);
                        }
                    }
                    
                    // Показываем уведомление
                    showNotification('Успех', 'Задача успешно удалена', 'success');
                } else {
                    // Восстанавливаем прозрачность элемента
                    if (taskRow) {
                        taskRow.style.opacity = '1';
                    }
                    
                    // Показываем ошибку
                    showNotification('Ошибка', response.message || 'Ошибка при удалении задачи', 'danger');
                }
            } catch (e) {
                console.error('Ошибка при обработке ответа:', e);
                
                // Восстанавливаем прозрачность элемента
                if (taskRow) {
                    taskRow.style.opacity = '1';
                }
                
                // Показываем ошибку
                showNotification('Ошибка', 'Произошла ошибка при удалении задачи', 'danger');
            }
        } else {
            // Восстанавливаем прозрачность элемента
            if (taskRow) {
                taskRow.style.opacity = '1';
            }
            
            // Показываем ошибку
            showNotification('Ошибка', 'Произошла ошибка при удалении задачи', 'danger');
        }
    };
    
    xhr.onerror = function() {
        // Восстанавливаем прозрачность элемента
        if (taskRow) {
            taskRow.style.opacity = '1';
        }
        
        // Показываем ошибку
        showNotification('Ошибка', 'Произошла ошибка сети', 'danger');
    };
    
    xhr.send();
}

/**
 * Функция для изменения статуса задачи в модальном окне
 * 
 * @param {number} taskId - ID задачи
 * @param {number} newStatusId - ID нового статуса
 */
function updateTaskStatusInModal(taskId, newStatusId) {
    // Проверка наличия параметров
    if (!taskId || !newStatusId) {
        showNotification('Ошибка', 'Недостаточно параметров для обновления статуса', 'danger');
        return;
    }

    // Выбор названия статуса для диалога подтверждения
    let statusName = '';
    let statusTitleName = '';
    switch(newStatusId) {
        case 1: 
            statusName = 'активную'; 
            statusTitleName = 'активная';
            break;
        case 2: 
            statusName = 'просроченную'; 
            statusTitleName = 'просроченная';
            break;
        case 3: 
            statusName = 'выполненную'; 
            statusTitleName = 'выполненная';
            break;
        default: 
            showNotification('Ошибка', 'Неизвестный статус', 'danger');
            return;
    }
    
    // Получаем информацию о задаче
    let taskTitle = '';
    const taskDate = currentSelectedDate;
    if (allTasks && allTasks[taskDate]) {
        const task = allTasks[taskDate].find(t => t.id == taskId);
        if (task) {
            taskTitle = task.title || `#${taskId}`;
        }
    }

    // Используем функцию showConfirmation для кастомного подтверждения
    showConfirmation(
        'Изменение статуса', 
        `Отметить задачу${taskTitle ? ` "${taskTitle}"` : ''} как ${statusName}?`,
        function() { 
            performStatusUpdate(taskId, newStatusId, taskDate, statusName, statusTitleName);
        }
    );
}

/**
 * Выполняет обновление статуса задачи
 * 
 * @param {number} taskId - ID задачи
 * @param {number} newStatusId - ID нового статуса
 * @param {string} taskDate - Дата задачи в формате YYYY-MM-DD
 * @param {string} statusName - Название статуса в родительном падеже (активную, просроченную)
 * @param {string} statusTitleName - Название статуса в именительном падеже (активная, просроченная)
 */
function performStatusUpdate(taskId, newStatusId, taskDate, statusName, statusTitleName) {
    // Показываем индикатор загрузки на затронутой строке
    const taskRow = document.getElementById(`task-row-${taskId}`);
    if (taskRow) {
        const oldContent = taskRow.innerHTML;
        taskRow.innerHTML = `<td colspan="6" class="text-center"><div class="spinner-border spinner-border-sm text-primary" role="status"></div> Обновление...</td>`;
        
        fetch('api/handler.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `action=change_status&task_id=${taskId}&status_id=${newStatusId}`
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('Ошибка сети');
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                // Обновляем статус задачи в локальном массиве задач
                if (allTasks[taskDate]) {
                    const taskIndex = allTasks[taskDate].findIndex(t => t.id == taskId);
                    if (taskIndex !== -1) {
                        // Обновляем статус в локальных данных
                        allTasks[taskDate][taskIndex].status_id = newStatusId;
                        allTasks[taskDate][taskIndex].status_name = statusTitleName;
                        
                        showDayTasks({ stopPropagation: () => {} }, taskDate);
                        
                        showNotification('Успех', `Задача отмечена как ${statusName}`, 'success');
                        
                        updateCalendarDayCell(taskDate);
                    }
                }
            } else {
                // Восстанавливаем исходное содержимое при ошибке
                taskRow.innerHTML = oldContent;
                showNotification('Ошибка', data.message || 'Не удалось обновить статус задачи', 'danger');
            }
        })
        .catch(error => {
            // Восстанавливаем исходное содержимое при ошибке
            taskRow.innerHTML = oldContent;
            console.error('Ошибка:', error);
            showNotification('Ошибка', 'Произошла ошибка при обновлении задачи', 'danger');
        });
    }
}

/**
 * Функция для обновления ячейки календаря без перезагрузки страницы
 * 
 * @param {string} date - Дата в формате YYYY-MM-DD
 */
function updateCalendarDayCell(date) {
    // Находим ячейку календаря для этой даты
    const calendarCells = document.querySelectorAll('.calendar-table td');
    let cellToUpdate = null;
    
    for (const cell of calendarCells) {
        if (cell.querySelector('.day-number') && 
            cell.getAttribute('onclick') && 
            cell.getAttribute('onclick').includes(date)) {
            cellToUpdate = cell;
            break;
        }
    }
    
    if (!cellToUpdate) return;
    
    // Получаем задачи для этой даты
    const tasks = allTasks[date] || [];
    
    // Очищаем существующий контейнер задач
    const tasksContainer = cellToUpdate.querySelector('.tasks-container');
    if (tasksContainer) {
        tasksContainer.innerHTML = '';
    } else {
        // Если контейнера нет, создаем его
        const newTasksContainer = document.createElement('div');
        newTasksContainer.className = 'tasks-container';
        cellToUpdate.appendChild(newTasksContainer);
    }
    
    // Обновляем бейдж с количеством задач
    const dayNumber = cellToUpdate.querySelector('.day-number');
    if (dayNumber) {
        // Удаляем существующий бейдж, если он есть
        const existingBadge = dayNumber.querySelector('.task-count');
        if (existingBadge) {
            existingBadge.remove();
        }
        
        // Добавляем новый бейдж, если есть задачи
        if (tasks.length > 0) {
            const taskCountBadge = document.createElement('span');
            taskCountBadge.className = 'task-count';
            taskCountBadge.title = `${tasks.length} задач(и)`;
            taskCountBadge.textContent = tasks.length;
            dayNumber.appendChild(taskCountBadge);
        }
    }
    
    // Перестраиваем задачи в ячейке
    if (tasks.length > 0 && tasksContainer) {
        const maxVisibleTasks = 3;
        const visibleTasks = tasks.slice(0, maxVisibleTasks);
        const hiddenTasksCount = tasks.length - maxVisibleTasks;
        
        // Добавляем видимые задачи
        visibleTasks.forEach(task => {
            const statusColor = getStatusColor(task);
            const taskEl = document.createElement('div');
            taskEl.className = 'task-item-calendar';
            taskEl.setAttribute('onclick', `viewTask(event, ${task.id})`);
            taskEl.style.borderLeft = `4px solid ${statusColor}`;
            
            taskEl.innerHTML = `
                <div class="task-time">
                    ${new Date(task.scheduled_date).toLocaleTimeString('ru-RU', {hour: '2-digit', minute: '2-digit'})}
                </div>
                <div class="task-title" title="${task.title}">
                    ${shortenText(task.title, 20)}
                </div>
            `;
            
            tasksContainer.appendChild(taskEl);
        });
        
        if (hiddenTasksCount > 0) {
            const moreTasksEl = document.createElement('div');
            moreTasksEl.className = 'more-tasks';
            moreTasksEl.setAttribute('onclick', `showDayTasks(event, '${date}')`);
            moreTasksEl.innerHTML = `<i class="fas fa-ellipsis-h"></i> Ещё ${hiddenTasksCount} задач...`;
            tasksContainer.appendChild(moreTasksEl);
        }
    }
}

/**
 * Функция для создания бейджа статуса
 * 
 * @param {Object} task - Объект задачи
 * @returns {string} HTML-код бейджа
 */
function getStatusBadge(task) {
    let color, label;
    
    switch(parseInt(task.status_id)) {
        case 1:
            color = 'primary';
            label = 'Активная';
            break;
        case 2:
            color = 'danger';
            label = 'Просроченная';
            break;
        case 3:
            color = 'success';
            label = 'Выполненная';
            break;
        default:
            color = 'secondary';
            label = task.status_name || 'Не указан';
    }
    
    return `<span class="badge bg-${color}">${label}</span>`;
}

/**
 * Функция для создания бейджа приоритета
 * 
 * @param {Object} task - Объект задачи
 * @returns {string} HTML-код бейджа
 */
function getPriorityBadge(task) {
    if(task.priority_name && task.priority_color) {
        return `<span class="badge" style="background-color: ${task.priority_color}">${task.priority_name}</span>`;
    } else {
        return '<span class="badge bg-secondary">Не указан</span>';
    }
}

/**
 * Загружает данные календаря для указанного месяца/года
 * 
 * @param {number} month - Номер месяца (1-12)
 * @param {number} year - Год
 */
function loadCalendar(month, year) {
    // Показываем индикатор загрузки
    const calendarBody = document.getElementById('calendarBody');
    if (calendarBody) {
        calendarBody.innerHTML = '<tr><td colspan="7" class="text-center p-5"><div class="spinner-border text-primary" role="status"></div><p class="mt-3">Загрузка календаря...</p></td></tr>';
    }

    // Отправляем запрос на получение данных календаря
    fetch(`api/calendar_data.php?month=${month}&year=${year}`)
        .then(response => {
            if (!response.ok) {
                throw new Error('Ошибка сети');
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                // Обновляем глобальный объект с задачами
                window.calendarData = {
                    tasks: {}
                };
                
                // Заполняем задачи по дням
                const calendar = data.calendar;
                if (calendar) {
                    for (const week of calendar) {
                        for (const day of week) {
                            if (day.tasks && day.tasks.length > 0) {
                                window.calendarData.tasks[day.date] = day.tasks;
                            }
                        }
                    }
                }
                
                // Обновляем заголовок календаря
                const calendarTitle = document.getElementById('calendarTitle');
                if (calendarTitle) {
                    calendarTitle.textContent = `${data.monthName} ${data.year}`;
                }
                
                // Обновляем кнопки навигации
                const prevMonthBtn = document.getElementById('prevMonthBtn');
                const nextMonthBtn = document.getElementById('nextMonthBtn');
                
                if (prevMonthBtn) {
                    prevMonthBtn.setAttribute('data-month', data.prevMonth);
                    prevMonthBtn.setAttribute('data-year', data.prevYear);
                }
                
                if (nextMonthBtn) {
                    nextMonthBtn.setAttribute('data-month', data.nextMonth);
                    nextMonthBtn.setAttribute('data-year', data.nextYear);
                }
                
                // Обновляем содержимое календаря
                updateCalendarContent(data.calendar);
                
                // Обновляем URL для bookmarking
                updateUrl(data.month, data.year);
            } else {
                showNotification('Ошибка', data.message || 'Не удалось загрузить календарь', 'danger');
            }
        })
        .catch(error => {
            console.error('Ошибка:', error);
            showNotification('Ошибка', 'Произошла ошибка при загрузке календаря', 'danger');
            
            // Восстанавливаем календарь в случае ошибки
            if (calendarBody) {
                calendarBody.innerHTML = '<tr><td colspan="7" class="text-center text-danger">Ошибка загрузки календаря</td></tr>';
            }
        });
}

/**
 * Обновляет содержимое календаря на основе полученных данных
 * 
 * @param {Array} calendar - Массив недель и дней
 */
function updateCalendarContent(calendar) {
    const calendarBody = document.getElementById('calendarBody');
    if (!calendarBody || !calendar) return;
    
    // Очищаем текущее содержимое
    calendarBody.innerHTML = '';
    
    // Добавляем новые данные
    for (const week of calendar) {
        const weekRow = document.createElement('tr');
        
        for (const day of week) {
            const dayCell = document.createElement('td');
            dayCell.className = day.class;
            
            if (day.date === new Date().toISOString().split('T')[0]) {
                dayCell.classList.add('today');
            }
            
            dayCell.setAttribute('onclick', `handleDayClick(event, '${day.date}')`);
            
            // Создаем контент ячейки
            const dayNumberDiv = document.createElement('div');
            dayNumberDiv.className = 'day-number';
            dayNumberDiv.textContent = day.day;
            
            // Добавляем счетчик задач, если есть
            if (day.tasks && day.tasks.length > 0) {
                const taskCountSpan = document.createElement('span');
                taskCountSpan.className = 'task-count';
                taskCountSpan.title = `${day.tasks.length} задач(и)`;
                taskCountSpan.textContent = day.tasks.length;
                dayNumberDiv.appendChild(taskCountSpan);
            }
            
            // Добавляем иконку добавления задачи
            const addTaskIcon = document.createElement('span');
            addTaskIcon.className = 'add-task-icon';
            addTaskIcon.title = 'Добавить задачу на этот день';
            addTaskIcon.setAttribute('onclick', `addTaskForDay(event, '${day.date}')`);
            addTaskIcon.innerHTML = '<i class="fas fa-plus-circle"></i>';
            dayNumberDiv.appendChild(addTaskIcon);
            
            dayCell.appendChild(dayNumberDiv);
            
            // Добавляем контейнер задач, если есть задачи
            if (day.tasks && day.tasks.length > 0) {
                const tasksContainer = document.createElement('div');
                tasksContainer.className = 'tasks-container';
                
                const maxVisibleTasks = 3;
                const visibleTasks = day.tasks.slice(0, maxVisibleTasks);
                const hiddenTasksCount = day.tasks.length - maxVisibleTasks;
                
                // Добавляем видимые задачи
                for (const task of visibleTasks) {
                    const statusColor = getStatusColor(task);
                    const taskTime = new Date(task.scheduled_date).toLocaleTimeString('ru-RU', {
                        hour: '2-digit',
                        minute: '2-digit'
                    });
                    
                    const taskItem = document.createElement('div');
                    taskItem.className = 'task-item-calendar';
                    taskItem.setAttribute('onclick', `viewTask(event, ${task.id})`);
                    taskItem.style.borderLeft = `4px solid ${statusColor}`;
                    
                    taskItem.innerHTML = `
                        <div class="task-time">${taskTime}</div>
                        <div class="task-title" title="${task.title}">${shortenText(task.title, 20)}</div>
                    `;
                    
                    tasksContainer.appendChild(taskItem);
                }
                
                // Добавляем ссылку "Еще задачи", если есть скрытые задачи
                if (hiddenTasksCount > 0) {
                    const moreTasksLink = document.createElement('div');
                    moreTasksLink.className = 'more-tasks';
                    moreTasksLink.setAttribute('onclick', `showDayTasks(event, '${day.date}')`);
                    moreTasksLink.innerHTML = `<i class="fas fa-ellipsis-h"></i> Ещё ${hiddenTasksCount} задач...`;
                    tasksContainer.appendChild(moreTasksLink);
                }
                
                dayCell.appendChild(tasksContainer);
            }
            
            weekRow.appendChild(dayCell);
        }
        
        calendarBody.appendChild(weekRow);
    }
}

/**
 * Обновляет URL страницы без перезагрузки
 * 
 * @param {number} month - Номер месяца (1-12)
 * @param {number} year - Год
 */
function updateUrl(month, year) {
    const url = new URL(window.location.href);
    url.searchParams.set('month', month);
    url.searchParams.set('year', year);
    window.history.pushState({ month, year }, '', url.toString());
}

/**
 * Обработчик для события popstate (навигация по истории браузера)
 */
function handlePopState(event) {
    const url = new URL(window.location.href);
    const month = url.searchParams.get('month') || new Date().getMonth() + 1;
    const year = url.searchParams.get('year') || new Date().getFullYear();
    
    loadCalendar(parseInt(month), parseInt(year));
}

/**
 * Функция для получения цвета статуса задачи
 * 
 * @param {Object} task - Объект задачи
 * @returns {string} Цвет в формате HEX
 */
function getStatusColor(task) {
    switch(parseInt(task.status_id)) {
        case 1: // Активная
            return '#007bff';
        case 2: // Просроченная
            return '#dc3545';
        case 3: // Выполненная
            return '#28a745';
        default:
            return '#6c757d';
    }
}

/**
 * Функция для сокращения текста
 * 
 * @param {string} text - Исходный текст
 * @param {number} length - Максимальная длина
 * @returns {string} Сокращенный текст
 */
function shortenText(text, length = 20) {
    return (text && text.length > length) ? text.substring(0, length) + '...' : text;
}

/**
 * Инициализация календаря при загрузке страницы
 */
document.addEventListener('DOMContentLoaded', function() {
    // Получаем элемент модального окна
    const dayTasksModal = document.getElementById('dayTasksModal');
    
    if (dayTasksModal) {
        // Добавляем обработчик события для полного закрытия модального окна
        dayTasksModal.addEventListener('hidden.bs.modal', function() {
            // Убедимся, что фон модального окна удален
            const backdrop = document.querySelector('.modal-backdrop');
            if (backdrop) {
                backdrop.remove();
            }
            
            // Удаляем класс modal-open с тела документа
            document.body.classList.remove('modal-open');
            document.body.style.overflow = '';
            document.body.style.paddingRight = '';
        });
    }
    
    // Проверка наличия параметра date в URL (например, при возврате после создания)
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.has('date')) {
        const date = urlParams.get('date');
        // Устанавливаем дату в форме создания
        const dateInput = document.getElementById('date');
        if (dateInput) {
            dateInput.value = date;
        }
    }
    
    // Инициализация подсказок
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
    
    // Добавляем обработчики для кнопок навигации календаря
    const prevMonthBtn = document.getElementById('prevMonthBtn');
    const nextMonthBtn = document.getElementById('nextMonthBtn');
    
    if (prevMonthBtn) {
        prevMonthBtn.addEventListener('click', function() {
            const month = parseInt(this.getAttribute('data-month'));
            const year = parseInt(this.getAttribute('data-year'));
            loadCalendar(month, year);
        });
    }
    
    if (nextMonthBtn) {
        nextMonthBtn.addEventListener('click', function() {
            const month = parseInt(this.getAttribute('data-month'));
            const year = parseInt(this.getAttribute('data-year'));
            loadCalendar(month, year);
        });
    }
    
    // Добавляем обработчик для навигации по истории браузера
    window.addEventListener('popstate', handlePopState);
});