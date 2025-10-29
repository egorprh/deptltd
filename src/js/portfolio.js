/**
 * Менеджер портфеля - Упрощенная версия
 * Обрабатывает переключение кошельков и обновление данных портфеля
 */

// Глобальные переменные для хранения состояния
let portfolioData = null;        // Данные портфеля из JSON файла
let activeWalletId = null;      // ID текущего активного кошелька

// Конфигурация селекторов - используем ID и data-атрибуты вместо CSS классов
const SELECTORS = {
    portfolioValue: '#portfolio-value',           // Основное значение капитала
    chartValue: '#chart-value',                   // Значение в центре круговой диаграммы
    portfolioStat: '#portfolio-stat-value',       // Статистика портфеля в карточке
    winrateStat: '#winrate-value',               // Значение винрейта
    annualReturnStat: '#annual-return-value',    // Значение годовой доходности
    yearlyReturn: '#yearly-return-value',         // Значение годовой доходности
    assetsList: '#assets-list',                  // Контейнер списка активов
    chartSegment: '#chart-segment',              // Сегмент круговой диаграммы
    walletSelection: '#wallet-selection',        // Контейнер кнопок выбора кошелька
    portfolioChart: '#portfolio-chart',           // График изменения портфеля
    sharpeChart: '#sharpe-chart',                 // График коэффициента Шарпа
    assetTemplate: '#asset-template'              // HTML шаблон для элементов активов
};

// Палитра цветов для круговой диаграммы (10 контрастных цветов)
const CHART_COLORS = [
    '#D1F767',  // Светло-зеленый (USDT)
    '#E8A005',   // Оранжевый (BTC)
    '#FF5343',  // Красный (ETH)
    '#627EEA',  // Синий (AVAX)
    '#6c757d',  // Серый (fallback)
    '#8B5CF6',  // Фиолетовый
    '#F59E0B',  // Янтарный
    '#10B981',  // Изумрудный
    '#EF4444',  // Красный
    '#3B82F6'   // Синий
];

// Инициализация портфеля после загрузки DOM
document.addEventListener('DOMContentLoaded', initPortfolio);

/**
 * Инициализация менеджера портфеля
 * Загружает данные, создает кнопки кошельков и устанавливает активный кошелек
 */
async function initPortfolio() {
    try {
        await loadData();                                                    // Загружаем данные из JSON
        createWalletButtons();                                               // Создаем кнопки выбора кошельков
        setActiveWallet(portfolioData.activeWalletId || portfolioData.wallets[0].id);  // Устанавливаем активный кошелек
    } catch (error) {
        console.error('Ошибка загрузки данных портфеля:', error);
    }
}

/**
 * Загрузка данных портфеля из JSON файла
 * Выполняет HTTP запрос к файлу с данными портфеля
 */
async function loadData() {
    const response = await fetch('/data/portfolio-data.json');        // Запрашиваем JSON файл
    if (!response.ok) {
        throw new Error(`HTTP ошибка! статус: ${response.status}`);          // Выбрасываем ошибку при неудачном запросе
    }
    portfolioData = await response.json();                                  // Парсим JSON и сохраняем в глобальную переменную
}


/**
 * Создание кнопок выбора кошельков
 * Динамически создает кнопки для каждого кошелька из данных
 */
function createWalletButtons() {
    const container = document.querySelector(SELECTORS.walletSelection);     // Находим контейнер для кнопок
    if (!container) return;                                                 // Выходим, если контейнер не найден
    
    // Очищаем только если есть существующие кнопки (fallback)
    const existingButtons = container.querySelectorAll('button');
    if (existingButtons.length > 0) {
        container.innerHTML = '';                                            // Очищаем fallback кнопки
    }
    
    // Создаем кнопку для каждого кошелька
    portfolioData.wallets.forEach(wallet => {
        const button = document.createElement('button');                    // Создаем элемент кнопки
        button.className = 'btn btn-outline-dark rounded-pill px-4';        // Устанавливаем CSS классы
        button.textContent = wallet.name;                                   // Устанавливаем текст кнопки
        button.setAttribute('data-wallet-id', wallet.id);                   // Сохраняем ID кошелька в атрибуте
        button.onclick = () => setActiveWallet(wallet.id);                  // Добавляем обработчик клика
        container.appendChild(button);                                     // Добавляем кнопку в контейнер
    });
}

/**
 * Установка активного кошелька и обновление интерфейса
 * Находит кошелек по ID, обновляет UI и состояние кнопок
 */
function setActiveWallet(walletId) {
    const wallet = portfolioData.wallets.find(w => w.id === walletId);      // Ищем кошелек по ID
    if (!wallet) return;                                                    // Выходим, если кошелек не найден
    
    activeWalletId = walletId;                                              // Сохраняем ID активного кошелька
    updateUI(wallet);                                                       // Обновляем все элементы интерфейса
    updateWalletButtons();                                                  // Обновляем состояние кнопок кошельков
}

/**
 * Обновление состояния кнопок кошельков
 * Изменяет стили кнопок в зависимости от того, какая активна
 */
function updateWalletButtons() {
    const buttons = document.querySelectorAll(`${SELECTORS.walletSelection} button`);  // Находим все кнопки кошельков
    buttons.forEach(button => {
        const walletId = parseInt(button.getAttribute('data-wallet-id'));              // Получаем ID кошелька из атрибута
        const isActive = walletId === activeWalletId;                                 // Проверяем, активен ли этот кошелек

        // Устанавливаем соответствующие CSS классы
        button.className = isActive
            ? 'btn btn-dark rounded-pill px-3 py-2 lh-1'        // Активная кнопка - темная
            : 'btn btn-outline-dark rounded-pill px-3 py-2 lh-1';  // Неактивная кнопка - с обводкой
    });
}

/**
 * Обновление всех элементов интерфейса данными кошелька
 * Обновляет капитал, статистику, активы и графики
 */
function updateUI(wallet) {
    // Обновляем значения капитала в разных местах
    updateElement(SELECTORS.portfolioValue, wallet.capital);               // Основное значение капитала
    updateElement(SELECTORS.chartValue, wallet.capital);                   // Значение в центре диаграммы
    updateElement(SELECTORS.portfolioStat, wallet.capital);               // Статистика в карточке
    
    // Обновляем статистические показатели
    updateElement(SELECTORS.winrateStat, wallet.winRate);                  // Винрейт
    updateElement(SELECTORS.annualReturnStat, wallet.annualReturn);        // Годовая доходность
    
    // Обновляем значение годовой доходности
    updateElement(SELECTORS.yearlyReturn, wallet.yearlyReturn);
    
    // Обновляем активы и графики
    updateAssetsList(wallet.assets);                                       // Список активов
    updateCircularChart(wallet.assets);                                    // Круговая диаграмма
    updateChartImages(wallet);                                             // Изображения графиков
}

/**
 * Обновление текстового содержимого элемента по селектору
 * Универсальная функция для обновления любого элемента на странице
 */
function updateElement(selector, value) {
    const element = document.querySelector(selector);                        // Находим элемент по селектору
    if (element) {
        element.textContent = value;                                        // Обновляем текстовое содержимое
    }
}


/**
 * Обновление списка активов с использованием HTML шаблона
 * Создает элементы активов из шаблона и заполняет их данными
 */
/**
 * Обновление списка активов с использованием HTML шаблона
 * Создает элементы активов из шаблона и заполняет их данными
 */
function updateAssetsList(assets) {
    const container = document.querySelector(SELECTORS.assetsList);          // Контейнер для списка активов
    const template = document.querySelector(SELECTORS.assetTemplate);       // HTML шаблон элемента актива
    if (!container || !template) return;                                   // Выходим, если элементы не найдены

    // Очищаем контейнер от существующих активов
    container.innerHTML = '';

    // Создаем новые элементы активов из шаблона
    assets.forEach((asset, index) => {
        const clone = template.content.cloneNode(true);                     // Клонируем содержимое шаблона

        // Определяем цвет для текущего актива
        const color = CHART_COLORS[index] || CHART_COLORS[CHART_COLORS.length - 1];

        // Применяем цвет границы к корневому элементу актива
        const assetItem = clone.querySelector('.asset-item');
        if (assetItem) {
            assetItem.style.border = `1px solid ${color}`;
            assetItem.style.borderRadius = '48px';
        }

        const assetIcon = clone.querySelector('.asset-icon');
        if (assetItem) {
            assetIcon.style.backgroundColor = `${color}`;
        }

        // Обновляем источник изображения иконки (ищем по названию токена)
        const img = clone.querySelector('.asset-icon-img');
        img.src = `./assets/icons/tokenico/${asset.name.toLowerCase()}.webp`; // Путь к иконке токена в нижнем регистре
        img.alt = asset.name;                                              // Альтернативный текст

        // Обновляем резервную букву (показывается при ошибке загрузки изображения)
        const fallback = clone.querySelector('.text-white.small.fw-bold');
        fallback.textContent = asset.name.charAt(0);                       // Первая буква названия актива

        // Обновляем текст с названием и процентом
        const text = clone.querySelector('.asset-text');
        text.textContent = `${asset.name} - ${asset.percentage}%`;         // Название и процент актива

        container.appendChild(clone);                                      // Добавляем элемент в контейнер
    });
}



let portfolioChartInstance = null;  // глобально

function updateCircularChart(assets) {
    const ctx = document.querySelector(SELECTORS.chartSegment)?.getContext('2d');
    if (!ctx) return;

    const data = assets.map(a => a.percentage);
    const labels = assets.map(a => a.name);
    const backgroundColors = assets.map((_, i) => CHART_COLORS[i] || CHART_COLORS[CHART_COLORS.length - 1]);

    if (portfolioChartInstance) {
        portfolioChartInstance.data.datasets[0].data = data;
        portfolioChartInstance.data.labels = labels;
        portfolioChartInstance.data.datasets[0].backgroundColor = backgroundColors;
        portfolioChartInstance.update();
        return;
    }

    portfolioChartInstance = new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: labels,
            datasets: [{
                data: data,
                backgroundColor: backgroundColors,
                borderWidth: 2,
                borderColor: '#ffffff',
                borderRadius: 11,
                spacing: 6
            }]
        },
        options: {
            cutout: '73%',
            responsive: true,
            plugins: {
                legend: { display: false },
                tooltip: { enabled: true }
            },
            animation: { animateRotate: true, animateScale: true }
        }
    });
}

/**
 * Обновление изображений графиков
 * Загружает соответствующие изображения графиков для выбранного кошелька
 */
function updateChartImages(wallet) {
    // Обновляем график изменения портфеля
    const portfolioChart = document.querySelector(SELECTORS.portfolioChart);
    if (portfolioChart) {
        portfolioChart.src = wallet.portfolioChart;                        // Путь к изображению графика портфеля
    }
    
    // Обновляем график коэффициента Шарпа
    const sharpeChart = document.querySelector(SELECTORS.sharpeChart);
    if (sharpeChart) {
        sharpeChart.src = wallet.sharpeChart;                              // Путь к изображению графика Шарпа
    }
}