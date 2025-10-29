<?php
require_once 'config.php';

// Авторизация
function checkAuth() {
    session_start();
    if (!isset($_SESSION['admin_logged_in']) || !$_SESSION['admin_logged_in']) {
        header('Location: index.php');
        exit;
    }
}

// Валидация JSON
function validateJSON($jsonString) {
    json_decode($jsonString);
    return json_last_error() === JSON_ERROR_NONE;
}

// Загрузка данных портфеля
function loadPortfolioData() {
    if (!file_exists(PORTFOLIO_FILE)) {
        return ['wallets' => [], 'activeWalletId' => null];
    }
    
    $content = file_get_contents(PORTFOLIO_FILE);
    $data = json_decode($content, true);
    
    if (!$data) {
        return ['wallets' => [], 'activeWalletId' => null];
    }
    
    return $data;
}

// Сохранение данных портфеля
function savePortfolioData($data) {
    $jsonString = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    return file_put_contents(PORTFOLIO_FILE, $jsonString) !== false;
}

// Поиск кошелька по ID
function getWalletById($data, $id) {
    foreach ($data['wallets'] as $index => $wallet) {
        if ($wallet['id'] == $id) {
            return ['wallet' => $wallet, 'index' => $index];
        }
    }
    return null;
}

// Получение следующего доступного ID
function getNextWalletId($data) {
    $maxId = 0;
    foreach ($data['wallets'] as $wallet) {
        if ($wallet['id'] > $maxId) {
            $maxId = $wallet['id'];
        }
    }
    return $maxId + 1;
}

// Парсинг строки активов
function parseAssets($assetsString) {
    $assets = [];
    $parts = explode(' ', trim($assetsString));
    
    foreach ($parts as $part) {
        $part = trim($part);
        if (empty($part)) continue;
        
        if (strpos($part, ':') !== false) {
            list($name, $percentage) = explode(':', $part, 2);
            $name = trim($name);
            $percentage = floatval(trim($percentage));
            
            if (!empty($name) && $percentage > 0) {
                $assets[] = [
                    'name' => $name,
                    'percentage' => $percentage
                ];
            }
        }
    }
    
    return $assets;
}

// Валидация активов
function validateAssets($assetsString) {
    $assets = parseAssets($assetsString);
    
    if (empty($assets)) {
        return ['valid' => false, 'message' => 'Не указаны активы'];
    }
    
    $totalPercentage = 0;
    foreach ($assets as $asset) {
        $totalPercentage += $asset['percentage'];
    }
    
    if ($totalPercentage != 100) {
        return ['valid' => false, 'message' => "Сумма процентов должна быть ровно 100%, получено: {$totalPercentage}%"];
    }
    
    return ['valid' => true, 'assets' => $assets];
}

// Получение списка загруженных изображений для dropdown
function getUploadedImageFiles() {
    $uploadDir = UPLOADS_DIR . 'images/';
    $files = [];
    
    if (is_dir($uploadDir)) {
        $fileList = array_diff(scandir($uploadDir), ['.', '..']);
        foreach ($fileList as $file) {
            $extension = strtolower(pathinfo($file, PATHINFO_EXTENSION));
            if (in_array($extension, ALLOWED_EXTENSIONS)) {
                $files[] = $file;
            }
        }
    }
    
    return $files;
}

// Очистка имени файла (заменяет пробелы и спецсимволы на _)
function sanitizeFilename($filename) {
    $filename = preg_replace('/[^a-zA-Z0-9._-]/', '_', $filename);
    return $filename;
}

// Получение списка файлов для таблицы загрузок
function getUploadedFiles() {
    $uploadDir = UPLOADS_DIR . 'images/';
    $files = [];
    
    if (is_dir($uploadDir)) {
        $fileList = array_diff(scandir($uploadDir), ['.', '..']);
        foreach ($fileList as $file) {
            $extension = strtolower(pathinfo($file, PATHINFO_EXTENSION));
            if (in_array($extension, ALLOWED_EXTENSIONS)) {
                $files[] = ['filename' => $file];
            }
        }
    }
    
    return $files;
}

// Форматирование размера файла
function formatFileSize($bytes) {
    $units = ['B', 'KB', 'MB', 'GB'];
    $i = 0;
    while ($bytes >= 1024 && $i < count($units) - 1) {
        $bytes /= 1024;
        $i++;
    }
    return round($bytes, 2) . ' ' . $units[$i];
}

// Загрузка файла
function uploadFile($file) {
    $debug = [];
    $debug['time'] = date('c');
    $debug['server'] = [
        'cwd' => getcwd(),
        'script' => isset($_SERVER['SCRIPT_FILENAME']) ? $_SERVER['SCRIPT_FILENAME'] : null,
        'user' => function_exists('get_current_user') ? get_current_user() : null,
    ];
    if (function_exists('posix_geteuid') && function_exists('posix_getpwuid')) {
        $euid = @posix_geteuid();
        $pw = $euid !== false ? @posix_getpwuid($euid) : null;
        $debug['server']['euid'] = $euid;
        $debug['server']['user_pw'] = $pw;
    }
    $debug['php_ini'] = [
        'file_uploads' => ini_get('file_uploads'),
        'upload_max_filesize' => ini_get('upload_max_filesize'),
        'post_max_size' => ini_get('post_max_size'),
        'memory_limit' => ini_get('memory_limit'),
        'upload_tmp_dir' => ini_get('upload_tmp_dir'),
        'open_basedir' => ini_get('open_basedir'),
    ];
    $debug['request_file'] = $file;

    // Валидация типа файла
    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($extension, ALLOWED_EXTENSIONS)) {
        return ['success' => false, 'message' => 'Недопустимый тип файла. Разрешены: ' . implode(', ', ALLOWED_EXTENSIONS), 'debug' => $debug];
    }
    
    // Валидация размера
    if ($file['size'] > MAX_FILE_SIZE) {
        return ['success' => false, 'message' => 'Файл слишком большой. Максимум: ' . formatFileSize(MAX_FILE_SIZE), 'debug' => $debug];
    }
    
    // Создание папки если не существует
    $uploadDir = UPLOADS_DIR . 'images/';
    $debug['paths'] = [
        'UPLOADS_DIR' => UPLOADS_DIR,
        'uploadDir' => $uploadDir,
        'uploadDir_exists' => is_dir($uploadDir),
        'UPLOADS_DIR_exists' => is_dir(UPLOADS_DIR),
        'UPLOADS_DIR_writable' => is_writable(UPLOADS_DIR),
        'uploadDir_writable' => is_dir($uploadDir) ? is_writable($uploadDir) : null,
    ];
    if (!is_dir($uploadDir)) {
        $mk = @mkdir($uploadDir, 0755, true);
        $debug['paths']['mkdir_created'] = $mk;
    }
    
    // Сохранение файла
    $filename = sanitizeFilename($file['name']);
    $targetPath = $uploadDir . $filename;
    $debug['paths']['targetPath'] = $targetPath;
    $debug['pre_checks'] = [
        'tmp_name_exists' => isset($file['tmp_name']) ? file_exists($file['tmp_name']) : null,
        'is_uploaded_file' => isset($file['tmp_name']) ? @is_uploaded_file($file['tmp_name']) : null,
    ];
    
    if (@move_uploaded_file($file['tmp_name'], $targetPath)) {
        return ['success' => true, 'message' => "Файл {$filename} успешно загружен", 'filename' => $filename, 'debug' => $debug];
    }
    
    $lastErr = function_exists('error_get_last') ? error_get_last() : null;
    $debug['move_error'] = [
        'last_error' => $lastErr,
        'target_parent_perms' => @substr(sprintf('%o', @fileperms($uploadDir)), -4),
        'target_parent_owner' => @fileowner($uploadDir),
        'target_parent_group' => @filegroup($uploadDir),
        'upload_tmp_dir_exists' => ($dir = ini_get('upload_tmp_dir')) ? is_dir($dir) : null,
        'upload_tmp_dir_writable' => ($dir = ini_get('upload_tmp_dir')) ? is_writable($dir) : null,
    ];
    
    return ['success' => false, 'message' => 'Ошибка загрузки файла', 'debug' => $debug];
}

// Удаление файла
function deleteFile($filename) {
    $filePath = UPLOADS_DIR . "images/{$filename}";
    if (file_exists($filePath)) {
        return unlink($filePath);
    }
    return false;
}

// Получение статистики портфеля
function getPortfolioStats() {
    if (!file_exists(PORTFOLIO_FILE)) {
        return ['wallets' => 0, 'size' => 0];
    }
    
    $data = json_decode(file_get_contents(PORTFOLIO_FILE), true);
    $wallets = isset($data['wallets']) ? count($data['wallets']) : 0;
    $size = filesize(PORTFOLIO_FILE);
    
    return ['wallets' => $wallets, 'size' => $size];
}
?>
