<?php
require_once 'get_products.php';

// Получаем параметры фильтрации
$category = $_GET['category'] ?? '';
$search = $_GET['q'] ?? '';
$page = max(1, intval($_GET['page'] ?? 1));
$perPage = 12;

// Получаем товары с учетом фильтров
$products = [];
$totalProducts = 0;

if (!empty($category)) {
    $products = getProductsByCategory($category, null);
    $totalProducts = count($products);
} elseif (!empty($search)) {
    // Поиск по названию товара
    global $conn;
    try {
        $stmt = $conn->prepare("
            SELECT * FROM products 
            WHERE name LIKE :search 
            ORDER BY created_at DESC
        ");
        $searchTerm = "%{$search}%";
        $stmt->bindParam(':search', $searchTerm);
        $stmt->execute();
        $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $totalProducts = count($products);
    } catch(PDOException $e) {
        error_log("Error in search: " . $e->getMessage());
    }
} else {
    $products = getAllProducts();
    $totalProducts = count($products);
}

// Пагинация
$totalPages = ceil($totalProducts / $perPage);
$offset = ($page - 1) * $perPage;
$products = array_slice($products, $offset, $perPage);

// Получаем название категории для заголовка
$categoryNames = [
    'smartphones' => 'Смартфоны',
    'laptops' => 'Ноутбуки',
    'computers' => 'Компьютеры',
    'tv' => 'Телевизоры',
    'audio' => 'Аудио',
    'accessories' => 'Аксессуары'
];
$categoryTitle = $categoryNames[$category] ?? 'Все товары';
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($categoryTitle); ?> — PixelMarket</title>
    <link rel="stylesheet" href="css/style.css">
    <link href="https://fonts.googleapis.com/css?family=Montserrat:400,700&display=swap" rel="stylesheet">
</head>
<body>
    <!-- Header -->
    <?php include 'includes/header.php'; ?>

    <main>
        <div class="container">
            <div class="catalog">
                <h1><?php echo htmlspecialchars($categoryTitle); ?></h1>

                <?php if (!empty($search)): ?>
                <p class="search-results">Результаты поиска по запросу: "<?php echo htmlspecialchars($search); ?>"</p>
                <?php endif; ?>

                <?php if (empty($products)): ?>
                <div class="no-products">
                    <p>Товары не найдены</p>
                    <a href="/catalog.php" class="btn btn--accent">Вернуться в каталог</a>
                </div>
                <?php else: ?>
                <div class="products__list">
                    <?php foreach ($products as $product): ?>
                    <div class="products__card">
                        <a href="product.php?id=<?php echo $product['id']; ?>">
                             <img src="<?php echo htmlspecialchars($product['image']); ?>" 
                                 alt="<?php echo htmlspecialchars($product['name']); ?>">
                            <h3><?php echo htmlspecialchars($product['name']); ?></h3>
                        </a>
                        <p class="price"><?php echo number_format($product['price'], 0, ',', ' '); ?> ₽</p>
                        <div class="product__actions">
                            <form action="add_to_cart.php" method="POST">
                                <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                                <button type="submit" class="btn btn--accent">В корзину</button>
                            </form>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>

                <?php if ($totalPages > 1): ?>
                <div class="pagination">
                    <?php if ($page > 1): ?>
                    <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page - 1])); ?>" 
                       class="btn btn--outline">&larr; Назад</a>
                    <?php endif; ?>

                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                    <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>" 
                       class="btn btn--outline <?php echo $i === $page ? 'active' : ''; ?>">
                        <?php echo $i; ?>
                    </a>
                    <?php endfor; ?>

                    <?php if ($page < $totalPages): ?>
                    <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page + 1])); ?>" 
                       class="btn btn--outline">Вперед &rarr;</a>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <!-- Footer -->
    <?php include 'includes/footer.php'; ?>
</body>
</html> 