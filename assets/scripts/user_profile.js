// Проверка формы изменения профиля
document.addEventListener('DOMContentLoaded', function() {
    const profileForm = document.querySelector('form[action="index.php?action=updateProfile"]');
    if (profileForm) {
        profileForm.addEventListener('submit', function(e) {
            const username = document.getElementById('username').value;
            const email = document.getElementById('email').value;
            
            if (username.trim() === '') {
                e.preventDefault();
                showNotification('Ошибка', 'Имя пользователя не может быть пустым', 'danger');
                return false;
            }
            
            if (email.trim() === '' || !isValidEmail(email)) {
                e.preventDefault();
                showNotification('Ошибка', 'Введите корректный email', 'danger');
                return false;
            }
            
            return true;
        });
    }
    
    // Проверка формы смены пароля
    const passwordForm = document.querySelector('form[action="index.php?action=changePassword"]');
    if (passwordForm) {
        passwordForm.addEventListener('submit', function(e) {
            const currentPassword = document.getElementById('current_password').value;
            const newPassword = document.getElementById('new_password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            
            if (currentPassword.trim() === '') {
                e.preventDefault();
                showNotification('Ошибка', 'Введите текущий пароль', 'danger');
                return false;
            }
            
            if (newPassword.trim() === '' || newPassword.length < 8) {
                e.preventDefault();
                showNotification('Ошибка', 'Новый пароль должен содержать не менее 8 символов', 'danger');
                return false;
            }
            
            if (newPassword !== confirmPassword) {
                e.preventDefault();
                showNotification('Ошибка', 'Пароли не совпадают', 'danger');
                return false;
            }
            
            return true;
        });
    }
});

// Функция для проверки корректности email
function isValidEmail(email) {
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return emailRegex.test(email);
}