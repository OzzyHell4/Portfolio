<?php
require_once 'auth.php';
require_once 'db_connect.php';

// Проверяем авторизацию
if (!isLoggedIn()) {
    header('Location: login.php');
    exit();
}

$user = getCurrentUser();
$orders = getUserOrders($user['id']);
$success = '';
$error = '';

// Обработка формы редактирования профиля
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_profile') {
    $name = $_POST['name'] ?? '';
    $email = $_POST['email'] ?? '';
    $phone = $_POST['phone'] ?? '';
    $address = $_POST['address'] ?? '';
    
    if (empty($name) || empty($email)) {
        $error = 'Имя и email обязательны для заполнения';
    } else {
        $data = [
            'name' => $name,
            'email' => $email,
            'phone' => $phone,
            'address' => $address
        ];
        
        if (updateUser($user['id'], $data)) {
            $success = 'Профиль успешно обновлен';
            $user = getCurrentUser(); // Обновляем данные пользователя
        } else {
            $error = 'Ошибка при обновлении профиля';
        }
    }
}

// Определяем режим отображения профиля
$isEditMode = isset($_GET['mode']) && $_GET['mode'] === 'edit';

$section = $_GET['section'] ?? 'profile';
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Личный кабинет - <?php echo htmlspecialchars($user['name']); ?></title>
    <link rel="stylesheet" href="css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <main class="container">
        <div class="account">
            <h1>Личный кабинет</h1>
            
            <div class="account__content">
                <nav class="account-nav">
                    <a href="#profile" class="active">Профиль</a>
                    <a href="#orders">Мои заказы</a>
                </nav>

                <div class="account__main">
                    <!-- Секция профиля -->
                    <section id="profile" class="account-section active">
                        <h2>Информация профиля</h2>
                        <?php if ($success): ?>
                        <div class="alert alert--success">
                            <?php echo htmlspecialchars($success); ?>
                        </div>
                        <?php endif; ?>

                        <?php if ($error): ?>
                        <div class="alert alert--error">
                            <?php echo htmlspecialchars($error); ?>
                        </div>
                        <?php endif; ?>

                        <div class="profile-info">
                            <div class="profile-info__details">
                                <?php if ($isEditMode): ?>
                                <form method="POST" action="/account.php" class="profile-form">
                                    <input type="hidden" name="action" value="update_profile">
                                    
                                    <div class="info-group">
                                        <label for="name">Имя </label>
                                    </div>

                                    <div class="info-group">
                                        <label for="email">Email</label>
                                        <input type="email" id="email" name="email" 
                                               value="<?php echo htmlspecialchars($user['email']); ?>" required>
                                    </div>

                                    <div class="info-group">
                                        <label for="phone">Телефон</label>
                                        <input type="tel" id="phone" name="phone" 
                                               value="<?php echo htmlspecialchars($user['phone_number'] ?? ''); ?>">
                                    </div>

                                    <div class="info-group">
                                        <label for="address">Адрес</label>
                                        <textarea id="address" name="address" rows="3"><?php echo htmlspecialchars($user['address'] ?? ''); ?></textarea>
                                    </div>

                                    <div class="profile-actions">
                                        <button type="submit" class="btn btn--accent">Сохранить изменения</button>
                                        <a href="/account.php" class="btn btn--outline">Отмена</a>
                                    </div>
                                </form>
                                <?php else: ?>
                                <div class="info-group">
                                    <p>Имя - <?php echo htmlspecialchars($user['name']); ?></p>
                                </div>

                                <div class="info-group">
                                    <p>Email - <?php echo htmlspecialchars($user['email']); ?></p>
                                </div>

                                <div class="info-group">
                                    <p>Телефон - <?php echo htmlspecialchars($user['phone_number'] ?? 'Не указан'); ?></p>
                                </div>

                                <div class="info-group">
                                    <p>Адрес - <?php echo htmlspecialchars($user['address'] ?? 'Не указан'); ?></p>
                                </div>

                                <div class="profile-actions">
                                    <a href="/support.php" class="btn btn--accent">Поддержка</a>
                                    <a href="/logout.php" class="btn btn--accent">Выйти из аккаунта</a>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </section>

                    <!-- Секция заказов -->
                    <section id="orders" class="account-section">
                        <h2>Мои заказы</h2>
                        <?php if (empty($orders)): ?>
                            <div class="orders-empty">
                                <p>У вас пока нет заказов</p>
                                <a href="catalog.php" class="btn">Перейти в каталог</a>
                            </div>
                        <?php else: ?>
                            <div class="orders-list">
                                <?php foreach ($orders as $order): ?>
                                    <div class="order-card">
                                        <div class="order-card__header">
                                            <div>
                                                <span class="order-number">Заказ #<?php echo $order['id']; ?></span>
                                                <span class="order-date"><?php echo date('d.m.Y H:i', strtotime($order['created_at'])); ?></span>
                                            </div>
                                        </div>
                                        <div class="order-card__items">
                                            <?php foreach ($order['items'] as $item): ?>
                                                <div class="order-item">
                                                    <img src="<?php echo htmlspecialchars($item['image']); ?>" alt="<?php echo htmlspecialchars($item['name']); ?>">
                                                    <div class="order-item__info">
                                                        <h4><?php echo htmlspecialchars($item['name']); ?></h4>
                                                        <p class="order-item__price"><?php echo number_format($item['price'], 0, ',', ' '); ?> ₽</p>
                                                        <p class="order-item__quantity">Количество: <?php echo $item['quantity']; ?></p>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                        <div class="order-card__footer">
                                            <div class="order-total">
                                                Итого: <?php echo number_format((float)($order['total_amount'] ?? 0), 0, ',', ' '); ?> ₽
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </section>

                    <!-- Избранное -->
                    <section id="favorites" class="account-section">
                        <h2>Избранное</h2>
                        <div class="products__list">
                            <?php foreach ($user['favorites'] as $product): ?>
                            <div class="products__card">
                                <img src="<?php echo htmlspecialchars($product['image']); ?>" 
                                     alt="<?php echo htmlspecialchars($product['name']); ?>">
                                <h3><?php echo htmlspecialchars($product['name']); ?></h3>
                                <p class="price">
                                    <?php echo number_format($product['price'], 0, ',', ' '); ?> ₽
                                </p>
                                <div class="product__actions">
                                    <button class="btn btn--accent">В корзину</button>
                                    <button class="icon-btn active" title="Удалить из избранного"></button>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </section>

                    <!-- Настройки -->
                    <section id="settings" class="account-section">
                        <h2>Настройки</h2>
                        <form class="settings-form">
                            <div class="form-group">
                                <label>Уведомления</label>
                                <div class="checkbox-group">
                                    <label class="checkbox">
                                        <input type="checkbox" name="notify_orders" checked>
                                        <span>Статус заказа</span>
                                    </label>
                                    <label class="checkbox">
                                        <input type="checkbox" name="notify_promo" checked>
                                        <span>Акции и скидки</span>
                                    </label>
                                    <label class="checkbox">
                                        <input type="checkbox" name="notify_news">
                                        <span>Новости магазина</span>
                                    </label>
                                </div>
                            </div>

                            <div class="form-group">
                                <label>Смена пароля</label>
                                <input type="password" name="current_password" placeholder="Текущий пароль">
                                <input type="password" name="new_password" placeholder="Новый пароль">
                                <input type="password" name="confirm_password" placeholder="Подтвердите пароль">
                            </div>

                            <button type="submit" class="btn btn--accent">Сохранить изменения</button>
                        </form>
                    </section>
                </div>
            </div>
        </div>
    </main>

    <?php include 'includes/footer.php'; ?>
    <script src="js/account.js"></script>
</body>
</html> 