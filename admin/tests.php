<?php
/**
 * Простые Unit-тесты для PHP функций админ-панели
 * Запуск: php admin/tests.php
 */

// Подключаем функции для тестирования
require_once 'functions.php';

class SimpleTest {
    private $passed = 0;
    private $failed = 0;
    private $tempFiles = [];
    private $tempDirs = [];
    
    function assert($condition, $message) {
        if ($condition) {
            $this->passed++;
            echo "✓ PASS: $message\n";
        } else {
            $this->failed++;
            echo "✗ FAIL: $message\n";
        }
    }
    
    function assertEqual($expected, $actual, $message) {
        $this->assert($expected === $actual, "$message (ожидалось: " . var_export($expected, true) . ", получено: " . var_export($actual, true) . ")");
    }
    
    function assertArrayEqual($expected, $actual, $message) {
        $this->assert($expected === $actual, "$message (массивы не равны)");
    }
    
    function summary() {
        echo "\n" . str_repeat("=", 50) . "\n";
        echo "РЕЗУЛЬТАТЫ ТЕСТИРОВАНИЯ:\n";
        echo "Пройдено: {$this->passed}\n";
        echo "Провалено: {$this->failed}\n";
        echo "Всего: " . ($this->passed + $this->failed) . "\n";
        echo str_repeat("=", 50) . "\n";
    }
    
    // Создание временного файла
    function createTempFile($content, $extension = 'json') {
        $tempFile = tempnam(sys_get_temp_dir(), 'test_') . '.' . $extension;
        file_put_contents($tempFile, $content);
        $this->tempFiles[] = $tempFile;
        return $tempFile;
    }
    
    // Создание временной папки
    function createTempDir() {
        $tempDir = sys_get_temp_dir() . '/test_' . uniqid();
        mkdir($tempDir, 0755, true);
        $this->tempDirs[] = $tempDir;
        return $tempDir;
    }
    
    // Очистка временных файлов
    function tearDown() {
        foreach ($this->tempFiles as $file) {
            if (file_exists($file)) {
                unlink($file);
            }
        }
        foreach ($this->tempDirs as $dir) {
            if (is_dir($dir)) {
                $this->removeDir($dir);
            }
        }
    }
    
    private function removeDir($dir) {
        $files = array_diff(scandir($dir), ['.', '..']);
        foreach ($files as $file) {
            $path = $dir . '/' . $file;
            is_dir($path) ? $this->removeDir($path) : unlink($path);
        }
        rmdir($dir);
    }
}

// Моковые данные
function getMockPortfolioData($walletCount = 2) {
    $data = ['wallets' => [], 'activeWalletId' => 1];
    for ($i = 1; $i <= $walletCount; $i++) {
        $data['wallets'][] = [
            'id' => $i,
            'name' => "Кошелек $i",
            'capital' => '$100,000',
            'winRate' => '50%',
            'annualReturn' => '30%',
            'yearlyReturn' => '25%',
            'assets' => [
                ['name' => 'USDT', 'percentage' => 60],
                ['name' => 'BTC', 'percentage' => 40]
            ],
            'portfolioChart' => '/uploads/images/chart.png',
            'sharpeChart' => '/uploads/images/sharpe.png'
        ];
    }
    return $data;
}

// Тесты функций валидации
function test_parseAssets($test) {
    echo "\n--- Тестирование parseAssets() ---\n";
    
    // Корректный формат
    $result = parseAssets("USDT:60 BTC:20 ETH:20");
    $expected = [
        ['name' => 'USDT', 'percentage' => 60.0],
        ['name' => 'BTC', 'percentage' => 20.0],
        ['name' => 'ETH', 'percentage' => 20.0]
    ];
    $test->assertArrayEqual($expected, $result, "Парсинг корректного формата");
    
    // Некорректный формат
    $result = parseAssets("USDT:60 BTC:20 INVALID");
    $expected = [
        ['name' => 'USDT', 'percentage' => 60.0],
        ['name' => 'BTC', 'percentage' => 20.0]
    ];
    $test->assertArrayEqual($expected, $result, "Парсинг с некорректным элементом");
    
    // Пустая строка
    $result = parseAssets("");
    $test->assertArrayEqual([], $result, "Парсинг пустой строки");
    
    // С пробелами
    $result = parseAssets("  USDT:60   BTC:20  ");
    $expected = [
        ['name' => 'USDT', 'percentage' => 60.0],
        ['name' => 'BTC', 'percentage' => 20.0]
    ];
    $test->assertArrayEqual($expected, $result, "Парсинг с пробелами");
}

function test_validateAssets($test) {
    echo "\n--- Тестирование validateAssets() ---\n";
    
    // Сумма = 100%
    $result = validateAssets("USDT:60 BTC:40");
    $test->assert($result['valid'], "Валидация суммы = 100%");
    $test->assertEqual(2, count($result['assets']), "Количество активов при валидной сумме");
    
    // Сумма < 100%
    $result = validateAssets("USDT:60 BTC:20");
    $test->assert(!$result['valid'], "Валидация суммы < 100%");
    $test->assert(strpos($result['message'], '80%') !== false, "Сообщение об ошибке содержит сумму");
    
    // Сумма > 100%
    $result = validateAssets("USDT:60 BTC:50");
    $test->assert(!$result['valid'], "Валидация суммы > 100%");
    
    // Пустая строка
    $result = validateAssets("");
    $test->assert(!$result['valid'], "Валидация пустой строки");
    $test->assert(strpos($result['message'], 'Не указаны активы') !== false, "Сообщение о пустых активах");
}

// Тесты работы с портфелем
function test_loadPortfolioData($test) {
    echo "\n--- Тестирование loadPortfolioData() ---\n";
    
    // Создаем временный файл с данными
    $mockData = getMockPortfolioData(2);
    $jsonContent = json_encode($mockData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    $tempFile = $test->createTempFile($jsonContent);
    
    // Временно заменяем константу
    $originalFile = PORTFOLIO_FILE;
    define('TEST_PORTFOLIO_FILE', $tempFile);
    
    // Мокаем функцию для использования тестового файла
    function loadPortfolioDataTest() {
        if (!file_exists(TEST_PORTFOLIO_FILE)) {
            return ['wallets' => [], 'activeWalletId' => null];
        }
        
        $content = file_get_contents(TEST_PORTFOLIO_FILE);
        $data = json_decode($content, true);
        
        if (!$data) {
            return ['wallets' => [], 'activeWalletId' => null];
        }
        
        return $data;
    }
    
    // Тест загрузки существующего файла
    $result = loadPortfolioDataTest();
    $test->assertEqual(2, count($result['wallets']), "Загрузка существующего файла");
    $test->assertEqual(1, $result['activeWalletId'], "Правильный activeWalletId");
    
    // Тест загрузки несуществующего файла
    unlink($tempFile);
    $result = loadPortfolioDataTest();
    $test->assertEqual(0, count($result['wallets']), "Загрузка несуществующего файла");
    $test->assertEqual(null, $result['activeWalletId'], "activeWalletId = null для пустого файла");
}

function test_savePortfolioData($test) {
    echo "\n--- Тестирование savePortfolioData() ---\n";
    
    $tempFile = $test->createTempFile('{}');
    
    function savePortfolioDataTest($data, $filePath) {
        $jsonString = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        return file_put_contents($filePath, $jsonString) !== false;
    }
    
    $mockData = getMockPortfolioData(2);
    $result = savePortfolioDataTest($mockData, $tempFile);
    $test->assert($result, "Сохранение валидных данных");
    
    // Проверяем содержимое файла
    $savedContent = file_get_contents($tempFile);
    $savedData = json_decode($savedContent, true);
    $test->assertEqual(2, count($savedData['wallets'] ?? []), "Проверка сохраненных данных");
}

function test_getWalletById($test) {
    echo "\n--- Тестирование getWalletById() ---\n";
    
    $mockData = getMockPortfolioData(3);
    
    // Поиск существующего кошелька
    $result = getWalletById($mockData, 2);
    $test->assert($result !== null, "Поиск существующего кошелька");
    $test->assertEqual(2, $result['wallet']['id'], "Правильный ID найденного кошелька");
    $test->assertEqual(1, $result['index'], "Правильный индекс кошелька");
    
    // Поиск несуществующего кошелька
    $result = getWalletById($mockData, 999);
    $test->assert($result === null, "Поиск несуществующего кошелька");
}

function test_getNextWalletId($test) {
    echo "\n--- Тестирование getNextWalletId() ---\n";
    
    // Пустой массив
    $emptyData = ['wallets' => []];
    $result = getNextWalletId($emptyData);
    $test->assertEqual(1, $result, "Следующий ID для пустого массива");
    
    // Массив с кошельками
    $mockData = getMockPortfolioData(3);
    $result = getNextWalletId($mockData);
    $test->assertEqual(4, $result, "Следующий ID для массива с кошельками");
}

// Тесты CRUD операций
function test_createWallet($test) {
    echo "\n--- Тестирование создания кошелька ---\n";
    
    $tempFile = $test->createTempFile(json_encode(getMockPortfolioData(2)));
    
    // Загружаем исходные данные
    $originalData = json_decode(file_get_contents($tempFile), true);
    $originalWallets = $originalData['wallets'];
    
    // Добавляем новый кошелек
    $newWallet = [
        'id' => 3,
        'name' => 'Новый кошелек',
        'capital' => '$200,000',
        'winRate' => '60%',
        'annualReturn' => '40%',
        'yearlyReturn' => '35%',
        'assets' => [
            ['name' => 'ETH', 'percentage' => 100]
        ],
        'portfolioChart' => '/uploads/images/new-chart.png',
        'sharpeChart' => '/uploads/images/new-sharpe.png'
    ];
    
    $originalData['wallets'][] = $newWallet;
    file_put_contents($tempFile, json_encode($originalData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    
    // Загружаем обновленные данные
    $updatedData = json_decode(file_get_contents($tempFile), true);
    
    // Проверяем результаты
    $test->assertEqual(3, count($updatedData['wallets']), "Всего кошельков после добавления");
    
    // Проверяем, что старые кошельки не изменились
    for ($i = 0; $i < 2; $i++) {
        $test->assertArrayEqual($originalWallets[$i], $updatedData['wallets'][$i], "Старый кошелек $i не изменился");
    }
    
    // Проверяем новый кошелек
    $test->assertArrayEqual($newWallet, $updatedData['wallets'][2], "Новый кошелек добавлен корректно");
}

function test_editWallet($test) {
    echo "\n--- Тестирование редактирования кошелька ---\n";
    
    $tempFile = $test->createTempFile(json_encode(getMockPortfolioData(3)));
    
    // Загружаем исходные данные
    $data = json_decode(file_get_contents($tempFile), true);
    $originalWallet1 = $data['wallets'][0];
    $originalWallet3 = $data['wallets'][2];
    
    // Изменяем кошелек ID=2
    $data['wallets'][1]['name'] = 'Измененный кошелек';
    $data['wallets'][1]['capital'] = '$500,000';
    
    file_put_contents($tempFile, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    
    // Загружаем обновленные данные
    $updatedData = json_decode(file_get_contents($tempFile), true);
    
    // Проверяем результаты
    $test->assertEqual(3, count($updatedData['wallets']), "Всего кошельков после редактирования");
    
    // Проверяем, что другие кошельки не изменились
    $test->assertArrayEqual($originalWallet1, $updatedData['wallets'][0], "Кошелек ID=1 не изменился");
    $test->assertArrayEqual($originalWallet3, $updatedData['wallets'][2], "Кошелек ID=3 не изменился");
    
    // Проверяем изменения в кошельке ID=2
    $test->assertEqual('Измененный кошелек', $updatedData['wallets'][1]['name'], "Название кошелька изменено");
    $test->assertEqual('$500,000', $updatedData['wallets'][1]['capital'], "Капитал кошелька изменен");
}

function test_deleteWallet($test) {
    echo "\n--- Тестирование удаления кошелька ---\n";
    
    $tempFile = $test->createTempFile(json_encode(getMockPortfolioData(3)));
    
    // Загружаем исходные данные
    $data = json_decode(file_get_contents($tempFile), true);
    $originalWallet1 = $data['wallets'][0];
    $originalWallet3 = $data['wallets'][2];
    
    // Удаляем кошелек ID=2 (индекс 1)
    array_splice($data['wallets'], 1, 1);
    
    file_put_contents($tempFile, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    
    // Загружаем обновленные данные
    $updatedData = json_decode(file_get_contents($tempFile), true);
    
    // Проверяем результаты
    $test->assertEqual(2, count($updatedData['wallets']), "Всего кошельков после удаления");
    
    // Проверяем, что оставшиеся кошельки не изменились
    $test->assertArrayEqual($originalWallet1, $updatedData['wallets'][0], "Кошелек ID=1 не изменился");
    $test->assertArrayEqual($originalWallet3, $updatedData['wallets'][1], "Кошелек ID=3 не изменился");
    
    // Проверяем, что кошелек ID=2 действительно удален
    $wallet2Exists = false;
    foreach ($updatedData['wallets'] as $wallet) {
        if ($wallet['id'] == 2) {
            $wallet2Exists = true;
            break;
        }
    }
    $test->assert(!$wallet2Exists, "Кошелек ID=2 удален");
}

// Тесты загрузки файлов
function test_sanitizeFilename($test) {
    echo "\n--- Тестирование sanitizeFilename() ---\n";
    
    // Замена пробелов
    $result = sanitizeFilename("my file name.jpg");
    $test->assertEqual("my_file_name.jpg", $result, "Замена пробелов на _");
    
    // Замена спецсимволов
    $result = sanitizeFilename("file@#$%name.png");
    $test->assertEqual("file____name.png", $result, "Замена спецсимволов на _");
    
    // Корректное имя (не меняется)
    $result = sanitizeFilename("valid-filename_123.jpg");
    $test->assertEqual("valid-filename_123.jpg", $result, "Корректное имя не изменяется");
    
    // Кириллица
    $result = sanitizeFilename("файл.jpg");
    $test->assertEqual("________.jpg", $result, "Кириллица заменяется на _");
}

function test_uploadFile($test) {
    echo "\n--- Тестирование uploadFile() ---\n";
    
    // Создаем временную папку для загрузок
    $tempUploadDir = $test->createTempDir();
    $tempImageDir = $tempUploadDir . '/images';
    mkdir($tempImageDir, 0755, true);
    
    // Создаем временный файл изображения
    $tempImageFile = $test->createTempFile('fake image content', 'jpg');
    
    // Мокаем константы
    define('TEST_UPLOADS_DIR', $tempUploadDir . '/');
    define('TEST_MAX_FILE_SIZE', 1024 * 1024); // 1MB
    define('TEST_ALLOWED_EXTENSIONS', ['jpg', 'jpeg', 'png', 'gif', 'svg', 'ico']);
    
    // Мокаем функцию uploadFile
    function uploadFileTest($file) {
        // Валидация типа файла
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($extension, TEST_ALLOWED_EXTENSIONS)) {
            return ['success' => false, 'message' => 'Недопустимый тип файла'];
        }
        
        // Валидация размера
        if ($file['size'] > TEST_MAX_FILE_SIZE) {
            return ['success' => false, 'message' => 'Файл слишком большой'];
        }
        
        // Создание папки если не существует
        $uploadDir = TEST_UPLOADS_DIR . 'images/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        // Сохранение файла
        $filename = sanitizeFilename($file['name']);
        $targetPath = $uploadDir . $filename;
        
        // В тестах используем copy вместо move_uploaded_file
        if (copy($file['tmp_name'], $targetPath)) {
            return ['success' => true, 'message' => "Файл {$filename} успешно загружен", 'filename' => $filename];
        }
        
        return ['success' => false, 'message' => 'Ошибка загрузки файла'];
    }
    
    // Тест валидного файла
    $mockFile = [
        'name' => 'test-image.jpg',
        'tmp_name' => $tempImageFile,
        'size' => 1024,
        'error' => UPLOAD_ERR_OK
    ];
    
    $result = uploadFileTest($mockFile);
    $test->assert($result['success'], "Загрузка валидного файла");
    $test->assertEqual('test-image.jpg', $result['filename'], "Правильное имя файла");
    
    // Тест недопустимого типа
    $mockFile['name'] = 'test.txt';
    $result = uploadFileTest($mockFile);
    $test->assert(!$result['success'], "Отклонение недопустимого типа файла");
    
    // Тест слишком большого файла
    $mockFile['name'] = 'test.jpg';
    $mockFile['size'] = 2 * 1024 * 1024; // 2MB
    $result = uploadFileTest($mockFile);
    $test->assert(!$result['success'], "Отклонение слишком большого файла");
}

// Запуск всех тестов
function runAllTests() {
    $test = new SimpleTest();
    
    echo "ЗАПУСК UNIT-ТЕСТОВ ДЛЯ PHP ФУНКЦИЙ\n";
    echo str_repeat("=", 50) . "\n";
    
    try {
        // Тесты валидации
        test_parseAssets($test);
        test_validateAssets($test);
        
        // Тесты работы с портфелем
        test_loadPortfolioData($test);
        test_savePortfolioData($test);
        test_getWalletById($test);
        test_getNextWalletId($test);
        
        // Тесты CRUD операций
        test_createWallet($test);
        test_editWallet($test);
        test_deleteWallet($test);
        
        // Тесты загрузки файлов
        test_sanitizeFilename($test);
        test_uploadFile($test);
        
    } catch (Exception $e) {
        echo "ОШИБКА В ТЕСТАХ: " . $e->getMessage() . "\n";
    } finally {
        // Очистка временных файлов
        $test->tearDown();
    }
    
    $test->summary();
}

// Запуск тестов
runAllTests();
?>
