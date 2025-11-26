<?php
require_once 'get_products.php';
require_once 'includes/header.php';

$searchQuery = isset($_GET['q']) ? trim($_GET['q']) : '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$perPage = 12;

$products = [];
$totalProducts = 0;

if (!empty($searchQuery)) {
    global $conn;
    try {
        $stmt = $conn->prepare("SELECT COUNT(*) as total FROM products WHERE name LIKE :query OR description LIKE :query");
        $searchParam = "%{$searchQuery}%";
        $stmt->bindParam(':query', $searchParam);
        $stmt->execute();
        $totalProducts = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

        $stmt = $conn->prepare("
            SELECT * FROM products 
            WHERE name LIKE :query OR description LIKE :query 
            ORDER BY created_at DESC 
            LIMIT :offset, :limit
        ");
        
        $offset = ($page - 1) * $perPage;
        $stmt->bindParam(':query', $searchParam);
        $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
        $stmt->bindParam(':limit', $perPage, PDO::PARAM_INT);
        $stmt->execute();
        
        $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch(PDOException $e) {
        error_log("Search error: " . $e->getMessage());
    }
}

$totalPages = ceil($totalProducts / $perPage);
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Поиск: <?php echo htmlspecialchars($searchQuery); ?> — PixelMarket</title>
    <link rel="stylesheet" href="css/style.css">
    <link href="https://fonts.googleapis.com/css?family=Montserrat:400,700&display=swap" rel="stylesheet">
</head>
<body>
    <main>
        <div class="container">
            <div class="search-results">
                <h1>Результаты поиска</h1>
                
                <?php if (!empty($searchQuery)): ?>
                    <p class="search-query">Поиск по запросу: "<?php echo htmlspecialchars($searchQuery); ?>"</p>
                    
                    <?php if (empty($products)): ?>
                        <div class="no-results">
                            <p>По вашему запросу ничего не найдено</p>
                            <a href="/" class="btn btn--accent">Вернуться на главную</a>
                        </div>
                    <?php else: ?>
                        <div class="products__list">
                            <?php foreach ($products as $product): ?>
                            <div class="products__card">
                                <img src="<?php echo htmlspecialchars($product['image']); ?>" 
                                     alt="<?php echo htmlspecialchars($product['name']); ?>">
                                <h3><?php echo htmlspecialchars($product['name']); ?></h3>
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
                                <a href="?q=<?php echo urlencode($searchQuery); ?>&page=<?php echo $page - 1; ?>" 
                                   class="pagination__prev">←</a>
                            <?php endif; ?>

                            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                <a href="?q=<?php echo urlencode($searchQuery); ?>&page=<?php echo $i; ?>" 
                                   class="pagination__item <?php echo $i === $page ? 'active' : ''; ?>">
                                    <?php echo $i; ?>
                                </a>
                            <?php endfor; ?>

                            <?php if ($page < $totalPages): ?>
                                <a href="?q=<?php echo urlencode($searchQuery); ?>&page=<?php echo $page + 1; ?>" 
                                   class="pagination__next">→</a>
                            <?php endif; ?>
                        </div>
                        <?php endif; ?>
                    <?php endif; ?>
                <?php else: ?>
                    <div class="no-results">
                        <p>Введите поисковый запрос</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <?php include 'includes/footer.php'; ?>
</body>
</html> 