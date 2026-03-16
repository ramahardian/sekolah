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
        }
        .heading-oswald { font-family: 'Oswald', sans-serif; }
        
        /* Custom scrollbar */
        .custom-scrollbar::-webkit-scrollbar { width: 6px; }
        .custom-scrollbar::-webkit-scrollbar-track { background: #f1f1f1; border-radius: 10px; }
        .custom-scrollbar::-webkit-scrollbar-thumb { background: #888; border-radius: 10px; }
        .custom-scrollbar::-webkit-scrollbar-thumb:hover { background: #555; }
        
        /* Video container */
        .video-container { 
            position: relative; 
            background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%); 
            border-radius: 16px; 
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
        }
        .video-element { width: 100%; height: 100%; object-fit: cover; }
        
        /* Chat bubble */
        .chat-bubble { max-width: 70%; word-wrap: break-word; }
        .chat-bubble.sent { 
            margin-left: auto; 
            background: linear-gradient(135deg, #002147 0%, #001a35 100%);
            color: white;
        }
        .chat-bubble.received { 
            margin-right: auto; 
            background: white;
            color: #1f2937;
            border: 1px solid #e5e7eb;
        }
        
        /* Animations */
        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.05); }
        }
        
        .pulse-animation {
            animation: pulse 2s infinite;
        }
        
        /* Glass morphism effect */
        .glass {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        
        /* Button hover effects */
        .btn-hover {
            transition: all 0.3s ease;
            transform: translateY(0);
        }
        
        .btn-hover:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.2);
        }
        
        /* Status indicator */
        .status-online {
            width: 12px;
            height: 12px;
            background: #10b981;
            border-radius: 50%;
            border: 2px solid white;
            position: absolute;
            bottom: 2px;
            right: 2px;
        }
        
        .status-offline {
            background: #6b7280;
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
        
        /* Layout fixes */
        .main-container {
            height: 100vh;
            display: flex;
            flex-direction: column;
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
        }
        
        .chat-section {
            flex: 0 0 50%;
            display: flex;
            flex-direction: column;
            min-width: 0; /* Prevent flex item from overflowing */
        }
        
        .sidebar-panel {
            flex: 0 0 320px;
            transition: transform 0.3s ease;
        }
        
        .sidebar-panel.hidden {
            transform: translateX(100%);
        }
        
        /* Responsive adjustments */
        @media (max-width: 768px) {
            .video-section {
                flex: 0 0 60%;
            }
            .chat-section {
                flex: 0 0 40%;
            }
            .sidebar-panel {
                position: absolute;
                right: 0;
                top: 0;
                height: 100%;
                z-index: 50;
            }
        }
    </style>
</head>
<body class="bg-gradient-to-br from-gray-50 to-gray-100">
    <div class="main-container">
        <!-- Header -->
        <header class="bg-gradient-to-r from-[#002147] to-[#001a35] text-white shadow-2xl flex-shrink-0">
            <div class="px-6 py-4">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-4">
                        <a href="index.php?page=video-classes" class="text-white hover:text-[#ffae01] transition-all duration-300 transform hover:scale-110">
                            <i class="fas fa-arrow-left text-xl"></i>
                        </a>
                        <div>
                            <h1 class="text-2xl font-bold heading-oswald flex items-center gap-2">
                                <i class="fas fa-video text-[#ffae01]"></i>
                                Kelas <?= htmlspecialchars($class['class_name']) ?>
                            </h1>
                            <p class="text-sm text-gray-300 flex items-center gap-2">
                                <i class="fas fa-circle text-green-400 text-xs pulse-animation"></i>
                                Video Chat & Grup Diskusi
                            </p>
                        </div>
                    </div>
                    <div class="flex items-center gap-4">
                        <div class="flex items-center gap-2 bg-white/10 px-3 py-1 rounded-full">
                            <i class="fas fa-users text-[#ffae01]"></i>
                            <span class="text-sm font-medium"><?= count($participants) ?> Online</span>
                        </div>
                        <button onclick="toggleParticipants()" class="bg-white/10 hover:bg-white/20 text-white p-2 rounded-lg transition-all duration-300 btn-hover">
                            <i class="fas fa-user-friends text-lg"></i>
                        </button>
                        <button onclick="toggleSettings()" class="bg-white/10 hover:bg-white/20 text-white p-2 rounded-lg transition-all duration-300 btn-hover">
                            <i class="fas fa-cog text-lg"></i>
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
                                <div class="mb-6">
                                    <i class="fas fa-video-slash text-7xl text-gray-400 pulse-animation"></i>
                                </div>
                                <h3 class="text-xl font-semibold mb-2">Menunggu Peserta</h3>
                                <p class="text-gray-400">Bagikan link room atau tunggu peserta lain bergabung</p>
                                <div class="mt-4">
                                    <button onclick="copyRoomLink()" class="bg-[#002147] hover:bg-[#001a35] text-white px-4 py-2 rounded-lg transition-all duration-300 btn-hover">
                                        <i class="fas fa-link mr-2"></i>Salin Link Room
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Local Video (Picture-in-Picture) -->
                    <div id="localVideoContainer" class="absolute bottom-6 right-6 w-48 h-36 video-container shadow-2xl glass">
                        <video id="localVideo" class="video-element" autoplay muted playsinline></video>
                        <div class="absolute top-2 left-2 bg-black/50 px-2 py-1 rounded text-xs text-white">
                            Anda
                        </div>
                    </div>
                </div>
                
                <!-- Video Controls -->
                <div class="bg-gradient-to-r from-gray-900 to-black p-6 border-t border-gray-800 flex-shrink-0">
                    <div class="flex justify-center items-center gap-3">
                        <button id="muteBtn" onclick="toggleMute()" class="bg-gray-700 hover:bg-gray-600 text-white p-4 rounded-full transition-all duration-300 btn-hover group">
                            <i class="fas fa-microphone text-xl group-hover:text-[#ffae01]"></i>
                        </button>
                        <button id="videoBtn" onclick="toggleVideo()" class="bg-gray-700 hover:bg-gray-600 text-white p-4 rounded-full transition-all duration-300 btn-hover group">
                            <i class="fas fa-video text-xl group-hover:text-[#ffae01]"></i>
                        </button>
                        <button onclick="shareScreen()" class="bg-gray-700 hover:bg-gray-600 text-white p-4 rounded-full transition-all duration-300 btn-hover group">
                            <i class="fas fa-desktop text-xl group-hover:text-[#ffae01]"></i>
                        </button>
                        <button onclick="toggleChat()" class="bg-gray-700 hover:bg-gray-600 text-white p-4 rounded-full transition-all duration-300 btn-hover group">
                            <i class="fas fa-comment text-xl group-hover:text-[#ffae01]"></i>
                        </button>
                        <button onclick="toggleRecord()" class="bg-gray-700 hover:bg-gray-600 text-white p-4 rounded-full transition-all duration-300 btn-hover group">
                            <i class="fas fa-record-vinyl text-xl group-hover:text-red-500"></i>
                        </button>
                        <button onclick="endCall()" class="bg-red-600 hover:bg-red-700 text-white p-4 rounded-full transition-all duration-300 btn-hover group">
                            <i class="fas fa-phone-slash text-xl"></i>
                        </button>
                    </div>
                </div>
            </section>

            <!-- Chat Section -->
            <section class="chat-section">
                <!-- Chat Header -->
                <div class="bg-gradient-to-r from-[#002147] to-[#001a35] text-white p-4 border-b flex-shrink-0">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <i class="fas fa-comments text-[#ffae01] text-xl"></i>
                            <div>
                                <h3 class="font-bold heading-oswald">Grup Diskusi</h3>
                                <p class="text-xs text-gray-300">Kelas <?= htmlspecialchars($class['class_name']) ?></p>
                            </div>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="text-xs bg-green-500 px-2 py-1 rounded-full">
                                <i class="fas fa-circle text-xs mr-1"></i><?= count($participants) ?> Online
                            </span>
                        </div>
                    </div>
                </div>
                
                <!-- Chat Messages -->
                <div id="chatMessages" class="flex-1 overflow-y-auto p-4 custom-scrollbar bg-gray-50">
                    <?php if (empty($messages)): ?>
                        <div class="text-center text-gray-400 py-8">
                            <i class="fas fa-comment-dots text-4xl mb-3"></i>
                            <p>Belum ada pesan. Mulai percakapan!</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($messages as $message): ?>
                            <div class="mb-4 <?= $message['user_id'] == $userId ? 'text-right' : 'text-left' ?> message-animate">
                                <div class="chat-bubble <?= $message['user_id'] == $userId ? 'sent' : 'received' ?> p-3 rounded-2xl shadow-md">
                                    <?php if ($message['user_id'] != $userId): ?>
                                        <div class="text-xs text-gray-500 mb-1 font-medium">
                                            <?= htmlspecialchars($message['full_name'] ?? $message['username']) ?>
                                        </div>
                                    <?php endif; ?>
                                    <div class="text-sm <?= $message['user_id'] == $userId ? 'text-white' : 'text-gray-800' ?>">
                                        <?= htmlspecialchars($message['message']) ?>
                                    </div>
                                    <div class="text-xs <?= $message['user_id'] == $userId ? 'text-gray-200' : 'text-gray-400' ?> mt-1">
                                        <?= date('H:i', strtotime($message['created_at'])) ?>
                                        <?php if ($message['user_id'] == $userId): ?>
                                            <i class="fas fa-check-double text-xs ml-1"></i>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
                
                <!-- Chat Input -->
                <div class="border-t bg-white p-4 flex-shrink-0">
                    <form id="chatForm" class="flex gap-2">
                        <div class="flex-1 relative">
                            <input type="text" id="messageInput" placeholder="Ketik pesan..." 
                                   class="w-full px-4 py-3 pr-12 border border-gray-300 rounded-full focus:ring-2 focus:ring-[#002147] focus:border-transparent outline-none transition-all duration-300">
                            <button type="button" class="absolute right-2 top-1/2 transform -translate-y-1/2 text-gray-400 hover:text-[#002147] transition-colors">
                                <i class="fas fa-smile"></i>
                            </button>
                        </div>
                        <button type="submit" class="bg-[#002147] hover:bg-[#001a35] text-white p-3 rounded-full transition-all duration-300 btn-hover">
                            <i class="fas fa-paper-plane"></i>
                        </button>
                    </form>
                </div>
            </section>

            <!-- Participants Sidebar -->
            <aside id="participantsSidebar" class="sidebar-panel bg-white border-l shadow-xl hidden">
                <div class="bg-gradient-to-r from-[#002147] to-[#001a35] text-white p-4 border-b">
                    <div class="flex items-center justify-between">
                        <h3 class="font-bold text-lg heading-oswald flex items-center gap-2">
                            <i class="fas fa-users text-[#ffae01]"></i>
                            Peserta (<?= count($participants) ?>)
                        </h3>
                        <button onclick="toggleParticipants()" class="text-white hover:text-[#ffae01] transition-colors">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                </div>
                <div class="overflow-y-auto custom-scrollbar">
                    <?php if (empty($participants)): ?>
                        <div class="text-center text-gray-400 py-8">
                            <i class="fas fa-user-slash text-4xl mb-3"></i>
                            <p>Belum ada peserta online</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($participants as $participant): ?>
                            <div class="flex items-center gap-3 p-4 hover:bg-gray-50 border-b transition-colors">
                                <div class="relative">
                                    <div class="w-12 h-12 bg-gradient-to-br from-[#002147] to-[#001a35] rounded-full flex items-center justify-center text-white font-bold text-lg shadow-lg">
                                        <?= strtoupper(substr($participant['full_name'] ?? $participant['username'], 0, 1)) ?>
                                    </div>
                                    <div class="status-online"></div>
                                </div>
                                <div class="flex-1">
                                    <div class="font-semibold text-gray-800">
                                        <?= htmlspecialchars($participant['full_name'] ?? $participant['username']) ?>
                                    </div>
                                    <div class="text-sm text-gray-500 flex items-center gap-2">
                                        <?php if ($participant['user_role'] === 'teacher'): ?>
                                            <i class="fas fa-chalkboard-teacher text-xs"></i>
                                            <span>Guru</span>
                                        <?php else: ?>
                                            <i class="fas fa-graduation-cap text-xs"></i>
                                            <span>Siswa</span>
                                        <?php endif; ?>
                                        <?php if ($participant['user_id'] == $userId): ?>
                                            <span class="text-xs bg-[#002147] text-white px-2 py-1 rounded-full">Anda</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="flex flex-col gap-1">
                                    <button class="text-gray-400 hover:text-[#002147] transition-colors">
                                        <i class="fas fa-microphone"></i>
                                    </button>
                                    <button class="text-gray-400 hover:text-[#002147] transition-colors">
                                        <i class="fas fa-video"></i>
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
