<?php
require_once __DIR__ . '/../../includes/auth_check.php';
require_once __DIR__ . '/../../config/database.php';

// Only students can access
if ($_SESSION['role'] !== 'siswa') {
    header("Location: index.php?page=dashboard");
    exit;
}

$userId = $_SESSION['user_id'] ?? 0;

// Get student data
$stmt = $pdo->prepare("SELECT id, nama_siswa, nis, kelas_id FROM siswa WHERE user_id = ?");
$stmt->execute([$userId]);
$student = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$student) {
    echo "<div class='bg-red-50 border border-red-200 text-red-700 p-4 rounded-lg'>Data siswa tidak ditemukan.</div>";
    return;
}

// Get class info
$className = '';
if ($student['kelas_id']) {
    $stmt = $pdo->prepare("SELECT class_name FROM classes WHERE id = ?");
    $stmt->execute([$student['kelas_id']]);
    $class = $stmt->fetch(PDO::FETCH_ASSOC);
    $className = $class['class_name'] ?? '';
}

// Get RFID card info
$stmt = $pdo->prepare("
    SELECT rfid_code, card_status, issued_date, expired_date, notes 
    FROM student_rfid 
    WHERE siswa_id = ?
");
$stmt->execute([$student['id']]);
$rfidCard = $stmt->fetch(PDO::FETCH_ASSOC);

// Get attendance history
$stmt = $pdo->prepare("
    SELECT a.*, rl.scan_type, rl.scan_time, rl.device_location 
    FROM absensi a 
    LEFT JOIN rfid_logs rl ON a.siswa_id = rl.siswa_id AND DATE(a.tanggal) = DATE(rl.scan_time)
    WHERE a.siswa_id = ? 
    ORDER BY a.tanggal DESC, rl.scan_time DESC 
    LIMIT 50
");
$stmt->execute([$student['id']]);
$attendanceHistory = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get recent RFID scans
$stmt = $pdo->prepare("
    SELECT * FROM rfid_logs 
    WHERE siswa_id = ? 
    ORDER BY scan_time DESC 
    LIMIT 20
");
$stmt->execute([$student['id']]);
$recentScans = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Calculate statistics
$stmt = $pdo->prepare("
    SELECT 
        COUNT(CASE WHEN status = 'hadir' THEN 1 END) as total_hadir,
        COUNT(CASE WHEN status = 'izin' THEN 1 END) as total_izin,
        COUNT(CASE WHEN status = 'sakit' THEN 1 END) as total_sakit,
        COUNT(CASE WHEN status = 'alpha' THEN 1 END) as total_alpha,
        COUNT(*) as total_hari
    FROM absensi 
    WHERE siswa_id = ? AND MONTH(tanggal) = MONTH(CURRENT_DATE) AND YEAR(tanggal) = YEAR(CURRENT_DATE)
");
$stmt->execute([$student['id']]);
$monthlyStats = $stmt->fetch(PDO::FETCH_ASSOC);

// Check if card is expired
$isExpired = false;
if ($rfidCard && $rfidCard['expired_date']) {
    $isExpired = strtotime($rfidCard['expired_date']) < strtotime(date('Y-m-d'));
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Absensi Saya - <?= htmlspecialchars($student['nama_siswa']) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&family=Oswald:wght@500;600&display=swap');
        body { font-family: 'Roboto', sans-serif; }
        .heading-oswald { font-family: 'Oswald', sans-serif; }
        
        .card-hover {
            transition: all 0.3s ease;
        }
        .card-hover:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
        }
        
        .status-badge {
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0% { opacity: 1; }
            50% { opacity: 0.7; }
            100% { opacity: 1; }
        }
        
        .scan-animation {
            animation: scan 1.5s ease-in-out infinite;
        }
        
        @keyframes scan {
            0% { transform: scale(1); opacity: 1; }
            50% { transform: scale(1.1); opacity: 0.8; }
            100% { transform: scale(1); opacity: 1; }
        }
    </style>
</head>
<body class="bg-gray-50 min-h-screen">
    <!-- Header -->
    <header class="bg-[#002147] text-white shadow-lg">
        <div class="container mx-auto px-4 py-4">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-4">
                    <a href="index.php?page=dashboard" class="text-white hover:text-[#ffae01] transition-colors">
                        <i class="fas fa-arrow-left text-xl"></i>
                    </a>
                    <div>
                        <h1 class="text-xl font-bold heading-oswald">Absensi Saya</h1>
                        <p class="text-sm text-gray-300"><?= htmlspecialchars($student['nama_siswa']) ?> (<?= htmlspecialchars($student['nis']) ?>)</p>
                    </div>
                </div>
                <div class="flex items-center gap-4">
                    <span class="text-sm">
                        <i class="fas fa-graduation-cap mr-2"></i>
                        <?= htmlspecialchars($className) ?>
                    </span>
                    <button onclick="refreshData()" class="text-white hover:text-[#ffae01] transition-colors">
                        <i class="fas fa-sync-alt text-xl"></i>
                    </button>
                </div>
            </div>
        </div>
    </header>

    <main class="container mx-auto px-4 py-8">
        <!-- RFID Card Status -->
        <section class="mb-8">
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 card-hover">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-lg font-bold text-gray-800 heading-oswald">Status Kartu RFID</h2>
                    <div id="refreshIcon" class="hidden">
                        <i class="fas fa-spinner fa-spin text-[#002147]"></i>
                    </div>
                </div>
                
                <?php if ($rfidCard): ?>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="space-y-4">
                            <div class="flex items-center gap-3">
                                <div class="w-12 h-12 bg-[#002147] rounded-lg flex items-center justify-center">
                                    <i class="fas fa-id-card text-white"></i>
                                </div>
                                <div>
                                    <p class="text-sm text-gray-500">Kode Kartu</p>
                                    <p class="font-mono font-bold text-gray-800"><?= htmlspecialchars($rfidCard['rfid_code']) ?></p>
                                </div>
                            </div>
                            
                            <div class="flex items-center gap-3">
                                <div class="w-12 h-12 <?= 
                                    ($rfidCard['card_status'] === 'active') ? 'bg-green-500' :
                                    (($rfidCard['card_status'] === 'inactive') ? 'bg-gray-500' :
                                    (($rfidCard['card_status'] === 'lost') ? 'bg-red-500' :
                                    'bg-orange-500'))
                                ?> rounded-lg flex items-center justify-center">
                                    <i class="fas fa-circle text-white text-xs"></i>
                                </div>
                                <div>
                                    <p class="text-sm text-gray-500">Status</p>
                                    <p class="font-bold text-gray-800">
                                        <?php
                                        switch ($rfidCard['card_status']) {
                                            case 'active': echo 'Aktif'; break;
                                            case 'inactive': echo 'Tidak Aktif'; break;
                                            case 'lost': echo 'Hilang'; break;
                                            case 'damaged': echo 'Rusak'; break;
                                            default: echo 'Tidak Diketahui'; break;
                                        }
                                        ?>
                                    </p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="space-y-4">
                            <div>
                                <p class="text-sm text-gray-500">Tanggal Terbit</p>
                                <p class="font-bold text-gray-800"><?= $rfidCard['issued_date'] ? date('d M Y', strtotime($rfidCard['issued_date'])) : '-' ?></p>
                            </div>
                            
                            <div>
                                <p class="text-sm text-gray-500">Tanggal Kadaluarsa</p>
                                <p class="font-bold <?= $isExpired ? 'text-red-600' : 'text-gray-800' ?>">
                                    <?= $rfidCard['expired_date'] ? date('d M Y', strtotime($rfidCard['expired_date'])) : 'Tidak ada' ?>
                                    <?php if ($isExpired): ?>
                                        <span class="ml-2 text-xs bg-red-100 text-red-700 px-2 py-1 rounded">KADALUARSA</span>
                                    <?php endif; ?>
                                </p>
                            </div>
                            
                            <?php if ($rfidCard['notes']): ?>
                                <div>
                                    <p class="text-sm text-gray-500">Catatan</p>
                                    <p class="text-gray-800"><?= htmlspecialchars($rfidCard['notes']) ?></p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <?php if ($isExpired): ?>
                        <div class="mt-4 p-4 bg-red-50 border border-red-200 rounded-lg">
                            <div class="flex items-center gap-3">
                                <i class="fas fa-exclamation-triangle text-red-500 text-xl"></i>
                                <div>
                                    <p class="font-bold text-red-700">Kartu RFID Kadaluarsa!</p>
                                    <p class="text-sm text-red-600">Silakan hubungi admin untuk perpanjangan kartu.</p>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                <?php else: ?>
                    <div class="text-center py-8">
                        <i class="fas fa-id-card text-6xl text-gray-300 mb-4"></i>
                        <h3 class="text-lg font-bold text-gray-700 mb-2">Belum Ada Kartu RFID</h3>
                        <p class="text-gray-500 mb-4">Anda belum terdaftar memiliki kartu RFID.</p>
                        <button onclick="requestCard()" class="bg-[#002147] hover:bg-[#001a35] text-white px-6 py-3 rounded-lg font-medium transition-colors">
                            <i class="fas fa-plus mr-2"></i>Ajukan Kartu RFID
                        </button>
                    </div>
                <?php endif; ?>
            </div>
        </section>

        <!-- Monthly Statistics -->
        <section class="mb-8">
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                <h2 class="text-lg font-bold text-gray-800 mb-4 heading-oswald">Statistik Bulan Ini (<?= date('F Y') ?>)</h2>
                
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                    <div class="text-center p-4 bg-green-50 rounded-lg">
                        <div class="w-12 h-12 bg-green-500 rounded-full flex items-center justify-center mx-auto mb-2">
                            <i class="fas fa-check text-white"></i>
                        </div>
                        <p class="text-2xl font-bold text-green-700"><?= $monthlyStats['total_hadir'] ?></p>
                        <p class="text-sm text-gray-600">Hadir</p>
                    </div>
                    
                    <div class="text-center p-4 bg-blue-50 rounded-lg">
                        <div class="w-12 h-12 bg-blue-500 rounded-full flex items-center justify-center mx-auto mb-2">
                            <i class="fas fa-calendar-check text-white"></i>
                        </div>
                        <p class="text-2xl font-bold text-blue-700"><?= $monthlyStats['total_izin'] ?></p>
                        <p class="text-sm text-gray-600">Izin</p>
                    </div>
                    
                    <div class="text-center p-4 bg-yellow-50 rounded-lg">
                        <div class="w-12 h-12 bg-yellow-500 rounded-full flex items-center justify-center mx-auto mb-2">
                            <i class="fas fa-heartbeat text-white"></i>
                        </div>
                        <p class="text-2xl font-bold text-yellow-700"><?= $monthlyStats['total_sakit'] ?></p>
                        <p class="text-sm text-gray-600">Sakit</p>
                    </div>
                    
                    <div class="text-center p-4 bg-red-50 rounded-lg">
                        <div class="w-12 h-12 bg-red-500 rounded-full flex items-center justify-center mx-auto mb-2">
                            <i class="fas fa-times text-white"></i>
                        </div>
                        <p class="text-2xl font-bold text-red-700"><?= $monthlyStats['total_alpha'] ?></p>
                        <p class="text-sm text-gray-600">Alpha</p>
                    </div>
                </div>
                
                <div class="mt-4 pt-4 border-t">
                    <div class="flex justify-between items-center">
                        <div>
                            <p class="text-sm text-gray-500">Total Hari Efektif</p>
                            <p class="text-lg font-bold text-gray-800"><?= $monthlyStats['total_hari'] ?> hari</p>
                        </div>
                        <div class="text-right">
                            <p class="text-sm text-gray-500">Kehadiran</p>
                            <p class="text-lg font-bold <?= 
                                (($monthlyStats['total_hadir'] / $monthlyStats['total_hari'] * 100) >= 75) ? 'text-green-600' :
                                (($monthlyStats['total_hadir'] / $monthlyStats['total_hari'] * 100) >= 50) ? 'text-yellow-600' :
                                'text-red-600'
                            ?>">
                                <?= $monthlyStats['total_hari'] > 0 ? round(($monthlyStats['total_hadir'] / $monthlyStats['total_hari']) * 100, 1) : 0 ?>%
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Recent Scans -->
        <section class="mb-8">
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                <div class="flex justify-between items-center mb-4">
                    <h2 class="text-lg font-bold text-gray-800 heading-oswald">Scan Terbaru</h2>
                    <button onclick="exportHistory()" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg text-sm font-medium transition-colors">
                        <i class="fas fa-download mr-2"></i>Export
                    </button>
                </div>
                
                <div class="overflow-x-auto">
                    <?php if ($recentScans): ?>
                        <div class="space-y-2">
                            <?php foreach ($recentScans as $scan): ?>
                                <div class="flex items-center gap-4 p-3 rounded-lg <?= 
                                    $scan['status'] === 'success' ? 'bg-green-50' :
                                    $scan['status'] === 'invalid_card' ? 'bg-red-50' :
                                    $scan['status'] === 'student_not_found' ? 'bg-orange-50' :
                                    'bg-gray-50'
                                ?> hover:bg-opacity-80 transition-colors">
                                    <div class="w-10 h-10 rounded-full flex items-center justify-center <?= 
                                        $scan['status'] === 'success' ? 'bg-green-500 text-white' :
                                        $scan['status'] === 'invalid_card' ? 'bg-red-500 text-white' :
                                        $scan['status'] === 'student_not_found' ? 'bg-orange-500 text-white' :
                                        'bg-gray-500 text-white'
                                    ?>">
                                        <i class="fas <?= 
                                            $scan['status'] === 'success' ? 'fa-check' :
                                            $scan['status'] === 'invalid_card' ? 'fa-times' :
                                            $scan['status'] === 'student_not_found' ? 'fa-user-slash' :
                                            'fa-question'
                                        ?> text-sm"></i>
                                    </div>
                                    <div class="flex-1">
                                        <div class="flex justify-between items-start">
                                            <div>
                                                <p class="font-medium text-gray-800">
                                                    <?= $scan['scan_type'] === 'check_in' ? 'Check In' : 'Check Out' ?>
                                                </p>
                                                <p class="text-sm text-gray-600">
                                                    <?= $scan['device_location'] ?? 'Unknown Location' ?>
                                                </p>
                                            </div>
                                            <div class="text-right">
                                                <p class="text-xs text-gray-500">
                                                    <?= date('d M Y', strtotime($scan['scan_time'])) ?>
                                                </p>
                                                <p class="text-sm font-medium text-gray-700">
                                                    <?= date('H:i', strtotime($scan['scan_time'])) ?>
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-8">
                            <i class="fas fa-history text-6xl text-gray-300 mb-4"></i>
                            <p class="text-gray-500">Belum ada data scan</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </section>

        <!-- Attendance History -->
        <section>
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                <div class="flex justify-between items-center mb-4">
                    <h2 class="text-lg font-bold text-gray-800 heading-oswald">Riwayat Absensi</h2>
                    <div class="flex items-center gap-2">
                        <input type="month" id="monthFilter" onchange="filterHistory()" 
                               class="px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-[#002147] focus:border-transparent outline-none">
                        <button onclick="printHistory()" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm font-medium transition-colors">
                            <i class="fas fa-print mr-2"></i>Cetak
                        </button>
                    </div>
                </div>
                
                <div class="overflow-x-auto">
                    <table class="w-full text-left" id="attendanceTable">
                        <thead>
                            <tr class="text-gray-400 text-sm uppercase tracking-wider border-b">
                                <th class="pb-3 font-medium">Tanggal</th>
                                <th class="pb-3 font-medium">Status</th>
                                <th class="pb-3 font-medium">Check In</th>
                                <th class="pb-3 font-medium">Check Out</th>
                                <th class="pb-3 font-medium">Keterangan</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <?php if ($attendanceHistory): ?>
                                <?php foreach ($attendanceHistory as $attendance): ?>
                                    <tr class="hover:bg-gray-50">
                                        <td class="py-4">
                                            <div class="font-medium text-gray-900">
                                                <?= date('d M Y', strtotime($attendance['tanggal'])) ?>
                                            </div>
                                            <div class="text-sm text-gray-500">
                                                <?= date('l', strtotime($attendance['tanggal'])) ?>
                                            </div>
                                        </td>
                                        <td class="py-4">
                                            <span class="px-2 py-1 text-xs font-bold rounded <?= 
                                                $attendance['status'] === 'hadir' ? 'bg-green-100 text-green-700' :
                                                $attendance['status'] === 'izin' ? 'bg-blue-100 text-blue-700' :
                                                $attendance['status'] === 'sakit' ? 'bg-yellow-100 text-yellow-700' :
                                                'bg-red-100 text-red-700'
                                            ?>">
                                                <?php
                                                switch ($attendance['status']) {
                                                    case 'hadir': echo 'Hadir'; break;
                                                    case 'izin': echo 'Izin'; break;
                                                    case 'sakit': echo 'Sakit'; break;
                                                    case 'alpha': echo 'Alpha'; break;
                                                    default: echo '-'; break;
                                                }
                                                ?>
                                            </span>
                                        </td>
                                        <td class="py-4 text-sm text-gray-600">
                                            <?= $attendance['scan_time'] && $attendance['scan_type'] === 'check_in' ? date('H:i', strtotime($attendance['scan_time'])) : '-' ?>
                                        </td>
                                        <td class="py-4 text-sm text-gray-600">
                                            <?= $attendance['scan_time'] && $attendance['scan_type'] === 'check_out' ? date('H:i', strtotime($attendance['scan_time'])) : '-' ?>
                                        </td>
                                        <td class="py-4 text-sm text-gray-800">
                                            <?= htmlspecialchars($attendance['keterangan'] ?? '-') ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5" class="py-8 text-center text-gray-500">
                                        <i class="fas fa-calendar-times text-4xl mb-2"></i>
                                        <p>Belum ada data absensi</p>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </section>
    </main>

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
        // Set current month in filter
        document.getElementById('monthFilter').value = '<?= date('Y-m') ?>';
        
        function refreshData() {
            const icon = document.getElementById('refreshIcon');
            icon.classList.remove('hidden');
            
            setTimeout(() => {
                window.location.reload();
            }, 1000);
        }
        
        function requestCard() {
            showNotification('info', 'Pengajuan Kartu', 'Silakan hubungi admin sekolah untuk pengajuan kartu RFID baru.');
        }
        
        function filterHistory() {
            const selectedMonth = document.getElementById('monthFilter').value;
            const rows = document.querySelectorAll('#attendanceTable tbody tr');
            
            rows.forEach(row => {
                const dateCell = row.querySelector('td:first-child');
                if (dateCell) {
                    const dateText = dateCell.textContent.trim();
                    const date = new Date(dateText);
                    const rowMonth = date.getFullYear() + '-' + String(date.getMonth() + 1).padStart(2, '0');
                    
                    row.style.display = rowMonth === selectedMonth ? '' : 'none';
                }
            });
        }
        
        function exportHistory() {
            const table = document.getElementById('attendanceTable');
            const rows = table.querySelectorAll('tbody tr');
            
            let csv = 'Tanggal,Status,Check In,Check Out,Keterangan\n';
            
            rows.forEach(row => {
                const cells = row.querySelectorAll('td');
                if (cells.length >= 5) {
                    const tanggal = cells[0].textContent.trim();
                    const status = cells[1].textContent.trim();
                    const checkIn = cells[2].textContent.trim();
                    const checkOut = cells[3].textContent.trim();
                    const keterangan = cells[4].textContent.trim();
                    
                    csv += `"${tanggal}","${status}","${checkIn}","${checkOut}","${keterangan}"\n`;
                }
            });
            
            const blob = new Blob([csv], { type: 'text/csv' });
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = 'absensi_<?= $student['nis'] ?>_' + new Date().toISOString().split('T')[0] + '.csv';
            a.click();
            window.URL.revokeObjectURL(url);
        }
        
        function printHistory() {
            window.print();
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
            } else if (type === 'error') {
                icon.className = 'fas fa-exclamation-circle text-red-500 text-xl mr-3';
                notification.querySelector('div').className = 'bg-white rounded-lg shadow-lg border-l-4 border-red-500 p-4 max-w-sm';
            } else {
                icon.className = 'fas fa-info-circle text-blue-500 text-xl mr-3';
                notification.querySelector('div').className = 'bg-white rounded-lg shadow-lg border-l-4 border-blue-500 p-4 max-w-sm';
            }
            
            titleEl.textContent = title;
            messageEl.textContent = message;
            
            notification.classList.remove('hidden');
            
            setTimeout(() => {
                notification.classList.add('hidden');
            }, 3000);
        }
        
        // Auto-refresh every 30 seconds
        setInterval(() => {
            refreshData();
        }, 30000);
        
        // Add print styles
        const printStyles = document.createElement('style');
        printStyles.textContent = `
            @media print {
                header, button, .no-print { display: none !important; }
                table { font-size: 12px; }
                th { background-color: #f3f4f6 !important; }
            }
        `;
        document.head.appendChild(printStyles);
    </script>
</body>
</html>
