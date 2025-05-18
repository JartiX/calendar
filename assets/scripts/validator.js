
/**
 * Клиентская валидация
 */
const Validator = {
    required: function(value) {
        return value !== null && value !== undefined && value.trim() !== '';
    },
    
    email: function(value) {
        const re = /^(([^<>()\[\]\\.,;:\s@"]+(\.[^<>()\[\]\\.,;:\s@"]+)*)|(".+"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/;
        return re.test(String(value).toLowerCase());
    },
    
    minLength: function(value, length) {
        return value.length >= length;
    },
    
    maxLength: function(value, length) {
        return value.length <= length;
    },
    
    passwordsMatch: function(password, confirmPassword) {
        return password === confirmPassword;
    }
};

/**
 * Настройка валидации для формы смены пароля
 */
function setupPasswordValidation() {
    const passwordForms = document.querySelectorAll('form[action="index.php?action=changePassword"]');
    
    passwordForms.forEach(form => {
        const currentPasswordField = form.querySelector('input[name="current_password"]');
        const newPasswordField = form.querySelector('input[name="new_password"]');
        const confirmPasswordField = form.querySelector('input[name="confirm_password"]');
        
        if (newPasswordField && confirmPasswordField) {
            // Минимальная длина пароля (должна соответствовать серверной валидации)
            const MIN_PASSWORD_LENGTH = 8;
            
            // Проверка обязательных полей
            [currentPasswordField, newPasswordField, confirmPasswordField].forEach(field => {
                field.addEventListener('blur', function() {
                    validateRequiredField(field);
                });
            });
            
            // Проверка длины пароля
            newPasswordField.addEventListener('input', function() {
                validatePasswordLength(newPasswordField, MIN_PASSWORD_LENGTH);
                
                if (confirmPasswordField.value) {
                    validatePasswordsMatch(newPasswordField, confirmPasswordField);
                }
            });
            
            // Проверка совпадения паролей
            confirmPasswordField.addEventListener('input', function() {
                validatePasswordsMatch(newPasswordField, confirmPasswordField);
            });
            
            // Добавляем валидацию перед отправкой формы
            form.addEventListener('submit', function(e) {
                const isCurrentPasswordValid = validateRequiredField(currentPasswordField);
                const isNewPasswordValid = validateRequiredField(newPasswordField) && 
                                          validatePasswordLength(newPasswordField, MIN_PASSWORD_LENGTH);
                const isConfirmPasswordValid = validateRequiredField(confirmPasswordField) && 
                                              validatePasswordsMatch(newPasswordField, confirmPasswordField);
                
                if (!isCurrentPasswordValid || !isNewPasswordValid || !isConfirmPasswordValid) {
                    e.preventDefault();
                    e.stopPropagation();
                    return false;
                }
            }, true);
        }
    });
}

/**
 * Валидация обязательного поля
 * 
 * @param {HTMLInputElement} field - Поле ввода
 * @returns {boolean} Результат валидации
 */
function validateRequiredField(field) {
    if (!Validator.required(field.value)) {
        field.classList.add('is-invalid');
        
        // Создаем или обновляем сообщение об ошибке
        let errorDiv = field.nextElementSibling;
        if (!errorDiv || !errorDiv.classList.contains('invalid-feedback')) {
            errorDiv = document.createElement('div');
            errorDiv.className = 'invalid-feedback';
            field.parentNode.insertBefore(errorDiv, field.nextSibling);
        }
        errorDiv.textContent = 'Это поле обязательно для заполнения';
        
        return false;
    } else {
        field.classList.remove('is-invalid');
        field.classList.add('is-valid');
        return true;
    }
}

/**
 * Валидация длины пароля
 * 
 * @param {HTMLInputElement} passwordField - Поле ввода пароля
 * @param {number} minLength - Минимальная длина пароля
 * @returns {boolean} Результат валидации
 */
function validatePasswordLength(passwordField, minLength) {
    if (!Validator.minLength(passwordField.value, minLength)) {
        passwordField.classList.add('is-invalid');
        
        // Создаем или обновляем сообщение об ошибке
        let errorDiv = passwordField.nextElementSibling;
        if (!errorDiv || !errorDiv.classList.contains('invalid-feedback')) {
            errorDiv = document.createElement('div');
            errorDiv.className = 'invalid-feedback';
            passwordField.parentNode.insertBefore(errorDiv, passwordField.nextSibling);
        }
        errorDiv.textContent = `Пароль должен содержать не менее ${minLength} символов`;
        
        return false;
    } else {
        passwordField.classList.remove('is-invalid');
        passwordField.classList.add('is-valid');
        return true;
    }
}

/**
 * Валидация совпадения паролей
 * 
 * @param {HTMLInputElement} passwordField - Поле ввода пароля
 * @param {HTMLInputElement} confirmField - Поле подтверждения пароля
 * @returns {boolean} Результат валидации
 */
function validatePasswordsMatch(passwordField, confirmField) {
    if (!Validator.passwordsMatch(passwordField.value, confirmField.value)) {
        confirmField.classList.add('is-invalid');
        
        // Создаем или обновляем сообщение об ошибке
        let errorDiv = confirmField.nextElementSibling;
        if (!errorDiv || !errorDiv.classList.contains('invalid-feedback')) {
            errorDiv = document.createElement('div');
            errorDiv.className = 'invalid-feedback';
            confirmField.parentNode.insertBefore(errorDiv, confirmField.nextSibling);
        }
        errorDiv.textContent = 'Пароли не совпадают';
        
        return false;
    } else {
        confirmField.classList.remove('is-invalid');
        confirmField.classList.add('is-valid');
        return true;
    }
}