<?php
require_once 'auth.php';
require_once 'db_connect.php';

// Проверяем авторизацию
if (!isLoggedIn()) {
    http_response_code(401);
    exit('Unauthorized');
}

$user = getCurrentUser();

// Получаем историю сообщений
try {
    $stmt = $conn->prepare("
        SELECT sc.*, u.name as user_name 
        FROM support_messages sc 
        JOIN users u ON sc.user_id = u.id 
        WHERE sc.user_id = ? 
        ORDER BY sc.created_at ASC
    ");
    $stmt->execute([$user['id']]);
    $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Отмечаем сообщения как прочитанные
    $stmt = $conn->prepare("
        UPDATE support_messages 
        SET is_read = 1 
        WHERE user_id = ? AND is_admin = 1 AND is_read = 0
    ");
    $stmt->execute([$user['id']]);

    // Выводим сообщения
    foreach ($messages as $message): ?>
        <div class="message <?php echo $message['is_admin'] ? 'admin' : 'user'; ?>">
            <div class="message-header">
                <?php echo htmlspecialchars($message['user_name']); ?> • 
                <?php echo date('d.m.Y H:i', strtotime($message['created_at'])); ?>
            </div>
            <div class="message-content">
                <?php echo nl2br(htmlspecialchars($message['message'])); ?>
            </div>
        </div>
    <?php endforeach;

} catch (PDOException $e) {
    http_response_code(500);
    exit('Database error');
}
?> 