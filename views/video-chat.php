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
?>
// Tambahkan user sebagai participant jika belum ada
$stmt = $pdo->prepare("SELECT COUNT(*) FROM room_participants WHERE room_id = ? AND user_id = ?");
$stmt->execute([$room['id'], $userId]);
if ($stmt->fetchColumn() == 0) {
    $role = ($userRole === 'teacher' || $userRole === 'admin') ? 'teacher' : 'student';
    $stmt = $pdo->prepare("INSERT INTO room_participants (room_id, user_id, role) VALUES (?, ?, ?)");
    $stmt->execute([$room['id'], $userId, $role]);
}

// Update last seen
$stmt = $pdo->prepare("UPDATE room_participants SET last_seen_at = NOW(), is_online = 1 WHERE room_id = ? AND user_id = ?");
$stmt->execute([$room['id'], $userId]);

// Ambil pesan terakhir
$stmt = $pdo->prepare("
    SELECT m.*, u.username, u.full_name, u.role as user_role 
    FROM chat_messages m 
    JOIN users u ON m.user_id = u.id 
    WHERE m.room_id = ? AND m.is_deleted = 0 
    ORDER BY m.created_at DESC 
    LIMIT 50
");
$stmt->execute([$room['id']]);
$messages = array_reverse($stmt->fetchAll(PDO::FETCH_ASSOC));

// Ambil participant yang online
$stmt = $pdo->prepare("
    SELECT p.*, u.username, u.full_name, u.role as user_role 
    FROM room_participants p 
    JOIN users u ON p.user_id = u.id 
    WHERE p.room_id = ? AND p.is_online = 1 
    ORDER BY p.joined_at
");
$stmt->execute([$room['id']]);
$participants = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!-- Debug Info (remove in production) -->
<div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 mb-4">
    <h3 class="text-lg font-bold text-yellow-800 mb-2">Debug Info:</h3>
    <div class="grid grid-cols-2 md:grid-cols-4 gap-2 text-sm">
        <div><strong>Class ID:</strong> <?= $debugInfo['class_id'] ?></div>
        <div><strong>Room ID:</strong> <?= $debugInfo['room_id'] ?></div>
        <div><strong>User ID:</strong> <?= $debugInfo['user_id'] ?></div>
        <div><strong>User Role:</strong> <?= $debugInfo['user_role'] ?></div>
        <div><strong>Access Granted:</strong> <?= $debugInfo['access_granted'] ? 'Yes' : 'No' ?></div>
        <div><strong>Access Reason:</strong> <?= $debugInfo['access_reason'] ?? 'N/A' ?></div>
        <div><strong>Class Found:</strong> <?= $debugInfo['class_found'] ? 'Yes' : 'No' ?></div>
        <div><strong>Room Found:</strong> <?= $debugInfo['room_found'] ? 'Yes' : 'No' ?></div>
        <div><strong>Room Created:</strong> <?= $debugInfo['room_created'] ? 'Yes' : 'No' ?></div>
        <div><strong>Final Room ID:</strong> <?= $debugInfo['final_room_id'] ?></div>
    </div>
    <div class="mt-2">
        <strong>Class Name:</strong> <?= htmlspecialchars($class['class_name']) ?><br>
        <strong>Room Code:</strong> <?= htmlspecialchars($room['room_code']) ?><br>
        <strong>Room Name:</strong> <?= htmlspecialchars($room['room_name']) ?>
    </div>
</div>

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
        body { font-family: 'Roboto', sans-serif; }
        .heading-oswald { font-family: 'Oswald', sans-serif; }
        
        /* Custom scrollbar */
        .custom-scrollbar::-webkit-scrollbar { width: 6px; }
        .custom-scrollbar::-webkit-scrollbar-track { background: #f1f1f1; border-radius: 10px; }
        .custom-scrollbar::-webkit-scrollbar-thumb { background: #888; border-radius: 10px; }
        .custom-scrollbar::-webkit-scrollbar-thumb:hover { background: #555; }
        
        /* Video container */
        .video-container { position: relative; background: #000; border-radius: 12px; overflow: hidden; }
        .video-element { width: 100%; height: 100%; object-fit: cover; }
        
        /* Chat bubble */
        .chat-bubble { max-width: 70%; word-wrap: break-word; }
        .chat-bubble.sent { margin-left: auto; }
        .chat-bubble.received { margin-right: auto; }
    </style>
</head>
<body class="bg-gray-100">
    <!-- Header -->
    <header class="bg-[#002147] text-white shadow-lg">
        <div class="px-6 py-4">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-4">
                    <a href="index.php?page=dashboard" class="text-white hover:text-[#ffae01] transition-colors">
                        <i class="fas fa-arrow-left text-xl"></i>
                    </a>
                    <div>
                        <h1 class="text-xl font-bold heading-oswald">Kelas <?= htmlspecialchars($class['class_name']) ?></h1>
                        <p class="text-sm text-gray-300">Video Chat & Grup Diskusi</p>
                    </div>
                </div>
                <div class="flex items-center gap-4">
                    <span class="text-sm">
                        <i class="fas fa-users mr-2"></i>
                        <?= count($participants) ?> Online
                    </span>
                    <button onclick="toggleParticipants()" class="text-white hover:text-[#ffae01] transition-colors">
                        <i class="fas fa-user-friends text-xl"></i>
                    </button>
                </div>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="flex h-[calc(100vh-80px)]">
        <!-- Video Section -->
        <section class="w-1/2 bg-black flex flex-col">
            <div class="flex-1 relative">
                <!-- Main Video (Remote) -->
                <div id="mainVideo" class="video-container w-full h-full">
                    <video id="remoteVideo" class="video-element" autoplay playsinline></video>
                    <div id="noRemoteVideo" class="absolute inset-0 flex items-center justify-center text-white">
                        <div class="text-center">
                            <i class="fas fa-video-slash text-6xl mb-4 text-gray-400"></i>
                            <p class="text-gray-400">Menunggu peserta lain...</p>
                        </div>
                    </div>
                </div>
                
                <!-- Local Video (Picture-in-Picture) -->
                <div id="localVideoContainer" class="absolute bottom-4 right-4 w-48 h-36 video-container shadow-lg">
                    <video id="localVideo" class="video-element" autoplay muted playsinline></video>
                </div>
            </div>
            
            <!-- Video Controls -->
            <div class="bg-gray-900 p-4">
                <div class="flex justify-center items-center gap-4">
                    <button id="muteBtn" onclick="toggleMute()" class="bg-gray-700 hover:bg-gray-600 text-white p-4 rounded-full transition-colors">
                        <i class="fas fa-microphone text-xl"></i>
                    </button>
                    <button id="videoBtn" onclick="toggleVideo()" class="bg-gray-700 hover:bg-gray-600 text-white p-4 rounded-full transition-colors">
                        <i class="fas fa-video text-xl"></i>
                    </button>
                    <button onclick="shareScreen()" class="bg-gray-700 hover:bg-gray-600 text-white p-4 rounded-full transition-colors">
                        <i class="fas fa-desktop text-xl"></i>
                    </button>
                    <button onclick="toggleChat()" class="bg-gray-700 hover:bg-gray-600 text-white p-4 rounded-full transition-colors">
                        <i class="fas fa-comment text-xl"></i>
                    </button>
                    <button onclick="endCall()" class="bg-red-600 hover:bg-red-700 text-white p-4 rounded-full transition-colors">
                        <i class="fas fa-phone-slash text-xl"></i>
                    </button>
                </div>
            </div>
        </section>

        <!-- Chat Section -->
        <section id="chatSection" class="w-1/2 bg-white flex flex-col">
            <!-- Chat Messages -->
            <div id="chatMessages" class="flex-1 overflow-y-auto p-4 custom-scrollbar">
                <?php foreach ($messages as $message): ?>
                    <div class="mb-4 <?= $message['user_id'] == $userId ? 'text-right' : 'text-left' ?>">
                        <div class="chat-bubble <?= $message['user_id'] == $userId ? 'sent bg-[#002147] text-white' : 'received bg-gray-100 text-gray-800' ?> p-3 rounded-lg">
                            <div class="text-xs opacity-75 mb-1">
                                <?= htmlspecialchars($message['full_name'] ?? $message['username']) ?>
                                <span class="ml-2"><?= date('H:i', strtotime($message['created_at'])) ?></span>
                            </div>
                            <div class="text-sm">
                                <?= htmlspecialchars($message['message']) ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <!-- Chat Input -->
            <div class="border-t p-4">
                <form id="chatForm" class="flex gap-2">
                    <input type="text" id="messageInput" placeholder="Ketik pesan..." 
                           class="flex-1 px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#002147] focus:border-transparent outline-none">
                    <button type="submit" class="bg-[#002147] hover:bg-[#001a35] text-white px-6 py-2 rounded-lg transition-colors">
                        <i class="fas fa-paper-plane"></i>
                    </button>
                </form>
            </div>
        </section>

        <!-- Participants Sidebar -->
        <aside id="participantsSidebar" class="w-80 bg-white border-l hidden">
            <div class="p-4 border-b">
                <h3 class="font-bold text-lg heading-oswald">Peserta (<?= count($participants) ?>)</h3>
            </div>
            <div class="overflow-y-auto custom-scrollbar">
                <?php foreach ($participants as $participant): ?>
                    <div class="flex items-center gap-3 p-4 hover:bg-gray-50 border-b">
                        <div class="relative">
                            <div class="w-10 h-10 bg-[#002147] rounded-full flex items-center justify-center text-white font-bold">
                                <?= strtoupper(substr($participant['full_name'] ?? $participant['username'], 0, 1)) ?>
                            </div>
                            <div class="absolute bottom-0 right-0 w-3 h-3 bg-green-500 rounded-full border-2 border-white"></div>
                        </div>
                        <div class="flex-1">
                            <div class="font-medium"><?= htmlspecialchars($participant['full_name'] ?? $participant['username']) ?></div>
                            <div class="text-sm text-gray-500">
                                <?= $participant['user_role'] === 'teacher' ? 'Guru' : 'Siswa' ?>
                                <?php if ($participant['user_id'] == $userId): ?>
                                    <span class="text-xs bg-blue-100 text-blue-800 px-2 py-1 rounded">Anda</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </aside>
    </main>

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
