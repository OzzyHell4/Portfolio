document.addEventListener('DOMContentLoaded', function() {
    // Получаем все ссылки навигации и секции
    const navLinks = document.querySelectorAll('.account-nav a');
    const sections = document.querySelectorAll('.account-section');

    // Функция для переключения активной секции
    function switchSection(sectionId) {
        // Убираем активный класс у всех ссылок и секций
        navLinks.forEach(link => link.classList.remove('active'));
        sections.forEach(section => section.classList.remove('active'));

        // Добавляем активный класс выбранной секции и ссылке
        document.querySelector(`.account-nav a[href="#${sectionId}"]`).classList.add('active');
        document.getElementById(sectionId).classList.add('active');
    }

    // Обработчик клика по ссылкам навигации
    navLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            const sectionId = this.getAttribute('href').substring(1);
            switchSection(sectionId);
        });
    });

    // Проверяем хэш в URL при загрузке страницы
    const hash = window.location.hash.substring(1);
    if (hash) {
        switchSection(hash);
    }
}); 