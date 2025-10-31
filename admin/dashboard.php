<?php
require_once 'functions.php';
checkAuth();

$stats = getPortfolioStats();
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Админ-панель - Dept.Ltd</title>
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
                <h1 class="mb-4">Панель управления</h1>
            </div>
        </div>
        
        <!-- Статистика -->
        <div class="row mb-4">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Портфель</h5>
                        <p class="card-text">
                            <strong><?= $stats['wallets'] ?></strong> кошельков<br>
                            Размер файла: <strong><?= formatFileSize($stats['size']) ?></strong>
                        </p>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Навигация -->
        <div class="row">
            <div class="col-md-6 mb-3">
                <div class="card h-100">
                    <div class="card-body d-flex flex-column">
                        <h5 class="card-title">Управление портфелем</h5>
                        <p class="card-text">CRUD интерфейс для управления кошельками портфеля</p>
                        <a href="portfolio.php" class="btn btn-primary mt-auto">Открыть редактор</a>
                    </div>
                </div>
            </div>
            <div class="col-md-6 mb-3">
                <div class="card h-100">
                    <div class="card-body d-flex flex-column">
                        <h5 class="card-title">Загрузка файлов</h5>
                        <p class="card-text">Загрузка и управление изображениями (графики, иконки)</p>
                        <a href="upload.php" class="btn btn-primary mt-auto">Открыть загрузчик</a>
                    </div>
                </div>
            </div>
        </div>
        
        
        <!-- Инструкция по редактированию портфеля -->
        <div class="row mt-5">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5>📋 Инструкция по управлению портфелем</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-4">
                                <h6>1️⃣ Управление кошельками</h6>
                                <p class="small text-muted">
                                    В разделе <strong>"Управление портфелем"</strong> вы можете:
                                    <br>• ➕ Добавлять новые кошельки
                                    <br>• ✏️ Редактировать существующие
                                    <br>• 🗑️ Удалять ненужные
                                    <br>• ↑↓ Изменять порядок отображения
                                </p>
                            </div>
                            <div class="col-md-4">
                                <h6>2️⃣ Замена графиков</h6>
                                <p class="small text-muted">
                                    Сначала загрузите новый файл в разделе <strong>"Загрузка файлов"</strong>, 
                                    затем выберите его в dropdown "График портфеля" или "График Шарпа" 
                                    при редактировании кошелька.
                                </p>
                            </div>
                            <div class="col-md-4">
                                <h6>3️⃣ Поля кошелька</h6>
                                <p class="small text-muted">
                                    <code>name</code> → Название кошелька (кнопка)<br>
                                    <code>capital</code> → Сумма портфеля<br>
                                    <code>winRate</code> → Винрейт<br>
                                    <code>annualReturn</code> → Годовая доходность<br>
                                    <code>yearlyReturn</code> → Доходность для диаграммы<br>
                                    <code>assets</code> → Активы (USDT:60 BTC:20 ETH:20)<br>
                                    <code>isActive</code> → Выбран по умолчанию
                                </p>
                                
                                <!-- Список доступных иконок токенов -->
                                <div class="mt-3">
                                    <button class="btn btn-outline-info btn-sm" type="button" data-bs-toggle="collapse" data-bs-target="#tokenIcons" aria-expanded="false">
                                        🪙 Доступные иконки токенов
                                    </button>
                                    <div class="collapse mt-2" id="tokenIcons">
                                        <div class="card card-body small">
                                            <?php
                                            $tokenIcons = [];
                                            $iconsDir = '../uploads/images/tokens/';
                                            if (is_dir($iconsDir)) {
                                                $files = array_diff(scandir($iconsDir), ['.', '..']);
                                                foreach ($files as $file) {
                                                    if (in_array(strtolower(pathinfo($file, PATHINFO_EXTENSION)), ['png', 'jpg', 'jpeg', 'svg'])) {
                                                        $tokenIcons[] = pathinfo($file, PATHINFO_FILENAME);
                                                    }
                                                }
                                            }
                                            
                                            // Если нет загруженных иконок, показываем стандартные
                                            if (empty($tokenIcons)) {
                                                $tokenIcons = ['BTC', 'ETH', 'USDT', 'AVAX', 'BNB', 'ADA', 'SOL', 'DOT', 'LINK', 'UNI', 'LTC', 'XRP', 'ATOM', 'FIL', 'ICP', 'HBAR', 'TON', 'TRB', 'RENDER', 'LDO', 'JTO', 'JUP', 'MOVE', 'OP', 'ORDI', 'PEPE', 'PNUT', 'POPCAT', 'SAND', 'TAO', 'TIA', 'TURBO', 'UXLINK', 'W', 'WIF', 'ZRO', 'AAPE', 'AGLD', 'APT', 'BAT', 'BERA', 'BOME', 'BRETT', 'DOT', 'ENA', 'GAS', 'GOAT', 'GRASS', 'LPT', 'S', 'HYPE'];
                                            }
                                            
                                            sort($tokenIcons);
                                            $chunks = array_chunk($tokenIcons, 5);
                                            foreach ($chunks as $chunk) {
                                                echo '<div class="row mb-2">';
                                                foreach ($chunk as $icon) {
                                                    echo '<div class="col-2"><code>' . htmlspecialchars($icon) . '</code></div>';
                                                }
                                                echo '</div>';
                                            }
                                            ?>
                                            <div class="mt-2 text-muted">
                                                <small>💡 Используйте эти названия в поле <code>name</code> для активов. Иконки находятся в assets/icons/tokenico/</small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="mt-3">
                            <a href="portfolio.php" class="btn btn-outline-primary btn-sm me-2">📝 Редактировать портфель</a>
                            <a href="upload.php" class="btn btn-outline-secondary btn-sm">📁 Загрузить файлы</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
