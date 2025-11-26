<?php
require_once 'get_products.php';

$smartphones = getProductsByCategory('smartphones');
$laptops = getProductsByCategory('laptops');
$tv = getProductsByCategory('tv');
$audio = getProductsByCategory('audio');
$accessories = getProductsByCategory('accessories');
?>
<!DOCTYPE html>
<html lang="ru">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>PixelMarket — Магазин электроники</title>
  <link rel="stylesheet" href="css/style.css">
  <link href="https://fonts.googleapis.com/css?family=Montserrat:400,700&display=swap" rel="stylesheet">
</head>
<body>
  <?php include 'includes/header.php'; ?>

  <main>
    <div class="container">
      <section class="banner">
        <div class="banner__content">
          <h1>НОВЫЙ СМАРТФОН</h1>
          <p>Уже в продаже</p>
          <a href="product.php" class="btn btn--orange">Подробнее</a>
        </div>
        <img src="img/products/baner.jpg" alt="Смартфон">
      </section>

      <section class="products products--category">
        <div class="products__header">
          <h2>Смартфоны</h2>
          <a href="/catalog.php?category=smartphones" class="btn btn--outline">Смотреть все</a>
        </div>
        <div class="products__list">
          <?php foreach ($smartphones as $product): ?>
          <div class="products__card">
            <a href="product.php?id=<?php echo $product['id']; ?>">
              <img src="<?php echo htmlspecialchars($product['image']); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>">
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
      </section>

      <section class="products products--category">
        <div class="products__header">
          <h2>Ноутбуки</h2>
          <a href="/catalog.php?category=laptops" class="btn btn--outline">Смотреть все</a>
        </div>
        <div class="products__list">
          <?php foreach ($laptops as $product): ?>
          <div class="products__card">
            <a href="product.php?id=<?php echo $product['id']; ?>">
              <img src="<?php echo htmlspecialchars($product['image']); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>">
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
      </section>

      <section class="products products--category">
        <div class="products__header">
          <h2>Телевизоры</h2>
          <a href="/catalog.php?category=tv" class="btn btn--outline">Смотреть все</a>
        </div>
        <div class="products__list">
          <?php foreach ($tv as $product): ?>
          <div class="products__card">
            <a href="product.php?id=<?php echo $product['id']; ?>">
              <img src="<?php echo htmlspecialchars($product['image']); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>">
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
      </section>

      <section class="products products--category">
        <div class="products__header">
          <h2>Аудио</h2>
          <a href="/catalog.php?category=audio" class="btn btn--outline">Смотреть все</a>
        </div>
        <div class="products__list">
          <?php foreach ($audio as $product): ?>
          <div class="products__card">
            <a href="product.php?id=<?php echo $product['id']; ?>">
              <img src="<?php echo htmlspecialchars($product['image']); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>">
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
      </section>

      <section class="products products--category">
        <div class="products__header">
          <h2>Аксессуары</h2>
          <a href="/catalog.php?category=accessories" class="btn btn--outline">Смотреть все</a>
        </div>
        <div class="products__list">
          <?php foreach ($accessories as $product): ?>
          <div class="products__card">
            <a href="product.php?id=<?php echo $product['id']; ?>">
              <img src="<?php echo htmlspecialchars($product['image']); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>">
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
      </section>
    </div>
  </main>

  <?php include 'includes/footer.php'; ?>
</body>
</html> 