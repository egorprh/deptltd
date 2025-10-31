<?php
require_once 'functions.php';
checkAuth();

$success = '';
$error = '';
$debugInfo = [];

// Загрузка данных портфеля
$portfolioData = loadPortfolioData();
$uploadedFiles = getUploadedImageFiles();

// Базовая диагностика окружения (всегда собираем, показываем при ошибке или включенной отладке)
$debugInfo['env'] = [
    'paths' => [
        '__DIR__' => __DIR__,
        'cwd' => getcwd(),
        'DATA_DIR' => defined('DATA_DIR') ? DATA_DIR : null,
        'PORTFOLIO_FILE' => defined('PORTFOLIO_FILE') ? PORTFOLIO_FILE : null,
    ],
    'state' => [
        'data_dir_exists' => defined('DATA_DIR') ? is_dir(DATA_DIR) : null,
        'data_dir_writable' => defined('DATA_DIR') && is_dir(DATA_DIR) ? is_writable(DATA_DIR) : null,
        'portfolio_exists' => defined('PORTFOLIO_FILE') ? file_exists(PORTFOLIO_FILE) : null,
        'portfolio_writable' => defined('PORTFOLIO_FILE') && file_exists(PORTFOLIO_FILE) ? is_writable(PORTFOLIO_FILE) : null,
    ],
    'perms' => [
        'data_dir_perms' => defined('DATA_DIR') && is_dir(DATA_DIR) ? substr(sprintf('%o', @fileperms(DATA_DIR)), -4) : null,
        'portfolio_perms' => defined('PORTFOLIO_FILE') && file_exists(PORTFOLIO_FILE) ? substr(sprintf('%o', @fileperms(PORTFOLIO_FILE)), -4) : null,
    ],
    'php_ini' => [
        'open_basedir' => ini_get('open_basedir'),
    ],
];
if (function_exists('posix_geteuid') && function_exists('posix_getpwuid')) {
    $euid = @posix_geteuid();
    $debugInfo['env']['user'] = [
        'euid' => $euid,
        'pw' => $euid !== false ? @posix_getpwuid($euid) : null,
    ];
}

// Обработка действий
if ($_POST) {
    $action = $_POST['action'] ?? '';
    $debugInfo['request'] = [
        'action' => $action,
        'post_keys' => array_keys($_POST ?? []),
    ];
    
    switch ($action) {
        case 'create':
            // Создание нового кошелька
            $newWallet = [
                'id' => getNextWalletId($portfolioData),
                'name' => trim($_POST['name'] ?? ''),
                'capital' => trim($_POST['capital'] ?? ''),
                'winRate' => trim($_POST['winRate'] ?? ''),
                'annualReturn' => trim($_POST['annualReturn'] ?? ''),
                'yearlyReturn' => trim($_POST['yearlyReturn'] ?? ''),
                'portfolioChart' => trim($_POST['portfolioChart'] ?? ''),
                'sharpeChart' => trim($_POST['sharpeChart'] ?? ''),
                'assets' => []
            ];
            
            // Валидация активов
            $assetsValidation = validateAssets($_POST['assets'] ?? '');
            $debugInfo['create'] = [
                'assets_input' => $_POST['assets'] ?? '',
                'assets_validation' => $assetsValidation,
            ];
            if (!$assetsValidation['valid']) {
                $error = $assetsValidation['message'];
                break;
            }
            $newWallet['assets'] = $assetsValidation['assets'];
            
            // Добавляем кошелек
            $portfolioData['wallets'][] = $newWallet;
            
            // Управление активным кошельком
            if (isset($_POST['isActive']) && $_POST['isActive']) {
                $portfolioData['activeWalletId'] = $newWallet['id'];
            }
            
            $saveOk = savePortfolioData($portfolioData);
            $debugInfo['create']['save'] = [ 'ok' => $saveOk, 'last_error' => function_exists('error_get_last') ? error_get_last() : null ];
            if ($saveOk) {
                $success = 'Кошелек успешно создан!';
                $portfolioData = loadPortfolioData(); // Перезагружаем данные
            } else {
                $error = 'Ошибка сохранения данных';
            }
            break;
            
        case 'edit':
            // Редактирование кошелька
            $walletId = intval($_POST['wallet_id'] ?? 0);
            $walletInfo = getWalletById($portfolioData, $walletId);
            
            if (!$walletInfo) {
                $error = 'Кошелек не найден';
                break;
            }
            
            $index = $walletInfo['index'];
            
            // Валидация активов
            $assetsValidation = validateAssets($_POST['assets'] ?? '');
            $debugInfo['edit'] = [
                'wallet_id' => $walletId,
                'assets_input' => $_POST['assets'] ?? '',
                'assets_validation' => $assetsValidation,
            ];
            if (!$assetsValidation['valid']) {
                $error = $assetsValidation['message'];
                break;
            }
            
            // Обновляем данные кошелька
            $portfolioData['wallets'][$index] = [
                'id' => $walletId,
                'name' => trim($_POST['name'] ?? ''),
                'capital' => trim($_POST['capital'] ?? ''),
                'winRate' => trim($_POST['winRate'] ?? ''),
                'annualReturn' => trim($_POST['annualReturn'] ?? ''),
                'yearlyReturn' => trim($_POST['yearlyReturn'] ?? ''),
                'portfolioChart' => trim($_POST['portfolioChart'] ?? ''),
                'sharpeChart' => trim($_POST['sharpeChart'] ?? ''),
                'assets' => $assetsValidation['assets']
            ];
            
            // Управление активным кошельком
            if (isset($_POST['isActive']) && $_POST['isActive']) {
                $portfolioData['activeWalletId'] = $walletId;
            }
            
            $saveOk = savePortfolioData($portfolioData);
            $debugInfo['edit']['save'] = [ 'ok' => $saveOk, 'last_error' => function_exists('error_get_last') ? error_get_last() : null ];
            if ($saveOk) {
                $success = 'Кошелек успешно обновлен!';
                $portfolioData = loadPortfolioData(); // Перезагружаем данные
            } else {
                $error = 'Ошибка сохранения данных';
            }
            break;
            
        case 'delete':
            // Удаление кошелька
            $walletId = intval($_POST['wallet_id'] ?? 0);
            $walletInfo = getWalletById($portfolioData, $walletId);
            
            if (!$walletInfo) {
                $error = 'Кошелек не найден';
                break;
            }
            
            $index = $walletInfo['index'];
            $walletName = $portfolioData['wallets'][$index]['name'];
            
            // Удаляем кошелек
            array_splice($portfolioData['wallets'], $index, 1);
            
            // Если удаляемый кошелек был активным, сбрасываем activeWalletId
            if ($portfolioData['activeWalletId'] == $walletId) {
                $portfolioData['activeWalletId'] = null;
            }
            
            $saveOk = savePortfolioData($portfolioData);
            $debugInfo['delete'] = [ 'wallet_id' => $walletId, 'save' => [ 'ok' => $saveOk, 'last_error' => function_exists('error_get_last') ? error_get_last() : null ]];
            if ($saveOk) {
                $success = "Кошелек '{$walletName}' успешно удален!";
                $portfolioData = loadPortfolioData(); // Перезагружаем данные
            } else {
                $error = 'Ошибка сохранения данных';
            }
            break;
            
        case 'move':
            // Изменение порядка кошельков
            $walletId = intval($_POST['wallet_id'] ?? 0);
            $direction = $_POST['direction'] ?? '';
            
            $walletInfo = getWalletById($portfolioData, $walletId);
            if (!$walletInfo) {
                $error = 'Кошелек не найден';
                break;
            }
            
            $index = $walletInfo['index'];
            $newIndex = $index;
            
            if ($direction === 'up' && $index > 0) {
                $newIndex = $index - 1;
            } elseif ($direction === 'down' && $index < count($portfolioData['wallets']) - 1) {
                $newIndex = $index + 1;
            }
            
            if ($newIndex !== $index) {
                // Меняем местами
                $temp = $portfolioData['wallets'][$index];
                $portfolioData['wallets'][$index] = $portfolioData['wallets'][$newIndex];
                $portfolioData['wallets'][$newIndex] = $temp;
                
                $saveOk = savePortfolioData($portfolioData);
                $debugInfo['move'] = [ 'wallet_id' => $walletId, 'direction' => $direction, 'save' => [ 'ok' => $saveOk, 'last_error' => function_exists('error_get_last') ? error_get_last() : null ] ];
                if ($saveOk) {
                    $success = 'Порядок кошельков обновлен!';
                    $portfolioData = loadPortfolioData(); // Перезагружаем данные
                } else {
                    $error = 'Ошибка сохранения данных';
                }
            }
            break;
    }
}

// Получение данных для редактирования
$editWallet = null;
if (isset($_GET['edit'])) {
    $walletId = intval($_GET['edit']);
    $walletInfo = getWalletById($portfolioData, $walletId);
    if ($walletInfo) {
        $editWallet = $walletInfo['wallet'];
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Управление портфелем - Dept.Ltd</title>
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
    
    <?php $showDebug = (defined('ADMIN_DEBUG') && ADMIN_DEBUG) || (isset($_GET['debug']) && $_GET['debug'] == '1') || (bool)$error; ?>
    <div class="container mt-4">
        <div class="row">
            <div class="col-12">
                <h1 class="mb-4">Управление портфелем</h1>
                
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
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Список кошельков -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5>Кошельки портфеля</h5>
                        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#walletModal">
                            ➕ Добавить кошелек
                        </button>
                    </div>
                    <div class="card-body">
                        <?php if (empty($portfolioData['wallets'])): ?>
                        <div class="alert alert-info">
                            Кошельки не добавлены. Нажмите "Добавить кошелек" для создания первого кошелька.
                        </div>
                        <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Название</th>
                                        <th>Капитал</th>
                                        <th>Винрейт</th>
                                        <th>Доходность</th>
                                        <th>Активен</th>
                                        <th>Действия</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($portfolioData['wallets'] as $index => $wallet): ?>
                                    <tr>
                                        <td><?= $wallet['id'] ?></td>
                                        <td><?= htmlspecialchars($wallet['name']) ?></td>
                                        <td><?= htmlspecialchars($wallet['capital']) ?></td>
                                        <td><?= htmlspecialchars($wallet['winRate']) ?></td>
                                        <td><?= htmlspecialchars($wallet['annualReturn']) ?></td>
                                        <td>
                                            <?php if ($portfolioData['activeWalletId'] == $wallet['id']): ?>
                                            <span class="badge bg-success">Активен</span>
                                            <?php else: ?>
                                            <span class="badge bg-secondary">Неактивен</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div class="btn-group btn-group-sm">
                                                <a href="?edit=<?= $wallet['id'] ?>" class="btn btn-outline-primary">✏️</a>
                                                
                                                <!-- Кнопки изменения порядка -->
                                                <form method="post" style="display: inline;">
                                                    <input type="hidden" name="action" value="move">
                                                    <input type="hidden" name="wallet_id" value="<?= $wallet['id'] ?>">
                                                    <input type="hidden" name="direction" value="up">
                                                    <button type="submit" class="btn btn-outline-secondary" 
                                                            <?= $index === 0 ? 'disabled' : '' ?> title="Переместить вверх">↑</button>
                                                </form>
                                                
                                                <form method="post" style="display: inline;">
                                                    <input type="hidden" name="action" value="move">
                                                    <input type="hidden" name="wallet_id" value="<?= $wallet['id'] ?>">
                                                    <input type="hidden" name="direction" value="down">
                                                    <button type="submit" class="btn btn-outline-secondary" 
                                                            <?= $index === count($portfolioData['wallets']) - 1 ? 'disabled' : '' ?> title="Переместить вниз">↓</button>
                                                </form>
                                                
                                                <form method="post" style="display: inline;" 
                                                      onsubmit="return confirm('Удалить кошелек \'<?= htmlspecialchars($wallet['name']) ?>\'?')">
                                                    <input type="hidden" name="action" value="delete">
                                                    <input type="hidden" name="wallet_id" value="<?= $wallet['id'] ?>">
                                                    <button type="submit" class="btn btn-outline-danger">🗑️</button>
                                                </form>
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
            </div>
        </div>
        
        <!-- Форма редактирования (если выбрано) -->
        <?php if ($editWallet): ?>
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5>Редактирование кошелька: <?= htmlspecialchars($editWallet['name']) ?></h5>
                    </div>
                    <div class="card-body">
                        <form method="post">
                            <input type="hidden" name="action" value="edit">
                            <input type="hidden" name="wallet_id" value="<?= $editWallet['id'] ?>">
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="name" class="form-label">Название кошелька</label>
                                    <input type="text" class="form-control" id="name" name="name" 
                                           value="<?= htmlspecialchars($editWallet['name']) ?>" required>
                                    <div class="form-text">Отображается на кнопке переключения кошельков</div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="capital" class="form-label">Капитал</label>
                                    <input type="text" class="form-control" id="capital" name="capital" 
                                           value="<?= htmlspecialchars($editWallet['capital']) ?>" required>
                                    <div class="form-text">Например: $125,450</div>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <label for="winRate" class="form-label">Винрейт</label>
                                    <input type="text" class="form-control" id="winRate" name="winRate" 
                                           value="<?= htmlspecialchars($editWallet['winRate']) ?>" required>
                                    <div class="form-text">Например: 42%</div>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label for="annualReturn" class="form-label">Годовая доходность</label>
                                    <input type="text" class="form-control" id="annualReturn" name="annualReturn" 
                                           value="<?= htmlspecialchars($editWallet['annualReturn']) ?>" required>
                                    <div class="form-text">Например: 38%</div>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label for="yearlyReturn" class="form-label">Доходность для диаграммы</label>
                                    <input type="text" class="form-control" id="yearlyReturn" name="yearlyReturn" 
                                           value="<?= htmlspecialchars($editWallet['yearlyReturn']) ?>" required>
                                    <div class="form-text">Например: 32%</div>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="portfolioChart" class="form-label">График портфеля</label>
                                    <select class="form-select" id="portfolioChart" name="portfolioChart">
                                        <option value="">Выберите файл</option>
                                        <?php foreach ($uploadedFiles as $file): ?>
                                        <option value="/uploads/images/<?= $file ?>" 
                                                <?= $editWallet['portfolioChart'] === "/uploads/images/{$file}" ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($file) ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <div class="form-text">График распределения активов</div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="sharpeChart" class="form-label">График Шарпа</label>
                                    <select class="form-select" id="sharpeChart" name="sharpeChart">
                                        <option value="">Выберите файл</option>
                                        <?php foreach ($uploadedFiles as $file): ?>
                                        <option value="/uploads/images/<?= $file ?>" 
                                                <?= $editWallet['sharpeChart'] === "/uploads/images/{$file}" ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($file) ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <div class="form-text">График коэффициента Шарпа</div>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="assets" class="form-label">Активы</label>
                                <textarea class="form-control" id="assets" name="assets" rows="3" required><?php
                                    $assetsStrings = [];
                                    foreach ($editWallet['assets'] as $asset) {
                                        $assetsStrings[] = $asset['name'] . ':' . $asset['percentage'];
                                    }
                                    echo htmlspecialchars(implode(' ', $assetsStrings));
                                ?></textarea>
                                <div class="form-text">Формат: USDT:60 BTC:20 ETH:20 (сумма должна быть ровно 100%)</div>
                            </div>
                            
                            <div class="mb-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="isActive" name="isActive" 
                                           <?= $portfolioData['activeWalletId'] == $editWallet['id'] ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="isActive">
                                        Выбран по умолчанию при загрузке страницы
                                    </label>
                                </div>
                            </div>
                            
                            <div class="d-flex gap-2">
                                <button type="submit" class="btn btn-success">Сохранить изменения</button>
                                <a href="portfolio.php" class="btn btn-secondary">Отмена</a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
        
        <div class="row mt-4">
            <div class="col-12">
                <a href="dashboard.php" class="btn btn-secondary">Назад к панели</a>
            </div>
        </div>
    </div>
    
    <!-- Modal для создания кошелька -->
    <div class="modal fade" id="walletModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Добавить новый кошелек</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="post">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="create">
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="modal_name" class="form-label">Название кошелька</label>
                                <input type="text" class="form-control" id="modal_name" name="name" required>
                                <div class="form-text">Отображается на кнопке переключения кошельков</div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="modal_capital" class="form-label">Капитал</label>
                                <input type="text" class="form-control" id="modal_capital" name="capital" required>
                                <div class="form-text">Например: $125,450</div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label for="modal_winRate" class="form-label">Винрейт</label>
                                <input type="text" class="form-control" id="modal_winRate" name="winRate" required>
                                <div class="form-text">Например: 42%</div>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="modal_annualReturn" class="form-label">Годовая доходность</label>
                                <input type="text" class="form-control" id="modal_annualReturn" name="annualReturn" required>
                                <div class="form-text">Например: 38%</div>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="modal_yearlyReturn" class="form-label">Доходность для диаграммы</label>
                                <input type="text" class="form-control" id="modal_yearlyReturn" name="yearlyReturn" required>
                                <div class="form-text">Например: 32%</div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="modal_portfolioChart" class="form-label">График портфеля</label>
                                <select class="form-select" id="modal_portfolioChart" name="portfolioChart">
                                    <option value="">Выберите файл</option>
                                    <?php foreach ($uploadedFiles as $file): ?>
                                    <option value="/uploads/images/<?= $file ?>"><?= htmlspecialchars($file) ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <div class="form-text">График распределения активов</div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="modal_sharpeChart" class="form-label">График Шарпа</label>
                                <select class="form-select" id="modal_sharpeChart" name="sharpeChart">
                                    <option value="">Выберите файл</option>
                                    <?php foreach ($uploadedFiles as $file): ?>
                                    <option value="/uploads/images/<?= $file ?>"><?= htmlspecialchars($file) ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <div class="form-text">График коэффициента Шарпа</div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="modal_assets" class="form-label">Активы</label>
                            <textarea class="form-control" id="modal_assets" name="assets" rows="3" required></textarea>
                            <div class="form-text">Формат: USDT:60 BTC:20 ETH:20 (сумма должна быть ровно 100%)</div>
                        </div>
                        
                        <div class="mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="modal_isActive" name="isActive">
                                <label class="form-check-label" for="modal_isActive">
                                    Выбран по умолчанию при загрузке страницы
                                </label>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Отмена</button>
                        <button type="submit" class="btn btn-primary">Создать кошелек</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>