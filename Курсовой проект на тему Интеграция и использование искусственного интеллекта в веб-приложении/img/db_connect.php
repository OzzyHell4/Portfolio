<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "pixelmarket";

// Определяем абсолютный путь к корневой директории
define('ROOT_PATH', dirname(__FILE__));

try {
    // Проверяем существование базы данных
    $tempConn = new PDO("mysql:host=$servername", $username, $password);
    $tempConn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Проверяем существование базы данных
    $stmt = $tempConn->query("SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = '$dbname'");
    if (!$stmt->fetch()) {
        // Создаем базу данных, если она не существует
        $tempConn->exec("CREATE DATABASE IF NOT EXISTS `$dbname` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        error_log("Database '$dbname' created successfully");
    }
    
    // Подключаемся к базе данных
    $conn = new PDO("mysql:host=$servername;dbname=$dbname;charset=utf8mb4", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    
    // Проверяем соединение
    $conn->query("SELECT 1");
    error_log("Database connection successful");

    // Проверяем и создаем необходимые директории
    $uploadDir = ROOT_PATH . '/img/products';
    if (!file_exists($uploadDir)) {
        if (!mkdir($uploadDir, 0777, true)) {
            error_log("Failed to create upload directory: " . $uploadDir);
        } else {
            error_log("Created upload directory: " . $uploadDir);
        }
    }
    
    // Проверяем права доступа к директории загрузки
    if (!is_writable($uploadDir)) {
        error_log("Upload directory is not writable: " . $uploadDir);
        chmod($uploadDir, 0777);
        error_log("Changed permissions for upload directory: " . $uploadDir);
    }

} catch(PDOException $e) {
    error_log("Database Connection failed: " . $e->getMessage() . "\nSQL State: " . $e->getCode());
    die("Ошибка подключения к базе данных. Пожалуйста, проверьте настройки подключения и попробуйте снова.");
}
?> 