<?php
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../config/database.php';

$userId = $_SESSION['user_id'] ?? 0;
$userRole = $_SESSION['role'] ?? '';

// Get user's classes
$classes = [];
if ($userRole === 'admin' || $userRole === 'teacher') {
    // Admin and teacher can see all classes
    $stmt = $pdo->prepare("SELECT c.*, COUNT(sc.id) as student_count 
                          FROM classes c 
                          LEFT JOIN student_classes sc ON c.id = sc.class_id 
                          GROUP BY c.id 
                          ORDER BY c.class_name");
    $stmt->execute();
    $classes = $stmt->fetchAll(PDO::FETCH_ASSOC);
} else {
    // Students can only see their own classes
    $stmt = $pdo->prepare("SELECT c.*, COUNT(sc2.id) as student_count 
                          FROM student_classes sc 
                          JOIN classes c ON sc.class_id = c.id 
                          LEFT JOIN student_classes sc2 ON c.id = sc2.class_id 
                          WHERE sc.student_id = ? 
                          GROUP BY c.id 
                          ORDER BY c.class_name");
    $stmt->execute([$userId]);
    $classes = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Get active chat rooms for these classes
$classIds = array_column($classes, 'id');
$activeRooms = [];
if (!empty($classIds)) {
    $placeholders = str_repeat('?,', count($classIds) - 1) . '?';
    $stmt = $pdo->prepare("
        SELECT cr.*, c.class_name,
               COUNT(rp.id) as active_participants,
               MAX(cm.created_at) as last_activity
        FROM chat_rooms cr 
        JOIN classes c ON cr.class_id = c.id 
        LEFT JOIN room_participants rp ON cr.id = rp.room_id AND rp.is_online = 1
        LEFT JOIN chat_messages cm ON cr.id = cm.room_id
        WHERE cr.class_id IN ($placeholders)
        GROUP BY cr.id
        ORDER BY last_activity DESC
    ");
    $stmt->execute($classIds);
    $activeRooms = $stmt->fetchAll(PDO::FETCH_ASSOC);
} else {
    // Debug: Show if no classes found
    error_log("No classes found for user: $userId, role: $userRole");
}

// Group rooms by class
$roomsByClass = [];
foreach ($activeRooms as $room) {
    $roomsByClass[$room['class_id']] = $room;
}
?>

<div class="mb-8">
    <h1 class="text-2xl font-bold text-gray-800">Kelas Video Chat</h1>
    <p class="text-gray-500">Pilih kelas untuk memulai video chat dan diskusi</p>
    
    <!-- Debug Info -->
    <div class="mt-4 p-4 bg-gray-100 rounded-lg text-sm">
        <p><strong>Debug Info:</strong></p>
        <p>User ID: <?= $userId ?></p>
        <p>User Role: <?= $userRole ?></p>
        <p>Classes Found: <?= count($classes) ?></p>
        <p>Active Rooms: <?= count($activeRooms) ?></p>
    </div>
</div>

<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
    <?php if (empty($classes)): ?>
        <div class="col-span-full">
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-8 text-center">
                <i class="fas fa-chalkboard text-6xl text-gray-300 mb-4"></i>
                <h3 class="text-lg font-bold text-gray-700 mb-2">Tidak Ada Kelas</h3>
                <p class="text-gray-500">
                    <?php if ($userRole === 'admin' || $userRole === 'teacher'): ?>
                        Belum ada data kelas. Silakan tambahkan kelas terlebih dahulu.
                    <?php else: ?>
                        Anda belum terdaftar dalam kelas manapun.
                    <?php endif; ?>
                </p>
                <?php if ($userRole === 'admin'): ?>
                    <a href="index.php?page=kelas-tambah" class="mt-4 inline-block bg-[#002147] hover:bg-[#001a35] text-white px-6 py-2 rounded-lg font-medium transition-colors">
                        <i class="fas fa-plus mr-2"></i>Tambah Kelas
                    </a>
                <?php endif; ?>
            </div>
        </div>
    <?php else: ?>
        <?php foreach ($classes as $class): ?>
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden hover:shadow-lg transition-shadow">
            <!-- Class Header -->
            <div class="bg-gradient-to-r from-[#002147] to-[#001a35] p-4 text-white">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-lg font-bold heading-oswald"><?= htmlspecialchars($class['class_name']) ?></h3>
                        <p class="text-sm opacity-90"><?= $class['student_count'] ?> Siswa</p>
                    </div>
                    <div class="w-12 h-12 bg-white/20 rounded-full flex items-center justify-center">
                        <i class="fas fa-chalkboard text-xl"></i>
                    </div>
                </div>
            </div>
            
            <!-- Class Info -->
            <div class="p-4">
                <?php if (isset($roomsByClass[$class['id']])): ?>
                    <?php $room = $roomsByClass[$class['id']]; ?>
                    <div class="mb-4">
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-sm font-medium text-gray-600">Status Room</span>
                            <span class="px-2 py-1 bg-green-100 text-green-700 text-xs font-bold rounded-full">
                                <i class="fas fa-circle text-green-500 mr-1" style="font-size: 8px;"></i>
                                Aktif
                            </span>
                        </div>
                        <div class="flex items-center gap-4 text-sm text-gray-500">
                            <span><i class="fas fa-users mr-1"></i> <?= $room['active_participants'] ?> Online</span>
                            <span><i class="fas fa-clock mr-1"></i> <?= $room['last_activity'] ? time_ago($room['last_activity']) : 'Belum ada aktivitas' ?></span>
                        </div>
                    </div>
                    
                    <div class="flex gap-2">
                        <a href="index.php?page=video-chat&class_id=<?= $class['id'] ?>&room_id=<?= $room['id'] ?>" 
                           class="flex-1 bg-[#002147] hover:bg-[#001a35] text-white py-2 px-4 rounded-lg text-center font-medium transition-colors">
                            <i class="fas fa-video mr-2"></i>Masuk Room
                        </a>
                        <button onclick="copyRoomCode('<?= $room['room_code'] ?>')" 
                                class="bg-gray-100 hover:bg-gray-200 text-gray-700 py-2 px-4 rounded-lg transition-colors">
                            <i class="fas fa-copy"></i>
                        </button>
                    </div>
                <?php else: ?>
                    <div class="mb-4">
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-sm font-medium text-gray-600">Status Room</span>
                            <span class="px-2 py-1 bg-gray-100 text-gray-600 text-xs font-bold rounded-full">
                                <i class="fas fa-circle text-gray-400 mr-1" style="font-size: 8px;"></i>
                                Belum Aktif
                            </span>
                        </div>
                        <p class="text-sm text-gray-500">Room chat belum dibuat untuk kelas ini</p>
                    </div>
                    
                    <button onclick="createRoom(<?= $class['id'] ?>)" 
                            class="w-full bg-green-600 hover:bg-green-700 text-white py-2 px-4 rounded-lg font-medium transition-colors">
                        <i class="fas fa-plus mr-2"></i>Buat Room
                    </button>
                <?php endif; ?>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<?php if (empty($classes)): ?>
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-8 text-center">
        <i class="fas fa-chalkboard text-6xl text-gray-300 mb-4"></i>
        <h3 class="text-lg font-bold text-gray-700 mb-2">Belum Ada Kelas</h3>
        <p class="text-gray-500">Anda belum terdaftar dalam kelas manapun.</p>
    </div>
<?php endif; ?>

<?php if (isset($_SESSION['success'])): ?>
    <div class="fixed top-4 right-4 z-50">
        <div class="bg-white rounded-lg shadow-lg border-l-4 border-green-500 p-4 max-w-sm">
            <div class="flex items-center">
                <i class="fas fa-check-circle text-green-500 text-xl mr-3"></i>
                <div>
                    <p class="font-bold">Berhasil</p>
                    <p class="text-sm text-gray-600"><?= htmlspecialchars($_SESSION['success']) ?></p>
                </div>
            </div>
        </div>
    </div>
    <?php unset($_SESSION['success']); ?>
<?php endif; ?>

<!-- Notification Toast -->
<div id="notification" class="fixed top-4 right-4 z-50 hidden">
    <div class="bg-white rounded-lg shadow-lg border-l-4 p-4 max-w-sm">
        <div class="flex items-center">
            <i id="notificationIcon" class="text-xl mr-3"></i>
            <div>
                <p id="notificationTitle" class="font-bold"></p>
                <p id="notificationMessage" class="text-sm text-gray-600"></p>
            </div>
        </div>
    </div>
</div>

<script>
function copyRoomCode(roomCode) {
    navigator.clipboard.writeText(roomCode).then(() => {
        showNotification('success', 'Berhasil!', `Kode room ${roomCode} disalin`);
    }).catch(() => {
        showNotification('error', 'Gagal', 'Tidak dapat menyalin kode room');
    });
}

async function createRoom(classId) {
    try {
        const response = await fetch('api/create_room.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                class_id: classId
            })
        });
        
        const result = await response.json();
        
        if (result.success) {
            showNotification('success', 'Berhasil!', 'Room chat berhasil dibuat');
            setTimeout(() => {
                window.location.href = `index.php?page=video-chat&class_id=${classId}&room_id=${result.room_id}`;
            }, 1500);
        } else {
            showNotification('error', 'Gagal', result.message || 'Terjadi kesalahan');
        }
    } catch (error) {
        console.error('Error creating room:', error);
        showNotification('error', 'Gagal', 'Terjadi kesalahan koneksi');
    }
}

function showNotification(type, title, message) {
    const notification = document.getElementById('notification');
    const icon = document.getElementById('notificationIcon');
    const titleEl = document.getElementById('notificationTitle');
    const messageEl = document.getElementById('notificationMessage');
    
    // Set icon and colors based on type
    if (type === 'success') {
        icon.className = 'fas fa-check-circle text-green-500 text-xl mr-3';
        notification.querySelector('div').className = 'bg-white rounded-lg shadow-lg border-l-4 border-green-500 p-4 max-w-sm';
    } else {
        icon.className = 'fas fa-exclamation-circle text-red-500 text-xl mr-3';
        notification.querySelector('div').className = 'bg-white rounded-lg shadow-lg border-l-4 border-red-500 p-4 max-w-sm';
    }
    
    titleEl.textContent = title;
    messageEl.textContent = message;
    
    notification.classList.remove('hidden');
    
    setTimeout(() => {
        notification.classList.add('hidden');
    }, 3000);
}

// Helper function for time ago
function time_ago(datetime) {
    const date = new Date(datetime);
    const now = new Date();
    const seconds = Math.floor((now - date) / 1000);
    
    if (seconds < 60) return 'Baru saja';
    if (seconds < 3600) return Math.floor(seconds / 60) + ' menit lalu';
    if (seconds < 86400) return Math.floor(seconds / 3600) + ' jam lalu';
    return Math.floor(seconds / 86400) + ' hari lalu';
}
</script>
