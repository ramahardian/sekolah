<?php
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../config/database.php';

$userId = $_SESSION['user_id'] ?? 0;
$userRole = $_SESSION['role'] ?? '';

// Simple debug info first
$debugInfo = [
    'user_id' => $userId,
    'user_role' => $userRole,
    'database_connected' => false,
    'classes_found' => 0,
    'error' => null
];

// Test database connection
try {
    $pdo->query("SELECT 1");
    $debugInfo['database_connected'] = true;
} catch (Exception $e) {
    $debugInfo['error'] = 'Database connection failed: ' . $e->getMessage();
}

// Get user's classes - simplified version
$classes = [];
if ($debugInfo['database_connected']) {
    try {
        if ($userRole === 'admin' || $userRole === 'teacher') {
            // Simple query for admin/teacher - use correct table name
            $stmt = $pdo->query("SELECT id, nama_kelas as class_name FROM kelas ORDER BY nama_kelas");
            $classes = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } else {
            // Query for students using student_classes table
            $stmt = $pdo->prepare("SELECT k.id, k.nama_kelas as class_name 
                                  FROM student_classes sc 
                                  JOIN kelas k ON sc.class_id = k.id 
                                  WHERE sc.student_id = ? AND sc.status = 'active'");
            $stmt->execute([$userId]);
            $classes = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Fallback: if no records in student_classes, try direct siswa.kelas_id
            if (empty($classes)) {
                $stmt = $pdo->prepare("SELECT k.id, k.nama_kelas as class_name 
                                      FROM siswa s 
                                      JOIN kelas k ON s.kelas_id = k.id 
                                      WHERE s.user_id = ?");
                $stmt->execute([$userId]);
                $classes = $stmt->fetchAll(PDO::FETCH_ASSOC);
            }
        }
        $debugInfo['classes_found'] = count($classes);
    } catch (Exception $e) {
        $debugInfo['error'] = 'Query failed: ' . $e->getMessage();
    }
}
?>

<div class="mb-8">
    <h1 class="text-2xl font-bold text-gray-800">Kelas Video Chat</h1>
    <p class="text-gray-500">Pilih kelas untuk memulai video chat dan diskusi</p>
    
    <!-- Debug Info -->
    <div class="mt-4 p-4 bg-gray-100 rounded-lg text-sm">
        <p><strong>Debug Info:</strong></p>
        <p>User ID: <?= $debugInfo['user_id'] ?></p>
        <p>User Role: <?= $debugInfo['user_role'] ?></p>
        <p>Database Connected: <?= $debugInfo['database_connected'] ? 'Yes' : 'No' ?></p>
        <p>Classes Found: <?= $debugInfo['classes_found'] ?></p>
        <p>Active Rooms: 0 (Simple Mode)</p>
        <?php if ($debugInfo['error']): ?>
            <p class="text-red-600">Error: <?= htmlspecialchars($debugInfo['error']) ?></p>
        <?php endif; ?>
    </div>
</div>

<?php if ($debugInfo['error']): ?>
    <div class="bg-red-50 border border-red-200 rounded-xl p-6 mb-6">
        <div class="flex items-center">
            <i class="fas fa-exclamation-triangle text-red-500 text-xl mr-3"></i>
            <div>
                <h3 class="text-lg font-bold text-red-800">System Error</h3>
                <p class="text-red-600">Terjadi kesalahan sistem. Mohon hubungi administrator.</p>
            </div>
        </div>
    </div>
<?php endif; ?>

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
                        <p class="text-sm opacity-90">Kelas Aktif</p>
                    </div>
                    <div class="w-12 h-12 bg-white/20 rounded-full flex items-center justify-center">
                        <i class="fas fa-chalkboard text-xl"></i>
                    </div>
                </div>
            </div>
            
            <!-- Class Info -->
            <div class="p-4">
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
            </div>
        </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<script>
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
            alert('Room berhasil dibuat!');
            window.location.href = `index.php?page=video-chat&class_id=${classId}&room_id=${result.room_id}`;
        } else {
            alert('Gagal membuat room: ' + (result.message || 'Terjadi kesalahan'));
        }
    } catch (error) {
        console.error('Error creating room:', error);
        alert('Terjadi kesalahan koneksi');
    }
}
</script>
