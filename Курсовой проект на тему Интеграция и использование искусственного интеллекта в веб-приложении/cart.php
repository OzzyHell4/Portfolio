<?php
ob_start(); // Start output buffering
require_once 'auth.php';
require_once 'db_connect.php';

// Initialize error variable
$error = null;

// --- Start of POST request handling ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Handle Order Submission
    if (isset($_POST['action']) && $_POST['action'] === 'place_order') {
        $cart = isset($_SESSION['cart']) ? $_SESSION['cart'] : [];
        
        if (!empty($cart)) {
            $productIds = array_keys($cart);
            $placeholders = str_repeat('?,', count($productIds) - 1) . '?';
            
            try {
                if (!isset($conn)) {
                    throw new Exception("Database connection not available.");
                }

                error_log("Cart: Preparing to fetch products in cart.");
                $stmt = $conn->prepare("SELECT id, name, price, image FROM products WHERE id IN ($placeholders)");
                $stmt->execute($productIds);
                $productsInCart = $stmt->fetchAll(PDO::FETCH_ASSOC);
                error_log("Cart: Finished fetching products in cart.");
                
                $orderItemsData = [];
                $calculatedTotal = 0;
                $validCartItemsExist = false;

                foreach ($productsInCart as $product) {
                    $productId = $product['id'];
                    if (isset($cart[$productId]) && $cart[$productId] > 0) {
                        $quantity = (int)$cart[$productId];
                        $price = (float)$product['price'];
                        $calculatedTotal += $price * $quantity;
                        $orderItemsData[] = [
                            'product_id' => $productId,
                            'quantity' => $quantity,
                            'price' => $price
                        ];
                        $validCartItemsExist = true;
                    }
                }
                
                if ($validCartItemsExist && $calculatedTotal > 0) {
                    // Fetch user details from session/database as form fields are removed
                    $user = getCurrentUser(); // Assuming getCurrentUser() gets full user details including address/phone

                    if (!$user) {
                         throw new Exception("User not logged in or user data not available.");
                    }

                    $name = $user['name'] ?? '';
                    $email = $user['email'] ?? '';
                    $phone = $user['phone_number'] ?? ''; // Assuming phone number is stored as phone_number
                    $address = $user['address'] ?? ''; // Assuming address is stored as address
                    $comment = trim($_POST['comment'] ?? ''); // Still allow comment from a potential hidden/separate field or default
                    
                    // Basic validation - ensure user data needed for order exists
                     if (empty($name) || empty($email) || empty($address)) {
                          throw new Exception("Missing required user profile information (Name, Email, Address). Please update your profile.");
                     }

                    // Generate a unique order number
                    $orderNumber = 'ORD-' . date('YmdHis') . '-' . uniqid();
                    
                    // Start a database transaction
                    $conn->beginTransaction();
                    error_log("Cart: Transaction started.");
                    
                    // Insert the order into the orders table
                    error_log("Cart: Preparing INSERT into orders table.");
                    $stmt = $conn->prepare("INSERT INTO orders (customer_id, contact_phone, notes) 
                                           VALUES (?, ?, ?)");
                    $stmt->execute([$user['id'], $phone, $comment]);
                    $orderId = $conn->lastInsertId();
                    error_log("Cart: INSERT into orders table successful. Order ID: " . $orderId);

                    // Log the order items data before insertion
                    error_log("Cart: Order Items Data for insertion: " . print_r($orderItemsData, true));

                    // The generated order ID is the order number in this schema
                    $orderNumber = $orderId; // Use the auto-generated ID as the order number
                    
                    // Insert order items into the order_items table
                    error_log("Cart: Preparing INSERT into order_items table.");
                    $stmt = $conn->prepare("INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)");
                    foreach ($orderItemsData as $item) {
                         $stmt->execute([$orderId, $item['product_id'], $item['quantity'], $item['price']]);
                    }
                    error_log("Cart: INSERT into order_items table successful.");
                    
                    // Commit the transaction
                    $conn->commit();
                    error_log("Cart: Transaction committed. Order placement successful.");
                    
                    // Clear the cart after successful order creation
                    $_SESSION['cart'] = [];
                    
                    // Redirect to the user's orders page
                    header("Location: account.php?section=orders");
                    exit();

                } else {
                     throw new Exception("Your cart is empty or contains invalid items.");
                }
                
            } catch (PDOException $e) {
                if ($conn->inTransaction()) {
                    $conn->rollBack();
                }
                $error = "Database error during order processing: " . $e->getMessage();
                error_log("Database error during order processing: " . $e->getMessage());
                header("Location: cart.php?error=" . urlencode($error));
                exit();
            } catch (Exception $e) {
                 if ($conn->inTransaction()) {
                    $conn->rollBack();
                 }
                $error = "Error processing your order: " . $e->getMessage();
                 error_log("Order processing failed: " . $e->getMessage());
                 header("Location: cart.php?error=" . urlencode($error));
                exit();
            }
            
        } else {
            header('Location: cart.php?error=empty_cart');
            exit();
        }
    }

    // Handle Quantity Update and Remove Item
    if (isset($_POST['action']) && ($_POST['action'] === 'update' || $_POST['action'] === 'remove')) {
        $productId = $_POST['product_id'];
        
        if ($_POST['action'] === 'update') {
             $quantity = (int)$_POST['quantity'];
            if ($quantity > 0) {
                $_SESSION['cart'][$productId] = $quantity;
            } else {
                unset($_SESSION['cart'][$productId]);
            }
        } elseif ($_POST['action'] === 'remove') {
            unset($_SESSION['cart'][$productId]);
        }
        
        header('Location: cart.php');
        exit();
    }

} // --- End of POST request handling ---

// --- Start of GET request handling or POST failure display ---

// REMOVE: This header include will be removed to fix the double header issue.
// require_once 'includes/header.php';

// Get products from the cart for display
$cartItems = [];
$totalPrice = 0;
$cart = isset($_SESSION['cart']) ? $_SESSION['cart'] : [];

if (!empty($cart)) {
    $productIds = array_keys($cart);
    if (isset($conn)) {
        $placeholders = str_repeat('?,', count($productIds) - 1) . '?';
        
        try {
            $stmt = $conn->prepare("SELECT id, name, price, image FROM products WHERE id IN ($placeholders)");
            $stmt->execute($productIds);
            $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($products as $product) {
                $productId = $product['id'];
                if (isset($cart[$productId]) && $cart[$productId] > 0) {
                    $quantity = (int)$cart[$productId];
                    $cartItems[] = [
                        'id' => $productId,
                        'name' => $product['name'],
                        'price' => (float)$product['price'],
                        'image' => $product['image'],
                        'quantity' => $quantity
                    ];
                    $totalPrice += (float)$product['price'] * $quantity;
                }
            }
        } catch (PDOException $e) {
             $error = "Error fetching cart details: " . $e->getMessage();
             error_log("Error fetching cart details for display: " . $e->getMessage());
             $cartItems = [];
             $totalPrice = 0;
        }
    } else {
        $error = "Database connection not available.";
        error_log("Database connection not available for cart display.");
         $cartItems = [];
         $totalPrice = 0;
    }
}

// Check for error parameter in URL (from failed POST redirect)
if (isset($_GET['error'])) {
    $error = htmlspecialchars($_GET['error']);
}

?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Корзина — PixelMarket</title>
    <link rel="stylesheet" href="css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <main class="container">
        <h1>Корзина</h1>
        
        <?php if (isset($error) && $error): // Display error if set ?>
            <div class="alert alert-danger">
                <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <?php if (empty($cartItems)): ?>
            <div class="cart-empty">
                <p>Ваша корzina пуста</p>
                <a href="catalog.php" class="btn btn--accent">Перейти в каталог</a>
            </div>
        <?php else: ?>
            <div class="cart">
                <div class="cart-items">
                    <?php foreach ($cartItems as $item): ?>
                        <div class="cart-item">
                            <img src="<?php echo htmlspecialchars($item['image']); ?>" alt="<?php echo htmlspecialchars($item['name']); ?>" class="cart-item__image">
                            <div class="cart-item__info">
                                <h3 class="cart-item__name"><?php echo htmlspecialchars($item['name']); ?></h3>
                                <p class="cart-item__price"><?php echo number_format($item['price'], 0, ',', ' '); ?> ₽</p>
                            </div>
                            <div class="cart-item__quantity">
                                <form method="POST" class="quantity-form">
                                    <input type="hidden" name="action" value="update">
                                    <input type="hidden" name="product_id" value="<?php echo $item['id']; ?>">
                                    <input type="number" name="quantity" value="<?php echo $item['quantity']; ?>" min="1" class="quantity-input" onchange="this.form.submit()">
                                </form>
                            </div>
                            <div class="cart-item__total">
                                <?php echo number_format($item['price'] * $item['quantity'], 0, ',', ' '); ?> ₽
                            </div>
                            <form method="POST" class="cart-item__remove">
                                <input type="hidden" name="action" value="remove">
                                <input type="hidden" name="product_id" value="<?php echo $item['id']; ?>">
                                <button type="submit" class="btn btn--danger">Удалить</button>
                            </form>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <div class="cart-summary">
                    <div class="cart-summary__total">
                        <span>Итого:</span>
                        <span class="cart-summary__price"><?php echo number_format($totalPrice, 0, ',', ' '); ?> ₽</span>
                    </div>

                    <?php // The place order button will now submit a simple form without delivery fields ?>
                    <form id="place-order-form" method="POST" action="cart.php"> <!-- Form submits to cart.php -->
                        <input type="hidden" name="action" value="place_order"> <!-- Hidden field to identify this form submission -->
                        <?php /* Removed delivery fields here */ ?>
                        <button type="submit" class="btn btn--accent btn--large">Оформить заказ</button>
                    </form>

                </div>
            </div>
        <?php endif; ?>
    </main>

    <?php include 'includes/footer.php'; ?>
</body>
</html>
<?php ob_end_flush(); // Flush output buffer ?> 