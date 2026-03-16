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
            height: 100vh;
            overflow: hidden;
        }

        .heading-oswald { font-family: 'Oswald', sans-serif; }

        /* Scrollbar Styling */
        .custom-scrollbar::-webkit-scrollbar { width: 5px; }
        .custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
        .custom-scrollbar::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }

        /* Video Area */
        .video-container { 
            background: #0f172a;
            position: relative;
            border-radius: 12px;
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        /* Sidebar Transition */
        #participantsSidebar {
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .sidebar-hidden {
            width: 0 !important;
            opacity: 0;
            overflow: hidden;
            border-left-width: 0 !important;
        }

        /* Pulse Animation */
        @keyframes pulse-soft {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
        }
        .pulse-animation { animation: pulse-soft 2s infinite; }
    </style>
</head>
<body class="bg-slate-100 flex flex-col">

    <header class="h-16 bg-[#002147] text-white flex items-center justify-between px-6 shadow-lg z-20">
        <div class="flex items-center gap-4">
            <a href="index.php?page=video-classes" class="hover:text-amber-400 transition-colors">
                <i class="fas fa-arrow-left text-lg"></i>
            </a>
            <div>
                <h1 class="font-bold heading-oswald leading-none">Kelas <?= htmlspecialchars($class['class_name']) ?></h1>
                <span class="text-[10px] uppercase tracking-wider text-slate-400">Live Session</span>
            </div>
        </div>
        
        <div class="flex items-center gap-3">
            <div class="hidden md:flex items-center gap-2 bg-white/10 px-3 py-1.5 rounded-full border border-white/10">
                <div class="w-2 h-2 bg-green-400 rounded-full pulse-animation"></div>
                <span class="text-xs font-medium"><?= count($participants) ?> Peserta</span>
            </div>
            <button onclick="toggleParticipants()" class="p-2 hover:bg-white/10 rounded-lg transition-colors">
                <i class="fas fa-users"></i>
            </button>
        </div>
    </header>

    <main class="flex-1 flex overflow-hidden">
        
        <div class="flex-1 flex flex-col md:flex-row overflow-hidden">
            
            <section class="flex-[1.5] flex flex-col bg-black p-4 gap-4 overflow-hidden">
                <div class="flex-1 video-container group">
                    <video id="remoteVideo" class="w-full h-full object-cover" autoplay playsinline></video>
                    
                    <div id="noRemoteVideo" class="absolute inset-0 flex flex-col items-center justify-center text-center p-6 bg-slate-900/80">
                        <i class="fas fa-video-slash text-5xl text-slate-600 mb-4"></i>
                        <p class="text-slate-300 font-medium">Menunggu peserta lain...</p>
                        <button onclick="copyRoomLink()" class="mt-4 text-xs bg-white/10 hover:bg-white/20 px-4 py-2 rounded-full transition-all">
                            <i class="fas fa-link mr-2"></i>Salin Link
                        </button>
                    </div>

                    <div class="absolute bottom-4 right-4 w-32 md:w-48 aspect-video bg-slate-800 rounded-lg overflow-hidden border-2 border-white/20 shadow-2xl">
                        <video id="localVideo" class="w-full h-full object-cover" autoplay muted playsinline></video>
                        <div class="absolute bottom-1 left-2 text-[10px] text-white bg-black/50 px-1 rounded">Anda</div>
                    </div>
                </div>

                <div class="h-16 flex items-center justify-center gap-4 bg-slate-900/50 rounded-2xl border border-white/5">
                    <button id="muteBtn" onclick="toggleMute()" class="w-10 h-10 rounded-full bg-slate-700 hover:bg-slate-600 text-white transition-all">
                        <i class="fas fa-microphone"></i>
                    </button>
                    <button id="videoBtn" onclick="toggleVideo()" class="w-10 h-10 rounded-full bg-slate-700 hover:bg-slate-600 text-white transition-all">
                        <i class="fas fa-video"></i>
                    </button>
                    <button onclick="shareScreen()" class="w-10 h-10 rounded-full bg-slate-700 hover:bg-slate-600 text-white transition-all hidden md:flex items-center justify-center">
                        <i class="fas fa-desktop text-sm"></i>
                    </button>
                    <div class="w-[1px] h-6 bg-white/10 mx-2"></div>
                    <button onclick="endCall()" class="w-12 h-10 rounded-xl bg-red-500 hover:bg-red-600 text-white transition-all shadow-lg shadow-red-500/20">
                        <i class="fas fa-phone-slash"></i>
                    </button>
                </div>
            </section>

            <section id="chatSection" class="flex-1 flex flex-col bg-white border-l border-slate-200 min-w-[320px]">
                <div class="p-4 border-b flex items-center justify-between bg-slate-50">
                    <h3 class="font-bold text-slate-700 flex items-center gap-2">
                        <i class="fas fa-comments text-[#002147]"></i> Diskusi Grup
                    </h3>
                </div>

                <div id="chatMessages" class="flex-1 overflow-y-auto p-4 space-y-4 custom-scrollbar bg-slate-50/50">
                    <div class="text-center text-slate-400 text-xs py-10">Mulai diskusi dengan peserta lain</div>
                </div>

                <div class="p-4 bg-white border-t">
                    <form id="chatForm" class="flex items-center gap-2">
                        <input type="text" id="messageInput" placeholder="Ketik pesan..." 
                               class="flex-1 bg-slate-100 border-none rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-[#002147] transition-all outline-none">
                        <button type="submit" class="bg-[#002147] text-white w-10 h-10 rounded-xl flex items-center justify-center hover:scale-105 transition-transform active:scale-95">
                            <i class="fas fa-paper-plane text-sm"></i>
                        </button>
                    </form>
                </div>
            </section>
        </div>

        <aside id="participantsSidebar" class="w-72 bg-white border-l border-slate-200 flex flex-col sidebar-hidden">
            <div class="p-4 border-b bg-slate-50 flex items-center justify-between">
                <span class="font-bold text-slate-700">Peserta</span>
                <button onclick="toggleParticipants()" class="text-slate-400 hover:text-red-500"><i class="fas fa-times"></i></button>
            </div>
            <div class="flex-1 overflow-y-auto custom-scrollbar">
                <?php foreach ($participants as $participant): ?>
                <div class="p-3 flex items-center gap-3 hover:bg-slate-50 border-b border-slate-100 transition-colors">
                    <div class="w-9 h-9 bg-slate-200 rounded-full flex items-center justify-center font-bold text-[#002147] text-xs">
                        <?= strtoupper(substr($participant['username'], 0, 1)) ?>
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-semibold text-slate-800 truncate"><?= htmlspecialchars($participant['full_name'] ?? $participant['username']) ?></p>
                        <p class="text-[10px] text-slate-500 uppercase"><?= $participant['user_role'] ?></p>
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
