<?php
session_start();
require_once 'includes/header.php';
require_once 'auth.php';

if (!isLoggedIn()) {
    header('Location: login.php');
    exit();
}

$orderNumber = $_GET['order'] ?? '';
if (empty($orderNumber)) {
    header('Location: index.php');
    exit();
}
?>

<div class="container mt-4">
    <div class="success-message">
        <h1>Заказ успешно оформлен!</h1>
        <div class="order-details">
            <p>Номер вашего заказа: <strong><?php echo htmlspecialchars($orderNumber); ?></strong></p>
            <p>Мы отправили подтверждение на вашу электронную почту.</p>
            <p>Вы можете отслеживать статус заказа в <a href="account.php?section=orders">личном кабинете</a>.</p>
        </div>
        <div class="order-actions">
            <a href="index.php" class="btn btn--accent">Вернуться на главную</a>
            <a href="account.php?section=orders" class="btn btn--outline">Мои заказы</a>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?> 