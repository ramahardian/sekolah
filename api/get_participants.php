<?php
require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json');

// Start session
session_start();

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$roomId = (int)($_GET['room_id'] ?? 0);
$userId = (int)$_SESSION['user_id'];

if (!$roomId) {
    echo json_encode(['success' => false, 'error' => 'Room ID required']);
    exit;
}

try {
    // Verify user is in this room
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM room_participants WHERE room_id = ? AND user_id = ?");
    $stmt->execute([$roomId, $userId]);
    if ($stmt->fetchColumn() == 0) {
        echo json_encode(['success' => false, 'error' => 'Not in this room']);
        exit;
    }

    // Get online participants
    $stmt = $pdo->prepare("
        SELECT p.user_id, u.username,
               CASE
                   WHEN u.role = 'guru'  THEN (SELECT g.nama_guru  FROM guru  g WHERE g.user_id = u.id)
                   WHEN u.role = 'siswa' THEN (SELECT s.nama_siswa FROM siswa s WHERE s.user_id = u.id)
                   ELSE u.username
               END as full_name,
               u.role as user_role
        FROM room_participants p
        JOIN users u ON p.user_id = u.id
        WHERE p.room_id = ? AND p.is_online = 1
        ORDER BY p.joined_at
    ");
    $stmt->execute([$roomId]);
    $participants = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($participants);

} catch (PDOException $e) {
    error_log("Get participants error: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Database error']);
} catch (Exception $e) {
    error_log("Get participants error: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Server error']);
}
?>
