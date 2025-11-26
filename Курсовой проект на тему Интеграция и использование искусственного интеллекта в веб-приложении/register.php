<?php
require_once 'auth.php';
require_once 'db_connect.php';

// Если пользователь уже авторизован, перенаправляем в личный кабинет
if (isLoggedIn()) {
    header('Location: /account.php');
    exit;
}

$error = '';
$success = '';

// Обработка формы регистрации
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'] ?? '';
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    $phone = $_POST['phone'] ?? '';
    $address = $_POST['address'] ?? '';
    
    // Валидация
    if (empty($name) || empty($email) || empty($password)) {
        $error = 'Все поля обязательны для заполнения';
    } elseif ($password !== $confirmPassword) {
        $error = 'Пароли не совпадают';
    } elseif (strlen($password) < 6) {
        $error = 'Пароль должен быть не менее 6 символов';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Некорректный email адрес';
    } else {
        // Попытка регистрации
        if (register($name, $email, $password, $phone, $address)) {
            $success = 'Регистрация успешна! Теперь вы можете войти.';
        } else {
            $error = 'Пользователь с таким email уже существует';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Регистрация — PixelMarket</title>
    <link rel="stylesheet" href="css/style.css">
    <link href="https://fonts.googleapis.com/css?family=Montserrat:400,700&display=swap" rel="stylesheet">
</head>
<body>
    <!-- Header -->
    <?php include 'includes/header.php'; ?>

    <main>
        <div class="container">
            <div class="auth-form">
                <h1>Регистрация</h1>
                
                <?php if ($error): ?>
                <div class="alert alert--error">
                    <?php echo htmlspecialchars($error); ?>
                </div>
                <?php endif; ?>

                <?php if ($success): ?>
                <div class="alert alert--success">
                    <?php echo htmlspecialchars($success); ?>
                    <p><a href="/login.php">Перейти на страницу входа</a></p>
                </div>
                <?php endif; ?>

                <form method="POST" action="/register.php">
                    <div class="form-group">
                        <label for="name">Имя</label>
                        <input type="text" id="name" name="name" required 
                               value="<?php echo isset($_POST['name']) ? htmlspecialchars($_POST['name']) : ''; ?>">
                    </div>

                    <div class="form-group">
                        <label for="email">Email</label>
                        <input type="email" id="email" name="email" required
                               value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                    </div>

                    <div class="form-group">
                        <label for="phone">Телефон</label>
                        <input type="tel" id="phone" name="phone" 
                               value="<?php echo isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : ''; ?>">
                    </div>

                    <div class="form-group">
                        <label for="address">Адрес</label>
                        <textarea id="address" name="address" rows="3"><?php echo isset($_POST['address']) ? htmlspecialchars($_POST['address']) : ''; ?></textarea>
                    </div>

                    <div class="form-group">
                        <label for="password">Пароль</label>
                        <input type="password" id="password" name="password" required>
                    </div>

                    <div class="form-group">
                        <label for="confirm_password">Подтвердите пароль</label>
                        <input type="password" id="confirm_password" name="confirm_password" required>
                    </div>

                    <button type="submit" class="btn btn--accent">Зарегистрироваться</button>
                </form>

                <div class="auth-form__links">
                    <p>Уже есть аккаунт? <a href="/login.php">Войти</a></p>
                </div>
            </div>
        </div>
    </main>

    <!-- Footer -->
    <?php include 'includes/footer.php'; ?>
</body>
</html> 