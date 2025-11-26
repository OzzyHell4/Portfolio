<?php
require_once 'db_connect.php';
require_once 'get_products.php';
require_once 'auth.php'; // Для проверки авторизации, если нужно добавлять в корзину

// Получаем ID товара из URL
$productId = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Получаем информацию о товаре
$product = getProductById($productId);

// Если товар не найден, перенаправляем на страницу каталога или показываем 404
if (!$product) {
    header('Location: catalog.php'); // Или header("HTTP/1.0 404 Not Found");
    exit;
}

// Можно добавить логику для галереи, если есть доп. изображения
$images = [$product['image']]; // Пока только основное изображение
// $images = getProductImages($productId); // Предполагаемая функция для получения доп. изображений
// if (empty($images)) { $images = [$product['image']]; }

?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($product['name']); ?> — PixelMarket</title>
    <link rel="stylesheet" href="css/style.css">
    <link href="https://fonts.googleapis.com/css?family=Montserrat:400,700&display=swap" rel="stylesheet">
</head>
<body>
    <!-- Header -->
    <?php include 'includes/header.php'; ?>

    <main>
        <div class="container">
            <div class="product__content">
                <!-- Галерея изображений -->
                <div class="product__gallery">
                    <div class="gallery__main">
                        <img src="<?php echo htmlspecialchars($images[0]); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>">
                    </div>
                    <?php if (count($images) > 1): // Если есть дополнительные изображения, показываем превью ?>
                        <div class="gallery__thumbs">
                            <?php foreach ($images as $img): ?>
                                <img src="<?php echo htmlspecialchars($img); ?>" alt="Превью">
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Информация о товаре -->
                <div class="product__info">
                    <h1><?php echo htmlspecialchars($product['name']); ?></h1>

                    <div class="product__price">
                        <span class="price"><?php echo number_format($product['price'], 0, ',', ' '); ?> ₽</span>
                        <!-- Если есть старая цена, можно добавить: <span class="old-price">120 000 ₽</span> -->
                    </div>

                    <div class="product__actions">
                         <form action="add_to_cart.php" method="POST">
                            <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                             <!-- Поле количества (опционально, если хотите добавить выбор количества на этой странице) -->
                             <!-- <div class="quantity">
                                 <button type="button" class="quantity__btn minus">-</button>
                                 <input type="number" name="quantity" value="1" min="1" class="quantity__input">
                                 <button type="button" class="quantity__btn plus">+</button>
                             </div> -->
                             <button type="submit" class="btn btn--accent btn--large">В корзину</button>
                         </form>
                    </div>

                     <!-- Блок характеристик, если они будут добавлены в БД -->
                     <!-- <div class="product__features">
                         <div class="feature">
                             <span class="feature__label">Процессор:</span>
                             <span class="feature__value">Intel Core i7</span>
                         </div>
                          <div class="feature">
                             <span class="feature__label">Память:</span>
                             <span class="feature__value">16GB</span>
                         </div>
                          <div class="feature">
                             <span class="feature__label">Наличие:</span>
                             <span class="feature__value in-stock">В наличии</span>
                         </div>
                     </div> -->

                    <div class="product__description">
                        <h2>Описание</h2>
                        <p><?php echo nl2br(htmlspecialchars($product['description'])); ?></p>
                    </div>

                     <!-- Табы для доп. информации, если нужно (Характеристики, Отзывы и т.п.) -->
                     <!-- <div class="tabs">
                         <button class="tab-btn active" data-tab="description">Описание</button>
                         <button class="tab-btn" data-tab="specs">Характеристики</button>
                         <button class="tab-btn" data-tab="reviews">Отзывы</button>
                     </div>

                     <div class="tab-content">
                         <div id="description" class="tab-pane active">
                              <p><?php echo nl2br(htmlspecialchars($product['description'])); ?></p>
                         </div>
                         <div id="specs" class="tab-pane">
                             <p>Детальные характеристики...</p>
                         </div>
                         <div id="reviews" class="tab-pane">
                              <h3>Отзывы</h3>
                              <div class="reviews">
                                   <div class="review">
                                        <div class="review__header">
                                             <span class="review__author">Имя пользователя</span>
                                             <span class="review__date">Дата</span>
                                             <div class="review__rating">
                                                  <span class="star active">★</span><span class="star active">★</span><span class="star active">★</span><span class="star">★</span><span class="star">★</span>
                                             </div>
                                        </div>
                                        <div class="review__content">
                                             <p>Текст отзыва...</p>
                                        </div>
                                   </div>
                                   </div>
                                   </div>

                              <h3>Оставить отзыв</h3>
                              <form class="review-form">
                                   <div class="form-group">
                                        <label for="rating">Ваша оценка</label>
                                        <select id="rating" name="rating">
                                             <option value="5">5 звезд</option>
                                             <option value="4">4 звезды</option>
                                             <option value="3">3 звезды</option>
                                             <option value="2">2 звезды</option>
                                             <option value="1">1 звезда</option>
                                        </select>
                                   </div>
                                   <div class="form-group">
                                        <label for="review-text">Ваш отзыв</label>
                                        <textarea id="review-text" name="review-text" rows="5"></textarea>
                                   </div>
                                   <button type="submit" class="btn btn--accent">Отправить отзыв</button>
                              </form>
                         </div>
                     </div> -->

                </div>
            </div>
        </div>
    </main>

    <!-- Footer -->
    <?php include 'includes/footer.php'; ?>

    <script>
        // JS для галереи (если добавите доп. изображения)
        // document.addEventListener('DOMContentLoaded', function() {
        //     const mainImage = document.querySelector('.gallery__main img');
        //     const thumbImages = document.querySelectorAll('.gallery__thumbs img');

        //     thumbImages.forEach(thumb => {
        //         thumb.addEventListener('click', function() {
        //             mainImage.src = this.src;
        //         });
        //     });
        // });

        // JS для табов (если добавите)
        // document.addEventListener('DOMContentLoaded', function() {
        //     const tabButtons = document.querySelectorAll('.tabs .tab-btn');
        //     const tabPanes = document.querySelectorAll('.tab-content .tab-pane');

        //     tabButtons.forEach(button => {
        //         button.addEventListener('click', function() {
        //             const targetTab = this.getAttribute('data-tab');

        //             tabButtons.forEach(btn => btn.classList.remove('active'));
        //             tabPanes.forEach(pane => pane.classList.remove('active'));

        //             this.classList.add('active');
        //             document.getElementById(targetTab).classList.add('active');
        //         });
        //     });
        // });
    </script>
</body>
</html> 