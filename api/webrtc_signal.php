<?php
require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json');

// Start session to get user info
session_start();

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$userId = (int)$_SESSION['user_id'];
$roomId = $_POST['room_id'] ?? $_GET['room_id'] ?? null;

if (!$roomId) {
    echo json_encode(['success' => false, 'error' => 'Room ID required']);
    exit;
}

$roomId = (int)$roomId;

try {
    // Verify user is in this room
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM room_participants WHERE room_id = ? AND user_id = ?");
    $stmt->execute([$roomId, $userId]);
    if ($stmt->fetchColumn() == 0) {
        echo json_encode(['success' => false, 'error' => 'Not in this room']);
        exit;
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Store signaling message
        $type = $_POST['type'] ?? '';
        $data = $_POST['data'] ?? '';
        $targetUserId = $_POST['target_user_id'] ?? null;

        if (!$type || !$data) {
            echo json_encode(['success' => false, 'error' => 'Missing parameters']);
            exit;
        }

        $stmt = $pdo->prepare("
            INSERT INTO webrtc_signals (room_id, user_id, target_user_id, signal_type, signal_data, created_at)
            VALUES (?, ?, ?, ?, ?, NOW())
        ");
        $stmt->execute([$roomId, $userId, $targetUserId, $type, $data]);

        echo json_encode(['success' => true]);
    } else {
        // Get signaling messages
        $lastId = (int)($_GET['last_id'] ?? 0);
        
        $stmt = $pdo->prepare("
            SELECT s.*, u.username 
            FROM webrtc_signals s
            JOIN users u ON s.user_id = u.id
            WHERE s.room_id = ? AND s.id > ? AND (s.target_user_id IS NULL OR s.target_user_id = ?)
            ORDER BY s.created_at ASC
            LIMIT 50
        ");
        $stmt->execute([$roomId, $lastId, $userId]);
        $signals = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode($signals);
    }

} catch (PDOException $e) {
    error_log("WebRTC signal error: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Database error']);
} catch (Exception $e) {
    error_log("WebRTC signal error: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Server error']);
}
?>
