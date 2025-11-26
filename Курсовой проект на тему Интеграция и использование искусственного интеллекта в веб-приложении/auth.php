<?php
session_start();
require_once 'db_connect.php';

// Функция для проверки авторизации
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Функция для получения данных текущего пользователя
function getCurrentUser() {
    if (!isLoggedIn()) {
        return null;
    }
    
    global $conn;
    try {
        $stmt = $conn->prepare("SELECT * FROM users WHERE id = :id");
        $stmt->bindParam(':id', $_SESSION['user_id'], PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch(PDOException $e) {
        return null;
    }
}

// Функция для авторизации пользователя
function login($email, $password) {
    global $conn;
    try {
        $stmt = $conn->prepare("SELECT * FROM users WHERE email = :email");
        $stmt->bindParam(':email', $email);
        $stmt->execute();
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user && password_verify($password, $user['password_hash'])) {
            $_SESSION['user_id'] = $user['id'];
            return true;
        }
        return false;
    } catch(PDOException $e) {
        return false;
    }
}

// Функция для выхода из системы
function logout() {
    session_destroy();
}

// Функция для регистрации нового пользователя
function register($name, $email, $password, $phone = '', $address = '') {
    global $conn;
    try {
        error_log("Starting registration process for email: " . $email);
        
        // Проверяем, существует ли пользователь с таким email
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = :email");
        $stmt->bindParam(':email', $email);
        $stmt->execute();
        
        if ($stmt->fetch()) {
            error_log("User with email $email already exists");
            return false;
        }
        
        // Создаем нового пользователя
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        $now = date('Y-m-d H:i:s');
        
        $sql = "INSERT INTO users (name, email, password_hash, phone_number, address, created_at, updated_at) 
                VALUES (:name, :email, :password_hash, :phone_number, :address, :created_at, :updated_at)";
        
        error_log("Preparing SQL query: " . $sql);
        
        $stmt = $conn->prepare($sql);
        
        // Привязываем параметры
        $stmt->bindParam(':name', $name);
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':password_hash', $hashedPassword);
        $stmt->bindParam(':phone_number', $phone);
        $stmt->bindParam(':address', $address);
        $stmt->bindParam(':created_at', $now);
        $stmt->bindParam(':updated_at', $now);
        
        error_log("Executing query with parameters: " . print_r([
            'name' => $name,
            'email' => $email,
            'phone' => $phone,
            'address' => $address,
            'created_at' => $now,
            'updated_at' => $now
        ], true));
        
        $result = $stmt->execute();
        
        if ($result) {
            error_log("Registration successful for user: " . $email);
            return true;
        } else {
            error_log("Registration failed. PDO Error Info: " . print_r($stmt->errorInfo(), true));
            return false;
        }
    } catch(PDOException $e) {
        error_log("Registration error: " . $e->getMessage());
        return false;
    }
}

// Функция для обновления данных пользователя
function updateUser($userId, $data) {
    global $conn;
    try {
        $allowedFields = ['name', 'email', 'phone', 'address'];
        $updates = [];
        $params = [];
        
        foreach ($data as $field => $value) {
            if (in_array($field, $allowedFields)) {
                $updates[] = "$field = :$field";
                $params[":$field"] = $value;
            }
        }
        
        if (empty($updates)) {
            return false;
        }
        
        $params[':id'] = $userId;
        $sql = "UPDATE users SET " . implode(', ', $updates) . " WHERE id = :id";
        $stmt = $conn->prepare($sql);
        
        return $stmt->execute($params);
    } catch(PDOException $e) {
        return false;
    }
}

// Функция для проверки прав администратора
function isAdmin() {
    if (!isLoggedIn()) {
        return false;
    }
    
    global $conn;
    try {
        $stmt = $conn->prepare("SELECT is_admin FROM users WHERE id = :user_id");
        $stmt->bindParam(':user_id', $_SESSION['user_id'], PDO::PARAM_INT);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $result && $result['is_admin'] == 1;
    } catch(PDOException $e) {
        error_log("Error in isAdmin: " . $e->getMessage());
        return false;
    }
}

// Функция для получения всех пользователей (только для админов)
function getAllUsers() {
    if (!isAdmin()) {
        return [];
    }
    
    global $conn;
    try {
        $stmt = $conn->query("SELECT id, name, email, phone_number, address, is_admin, created_at FROM users ORDER BY created_at DESC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch(PDOException $e) {
        error_log("Error in getAllUsers: " . $e->getMessage());
        return [];
    }
}

// Функция для удаления пользователя (только для админов)
function deleteUser($userId) {
    if (!isAdmin()) {
        return false;
    }
    
    global $conn;
    try {
        $stmt = $conn->prepare("DELETE FROM users WHERE id = :id");
        $stmt->bindParam(':id', $userId, PDO::PARAM_INT);
        return $stmt->execute();
    } catch(PDOException $e) {
        error_log("Error in deleteUser: " . $e->getMessage());
        return false;
    }
}

// Функция для изменения прав администратора (только для админов)
function updateAdminStatus($userId, $isAdmin) {
    if (!isAdmin()) {
        return false;
    }
    
    global $conn;
    try {
        $stmt = $conn->prepare("UPDATE users SET is_admin = :is_admin WHERE id = :id");
        $stmt->bindParam(':is_admin', $isAdmin, PDO::PARAM_INT);
        $stmt->bindParam(':id', $userId, PDO::PARAM_INT);
        return $stmt->execute();
    } catch(PDOException $e) {
        error_log("Error in updateAdminStatus: " . $e->getMessage());
        return false;
    }
}

// Функция для получения заказов пользователя
function getUserOrders($userId) {
    global $conn;
    try {
        // Получаем все заказы пользователя
        $stmt = $conn->prepare("
            SELECT o.id, o.customer_id, o.contact_phone, o.notes, o.created_at, 
                   GROUP_CONCAT(
                       CONCAT(p.name, '|', oi.quantity, '|', oi.price, '|', p.image)
                       SEPARATOR '||'
                   ) as items,
                   SUM(oi.quantity * oi.price) as total_amount
            FROM orders o
            LEFT JOIN order_items oi ON o.id = oi.order_id
            LEFT JOIN products p ON oi.product_id = p.id
            WHERE o.customer_id = :user_id
            GROUP BY o.id
            ORDER BY o.created_at DESC
        ");
        
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $stmt->execute();
        $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Форматируем данные заказов
        foreach ($orders as &$order) {
            $order['items'] = [];
            if ($order['items']) {
                $items = explode('||', $order['items']);
                foreach ($items as $item) {
                    list($name, $quantity, $price, $image) = explode('|', $item);
                    $order['items'][] = [
                        'name' => $name,
                        'quantity' => $quantity,
                        'price' => $price,
                        'image' => $image
                    ];
                }
            }
        }
        
        return $orders;
    } catch(PDOException $e) {
        error_log("Error in getUserOrders: " . $e->getMessage());
        return [];
    }
}

// Функция для получения всех заказов (только для админов)
function getAllOrders() {
    if (!isAdmin()) {
        return [];
    }
    
    global $conn;
    try {
        // Получаем все заказы с информацией о пользователях и товарах
        $stmt = $conn->prepare("
            SELECT o.*, 
                   u.name as user_name,
                   u.email as user_email,
                   GROUP_CONCAT(
                       CONCAT(p.name, '|', oi.quantity, '|', oi.price, '|', p.image)
                       SEPARATOR '||'
                   ) as items
            FROM orders o
            LEFT JOIN users u ON o.customer_id = u.id
            LEFT JOIN order_items oi ON o.id = oi.order_id
            LEFT JOIN products p ON oi.product_id = p.id
            GROUP BY o.id
            ORDER BY o.created_at DESC
        ");
        
        $stmt->execute();
        $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Форматируем данные заказов
        foreach ($orders as &$order) {
            $order['items'] = [];
            if ($order['items']) {
                $items = explode('||', $order['items']);
                foreach ($items as $item) {
                    list($name, $quantity, $price, $image) = explode('|', $item);
                    $order['items'][] = [
                        'name' => $name,
                        'quantity' => $quantity,
                        'price' => $price,
                        'image' => $image
                    ];
                }
            }
        }
        
        return $orders;
    } catch(PDOException $e) {
        error_log("Error in getAllOrders: " . $e->getMessage());
        return [];
    }
}
?> 