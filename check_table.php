<?php
require_once 'config/database.php';

try {
    $stmt = $pdo->query('DESCRIBE chat_messages');
    echo "chat_messages table structure:\n";
    echo "=============================\n";
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo $row['Field'] . ' - ' . $row['Type'] . PHP_EOL;
    }
    
    // Check if deletion columns exist
    $stmt = $pdo->query("SHOW COLUMNS FROM chat_messages LIKE 'is_deleted'");
    $isDeleted = $stmt->fetch();
    
    $stmt = $pdo->query("SHOW COLUMNS FROM chat_messages LIKE 'deleted_at'");
    $deletedAt = $stmt->fetch();
    
    $stmt = $pdo->query("SHOW COLUMNS FROM chat_messages LIKE 'deleted_by'");
    $deletedBy = $stmt->fetch();
    
    echo "\nDeletion columns status:\n";
    echo "=====================\n";
    echo "is_deleted: " . ($isDeleted ? "EXISTS" : "MISSING") . PHP_EOL;
    echo "deleted_at: " . ($deletedAt ? "EXISTS" : "MISSING") . PHP_EOL;
    echo "deleted_by: " . ($deletedBy ? "EXISTS" : "MISSING") . PHP_EOL;
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . PHP_EOL;
}
?>
