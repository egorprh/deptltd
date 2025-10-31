<?php
require_once 'functions.php';
checkAuth();

$success = '';
$error = '';
$debugInfo = [];

// Обработка загрузки файла
if ($_POST && isset($_FILES['file'])) {
    $file = $_FILES['file'];
    $customName = $_POST['custom_name'] ?? '';
    
    if ($file['error'] === UPLOAD_ERR_OK) {
        // Используем кастомное имя если указано
        if (!empty($customName)) {
            $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
            $file['name'] = $customName . '.' . $extension;
        }
        
		$result = uploadFile($file);
        
        if ($result['success']) {
            $success = $result['message'];
        } else {
            $error = $result['message'];
        }
		if (isset($result['debug'])) {
			$debugInfo['upload'] = $result['debug'];
		}
    } else {
        $error = 'Ошибка загрузки файла';
		$debugInfo['files_error_code'] = $file['error'] ?? null;
	}
}

// Обработка удаления файла
if ($_GET && isset($_GET['delete'])) {
    $filename = $_GET['file'] ?? '';
    
    if (deleteFile($filename)) {
        $success = "Файл {$filename} удален";
    } else {
        $error = "Ошибка удаления файла";
    }
}

// Получение списка файлов
$files = getUploadedFiles();
$showDebug = (defined('ADMIN_DEBUG') && ADMIN_DEBUG) || (isset($_GET['debug']) && $_GET['debug'] == '1') || (bool)$error;

// Базовая диагностика окружения (всегда собирается, показывается при ошибке или ?debug=1)
$uploadDir = UPLOADS_DIR . 'images/';
$debugInfo['env'] = [
	'paths' => [
		'__DIR__' => __DIR__,
		'cwd' => getcwd(),
		'UPLOADS_DIR' => UPLOADS_DIR,
		'uploadDir' => $uploadDir,
		'DATA_DIR' => defined('DATA_DIR') ? DATA_DIR : null,
		'PORTFOLIO_FILE' => defined('PORTFOLIO_FILE') ? PORTFOLIO_FILE : null,
	],
	'paths_state' => [
		'uploads_exists' => is_dir(UPLOADS_DIR),
		'uploads_writable' => is_dir(UPLOADS_DIR) ? is_writable(UPLOADS_DIR) : null,
		'uploadDir_exists' => is_dir($uploadDir),
		'uploadDir_writable' => is_dir($uploadDir) ? is_writable($uploadDir) : null,
		'data_exists' => defined('DATA_DIR') ? is_dir(DATA_DIR) : null,
		'portfolio_exists' => defined('PORTFOLIO_FILE') ? file_exists(PORTFOLIO_FILE) : null,
		'portfolio_writable' => defined('PORTFOLIO_FILE') && file_exists(PORTFOLIO_FILE) ? is_writable(PORTFOLIO_FILE) : null,
	],
	'perms' => [
		'uploads_perms' => is_dir(UPLOADS_DIR) ? substr(sprintf('%o', @fileperms(UPLOADS_DIR)), -4) : null,
		'uploadDir_perms' => is_dir($uploadDir) ? substr(sprintf('%o', @fileperms($uploadDir)), -4) : null,
	],
	'php_ini' => [
		'file_uploads' => ini_get('file_uploads'),
		'upload_max_filesize' => ini_get('upload_max_filesize'),
		'post_max_size' => ini_get('post_max_size'),
		'memory_limit' => ini_get('memory_limit'),
		'upload_tmp_dir' => ini_get('upload_tmp_dir'),
		'open_basedir' => ini_get('open_basedir'),
	],
];
if (function_exists('posix_geteuid') && function_exists('posix_getpwuid')) {
	$euid = @posix_geteuid();
	$debugInfo['env']['user'] = [
		'euid' => $euid,
		'pw' => $euid !== false ? @posix_getpwuid($euid) : null,
	];
} else {
	$debugInfo['env']['user'] = [ 'current' => function_exists('get_current_user') ? get_current_user() : null ];
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Загрузка файлов - Dept.Ltd</title>
    <link rel="icon" type="image/x-icon" href="favicon.ico">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <nav class="navbar navbar-dark bg-dark">
        <div class="container-fluid">
            <a class="navbar-brand" href="dashboard.php">Админ-панель Dept.Ltd</a>
            <a href="logout.php" class="btn btn-outline-light btn-sm">Выход</a>
        </div>
    </nav>
    
    <div class="container mt-4">
        <div class="row">
            <div class="col-12">
                <h1 class="mb-4">Загрузка файлов</h1>
                
                <?php if ($success): ?>
                <div class="alert alert-success" role="alert">
                    <?= htmlspecialchars($success) ?>
                </div>
                <?php endif; ?>
                
                <?php if ($error): ?>
                <div class="alert alert-danger" role="alert">
                    <?= htmlspecialchars($error) ?>
                </div>
                <?php endif; ?>

                <div class="mb-2">
                    <form method="get" class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" role="switch" id="toggleDebug" name="debug" value="1" <?= $showDebug ? 'checked' : '' ?> onchange="this.form.submit()">
                        <label class="form-check-label" for="toggleDebug">Режим отладки</label>
                    </form>
                </div>

                <?php if ($showDebug): ?>
                <div class="alert alert-secondary" role="alert">
                    <div class="d-flex justify-content-between align-items-center">
                        <strong>Отладочная информация</strong>
                        <a href="?" class="btn btn-sm btn-outline-secondary">Скрыть</a>
                    </div>
                    <pre style="white-space: pre-wrap; word-break: break-word; background: #f8f9fa; padding: 12px; border-radius: 6px; margin-top: 10px; max-height: 500px; overflow: auto;">
<?= htmlspecialchars(json_encode($debugInfo, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) ?>
                    </pre>
                </div>
                <?php else: ?>
                <div class="alert alert-light" role="alert">
                    Для подробной диагностики включите режим отладки переключателем выше или параметром <a href="?debug=1">?debug=1</a>
                </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Форма загрузки -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5>Загрузить новый файл</h5>
                    </div>
                    <div class="card-body">
                        <form method="post" enctype="multipart/form-data">
                            <div class="row">
                                <div class="col-md-8 mb-3">
                                    <label for="file" class="form-label">Выберите файл</label>
                                    <input type="file" class="form-control" id="file" name="file" accept="image/*" required>
                                    <div class="form-text">
                                        Разрешены: <?= implode(', ', ALLOWED_EXTENSIONS) ?><br>
                                        Максимальный размер: <?= formatFileSize(MAX_FILE_SIZE) ?>
                                    </div>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label for="custom_name" class="form-label">Имя файла (необязательно)</label>
                                    <input type="text" class="form-control" id="custom_name" name="custom_name" 
                                           placeholder="graph-new">
                                    <div class="form-text">Оставьте пустым для оригинального имени</div>
                                </div>
                            </div>
                            <button type="submit" class="btn btn-primary">Загрузить файл</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Список загруженных файлов -->
        <div class="row">
            <div class="col-12">
                <h5>Загруженные файлы</h5>
                
                <?php if (empty($files)): ?>
                <div class="alert alert-info">
                    Файлы не загружены
                </div>
                <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Файл</th>
                                <th>Путь</th>
                                <th>Размер</th>
                                <th>Дата</th>
                                <th>Действия</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($files as $file): ?>
                            <tr>
                                <td>
                                    <?php
                                    $filePath = UPLOADS_DIR . "images/{$file['filename']}";
                                    $isImage = in_array(strtolower(pathinfo($file['filename'], PATHINFO_EXTENSION)), ['jpg', 'jpeg', 'png', 'gif']);
                                    ?>
                                    <?php if ($isImage && file_exists($filePath)): ?>
                                    <img src="/uploads/images/<?= $file['filename'] ?>" 
                                         class="img-thumbnail me-2" style="width: 50px; height: 50px; object-fit: cover;">
                                    <?php endif; ?>
                                    <?= htmlspecialchars($file['filename']) ?>
                                </td>
                                <td>
                                    <div class="input-group input-group-sm">
                                        <input type="text" class="form-control" 
                                               value="/uploads/images/<?= $file['filename'] ?>" 
                                               id="path-<?= md5($file['filename']) ?>" readonly>
                                        <button class="btn btn-outline-secondary" type="button" 
                                              onclick="copyToClipboard('path-<?= md5($file['filename']) ?>')"
                                              title="Копировать путь">
                                            <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor">
                                                <path d="M16 1H4c-1.1 0-2 .9-2 2v14h2V3h12V1zm3 4H8c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h11c1.1 0 2-.9 2-2V7c0-1.1-.9-2-2-2zm0 16H8V7h11v14z"/>
                                            </svg>
                                        </button>
                                    </div>
                                </td>
                                <td>
                                    <?php
                                    if (file_exists($filePath)) {
                                        echo formatFileSize(filesize($filePath));
                                    } else {
                                        echo 'N/A';
                                    }
                                    ?>
                                </td>
                                <td>
                                    <?php
                                    if (file_exists($filePath)) {
                                        echo date('d.m.Y H:i', filemtime($filePath));
                                    } else {
                                        echo 'N/A';
                                    }
                                    ?>
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <?php if ($isImage && file_exists($filePath)): ?>
                                        <a href="/uploads/images/<?= $file['filename'] ?>" 
                                           target="_blank" class="btn btn-outline-primary btn-sm">Просмотр</a>
                                        <?php endif; ?>
                                        <a href="?delete=1&file=<?= urlencode($file['filename']) ?>" 
                                           class="btn btn-outline-danger btn-sm" 
                                           onclick="return confirm('Удалить файл?')">Удалить</a>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="row mt-4">
            <div class="col-12">
                <a href="dashboard.php" class="btn btn-secondary">Назад к панели</a>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Функция копирования в буфер обмена
        function copyToClipboard(inputId) {
            const input = document.getElementById(inputId);
            input.select();
            input.setSelectionRange(0, 99999); // Для мобильных устройств
            
            try {
                document.execCommand('copy');
                
                // Показываем уведомление
                const button = input.nextElementSibling;
                const originalHTML = button.innerHTML;
                button.innerHTML = '<svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.42z"/></svg>';
                button.classList.remove('btn-outline-secondary');
                button.classList.add('btn-success');
                
                setTimeout(() => {
                    button.innerHTML = originalHTML;
                    button.classList.remove('btn-success');
                    button.classList.add('btn-outline-secondary');
                }, 2000);
                
            } catch (err) {
                // Fallback для старых браузеров
                navigator.clipboard.writeText(input.value).then(() => {
                    const button = input.nextElementSibling;
                    const originalHTML = button.innerHTML;
                    button.innerHTML = '<svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.42z"/></svg>';
                    button.classList.remove('btn-outline-secondary');
                    button.classList.add('btn-success');
                    
                    setTimeout(() => {
                        button.innerHTML = originalHTML;
                        button.classList.remove('btn-success');
                        button.classList.add('btn-outline-secondary');
                    }, 2000);
                });
            }
        }
    </script>
</body>
</html>