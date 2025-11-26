<?php
require_once __DIR__ . '/../auth.php';
?>
<header class="header">
    <div class="container header__container">
        <a href="index.php" class="logo">PixelMarket</a>
        
        <form class="search" action="search.php" method="GET">
            <input type="text" name="q" placeholder="Поиск по каталогу" 
                   value="<?php echo isset($_GET['q']) ? htmlspecialchars($_GET['q']) : ''; ?>">
            <button type="submit" class="search__btn">
                <svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M9 17C13.4183 17 17 13.4183 17 9C17 4.58172 13.4183 1 9 1C4.58172 1 1 4.58172 1 9C1 13.4183 4.58172 17 9 17Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    <path d="M19 19L14.65 14.65" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            </button>
        </form>

        <div class="header__icons">
            <a href="cart.php" class="icon-btn" title="Корзина">
                <img src="img/products/Без-имени.png" alt="Корзина" class="cart-icon-img">
            </a>
            <?php if (isLoggedIn()): ?>
                <?php if (isAdmin()): ?>
                <a href="admin.php" class="btn btn--accent">Админ-панель</a>
                <?php endif; ?>
                <a href="account.php" class="btn btn--accent">Личный кабинет</a>
            <?php else: ?>
                <a href="login.php" class="btn btn--accent">Войти</a>
            <?php endif; ?>
        </div>

        <button class="mobile-menu-btn" aria-label="Открыть меню">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M3 12H21" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                <path d="M3 6H21" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                <path d="M3 18H21" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
        </button>
    </div>

    <nav class="nav">
        <div class="container">
            <ul>
                <li><a href="catalog.php?category=smartphones">Смартфоны</a></li>
                <li><a href="catalog.php?category=laptops">Ноутбуки</a></li>
                <li><a href="catalog.php?category=computers">Компьютеры</a></li>
                <li><a href="catalog.php?category=tv">Телевизоры</a></li>
                <li><a href="catalog.php?category=audio">Аудио</a></li>
                <li><a href="catalog.php?category=accessories">Аксессуары</a></li>
            </ul>
        </div>
    </nav>
</header>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const mobileMenuBtn = document.querySelector('.mobile-menu-btn');
    const nav = document.querySelector('.nav');
    
    mobileMenuBtn.addEventListener('click', function() {
        nav.classList.toggle('active');
        this.setAttribute('aria-expanded', nav.classList.contains('active'));
    });

    // Закрытие меню при клике вне его
    document.addEventListener('click', function(event) {
        if (!nav.contains(event.target) && !mobileMenuBtn.contains(event.target)) {
            nav.classList.remove('active');
            mobileMenuBtn.setAttribute('aria-expanded', 'false');
        }
    });

    // Закрытие меню при изменении размера окна
    window.addEventListener('resize', function() {
        if (window.innerWidth > 768) {
            nav.classList.remove('active');
            mobileMenuBtn.setAttribute('aria-expanded', 'false');
        }
    });
});
</script> 