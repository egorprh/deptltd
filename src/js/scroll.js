/**
 * Простые анимации прокрутки
 * Эффект фокуса - элементы появляются из размытия в четкость
 */

// Инициализация при загрузке DOM
document.addEventListener('DOMContentLoaded', initScrollAnimations);

/**
 * Инициализация анимаций прокрутки
 */
function initScrollAnimations() {
    // Настраиваем Intersection Observer для эффекта фокуса
    setupFocusObserver();
}

/**
 * Настройка наблюдателя для эффекта фокуса
 * Элементы появляются из размытия в четкость при прокрутке
 */
function setupFocusObserver() {
    // Проверяем поддержку Intersection Observer
    if (!('IntersectionObserver' in window)) {
        console.warn('IntersectionObserver не поддерживается, используем fallback');
        setupScrollFallback();
        return;
    }

    // Настройки наблюдателя
    const observerOptions = {
        root: null,                    // Используем viewport
        rootMargin: '-20% 0px -20% 0px',  // Срабатывает когда элемент на 20% в viewport
        threshold: [0, 0.1, 0.2, 0.3, 0.4, 0.5, 0.6, 0.7, 0.8, 0.9, 1.0]       // Упрощенные пороги для лучшей производительности
    };

    // Создаем наблюдатель
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            handleFocusEffect(entry);
        });
    }, observerOptions);

    // Наблюдаем все элементы с классом scroll-hidden
    const elementsToObserve = document.querySelectorAll('.scroll-hidden');
    elementsToObserve.forEach(element => {
        observer.observe(element);
    });
}

/**
 * Обработка эффекта фокуса
 * Плавный переход с множественными threshold для лучшей анимации
 */
function handleFocusEffect(entry) {
    const element = entry.target;
    const ratio = entry.intersectionRatio;
    const isIntersecting = entry.isIntersecting;

    // Более плавное появление - срабатывает раньше
    if (isIntersecting && ratio > 0.05) {
        // Элемент входит в viewport - убираем размытие, добавляем четкость
        element.classList.remove('scroll-hidden');
        element.classList.add('scroll-visible');
    } else if (!isIntersecting && ratio === 0) {
        // Элемент покидает viewport - добавляем размытие
        element.classList.add('scroll-hidden');
        element.classList.remove('scroll-visible');
    }
}

/**
 * Fallback для браузеров без поддержки Intersection Observer
 * Использует scroll события
 */
function setupScrollFallback() {
    let ticking = false;

    const handleScroll = () => {
        if (!ticking) {
            requestAnimationFrame(() => {
                checkElementsInView();
                ticking = false;
            });
            ticking = true;
        }
    };

    window.addEventListener('scroll', handleScroll, { passive: true });
    checkElementsInView(); // Проверяем начальное состояние
}

/**
 * Проверка элементов в viewport (fallback метод)
 * Ручная проверка позиций элементов относительно viewport
 */
function checkElementsInView() {
    const elements = document.querySelectorAll('.scroll-hidden');
    const windowHeight = window.innerHeight;
    const scrollTop = window.pageYOffset;

    elements.forEach(element => {
        const elementTop = element.offsetTop;
        const elementHeight = element.offsetHeight;
        const elementBottom = elementTop + elementHeight;
        
        // Элемент в viewport если частично виден
        const isInView = elementBottom > scrollTop && elementTop < scrollTop + windowHeight;
        
        if (isInView) {
            // В viewport - четкость
            element.classList.remove('scroll-hidden');
            element.classList.add('scroll-visible');
        } else {
            // Вне viewport - размытие
            element.classList.add('scroll-hidden');
            element.classList.remove('scroll-visible');
        }
    });
}