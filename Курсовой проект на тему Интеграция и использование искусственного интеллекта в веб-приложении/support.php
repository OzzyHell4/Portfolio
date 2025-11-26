<?php
require_once 'auth.php';
require_once 'db_connect.php';

// Проверяем авторизацию
if (!isLoggedIn()) {
    header('Location: login.php');
    exit();
}

$user = getCurrentUser();
$error = '';
$success = '';

// Определяем предопределенные ответы
$auto_responses = [
    'доставка' => 'Информация о доставке: Мы осуществляем доставку курьерской службой и через пункты выдачи. Сроки и стоимость зависят от вашего региона и выбранного способа. Подробнее на странице "Доставка".',
    'оплата' => 'Информация об оплате: Вы можете оплатить заказ онлайн банковской картой, электронными деньгами или при получении наличными. Все доступные способы указаны при оформлении заказа.',
    'гарантия' => 'Информация о гарантии: На все товары предоставляется гарантия производителя. Срок гарантии указан в описании товара и гарантийном талоне. По вопросам гарантийного обслуживания обращайтесь в наш сервисный центр.',
    'возврат' => 'Информация о возврате товара: Вы можете вернуть товар надлежащего качества в течение 14 дней с момента получения при сохранении его товарного вида и потребительских свойств. Подробнее в разделе "Возврат и обмен".',
    'каталог' => 'Наш каталог товаров доступен по адресу /catalog.php. Там вы можете найти всю нашу продукцию.',
    'спасибо' => 'Всегда пожалуйста! Рад помочь.',
    'привет' => 'Здравствуйте! Чем могу вам помочь сегодня?',
    'статус заказа' => 'Вы можете проверить статус вашего заказа в личном кабинете, в разделе "Мои заказы". Там отображается актуальная информация по каждому вашему заказу.',
    'мой заказ' => 'Вы можете проверить статус вашего заказа в личном кабинете, в разделе "Мои заказы". Там отображается актуальная информация по каждому вашему заказу.',
];

// Обработка отправки сообщения
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['message'])) {
    $message = trim($_POST['message']);
    
    if (empty($message)) {
        $error = 'Сообщение не может быть пустым';
    } else {
        try {
            // Вставляем сообщение пользователя
            $stmt = $conn->prepare("INSERT INTO support_messages (user_id, message) VALUES (?, ?)");
            if ($stmt->execute([$user['id'], $message])) {
                $success = 'Сообщение отправлено';

                // Проверяем наличие ключевых слов и отправляем автоответ
                $lower_message = mb_strtolower($message, 'UTF-8');
                $found_keyword = null;
                foreach ($auto_responses as $keyword => $response) {
                    if (mb_strpos($lower_message, $keyword, 0, 'UTF-8') !== false) {
                        $found_keyword = $keyword;
                        break;
                    }
                }

                if ($found_keyword) {
                    // Вставляем автоответ от имени администратора (user_id = 1)
                    $admin_user_id = 1; // Предполагаем, что admin имеет user_id = 1
                    $admin_message = $auto_responses[$found_keyword];
                    $stmt_admin = $conn->prepare("INSERT INTO support_messages (user_id, message, is_admin) VALUES (?, ?, 1)");
                    $stmt_admin->execute([$admin_user_id, $admin_message]);
                }

            } else {
                $error = 'Ошибка при отправке сообщения пользователя';
            }
        } catch (PDOException $e) {
            $error = 'Ошибка при отправке сообщения или получении автоответа: ' . $e->getMessage();
        }
    }
}

// Получаем историю сообщений
try {
    $stmt = $conn->prepare("
        SELECT sc.*, u.name as user_name 
        FROM support_messages sc 
        JOIN users u ON sc.user_id = u.id 
        WHERE sc.user_id = ? OR sc.is_admin = 1
        ORDER BY sc.created_at ASC
    ");
     // Изменено: теперь получаем все сообщения, где user_id совпадает или is_admin = 1
     // Это нужно для отображения ответов администратора в чате пользователя
    $stmt->execute([$user['id']]);
    $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = 'Ошибка при получении сообщений: ' . $e->getMessage();
    $messages = [];
}

// Находим имя администратора для отображения
$admin_name = 'Поддержка'; // Имя по умолчанию
try {
    $stmt_admin_name = $conn->prepare("SELECT name FROM users WHERE is_admin = 1 LIMIT 1");
    $stmt_admin_name->execute();
    $admin_row = $stmt_admin_name->fetch(PDO::FETCH_ASSOC);
    if ($admin_row) {
        $admin_name = $admin_row['name'];
    }
} catch (PDOException $e) {
    // Просто игнорируем ошибку, если не можем получить имя администратора
}

?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Поддержка - PixelMarket</title>
    <link rel="stylesheet" href="css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
    <style>
        .chat-container {
            max-width: 800px;
            margin: 20px auto;
            background: rgba(42, 42, 42, 0.95);
            border-radius: 20px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
            padding: 25px;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .chat-messages {
            height: 400px;
            overflow-y: auto;
            padding: 20px;
            background: rgba(15, 15, 15, 0.95);
            border-radius: 12px;
            margin-bottom: 20px;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .message {
            margin-bottom: 15px;
            padding: 12px 16px;
            border-radius: 12px;
            max-width: 70%;
            animation: messageAppear 0.3s ease;
        }

        .message.user {
            background: linear-gradient(135deg, #00bcd4, #3f51b5);
            margin-left: auto;
            color: #fff;
        }

        .message.admin {
            background: rgba(255, 255, 255, 0.05);
            margin-right: auto;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .message-header {
            font-size: 0.8em;
            color: rgba(255, 255, 255, 0.7);
            margin-bottom: 5px;
        }

        .message-content {
            word-wrap: break-word;
            line-height: 1.5;
        }

        .chat-form {
            display: flex;
            gap: 15px;
        }

        .chat-form textarea {
            flex: 1;
            padding: 14px;
            background: rgba(255, 255, 255, 0.05);
            border: 2px solid rgba(255, 255, 255, 0.1);
            border-radius: 12px;
            color: #fff;
            resize: vertical;
            min-height: 60px;
            font-family: inherit;
            font-size: 14px;
            line-height: 1.5;
            transition: all 0.3s ease;
        }

        .chat-form textarea:focus {
            outline: none;
            border-color: #00bcd4;
            box-shadow: 0 0 0 4px rgba(0, 188, 212, 0.2);
            background: rgba(255, 255, 255, 0.08);
        }

        .chat-form textarea::placeholder {
            color: rgba(255, 255, 255, 0.5);
        }

        .chat-form button {
            align-self: flex-end;
            background: linear-gradient(135deg, #00bcd4, #3f51b5);
            color: #fff;
            padding: 12px 24px;
            border: none;
            border-radius: 12px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(0, 188, 212, 0.3);
        }

        .chat-form button:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(0, 188, 212, 0.4);
            background: linear-gradient(135deg, #00e5ff, #5c6bc0);
        }

        @keyframes messageAppear {
            from {
                opacity: 0;
                transform: translateY(10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Стилизация скроллбара */
        .chat-messages::-webkit-scrollbar {
            width: 8px;
        }

        .chat-messages::-webkit-scrollbar-track {
            background: rgba(255, 255, 255, 0.05);
            border-radius: 4px;
        }

        .chat-messages::-webkit-scrollbar-thumb {
            background: rgba(0, 188, 212, 0.5);
            border-radius: 4px;
        }

        .chat-messages::-webkit-scrollbar-thumb:hover {
            background: rgba(0, 188, 212, 0.7);
        }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <main class="container">
        <div class="chat-container">
            <h1>Поддержка</h1>
            
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

            <div class="chat-messages" id="chatMessages">
                <?php foreach ($messages as $message): ?>
                    <div class="message <?php echo $message['is_admin'] ? 'admin' : 'user'; ?>">
                        <div class="message-header">
                            <?php echo htmlspecialchars($message['is_admin'] ? $admin_name : $message['user_name']); ?> • 
                            <?php echo date('d.m.Y H:i', strtotime($message['created_at'])); ?>
                        </div>
                        <div class="message-content">
                            <?php echo nl2br(htmlspecialchars($message['message'])); ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <form method="POST" class="chat-form">
                <textarea name="message" placeholder="Введите ваше сообщение..." required></textarea>
                <button type="submit" class="btn btn--accent">Отправить</button>
            </form>
        </div>
    </main>

    <?php include 'includes/footer.php'; ?>

    <script>
        // Автоматическая прокрутка к последнему сообщению
        const chatMessages = document.getElementById('chatMessages');
        chatMessages.scrollTop = chatMessages.scrollHeight;

        // Обновление чата каждые 5 секунд
        setInterval(() => {
            fetch('get_messages.php')
                .then(response => response.text())
                .then(html => {
                    const tempDiv = document.createElement('div');
                    tempDiv.innerHTML = html;
                    const newMessages = tempDiv.querySelectorAll('.message');
                    const currentMessages = chatMessages.querySelectorAll('.message');
                    
                    if (newMessages.length > currentMessages.length) {
                        chatMessages.innerHTML = html;
                        chatMessages.scrollTop = chatMessages.scrollHeight;
                    }
                })
                .catch(error => console.error('Ошибка при обновлении чата:', error));
        }, 5000);
    </script>
</body>
</html> 