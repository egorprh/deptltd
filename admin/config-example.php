<?php
// Конфигурация админ-панели Department

// Учетные данные
define('ADMIN_LOGIN', 'admin');
define('ADMIN_PASSWORD', password_hash('admin123', PASSWORD_DEFAULT)); // Замените на свой пароль

// Пути
define('DATA_DIR', __DIR__ . '/../data/');
define('UPLOADS_DIR', __DIR__ . '/../uploads/');
define('PORTFOLIO_FILE', DATA_DIR . 'portfolio-data.json');

// Настройки загрузки
define('MAX_FILE_SIZE', 5 * 1024 * 1024); // 5MB
define('ALLOWED_EXTENSIONS', ['jpg', 'jpeg', 'png', 'gif', 'svg', 'ico']);

?>
