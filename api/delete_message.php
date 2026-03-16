<?php
require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json');

// Get JSON input
$json = file_get_contents('php://input');
$data = json_decode($json, true);

if (!$data || !isset($data['message_id']) || !isset($data['room_id'])) {
    echo json_encode(['success' => false, 'error' => 'Parameter tidak lengkap']);
    exit;
}

$messageId = (int)$data['message_id'];
$roomId = (int)$data['room_id'];

// Start session to get user info
session_start();

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$userId = (int)$_SESSION['user_id'];
$userRole = $_SESSION['role'] ?? '';

try {
    // First, get the message details to check permissions
    $stmt = $pdo->prepare("
        SELECT m.*, r.class_id 
        FROM chat_messages m 
        JOIN chat_rooms r ON m.room_id = r.id 
        WHERE m.id = ? AND m.room_id = ?
    ");
    $stmt->execute([$messageId, $roomId]);
    $message = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$message) {
        echo json_encode(['success' => false, 'error' => 'Pesan tidak ditemukan']);
        exit;
    }

    // Check if user has permission to delete this message
    $canDelete = false;
    
    // User can delete their own message
    if ($message['user_id'] == $userId) {
        $canDelete = true;
    }
    
    // Admin/Teacher/Guru can delete any message in the room
    if (in_array($userRole, ['admin', 'teacher', 'guru'])) {
        // Verify user has access to this class/room
        $stmt = $pdo->prepare("
            SELECT COUNT(*) FROM room_participants 
            WHERE room_id = ? AND user_id = ? AND role IN ('teacher', 'admin')
        ");
        $stmt->execute([$roomId, $userId]);
        if ($stmt->fetchColumn() > 0) {
            $canDelete = true;
        }
    }

    if (!$canDelete) {
        echo json_encode(['success' => false, 'error' => 'Tidak memiliki izin untuk menghapus pesan ini']);
        exit;
    }

    // Try soft delete first (if columns exist)
    try {
        $stmt = $pdo->prepare("
            UPDATE chat_messages 
            SET is_deleted = 1, deleted_at = NOW(), deleted_by = ? 
            WHERE id = ?
        ");
        $stmt->execute([$userId, $messageId]);
        
        if ($stmt->rowCount() > 0) {
            echo json_encode(['success' => true, 'message' => 'Pesan berhasil dihapus']);
            exit;
        }
    } catch (PDOException $e) {
        // Soft delete columns don't exist, use hard delete instead
        error_log("Soft delete not available, using hard delete: " . $e->getMessage());
    }

    // Hard delete the message (actually remove from database)
    $stmt = $pdo->prepare("DELETE FROM chat_messages WHERE id = ?");
    $stmt->execute([$messageId]);

    if ($stmt->rowCount() > 0) {
        echo json_encode(['success' => true, 'message' => 'Pesan berhasil dihapus']);
    } else {
        echo json_encode(['success' => false, 'error' => 'Gagal menghapus pesan']);
    }

} catch (PDOException $e) {
    error_log("Delete message error: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Kesalahan database: ' . $e->getMessage()]);
} catch (Exception $e) {
    error_log("Delete message error: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Terjadi kesalahan: ' . $e->getMessage()]);
}
?>
