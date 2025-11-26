<?php
require_once 'db_connect.php';

function getProducts($limit = 8) {
    global $conn;
    try {
        $stmt = $conn->prepare("SELECT * FROM products ORDER BY created_at DESC LIMIT :limit");
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch(PDOException $e) {
        echo "Error: " . $e->getMessage();
        return [];
    }
}

function getProductsByCategory($category, $limit = 4, $priceMin = null, $priceMax = null) {
    global $conn;
    try {
        $sql = "SELECT id, name, price, image, description 
                FROM products 
                WHERE category = :category";
        $params = [':category' => $category];
        
        if ($priceMin !== null) {
            $sql .= " AND price >= :price_min";
            $params[':price_min'] = $priceMin;
        }
        if ($priceMax !== null) {
            $sql .= " AND price <= :price_max";
            $params[':price_max'] = $priceMax;
        }
        
        $sql .= " ORDER BY created_at DESC";
        
        if ($limit !== null) {
            $sql .= " LIMIT :limit";
            $params[':limit'] = $limit;
        }
        
        $stmt = $conn->prepare($sql);
        
        foreach ($params as $key => $value) {
            if ($key === ':limit') {
                $stmt->bindValue($key, $value, PDO::PARAM_INT);
            } else {
                $stmt->bindValue($key, $value);
            }
        }
        
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch(PDOException $e) {
        error_log("Error in getProductsByCategory: " . $e->getMessage());
        return [];
    }
}

function getTotalProducts() {
    global $conn;
    try {
        $stmt = $conn->query("SELECT COUNT(*) as total FROM products");
        return $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    } catch(PDOException $e) {
        error_log("Error in getTotalProducts: " . $e->getMessage());
        return 0;
    }
}

function getPopularProducts($limit = 8) {
    global $conn;
    try {
        $stmt = $conn->prepare("
            SELECT id, name, price, image, description 
            FROM products 
            ORDER BY views DESC 
            LIMIT :limit
        ");
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch(PDOException $e) {
        error_log("Error in getPopularProducts: " . $e->getMessage());
        return [];
    }
}

function getProductById($id) {
    global $conn;
    try {
        $stmt = $conn->prepare("SELECT * FROM products WHERE id = :id");
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch(PDOException $e) {
        error_log("Error in getProductById: " . $e->getMessage());
        return null;
    }
}

function addProduct($name, $description, $price, $category, $image) {
    global $conn;
    try {
        // Валидация входных данных
        if (empty($name) || empty($description) || empty($category) || $price <= 0) {
            error_log("Invalid input data in addProduct: name=$name, price=$price, category=$category");
            return false;
        }

        // Проверяем подключение к базе данных
        if (!$conn) {
            error_log("Database connection is not available in addProduct");
            return false;
        }

        // Проверяем существование таблицы
        $checkTable = $conn->query("SHOW TABLES LIKE 'products'");
        if ($checkTable->rowCount() == 0) {
            error_log("Table 'products' does not exist");
            return false;
        }

        // Проверяем структуру таблицы
        $columns = $conn->query("SHOW COLUMNS FROM products")->fetchAll(PDO::FETCH_COLUMN);
        $requiredColumns = ['name', 'description', 'price', 'category', 'image', 'created_at'];
        $missingColumns = array_diff($requiredColumns, $columns);
        if (!empty($missingColumns)) {
            error_log("Missing columns in products table: " . implode(', ', $missingColumns));
            return false;
        }

        // Проверяем наличие колонки updated_at
        $hasUpdatedAt = in_array('updated_at', $columns);
        
        // Логируем входные данные
        error_log("Attempting to add product with data: " . 
                 "name=" . $name . 
                 ", price=" . $price . 
                 ", category=" . $category . 
                 ", image=" . $image);

        // Формируем SQL запрос в зависимости от наличия колонки updated_at
        $sql = "INSERT INTO products (name, description, price, category, image, created_at" . 
               ($hasUpdatedAt ? ", updated_at" : "") . 
               ") VALUES (:name, :description, :price, :category, :image, NOW()" . 
               ($hasUpdatedAt ? ", NOW()" : "") . 
               ")";

        $stmt = $conn->prepare($sql);
        
        if (!$stmt) {
            error_log("Failed to prepare statement in addProduct. Error: " . print_r($conn->errorInfo(), true));
            return false;
        }

        // Привязываем параметры с проверкой типов
        $stmt->bindParam(':name', $name, PDO::PARAM_STR);
        $stmt->bindParam(':description', $description, PDO::PARAM_STR);
        $stmt->bindParam(':price', $price, PDO::PARAM_STR); // Используем строку для decimal
        $stmt->bindParam(':category', $category, PDO::PARAM_STR);
        $stmt->bindParam(':image', $image, PDO::PARAM_STR);
        
        // Выполняем запрос
        $result = $stmt->execute();
        if (!$result) {
            error_log("Error in addProduct: Failed to execute statement. Error info: " . print_r($stmt->errorInfo(), true));
            return false;
        }
        
        $lastId = $conn->lastInsertId();
        error_log("Product successfully added with ID: " . $lastId);
        return true;
    } catch(PDOException $e) {
        error_log("Error in addProduct: " . $e->getMessage() . 
                 "\nSQL State: " . $e->getCode() . 
                 "\nStack trace: " . $e->getTraceAsString());
        return false;
    }
}

function updateProduct($id, $name, $description, $price, $category, $image) {
    global $conn;
    try {
        // Логируем входные данные
        error_log("Attempting to update product with data: " . 
                 "id=" . $id . 
                 ", name=" . $name . 
                 ", price=" . $price . 
                 ", category=" . $category . 
                 ", image=" . $image);

        // Проверяем подключение к базе данных
        if (!$conn) {
            error_log("Database connection is not available in updateProduct");
            return false;
        }

        // Проверяем структуру таблицы
        $columns = $conn->query("SHOW COLUMNS FROM products")->fetchAll(PDO::FETCH_COLUMN);
        $hasUpdatedAt = in_array('updated_at', $columns);

        // Формируем SQL запрос в зависимости от наличия колонки updated_at
        $sql = "UPDATE products 
                SET name = :name,
                    description = :description,
                    price = :price,
                    category = :category,
                    image = :image" . 
                    ($hasUpdatedAt ? ", updated_at = NOW()" : "") . 
                " WHERE id = :id";

        $stmt = $conn->prepare($sql);
        
        if (!$stmt) {
            error_log("Failed to prepare statement in updateProduct. Error: " . print_r($conn->errorInfo(), true));
            return false;
        }

        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->bindParam(':name', $name, PDO::PARAM_STR);
        $stmt->bindParam(':description', $description, PDO::PARAM_STR);
        $stmt->bindParam(':price', $price, PDO::PARAM_STR);
        $stmt->bindParam(':category', $category, PDO::PARAM_STR);
        $stmt->bindParam(':image', $image, PDO::PARAM_STR);
        
        $result = $stmt->execute();
        if (!$result) {
            error_log("Error in updateProduct: Failed to execute statement. Error info: " . print_r($stmt->errorInfo(), true));
            return false;
        }

        $rowCount = $stmt->rowCount();
        error_log("Product update affected rows: " . $rowCount);
        return $rowCount > 0;
    } catch(PDOException $e) {
        error_log("Error in updateProduct: " . $e->getMessage() . 
                 "\nSQL State: " . $e->getCode() . 
                 "\nStack trace: " . $e->getTraceAsString());
        return false;
    }
}

function deleteProduct($id) {
    global $conn;
    try {
        $product = getProductById($id);
        if ($product && $product['image']) {
            if (file_exists($product['image'])) {
                unlink($product['image']);
            }
        }
        
        $stmt = $conn->prepare("DELETE FROM products WHERE id = :id");
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        return $stmt->execute();
    } catch(PDOException $e) {
        error_log("Error in deleteProduct: " . $e->getMessage());
        return false;
    }
}

function getAllProducts() {
    global $conn;
    try {
        $stmt = $conn->query("SELECT * FROM products ORDER BY created_at DESC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch(PDOException $e) {
        error_log("Error in getAllProducts: " . $e->getMessage());
        return [];
    }
}
?> 