<?php
require_once 'db_connect.php';

try {
    // Проверяем существование базы данных
    $stmt = $conn->query("SELECT DATABASE()");
    $currentDb = $stmt->fetchColumn();
    echo "Текущая база данных: " . $currentDb . "<br>";

    // Проверяем и добавляем колонку updated_at в таблицу products
    try {
        $checkColumn = $conn->query("SHOW COLUMNS FROM products LIKE 'updated_at'");
        if ($checkColumn->rowCount() == 0) {
            $conn->exec("ALTER TABLE products ADD COLUMN updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP");
            echo "Добавлена колонка updated_at в таблицу products<br>";
        }
    } catch (PDOException $e) {
        echo "Ошибка при проверке/добавлении колонки updated_at: " . $e->getMessage() . "<br>";
    }

    // Читаем SQL файл
    $sql = file_get_contents('PixelMarket.sql');
    if ($sql === false) {
        throw new Exception("Не удалось прочитать файл PixelMarket.sql");
    }

    // Разбиваем SQL на отдельные запросы
    $queries = array_filter(array_map('trim', explode(';', $sql)));
    
    $successCount = 0;
    $errorCount = 0;
    
    foreach ($queries as $query) {
        if (empty($query)) continue;
        
        try {
            // Пропускаем запросы CREATE TABLE, если таблица уже существует
            if (stripos($query, 'CREATE TABLE') !== false) {
                $tableName = '';
                if (preg_match('/CREATE TABLE `?(\w+)`?/i', $query, $matches)) {
                    $tableName = $matches[1];
                    
                    // Проверяем существование таблицы
                    $checkTable = $conn->query("SHOW TABLES LIKE '$tableName'");
                    if ($checkTable->rowCount() > 0) {
                        echo "Таблица '$tableName' уже существует, пропускаем создание<br>";
                        continue;
                    }
                }
            }
            
            // Пропускаем запросы ALTER TABLE для индексов и внешних ключей
            if (stripos($query, 'ALTER TABLE') !== false) {
                if (stripos($query, 'ADD PRIMARY KEY') !== false || 
                    stripos($query, 'ADD KEY') !== false || 
                    stripos($query, 'ADD CONSTRAINT') !== false) {
                    echo "Индекс или внешний ключ уже существует, пропускаем создание<br>";
                    continue;
                }
            }
            
            // Пропускаем INSERT запросы, если данные уже существуют
            if (stripos($query, 'INSERT INTO') !== false) {
                $tableName = '';
                if (preg_match('/INSERT INTO `?(\w+)`?/i', $query, $matches)) {
                    $tableName = $matches[1];
                    $checkData = $conn->query("SELECT COUNT(*) as count FROM $tableName");
                    $count = $checkData->fetch()['count'];
                    if ($count > 0) {
                        echo "Данные в таблице '$tableName' уже существуют, пропускаем вставку<br>";
                        continue;
                    }
                }
            }
            
            // Выполняем запрос
            $result = $conn->exec($query);
            if ($result !== false) {
                $successCount++;
            }
        } catch (PDOException $e) {
            // Пропускаем ошибки о дублировании ключей и индексов
            if (in_array($e->getCode(), ['42S01', '23000', '42000', '1022'])) {
                echo "Объект уже существует, пропускаем создание<br>";
                continue;
            }
            $errorCount++;
            echo "Ошибка при выполнении запроса: " . $e->getMessage() . "<br>";
        }
    }
    
    echo "<br>Настройка базы данных завершена:<br>";
    echo "Успешно выполнено запросов: $successCount<br>";
    echo "Ошибок: $errorCount<br>";
    
    if ($errorCount == 0) {
        echo "<br>База данных успешно настроена!";
    } else {
        echo "<br>База данных настроена с ошибками. Проверьте сообщения выше.";
    }
    
} catch(Exception $e) {
    echo "Ошибка: " . $e->getMessage() . "<br>";
    echo "Код ошибки: " . $e->getCode() . "<br>";
    if (isset($conn)) {
        $error = $conn->errorInfo();
        echo "SQL ошибка: " . $error[2] . "<br>";
    }
}
?> 