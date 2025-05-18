/**
 * Обработка форм через AJAX
 * 
 *
 */
document.addEventListener('DOMContentLoaded', function() {
    // Находим все формы, которые нужно обработать через AJAX
    setupAjaxForm('form[action="index.php?action=updateProfile"]', handleProfileUpdate);
    setupAjaxForm('form[action="index.php?action=changePassword"]', handlePasswordChange);
    setupAjaxForm('form[action="index.php?action=store"]', handleTaskCreate);
    setupAjaxForm('form[action="index.php?action=update&id"]', handleTaskUpdate);
    
    // Добавляем клиентскую валидацию для формы смены пароля
    setupPasswordValidation();
});


/**
 * Настройка формы для AJAX отправки
 * 
 * @param {string} selector - CSS селектор для поиска формы
 * @param {Function} successCallback - Функция обработки успешного ответа
 */
function setupAjaxForm(selector, successCallback) {
    const forms = document.querySelectorAll(selector);
    
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            e.preventDefault(); // Предотвращаем стандартную отправку формы
            
            // Проверяем, есть ли у формы валидация, и она не прошла
            if (form.checkValidity() === false) {
                e.stopPropagation();
                form.classList.add('was-validated');
                return;
            }
            
            // Для формы смены пароля - дополнительная проверка
            if (form.action.includes('action=changePassword')) {
                const currentPasswordField = form.querySelector('input[name="current_password"]');
                const newPasswordField = form.querySelector('input[name="new_password"]');
                const confirmPasswordField = form.querySelector('input[name="confirm_password"]');
                
                if (newPasswordField && confirmPasswordField) {
                    const MIN_PASSWORD_LENGTH = 8;
                    const isCurrentPasswordValid = validateRequiredField(currentPasswordField);
                    const isNewPasswordValid = validateRequiredField(newPasswordField) && 
                                            validatePasswordLength(newPasswordField, MIN_PASSWORD_LENGTH);
                    const isConfirmPasswordValid = validateRequiredField(confirmPasswordField) && 
                                                validatePasswordsMatch(newPasswordField, confirmPasswordField);
                    
                    if (!isCurrentPasswordValid || !isNewPasswordValid || !isConfirmPasswordValid) {
                        return;
                    }
                }
            }
            
            // Собираем данные формы
            const formData = new FormData(form);
            
            // Добавляем индикатор загрузки
            const submitButton = form.querySelector('button[type="submit"]');
            const originalButtonText = submitButton.innerHTML;
            submitButton.disabled = true;
            submitButton.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Загрузка...';
            
            // Отправляем запрос
            fetch(form.action, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => {
                // Проверяем, что ответ в формате JSON
                const contentType = response.headers.get('content-type');
                if (contentType && contentType.includes('application/json')) {
                    return response.json().then(data => {
                        // Добавляем поле ok из response в data для дополнительной проверки
                        return { ...data, responseOk: response.ok };
                    });
                } else {
                    // Если сервер не вернул JSON, это может быть ошибка или редирект
                    // Проверяем HTTP статус
                    if (response.ok) {
                        // Можно перейти на новую страницу, если сервер вернул HTML
                        return {
                            success: true,
                            responseOk: true,
                            redirect: response.url
                        };
                    } else {
                        throw new Error('Сервер вернул неверный формат ответа');
                    }
                }
            })
            .then(data => {
                // Восстанавливаем кнопку
                submitButton.disabled = false;
                submitButton.innerHTML = originalButtonText;
                
                // Проверяем, является ли ответ успешным - учитываем как success из JSON,
                // так и HTTP статус (responseOk)
                if (data.success === true && data.responseOk !== false) {
                    // Вызываем функцию для обработки успешного ответа
                    successCallback(data, form);
                } else {
                    // Выводим сообщение об ошибке
                    showNotification('Ошибка', data.message || 'Произошла ошибка при обработке запроса', 'danger');
                    
                    // Для формы смены пароля обрабатываем ошибку текущего пароля
                    if (form.action.includes('action=changePassword')) {
                        const currentPasswordField = form.querySelector('input[name="current_password"]');
                        if (currentPasswordField && data.message && data.message.toLowerCase().includes('текущий пароль')) {
                            // Показываем ошибку на поле текущего пароля
                            currentPasswordField.classList.add('is-invalid');
                            
                            let errorDiv = currentPasswordField.nextElementSibling;
                            if (!errorDiv || !errorDiv.classList.contains('invalid-feedback')) {
                                errorDiv = document.createElement('div');
                                errorDiv.className = 'invalid-feedback';
                                currentPasswordField.parentNode.insertBefore(errorDiv, currentPasswordField.nextSibling);
                            }
                            
                            errorDiv.textContent = 'Неверный текущий пароль';
                        }
                    }
                }
            })
            .catch(error => {
                console.error('Ошибка:', error);
                
                // Восстанавливаем кнопку
                submitButton.disabled = false;
                submitButton.innerHTML = originalButtonText;
                
                showNotification('Ошибка', 'Произошла ошибка при отправке формы', 'danger');
            });
        });
    });
}

/**
 * Загружает данные профиля пользователя через AJAX
 */
function loadProfileData() {
    // Запрашиваем актуальные данные
    fetch('index.php?action=profile&format=json', {
        method: 'GET',
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('Ошибка получения данных профиля');
        }
        return response.json();
    })
    .then(data => {
        if (data.success && data.user) {
            // Обновляем значения полей в форме обновления профиля
            const usernameFields = document.querySelectorAll('input[name="username"]');
            const emailFields = document.querySelectorAll('input[name="email"]');
            
            usernameFields.forEach(field => {
                field.value = data.user.username;
            });
            
            emailFields.forEach(field => {
                field.value = data.user.email;
            });
            
            // Обновляем отображение имени пользователя, если есть
            const usernameDisplays = document.querySelectorAll('.username-display');
            usernameDisplays.forEach(display => {
                display.textContent = data.user.username;
            });
            
            // Обновляем информацию профиля в таблице, если есть
            updateProfileTableInfo(data.user);
        }
    })
    .catch(error => {
        console.error('Ошибка при обновлении профиля:', error);
    });
}

/**
 * Обновляет информацию профиля в таблице
 * 
 * @param {Object} userData - Данные пользователя
 */
function updateProfileTableInfo(userData) {
    const tables = document.querySelectorAll('table');
    
    tables.forEach(table => {
        const rows = table.querySelectorAll('tr');
        rows.forEach(row => {
            const headerCell = row.querySelector('th');
            const dataCell = row.querySelector('td');
            
            if (headerCell && dataCell) {
                const headerText = headerCell.textContent.trim().toLowerCase();
                
                if (headerText.includes('имя пользователя') && userData.username) {
                    dataCell.textContent = userData.username;
                } else if (headerText.includes('email') && userData.email) {
                    dataCell.textContent = userData.email;
                }
            }
        });
    });
}

/**
 * Обработка успешного обновления профиля
 * 
 * @param {Object} data - Данные ответа от сервера
 * @param {HTMLFormElement} form - Форма, которая была отправлена
 */
function handleProfileUpdate(data, form) {
    showNotification('Успех', 'Профиль успешно обновлен', 'success');
    
    // Обновляем имя пользователя и email во всех местах отображения
    const usernameField = form.querySelector('input[name="username"]');
    const emailField = form.querySelector('input[name="email"]');
    
    if (usernameField) {
        // Обновляем имя пользователя в интерфейсе, если оно где-то отображается
        const usernameDisplays = document.querySelectorAll('.username-display');
        usernameDisplays.forEach(display => {
            display.textContent = usernameField.value;
        });
    }
    
    // Обновляем информацию в профиле
    const userData = {
        username: usernameField ? usernameField.value : null,
        email: emailField ? emailField.value : null
    };
    
    // Обновляем данные в таблице профиля
    updateProfileTableInfo(userData);
    
    // Удаляем классы валидации
    form.querySelectorAll('.is-valid, .is-invalid').forEach(el => {
        el.classList.remove('is-valid', 'is-invalid');
    });
    
    // Обновляем URL без перезагрузки страницы
    const redirectTo = form.querySelector('input[name="redirect_to"]');
    if (redirectTo && redirectTo.value === 'index') {
        // Активируем вкладку профиля
        const profileTab = document.getElementById('profile-tab');
        if (profileTab) {
            const tabTrigger = new bootstrap.Tab(profileTab);
            tabTrigger.show();
            
            // Обновляем URL
            const url = new URL(window.location.href);
            url.searchParams.set('tab', 'profile');
            history.pushState({}, '', url);
        }
    }
}

/**
 * Обработка успешного изменения пароля
 * 
 * @param {Object} data - Данные ответа от сервера
 * @param {HTMLFormElement} form - Форма, которая была отправлена
 */
function handlePasswordChange(data, form) {
    // Проверяем успешность операции
    if (data.success === true) {
        showNotification('Успех', 'Пароль успешно изменен', 'success');
        
        // Сбрасываем форму пароля
        form.reset();
        
        // Удаляем классы валидации
        form.querySelectorAll('.is-valid, .is-invalid').forEach(el => {
            el.classList.remove('is-valid', 'is-invalid');
        });
        
        // Обновляем URL без перезагрузки страницы
        const redirectToInput = form.querySelector('input[name="redirect_to"]');
        if (redirectToInput && redirectToInput.value === 'index') {
            // Активируем вкладку профиля
            const profileTab = document.getElementById('profile-tab');
            if (profileTab) {
                const tabTrigger = new bootstrap.Tab(profileTab);
                tabTrigger.show();
                
                // Обновляем URL
                const url = new URL(window.location.href);
                url.searchParams.set('tab', 'profile');
                history.pushState({}, '', url);
            }
        }
    }
}

/**
 * Обработка успешного создания задачи
 * 
 * @param {Object} data - Данные ответа от сервера
 * @param {HTMLFormElement} form - Форма, которая была отправлена
 */
function handleTaskCreate(data, form) {
    showNotification('Успех', 'Задача успешно создана', 'success');
    
    // Сбрасываем форму
    form.reset();
    
    // Устанавливаем актуальную дату и время
    const dateField = form.querySelector('input[name="date"]');
    const timeField = form.querySelector('input[name="time"]');
    if (dateField) {
        dateField.value = new Date().toISOString().split('T')[0];
    }
    if (timeField) {
        const now = new Date();
        timeField.value = `${String(now.getHours()).padStart(2, '0')}:${String(now.getMinutes()).padStart(2, '0')}`;
    }
    
    // Перенаправляем на главную страницу
    window.location.href = 'index.php?action=index&notification=task_created';
}

/**
 * Обработка успешного обновления задачи
 * 
 * @param {Object} data - Данные ответа от сервера
 * @param {HTMLFormElement} form - Форма, которая была отправлена
 */
function handleTaskUpdate(data, form) {
    showNotification('Успех', 'Задача успешно обновлена', 'success');
    
    // Получаем URL для возврата
    const returnUrl = form.querySelector('input[name="return_url"]');
    if (returnUrl && returnUrl.value) {
        // Перенаправляем на указанный URL
        window.location.href = returnUrl.value;
    } else {
        // Извлекаем ID задачи из URL формы
        const taskIdMatch = form.action.match(/id=(\d+)/);
        if (taskIdMatch && taskIdMatch[1]) {
            // Перенаправляем на страницу просмотра задачи
            window.location.href = `index.php?action=show&id=${taskIdMatch[1]}&notification=task_updated`;
        } else {
            // Возвращаемся на главную
            window.location.href = 'index.php?action=index';
        }
    }
}