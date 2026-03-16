<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../config/database.php';

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

// Get JSON data
$data = json_decode(file_get_contents('php://input'), true);
if (!$data) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid JSON']);
    exit;
}

$roomId = $data['room_id'] ?? null;
$userId = $_SESSION['user_id'] ?? null;

if (!$roomId || !$userId) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing required fields']);
    exit;
}

// Validate user is participant in the room and has priority (teacher/admin)
$stmt = $pdo->prepare("
    SELECT p.role, u.role as global_role 
    FROM room_participants p 
    JOIN users u ON p.user_id = u.id 
    WHERE p.room_id = ? AND p.user_id = ?
");
$stmt->execute([$roomId, $userId]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user || !($user['role'] === 'teacher' || $user['global_role'] === 'admin' || $user['global_role'] === 'guru')) {
    http_response_code(403);
    echo json_encode(['error' => 'Access denied. Only hosts can clear chat.']);
    exit;
}

// Clear chat by setting is_deleted = 1
try {
    $stmt = $pdo->prepare("UPDATE chat_messages SET is_deleted = 1 WHERE room_id = ?");
    $stmt->execute([$roomId]);
    
    echo json_encode(['success' => true, 'message' => 'Chat cleared successfully']);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to clear chat: ' . $e->getMessage()]);
}
?>
