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

$classId = $data['class_id'] ?? null;
$userId = $_SESSION['user_id'] ?? null;
$userRole = $_SESSION['role'] ?? '';

if (!$classId || !$userId) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing required fields']);
    exit;
}

// Check if user has permission to create room for this class
$hasPermission = false;
if ($userRole === 'admin' || $userRole === 'teacher') {
    $hasPermission = true;
} else {
    // Students need to be enrolled in the class
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM student_classes WHERE student_id = ? AND class_id = ?");
    $stmt->execute([$userId, $classId]);
    $hasPermission = $stmt->fetchColumn() > 0;
}

if (!$hasPermission) {
    http_response_code(403);
    echo json_encode(['error' => 'Access denied']);
    exit;
}

// Check if room already exists
$stmt = $pdo->prepare("SELECT id FROM chat_rooms WHERE class_id = ?");
$stmt->execute([$classId]);
$existingRoom = $stmt->fetch();

if ($existingRoom) {
    echo json_encode([
        'success' => true,
        'room_id' => $existingRoom['id'],
        'message' => 'Room already exists'
    ]);
    exit;
}

// Get class info
$stmt = $pdo->prepare("SELECT class_name FROM classes WHERE id = ?");
$stmt->execute([$classId]);
$class = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$class) {
    http_response_code(404);
    echo json_encode(['error' => 'Class not found']);
    exit;
}

// Create new room
$roomCode = 'CLASS_' . $classId . '_' . strtoupper(substr(md5(time() . $userId), 0, 8));
$roomName = 'Kelas ' . $class['class_name'];

$stmt = $pdo->prepare("
    INSERT INTO chat_rooms (class_id, room_name, room_code, created_by) 
    VALUES (?, ?, ?, ?)
");
$stmt->execute([$classId, $roomName, $roomCode, $userId]);

$roomId = $pdo->lastInsertId();

// Add creator as participant
$role = ($userRole === 'teacher' || $userRole === 'admin') ? 'teacher' : 'student';
$stmt = $pdo->prepare("
    INSERT INTO room_participants (room_id, user_id, role) 
    VALUES (?, ?, ?)
");
$stmt->execute([$roomId, $userId, $role]);

echo json_encode([
    'success' => true,
    'room_id' => $roomId,
    'room_code' => $roomCode,
    'message' => 'Room created successfully'
]);
?>
