<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../config/database.php';

$roomId = $_GET['room_id'] ?? null;
$lastId = $_GET['last_id'] ?? 0;
$userId = $_SESSION['user_id'] ?? null;

if (!$roomId || !$userId) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing parameters']);
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

// Get new messages
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
    WHERE m.room_id = ? AND m.id > ? AND m.is_deleted = 0
    ORDER BY m.created_at ASC
    LIMIT 50
");
$stmt->execute([$roomId, $lastId]);
$messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode($messages);
?>
