<?php
require_once 'auth.php';

// Выполняем выход
logout();

// Перенаправляем на главную страницу
header('Location: /');
exit;
?> 