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
    // Валидация типа файла
    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($extension, ALLOWED_EXTENSIONS)) {
        return ['success' => false, 'message' => 'Недопустимый тип файла. Разрешены: ' . implode(', ', ALLOWED_EXTENSIONS)];
    }
    
    // Валидация размера
    if ($file['size'] > MAX_FILE_SIZE) {
        return ['success' => false, 'message' => 'Файл слишком большой. Максимум: ' . formatFileSize(MAX_FILE_SIZE)];
    }
    
    // Создание папки если не существует
    $uploadDir = UPLOADS_DIR . 'images/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    
    // Сохранение файла
    $filename = sanitizeFilename($file['name']);
    $targetPath = $uploadDir . $filename;
    
    if (move_uploaded_file($file['tmp_name'], $targetPath)) {
        return ['success' => true, 'message' => "Файл {$filename} успешно загружен", 'filename' => $filename];
    }
    
    return ['success' => false, 'message' => 'Ошибка загрузки файла'];
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
