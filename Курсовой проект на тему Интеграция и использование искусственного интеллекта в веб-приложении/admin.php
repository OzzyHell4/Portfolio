<?php
require_once 'auth.php';
require_once 'get_products.php';

// Проверка прав администратора
if (!isLoggedIn() || !isAdmin()) {
    header('Location: /login.php');
    exit;
}

$error = '';
$success = '';

// Обработка добавления/редактирования товара
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'] ?? null;
    $name = $_POST['name'] ?? '';
    $description = $_POST['description'] ?? '';
    $price = floatval($_POST['price'] ?? 0);
    $category = $_POST['category'] ?? '';
    $image = $_FILES['image'] ?? null;

    // Добавляем логирование для отладки
    error_log("POST data received: " . print_r($_POST, true));
    error_log("FILES data received: " . print_r($_FILES, true));

    if (empty($name) || empty($description) || empty($category) || $price <= 0) {
        $error = 'Все поля обязательны для заполнения';
    } else {
        // Обработка загрузки изображения
        $imagePath = '';
        if ($image && $image['error'] === UPLOAD_ERR_OK) {
            $uploadDir = ROOT_PATH . '/img/products/';
            error_log("Upload directory path: " . $uploadDir);
            
            // Проверяем и создаем директорию
            if (!file_exists($uploadDir)) {
                error_log("Attempting to create directory: " . $uploadDir);
                if (!mkdir($uploadDir, 0777, true)) {
                    $error = 'Не удалось создать директорию для загрузки изображений. Проверьте права доступа.';
                    error_log("Failed to create directory: " . $uploadDir . ". Error: " . error_get_last()['message']);
                }
            }
            
            if (empty($error)) {
                // Проверяем права доступа
                if (!is_writable($uploadDir)) {
                    $error = 'Нет прав на запись в директорию для загрузки изображений. Текущие права: ' . substr(sprintf('%o', fileperms($uploadDir)), -4);
                    error_log("Directory not writable: " . $uploadDir . ". Current permissions: " . substr(sprintf('%o', fileperms($uploadDir)), -4));
                    // Пытаемся изменить права доступа
                    chmod($uploadDir, 0777);
                    error_log("Changed permissions for directory: " . $uploadDir);
                }
                
                $extension = strtolower(pathinfo($image['name'], PATHINFO_EXTENSION));
                $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif'];
                
                if (!in_array($extension, $allowedExtensions)) {
                    $error = 'Недопустимый формат изображения. Разрешены: jpg, jpeg, png, gif';
                    error_log("Invalid file extension: " . $extension);
                } else {
                    $filename = uniqid() . '.' . $extension;
                    $imagePath = 'img/products/' . $filename; // Относительный путь для БД
                    $fullPath = $uploadDir . $filename; // Полный путь для загрузки
                    
                    error_log("Attempting to move uploaded file to: " . $fullPath);
                    if (!move_uploaded_file($image['tmp_name'], $fullPath)) {
                        $error = 'Ошибка при загрузке изображения. Код ошибки: ' . $image['error'];
                        error_log("Failed to move uploaded file. Error code: " . $image['error'] . ". PHP error: " . error_get_last()['message']);
                    } else {
                        error_log("File successfully uploaded to: " . $fullPath);
                        // Если это редактирование и есть новое изображение, удаляем старое
                        if ($id && isset($_POST['old_image'])) {
                            $oldImage = ROOT_PATH . '/' . $_POST['old_image'];
                            if (file_exists($oldImage)) {
                                if (!unlink($oldImage)) {
                                    error_log("Failed to delete old image: " . $oldImage);
                                } else {
                                    error_log("Successfully deleted old image: " . $oldImage);
                                }
                            }
                        }
                    }
                }
            }
        } elseif ($id) {
            // При редактировании сохраняем старое изображение
            $imagePath = $_POST['old_image'] ?? '';
        } else {
            $error = 'Изображение обязательно для нового товара';
        }

        if (empty($error)) {
            try {
                if ($id) {
                    // Редактирование существующего товара
                    if (updateProduct($id, $name, $description, $price, $category, $imagePath)) {
                        $success = 'Товар успешно обновлен';
                        header('Location: admin.php?success=updated');
                        exit;
                    } else {
                        $error = 'Ошибка при обновлении товара. Проверьте логи сервера для подробностей.';
                    }
                } else {
                    // Добавление нового товара
                    if (addProduct($name, $description, $price, $category, $imagePath)) {
                        $success = 'Товар успешно добавлен';
                        header('Location: admin.php?success=added');
                        exit;
                    } else {
                        $error = 'Ошибка при добавлении товара. Проверьте логи сервера для подробностей.';
                    }
                }
            } catch (Exception $e) {
                $error = 'Произошла ошибка: ' . $e->getMessage();
                error_log("Error saving product: " . $e->getMessage() . "\nStack trace: " . $e->getTraceAsString());
            }
        }
    }
}

// Обработка удаления товара
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    if (deleteProduct($id)) {
        $success = 'Товар успешно удален';
    } else {
        $error = 'Ошибка при удалении товара';
    }
}

// Получение списка всех товаров
$products = getAllProducts();

// Получение товара для редактирования
$editProduct = null;
if (isset($_GET['edit'])) {
    if ($_GET['edit'] === 'new') {
        $editProduct = [
            'id' => null,
            'name' => '',
            'description' => '',
            'price' => '',
            'category' => '',
            'image' => ''
        ];
    } else {
        $editProduct = getProductById(intval($_GET['edit']));
    }
}

// Обработка действий

// Получаем данные
$orders = getAllOrders();
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Админ-панель — PixelMarket</title>
    <link rel="stylesheet" href="css/style.css">
    <link href="https://fonts.googleapis.com/css?family=Montserrat:400,700&display=swap" rel="stylesheet">
</head>
<body>
    <!-- Header -->
    <?php include 'includes/header.php'; ?>

    <main>
        <div class="container">
            <div class="admin-panel">
                <h1>Админ-панель</h1>

                <?php if ($error): ?>
                <div class="alert alert--error">
                    <?php echo htmlspecialchars($error); ?>
                </div>
                <?php endif; ?>

                <?php if ($success): ?>
                <div class="alert alert--success">
                    <?php echo htmlspecialchars($success); ?>
                </div>
                <?php endif; ?>

                <!-- Товары -->
                <section class="admin-section">
                    <div class="section-header">
                        <h2>Управление товарами</h2>
                        <a href="?edit=new" class="btn btn--accent">Добавить товар</a>
                    </div>

                    <?php if ($editProduct !== null): ?>
                    <form method="POST" action="admin.php" enctype="multipart/form-data" class="admin-form product-form">
                        <input type="hidden" name="id" value="<?php echo $editProduct['id'] ?? ''; ?>">
                        <?php if (isset($editProduct['image']) && $editProduct['image']): ?>
                        <input type="hidden" name="old_image" value="<?php echo htmlspecialchars($editProduct['image']); ?>">
                        <?php endif; ?>

                        <div class="form-grid">
                            <div class="form-group">
                                <label for="name">Название товара</label>
                                <input type="text" id="name" name="name" required 
                                       value="<?php echo htmlspecialchars($editProduct['name'] ?? ''); ?>"
                                       placeholder="Введите название товара">
                            </div>

                            <div class="form-group">
                                <label for="price">Цена (₽)</label>
                                <input type="number" id="price" name="price" required min="0" step="0.01"
                                       value="<?php echo htmlspecialchars($editProduct['price'] ?? ''); ?>"
                                       placeholder="0.00">
                            </div>

                            <div class="form-group">
                                <label for="category">Категория</label>
                                <select id="category" name="category" required>
                                    <option value="">Выберите категорию</option>
                                    <option value="smartphones" <?php echo (isset($editProduct['category']) && $editProduct['category'] === 'smartphones') ? 'selected' : ''; ?>>Смартфоны</option>
                                    <option value="laptops" <?php echo (isset($editProduct['category']) && $editProduct['category'] === 'laptops') ? 'selected' : ''; ?>>Ноутбуки</option>
                                    <option value="computers" <?php echo (isset($editProduct['category']) && $editProduct['category'] === 'computers') ? 'selected' : ''; ?>>Компьютеры</option>
                                    <option value="tv" <?php echo (isset($editProduct['category']) && $editProduct['category'] === 'tv') ? 'selected' : ''; ?>>Телевизоры</option>
                                    <option value="audio" <?php echo (isset($editProduct['category']) && $editProduct['category'] === 'audio') ? 'selected' : ''; ?>>Аудио</option>
                                    <option value="accessories" <?php echo (isset($editProduct['category']) && $editProduct['category'] === 'accessories') ? 'selected' : ''; ?>>Аксессуары</option>
                                </select>
                            </div>

                            <div class="form-group">
                                <label for="image">Изображение товара</label>
                                <?php if (isset($editProduct['image']) && $editProduct['image']): ?>
                                <div class="current-image">
                                    <img src="<?php echo htmlspecialchars($editProduct['image']); ?>" alt="Текущее изображение">
                                </div>
                                <?php endif; ?>
                                <div class="file-input-wrapper">
                                    <input type="file" id="image" name="image" accept="image/*" <?php echo !isset($editProduct['id']) ? 'required' : ''; ?>>
                                    <label for="image" class="file-input-label">
                                        <span class="file-input-text">Выберите файл</span>
                                    </label>
                                </div>
                            </div>

                            <div class="form-group full-width">
                                <label for="description">Описание товара</label>
                                <textarea id="description" name="description" required rows="5" 
                                          placeholder="Введите описание товара"><?php echo htmlspecialchars($editProduct['description'] ?? ''); ?></textarea>
                            </div>
                        </div>

                        <div class="form-actions">
                            <button type="submit" class="btn btn--accent">Сохранить товар</button>
                            <a href="/admin.php" class="btn btn--outline">Отмена</a>
                        </div>
                    </form>
                    <?php else: ?>
                    <div class="products-table-wrapper">
                        <table class="products-table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Изображение</th>
                                    <th>Название</th>
                                    <th>Категория</th>
                                    <th>Цена</th>
                                    <th>Действия</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($products as $product): ?>
                                <tr>
                                    <td class="product-id">#<?php echo $product['id']; ?></td>
                                    <td class="product-image">
                                        <img src="<?php echo htmlspecialchars($product['image']); ?>" 
                                             alt="<?php echo htmlspecialchars($product['name']); ?>"
                                             class="product-thumbnail">
                                    </td>
                                    <td class="product-name"><?php echo htmlspecialchars($product['name']); ?></td>
                                    <td class="product-category"><?php echo htmlspecialchars($product['category']); ?></td>
                                    <td class="product-price"><?php echo number_format($product['price'], 0, ',', ' '); ?> ₽</td>
                                    <td class="product-actions">
                                        <a href="?edit=<?php echo $product['id']; ?>" class="btn btn--small btn--edit" title="Редактировать">
                                            <span class="icon">✎</span>
                                        </a>
                                        <a href="?delete=<?php echo $product['id']; ?>" 
                                           class="btn btn--small btn--danger" 
                                           title="Удалить"
                                           onclick="return confirm('Вы уверены, что хотите удалить этот товар?');">
                                            <span class="icon">×</span>
                                        </a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php endif; ?>
                </section>
            </div>
        </div>
    </main>

    <!-- Footer -->
    <?php include 'includes/footer.php'; ?>
</body>
</html> 