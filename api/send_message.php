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
$message = $data['message'] ?? null;
$userId = $_SESSION['user_id'] ?? null;

if (!$roomId || !$message || !$userId) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing required fields']);
    exit;
}

// Validate user is participant in the room
$stmt = $pdo->prepare("SELECT COUNT(*) FROM room_participants WHERE room_id = ? AND user_id = ?");
$stmt->execute([$roomId, $userId]);
if ($stmt->fetchColumn() == 0) {
    http_response_code(403);
    echo json_encode(['error' => 'Access denied']);
    exit;
}

// Insert message
$stmt = $pdo->prepare("
    INSERT INTO chat_messages (room_id, user_id, message_type, message) 
    VALUES (?, ?, 'text', ?)
");
$stmt->execute([$roomId, $userId, trim($message)]);

$messageId = $pdo->lastInsertId();

// Get message details with user info
$stmt = $pdo->prepare("
    SELECT m.*, u.username,
           CASE
               WHEN u.role = 'guru'  THEN (SELECT g.nama_guru  FROM guru  g WHERE g.user_id = u.id)
               WHEN u.role = 'siswa' THEN (SELECT s.nama_siswa FROM siswa s WHERE s.user_id = u.id)
               ELSE u.username
           END as full_name,
           u.role as user_role
    FROM chat_messages m
    JOIN users u ON m.user_id = u.id
    WHERE m.id = ?
");
$stmt->execute([$messageId]);
$messageData = $stmt->fetch(PDO::FETCH_ASSOC);

echo json_encode([
    'success' => true,
    'message' => $messageData
]);
?>
