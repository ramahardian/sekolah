<?php
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../config/database.php';

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Ambil parameter kelas
$classId = $_GET['class_id'] ?? null;
$roomId = $_GET['room_id'] ?? null;

// Debug info
$debugInfo = [
    'class_id' => $classId,
    'room_id' => $roomId,
    'user_id' => $_SESSION['user_id'] ?? null,
    'user_role' => $_SESSION['role'] ?? '',
    'access_granted' => false
];

if (!$classId) {
    header("Location: index.php?page=dashboard");
    exit;
}

// Verifikasi user memiliki akses ke kelas ini
$userRole = $_SESSION['role'] ?? '';
$userId = $_SESSION['user_id'] ?? 0;

// Cek apakah user adalah guru atau siswa di kelas ini
$classAccess = false;
if ($userRole === 'admin' || $userRole === 'teacher') {
    // Guru dan admin bisa akses semua kelas
    $classAccess = true;
    $debugInfo['access_reason'] = 'Admin/Teacher access';
} else {
    // Cek apakah siswa terdaftar di kelas ini
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM student_classes WHERE student_id = ? AND class_id = ?");
    $stmt->execute([$userId, $classId]);
    $classAccess = $stmt->fetchColumn() > 0;
    $debugInfo['access_reason'] = 'student_classes check';
    
    // Fallback: check siswa.kelas_id if no student_classes record
    if (!$classAccess) {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM siswa WHERE user_id = ? AND kelas_id = ?");
        $stmt->execute([$userId, $classId]);
        $classAccess = $stmt->fetchColumn() > 0;
        $debugInfo['access_reason'] = 'siswa.kelas_id fallback';
    }
}

$debugInfo['access_granted'] = $classAccess;

if (!$classAccess) {
    header("Location: index.php?page=dashboard");
    exit;
}

// Ambil info kelas
$stmt = $pdo->prepare("SELECT id, nama_kelas as class_name FROM kelas WHERE id = ?");
$stmt->execute([$classId]);
$class = $stmt->fetch(PDO::FETCH_ASSOC);

$debugInfo['class_found'] = !!$class;

if (!$class) {
    header("Location: index.php?page=dashboard");
    exit;
}

// Cari atau buat room chat untuk kelas ini
$stmt = $pdo->prepare("SELECT * FROM chat_rooms WHERE class_id = ?");
$stmt->execute([$classId]);
$room = $stmt->fetch(PDO::FETCH_ASSOC);

$debugInfo['room_found'] = !!$room;

if (!$room) {
    // Buat room baru jika belum ada
    $roomCode = 'CLASS_' . $classId . '_' . strtoupper(substr(md5(time()), 0, 8));
    $stmt = $pdo->prepare("INSERT INTO chat_rooms (class_id, room_name, room_code, created_by) VALUES (?, ?, ?, ?)");
    $stmt->execute([$classId, 'Kelas ' . $class['class_name'], $roomCode, $userId]);
    
    $stmt = $pdo->prepare("SELECT * FROM chat_rooms WHERE class_id = ?");
    $stmt->execute([$classId]);
    $room = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $debugInfo['room_created'] = true;
} else {
    $debugInfo['room_created'] = false;
}

$debugInfo['final_room_id'] = $room['id'];

// Tambahkan user sebagai participant jika belum ada
try {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM room_participants WHERE room_id = ? AND user_id = ?");
    $stmt->execute([$room['id'], $userId]);
    if ($stmt->fetchColumn() == 0) {
        $role = ($userRole === 'teacher' || $userRole === 'admin') ? 'teacher' : 'student';
        $stmt = $pdo->prepare("INSERT INTO room_participants (room_id, user_id, role) VALUES (?, ?, ?)");
        $stmt->execute([$room['id'], $userId, $role]);
        $debugInfo['participant_added'] = true;
    } else {
        $debugInfo['participant_added'] = false;
    }
} catch (Exception $e) {
    $debugInfo['participant_error'] = $e->getMessage();
    $debugInfo['participant_added'] = false;
}

// Update last seen
try {
    $stmt = $pdo->prepare("UPDATE room_participants SET last_seen_at = NOW(), is_online = 1 WHERE room_id = ? AND user_id = ?");
    $stmt->execute([$room['id'], $userId]);
    $debugInfo['last_seen_updated'] = true;
} catch (Exception $e) {
    $debugInfo['last_seen_error'] = $e->getMessage();
    $debugInfo['last_seen_updated'] = false;
}

// Ambil pesan terakhir
try {
    $stmt = $pdo->prepare("
        SELECT m.*, u.username, 
               CASE 
                   WHEN u.role = 'guru' THEN (SELECT g.nama_guru FROM guru g WHERE g.user_id = u.id)
                   WHEN u.role = 'siswa' THEN (SELECT s.nama_siswa FROM siswa s WHERE s.user_id = u.id)
                   ELSE u.username
               END as full_name,
               u.role as user_role 
        FROM chat_messages m 
        JOIN users u ON m.user_id = u.id 
        WHERE m.room_id = ? AND m.is_deleted = 0 
        ORDER BY m.created_at DESC 
        LIMIT 50
    ");
    $stmt->execute([$room['id']]);
    $messages = array_reverse($stmt->fetchAll(PDO::FETCH_ASSOC));
    $debugInfo['messages_loaded'] = count($messages);
} catch (Exception $e) {
    $debugInfo['messages_error'] = $e->getMessage();
    $messages = [];
    $debugInfo['messages_loaded'] = 0;
}

// Ambil participant yang online
try {
    $stmt = $pdo->prepare("
        SELECT p.*, u.username, 
               CASE 
                   WHEN u.role = 'guru' THEN (SELECT g.nama_guru FROM guru g WHERE g.user_id = u.id)
                   WHEN u.role = 'siswa' THEN (SELECT s.nama_siswa FROM siswa s WHERE s.user_id = u.id)
                   ELSE u.username
               END as full_name,
               u.role as user_role 
        FROM room_participants p 
        JOIN users u ON p.user_id = u.id 
        WHERE p.room_id = ? AND p.is_online = 1 
        ORDER BY p.joined_at
    ");
    $stmt->execute([$room['id']]);
    $participants = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $debugInfo['participants_loaded'] = count($participants);
} catch (Exception $e) {
    $debugInfo['participants_error'] = $e->getMessage();
    $participants = [];
    $debugInfo['participants_loaded'] = 0;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Video Chat - <?= htmlspecialchars($class['class_name']) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&family=Oswald:wght@500;600&display=swap');
        body { 
            font-family: 'Roboto', sans-serif; 
            margin: 0;
            padding: 0;
            overflow: hidden;
            background: #f0f2f5;
        }
        .heading-oswald { font-family: 'Oswald', sans-serif; }
        
        /* Custom scrollbar */
        .custom-scrollbar::-webkit-scrollbar { width: 6px; }
        .custom-scrollbar::-webkit-scrollbar-track { background: #f1f1f1; border-radius: 10px; }
        .custom-scrollbar::-webkit-scrollbar-thumb { background: #888; border-radius: 10px; }
        .custom-scrollbar::-webkit-scrollbar-thumb:hover { background: #555; }
        
        /* Zoom-style video container */
        .video-container { 
            position: relative; 
            background: #262626; 
            border-radius: 8px; 
            overflow: hidden;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            border: 1px solid #e1e5e9;
        }
        .video-element { width: 100%; height: 100%; object-fit: cover; }
        
        /* Zoom-style chat bubble */
        .chat-bubble { max-width: 70%; word-wrap: break-word; }
        .chat-bubble.sent { 
            margin-left: auto; 
            background: #007bff;
            color: white;
            border-radius: 18px;
        }
        .chat-bubble.received { 
            margin-right: auto; 
            background: white;
            color: #333;
            border: 1px solid #e1e5e9;
            border-radius: 18px;
        }
        
        /* Animations */
        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.05); }
        }
        
        .pulse-animation {
            animation: pulse 2s infinite;
        }
        
        /* Zoom-style button effects */
        .btn-zoom {
            transition: all 0.2s ease;
            transform: translateY(0);
            background: #2d2d2d;
            border: 2px solid transparent;
        }
        
        .btn-zoom:hover {
            background: #3d3d3d;
            border-color: #007bff;
            transform: scale(1.05);
        }
        
        .btn-zoom.active {
            background: #007bff;
            border-color: #0056b3;
        }
        
        .btn-zoom.danger {
            background: #dc3545;
            border-color: #dc3545;
        }
        
        .btn-zoom.danger:hover {
            background: #c82333;
            border-color: #c82333;
        }
        
        /* Status indicator */
        .status-online {
            width: 12px;
            height: 12px;
            background: #28a745;
            border-radius: 50%;
            border: 2px solid white;
            position: absolute;
            bottom: 2px;
            right: 2px;
        }
        
        .status-offline {
            background: #6c757d;
        }
        
        /* Message animations */
        @keyframes slideInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .message-animate {
            animation: slideInUp 0.3s ease-out;
        }
        
        /* Zoom-style layout fixes */
        .main-container {
            height: 100vh;
            display: flex;
            flex-direction: column;
            background: #f0f2f5;
        }
        
        .content-area {
            flex: 1;
            display: flex;
            overflow: hidden;
        }
        
        .video-section {
            flex: 0 0 50%;
            display: flex;
            flex-direction: column;
            background: #f0f2f5;
        }
        
        .chat-section {
            flex: 1;
            display: flex;
            flex-direction: column;
            min-width: 0;
            background: white;
            border-left: 1px solid #e1e5e9;
        }
        
        .sidebar-panel {
            flex: 0 0 320px;
            transition: transform 0.3s ease;
            background: white;
            border-left: 1px solid #e1e5e9;
        }
        
        .sidebar-panel.hidden {
            transform: translateX(100%);
        }
        
        /* Zoom-style header */
        .zoom-header {
            background: #2d2d2d;
            color: white;
            border-bottom: 1px solid #3d3d3d;
        }
        
        /* Zoom-style video controls */
        .zoom-controls {
            background: #2d2d2d;
            border-top: 1px solid #3d3d3d;
            padding: 12px;
        }
        
        /* Zoom-style chat header */
        .chat-header {
            background: #f8f9fa;
            border-bottom: 1px solid #e1e5e9;
            padding: 16px;
        }
        
        /* Zoom-style participant avatar */
        .participant-avatar {
            width: 36px;
            height: 36px;
            background: #007bff;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 500;
            font-size: 14px;
        }
        
        /* Zoom-style input */
        .zoom-input {
            border: 1px solid #e1e5e9;
            border-radius: 8px;
            padding: 8px 12px;
            font-size: 14px;
            transition: border-color 0.2s ease;
        }
        
        .zoom-input:focus {
            outline: none;
            border-color: #007bff;
            box-shadow: 0 0 0 2px rgba(0, 123, 255, 0.25);
        }
        
        /* Local video positioning */
        #localVideoContainer {
            transition: all 0.3s ease;
        }
        
        @media (max-width: 768px) {
            #localVideoContainer {
                width: 120px;
                height: 90px;
                bottom: 70px;
                left: 8px;
            }
        }
        
        @media (max-width: 480px) {
            #localVideoContainer {
                width: 80px;
                height: 60px;
                bottom: 70px;
                left: 8px;
            }
        }
        
        /* Responsive adjustments */
        @media (max-width: 1024px) {
            .video-section {
                flex: 0 0 50%;
            }
            .chat-section {
                flex: 1;
            }
        }
        
        @media (max-width: 768px) {
            .content-area {
                flex-direction: column;
            }
            
            .video-section {
                flex: 0 0 60%;
                width: 100%;
            }
            
            .chat-section {
                flex: 1;
                width: 100%;
                border-left: none;
                border-top: 1px solid #e1e5e9;
            }
            
            .sidebar-panel {
                position: absolute;
                right: 0;
                top: 0;
                height: 100%;
                z-index: 50;
                width: 280px;
            }
            
            .video-container {
                border-radius: 4px;
            }
            
            .zoom-controls {
                padding: 8px;
            }
            
            .btn-zoom {
                width: 40px;
                height: 40px;
                padding: 8px;
            }
            
            .btn-zoom i {
                font-size: 14px;
            }
        }
        
        @media (max-width: 480px) {
            .zoom-header {
                padding: 8px 12px;
            }
            
            .zoom-header h1 {
                font-size: 14px;
            }
            
            .zoom-header p {
                font-size: 10px;
            }
            
            .video-section {
                padding: 8px;
                flex: 0 0 50%;
            }
            
            .chat-section {
                flex: 1;
            }
            
            #localVideoContainer {
                width: 80px;
                height: 60px;
                bottom: 8px;
                right: 8px;
            }
            
            .zoom-controls {
                padding: 6px;
            }
            
            .btn-zoom {
                width: 36px;
                height: 36px;
                padding: 6px;
            }
            
            .btn-zoom i {
                font-size: 12px;
            }
        }
    </style>
</head>
<body class="bg-gradient-to-br from-gray-50 to-gray-100">
    <div class="main-container">
        <!-- Header -->
        <header class="zoom-header flex-shrink-0">
            <div class="px-4 py-3">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <a href="index.php?page=video-classes" class="text-white hover:text-blue-400 transition-colors p-2">
                            <i class="fas fa-arrow-left text-lg"></i>
                        </a>
                        <div>
                            <h1 class="text-lg font-semibold flex items-center gap-2">
                                <i class="fas fa-video text-blue-400"></i>
                                <?= htmlspecialchars($class['class_name']) ?>
                            </h1>
                            <p class="text-xs text-gray-400 flex items-center gap-2">
                                <i class="fas fa-circle text-green-400 text-xs pulse-animation"></i>
                                Meeting
                            </p>
                        </div>
                    </div>
                    <div class="flex items-center gap-2">
                        <div class="flex items-center gap-2 bg-gray-700 px-3 py-1 rounded-full text-sm">
                            <i class="fas fa-users text-green-400"></i>
                            <span><?= count($participants) ?></span>
                        </div>
                        <button onclick="toggleParticipants()" class="text-white hover:text-blue-400 transition-colors p-2">
                            <i class="fas fa-user-friends"></i>
                        </button>
                        <button onclick="toggleSettings()" class="text-white hover:text-blue-400 transition-colors p-2">
                            <i class="fas fa-cog"></i>
                        </button>
                    </div>
                </div>
            </div>
        </header>

        <!-- Main Content -->
        <div class="content-area">
            <!-- Video Section -->
            <section class="video-section">
                <div class="flex-1 relative p-4">
                    <!-- Main Video (Remote) -->
                    <div id="mainVideo" class="video-container w-full h-full">
                        <video id="remoteVideo" class="video-element" autoplay playsinline></video>
                        <div id="noRemoteVideo" class="absolute inset-0 flex items-center justify-center text-white">
                            <div class="text-center">
                                <div class="mb-4">
                                    <i class="fas fa-video-slash text-5xl text-gray-400"></i>
                                </div>
                                <h3 class="text-lg font-medium mb-2">Waiting for others to join</h3>
                                <p class="text-gray-400 text-sm mb-4">Share this meeting link with others</p>
                                <div class="bg-gray-700 rounded-lg p-3 inline-block">
                                    <code class="text-xs text-green-400"><?= $room['room_code'] ?></code>
                                </div>
                                <div class="mt-3">
                                    <button onclick="copyRoomLink()" class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-lg transition-colors text-sm">
                                        <i class="fas fa-link mr-2"></i>Copy Link
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Local Video (Bottom Position) -->
                    <div id="localVideoContainer" class="absolute bottom-20 left-4 w-48 h-36 video-container shadow-lg">
                        <video id="localVideo" class="video-element" autoplay muted playsinline></video>
                        <div class="absolute top-2 left-2 bg-black/70 px-2 py-1 rounded text-xs text-white">
                            You
                        </div>
                    </div>
                </div>
                
                <!-- Simplified Video Controls -->
                <div class="zoom-controls flex-shrink-0">
                    <div class="flex justify-center items-center gap-3">
                        <button id="muteBtn" onclick="toggleMute()" class="btn-zoom text-white p-3 rounded-full transition-all">
                            <i class="fas fa-microphone text-lg"></i>
                        </button>
                        <button onclick="endCall()" class="btn-zoom danger text-white p-3 rounded-full transition-all">
                            <i class="fas fa-phone-slash text-lg"></i>
                        </button>
                    </div>
                </div>
            </section>

            <!-- Chat Section -->
            <section class="chat-section">
                <!-- Chat Header -->
                <div class="chat-header flex-shrink-0">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <i class="fas fa-comments text-blue-500"></i>
                            <div>
                                <h3 class="font-semibold text-sm">Chat</h3>
                                <p class="text-xs text-gray-500"><?= htmlspecialchars($class['class_name']) ?></p>
                            </div>
                        </div>
                        <div class="flex items-center gap-1">
                            <span class="text-xs bg-green-100 text-green-700 px-2 py-1 rounded-full">
                                <i class="fas fa-circle text-xs mr-1"></i><?= count($participants) ?>
                            </span>
                        </div>
                    </div>
                </div>
                
                <!-- Chat Messages -->
                <div id="chatMessages" class="flex-1 overflow-y-auto p-3 custom-scrollbar">
                    <?php if (empty($messages)): ?>
                        <div class="text-center text-gray-400 py-8">
                            <i class="fas fa-comment-dots text-3xl mb-2"></i>
                            <p class="text-sm">No messages yet. Start a conversation!</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($messages as $message): ?>
                            <div class="mb-3 <?= $message['user_id'] == $userId ? 'text-right' : 'text-left' ?> message-animate">
                                <div class="chat-bubble <?= $message['user_id'] == $userId ? 'sent' : 'received' ?> p-2 shadow-sm">
                                    <?php if ($message['user_id'] != $userId): ?>
                                        <div class="text-xs text-gray-500 mb-1 font-medium">
                                            <?= htmlspecialchars($message['full_name'] ?? $message['username']) ?>
                                        </div>
                                    <?php endif; ?>
                                    <div class="text-sm <?= $message['user_id'] == $userId ? 'text-white' : 'text-gray-800' ?>">
                                        <?= htmlspecialchars($message['message']) ?>
                                    </div>
                                    <div class="text-xs <?= $message['user_id'] == $userId ? 'text-blue-100' : 'text-gray-400' ?> mt-1">
                                        <?= date('H:i', strtotime($message['created_at'])) ?>
                                        <?php if ($message['user_id'] == $userId): ?>
                                            <i class="fas fa-check text-xs ml-1"></i>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
                
                <!-- Chat Input -->
                <div class="border-t border-gray-200 p-3 flex-shrink-0 bg-white">
                    <form id="chatForm" class="flex gap-2">
                        <div class="flex-1 relative">
                            <input type="text" id="messageInput" placeholder="Type a message..." 
                                   class="zoom-input w-full pr-10">
                            <button type="button" class="absolute right-2 top-1/2 transform -translate-y-1/2 text-gray-400 hover:text-blue-500 transition-colors">
                                <i class="fas fa-smile text-sm"></i>
                            </button>
                        </div>
                        <button type="submit" class="bg-blue-500 hover:bg-blue-600 text-white p-2 rounded-lg transition-colors">
                            <i class="fas fa-paper-plane text-sm"></i>
                        </button>
                    </form>
                </div>
            </section>

            <!-- Participants Sidebar -->
            <aside id="participantsSidebar" class="sidebar-panel hidden">
                <div class="chat-header border-b">
                    <div class="flex items-center justify-between">
                        <h3 class="font-semibold text-sm flex items-center gap-2">
                            <i class="fas fa-users text-blue-500"></i>
                            Participants (<?= count($participants) ?>)
                        </h3>
                        <button onclick="toggleParticipants()" class="text-gray-500 hover:text-gray-700 transition-colors">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                </div>
                <div class="overflow-y-auto custom-scrollbar">
                    <?php if (empty($participants)): ?>
                        <div class="text-center text-gray-400 py-8">
                            <i class="fas fa-user-slash text-3xl mb-2"></i>
                            <p class="text-sm">No participants yet</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($participants as $participant): ?>
                            <div class="flex items-center gap-3 p-3 hover:bg-gray-50 border-b transition-colors">
                                <div class="relative">
                                    <div class="participant-avatar">
                                        <?= strtoupper(substr($participant['full_name'] ?? $participant['username'], 0, 1)) ?>
                                    </div>
                                    <div class="status-online"></div>
                                </div>
                                <div class="flex-1">
                                    <div class="font-medium text-sm text-gray-800">
                                        <?= htmlspecialchars($participant['full_name'] ?? $participant['username']) ?>
                                    </div>
                                    <div class="text-xs text-gray-500 flex items-center gap-1">
                                        <?php if ($participant['user_role'] === 'teacher'): ?>
                                            <i class="fas fa-chalkboard-teacher text-xs"></i>
                                            <span>Host</span>
                                        <?php else: ?>
                                            <i class="fas fa-user text-xs"></i>
                                            <span>Participant</span>
                                        <?php endif; ?>
                                        <?php if ($participant['user_id'] == $userId): ?>
                                            <span class="text-xs bg-blue-100 text-blue-700 px-2 py-0.5 rounded">You</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="flex gap-1">
                                    <button class="text-gray-400 hover:text-blue-500 transition-colors p-1">
                                        <i class="fas fa-microphone text-sm"></i>
                                    </button>
                                    <button class="text-gray-400 hover:text-blue-500 transition-colors p-1">
                                        <i class="fas fa-video text-sm"></i>
                                    </button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </aside>
        </div>
    </div>

    <script>
        // Global variables
        let localStream = null;
        let remoteStream = null;
        let peerConnection = null;
        let isMuted = false;
        let isVideoOff = false;
        const userId = <?= $userId ?>;
        const roomId = '<?= $room['room_code'] ?>';
        
        // DOM elements
        const localVideo = document.getElementById('localVideo');
        const remoteVideo = document.getElementById('remoteVideo');
        const chatMessages = document.getElementById('chatMessages');
        const messageInput = document.getElementById('messageInput');
        const chatForm = document.getElementById('chatForm');
        const participantsSidebar = document.getElementById('participantsSidebar');
        const chatSection = document.getElementById('chatSection');

        // Initialize WebRTC
        async function initWebRTC() {
            try {
                localStream = await navigator.mediaDevices.getUserMedia({ 
                    video: true, 
                    audio: true 
                });
                localVideo.srcObject = localStream;
                
                // Simple peer connection setup (would need WebRTC signaling server for production)
                createPeerConnection();
                
            } catch (error) {
                console.error('Error accessing media devices:', error);
                alert('Tidak dapat mengakses kamera/mikrofon. Pastikan izin diberikan.');
            }
        }

        function createPeerConnection() {
            // This is a simplified version - production would need signaling server
            const configuration = {
                iceServers: [
                    { urls: 'stun:stun.l.google.com:19302' }
                ]
            };
            
            peerConnection = new RTCPeerConnection(configuration);
            
            peerConnection.ontrack = (event) => {
                remoteStream = event.streams[0];
                remoteVideo.srcObject = remoteStream;
                document.getElementById('noRemoteVideo').style.display = 'none';
            };
            
            localStream.getTracks().forEach(track => {
                peerConnection.addTrack(track, localStream);
            });
        }

        // Control functions
        function toggleMute() {
            isMuted = !isMuted;
            localStream.getAudioTracks().forEach(track => {
                track.enabled = !isMuted;
            });
            
            const muteBtn = document.getElementById('muteBtn');
            muteBtn.innerHTML = isMuted ? 
                '<i class="fas fa-microphone-slash text-xl"></i>' : 
                '<i class="fas fa-microphone text-xl"></i>';
            muteBtn.classList.toggle('bg-red-600');
        }

        function toggleVideo() {
            isVideoOff = !isVideoOff;
            localStream.getVideoTracks().forEach(track => {
                track.enabled = !isVideoOff;
            });
            
            const videoBtn = document.getElementById('videoBtn');
            videoBtn.innerHTML = isVideoOff ? 
                '<i class="fas fa-video-slash text-xl"></i>' : 
                '<i class="fas fa-video text-xl"></i>';
            videoBtn.classList.toggle('bg-red-600');
        }

        async function shareScreen() {
            try {
                const screenStream = await navigator.mediaDevices.getDisplayMedia({ 
                    video: true 
                });
                
                const videoTrack = screenStream.getVideoTracks()[0];
                const sender = peerConnection.getSenders().find(
                    s => s.track && s.track.kind === 'video'
                );
                
                if (sender) {
                    sender.replaceTrack(videoTrack);
                }
                
                videoTrack.onended = () => {
                    toggleVideo(); // Switch back to camera
                };
                
            } catch (error) {
                console.error('Error sharing screen:', error);
                alert('Tidak dapat berbagi layar.');
            }
        }

        function toggleParticipants() {
            participantsSidebar.classList.toggle('hidden');
            if (!participantsSidebar.classList.contains('hidden')) {
                chatSection.classList.remove('w-1/2');
                chatSection.classList.add('w-1/3');
            } else {
                chatSection.classList.remove('w-1/3');
                chatSection.classList.add('w-1/2');
            }
        }

        function toggleChat() {
            chatSection.classList.toggle('hidden');
        }

        function endCall() {
            if (confirm('Apakah Anda yakin ingin mengakhiri panggilan?')) {
                if (localStream) {
                    localStream.getTracks().forEach(track => track.stop());
                }
                if (peerConnection) {
                    peerConnection.close();
                }
                window.location.href = 'index.php?page=dashboard';
            }
        }

        // Chat functions
        chatForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const message = messageInput.value.trim();
            
            if (message) {
                // Send message to server (would need WebSocket for real-time)
                try {
                    const response = await fetch('api/send_message.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({
                            room_id: <?= $room['id'] ?>,
                            message: message,
                            user_id: userId
                        })
                    });
                    
                    if (response.ok) {
                        messageInput.value = '';
                        // Message will be added via real-time update or refresh
                    }
                } catch (error) {
                    console.error('Error sending message:', error);
                }
            }
        });

        // Auto-scroll chat to bottom
        function scrollToBottom() {
            chatMessages.scrollTop = chatMessages.scrollHeight;
        }

        // Initialize on page load
        document.addEventListener('DOMContentLoaded', () => {
            initWebRTC();
            scrollToBottom();
            
            // Simulate real-time updates (would use WebSocket in production)
            setInterval(() => {
                // Check for new messages
                checkNewMessages();
            }, 3000);
        });

        async function checkNewMessages() {
            try {
                const response = await fetch(`api/get_messages.php?room_id=<?= $room['id'] ?>&last_id=${getLastMessageId()}`);
                const newMessages = await response.json();
                
                if (newMessages.length > 0) {
                    newMessages.forEach(msg => addMessageToChat(msg));
                    scrollToBottom();
                }
            } catch (error) {
                console.error('Error checking new messages:', error);
            }
        }

        function getLastMessageId() {
            const messages = document.querySelectorAll('[data-message-id]');
            return messages.length > 0 ? messages[messages.length - 1].dataset.messageId : 0;
        }

        function addMessageToChat(message) {
            const messageDiv = document.createElement('div');
            messageDiv.className = `mb-4 ${message.user_id == userId ? 'text-right' : 'text-left'}`;
            messageDiv.dataset.messageId = message.id;
            
            messageDiv.innerHTML = `
                <div class="chat-bubble ${message.user_id == userId ? 'sent bg-[#002147] text-white' : 'received bg-gray-100 text-gray-800'} p-3 rounded-lg">
                    <div class="text-xs opacity-75 mb-1">
                        ${message.full_name || message.username}
                        <span class="ml-2">${new Date(message.created_at).toLocaleTimeString('id-ID', {hour: '2-digit', minute:'2-digit'})}</span>
                    </div>
                    <div class="text-sm">${message.message}</div>
                </div>
            `;
            
            chatMessages.appendChild(messageDiv);
        }

        // Cleanup on page unload
        window.addEventListener('beforeunload', () => {
            if (localStream) {
                localStream.getTracks().forEach(track => track.stop());
            }
            if (peerConnection) {
                peerConnection.close();
            }
        });
    </script>
</body>
</html>
