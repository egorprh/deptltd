document.addEventListener('DOMContentLoaded', () => {
    const html = document.documentElement;
    const body = document.body;
    const openBtn = document.querySelector('.navbar-toggler-open');
    const closeBtn = document.querySelector('.navbar-toggler-cancel');
    const collapse = document.getElementById('navbarNav');
    const backdrop = document.querySelector('.navbar-backdrop');

    const openMenu = () => {
        collapse.classList.add('show');
        backdrop.classList.add('active');
        html.classList.add('overflow-hidden');
        body.classList.add('overflow-hidden');
    };

    const closeMenu = () => {
        collapse.classList.remove('show');
        backdrop.classList.remove('active');
        html.classList.remove('overflow-hidden');
        body.classList.remove('overflow-hidden');
    };

    openBtn.addEventListener('click', openMenu);
    closeBtn.addEventListener('click', closeMenu);
    backdrop.addEventListener('click', closeMenu);

    // Закрываем меню при клике на ссылку внутри
    collapse.querySelectorAll('a').forEach(link => {
        link.addEventListener('click', closeMenu);
    });
});
