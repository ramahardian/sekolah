<?php
require_once __DIR__ . '/config/database.php';

// Get school info
$stmtSettings = $pdo->query("SELECT setting_key, setting_value FROM cms_setting WHERE setting_key IN ('header_title', 'header_logo', 'school_name', 'school_address', 'school_phone')");
$settings = [];
if ($stmtSettings) {
    while ($row = $stmtSettings->fetch(PDO::FETCH_ASSOC)) {
        $settings[$row['setting_key']] = $row['setting_value'];
    }
}

$pageTitle = $settings['header_title'] ?? 'SIS Pro';
$schoolName = $settings['school_name'] ?? 'Sekolah';
$schoolAddress = $settings['school_address'] ?? 'Jl. Pendidikan No. 10';
$schoolPhone = $settings['school_phone'] ?? '021-123456';
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Portal Siswa - <?= htmlspecialchars($pageTitle) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&family=Oswald:wght@500;600;700&display=swap');
        body { font-family: 'Roboto', sans-serif; }
        .heading-oswald { font-family: 'Oswald', sans-serif; }
        
        .hero-gradient {
            background: linear-gradient(135deg, #002147 0%, #001a35 100%);
        }
        
        .card-hover {
            transition: all 0.3s ease;
            transform: translateY(0);
        }
        
        .card-hover:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 40px rgba(0,0,0,0.15);
        }
        
        .pulse-animation {
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0% { opacity: 1; }
            50% { opacity: 0.7; }
            100% { opacity: 1; }
        }
        
        .float-animation {
            animation: float 3s ease-in-out infinite;
        }
        
        @keyframes float {
            0% { transform: translateY(0px); }
            50% { transform: translateY(-10px); }
            100% { transform: translateY(0px); }
        }
        
        .clock-animation {
            animation: clockTick 1s steps(60) infinite;
        }
        
        @keyframes clockTick {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }
    </style>
</head>
<body class="bg-gray-50">
    <!-- Header -->
    <header class="bg-white shadow-md border-b border-gray-200">
        <div class="container mx-auto px-4 py-4">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <?php if (!empty($settings['header_logo'])): ?>
                        <img src="<?= htmlspecialchars($settings['header_logo']) ?>" alt="Logo" class="h-10 w-auto object-contain">
                    <?php endif; ?>
                    <div>
                        <h1 class="text-xl font-bold text-[#002147] heading-oswald"><?= htmlspecialchars($schoolName) ?></h1>
                        <p class="text-sm text-gray-500">Portal Informasi Siswa</p>
                    </div>
                </div>
                <div class="flex items-center gap-4">
                    <div class="text-right">
                        <p class="text-sm text-gray-500">Senin, 16 Maret 2026</p>
                        <p class="text-lg font-bold text-[#002147]" id="currentTime">00:00:00</p>
                    </div>
                    <div class="w-12 h-12 bg-[#ffae01] rounded-full flex items-center justify-center">
                        <i class="fas fa-user-graduate text-[#002147] text-xl"></i>
                    </div>
                </div>
            </div>
        </div>
    </header>

 

    <!-- Quick Actions -->
    <section class="py-16">
        <div class="container mx-auto px-4">
 
            
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                <!-- Absensi Card -->
                <div class="bg-white rounded-xl shadow-lg p-6 card-hover cursor-pointer" onclick="openAttendance()">
                    <div class="text-center mb-4">
                        <div class="w-16 h-16 bg-gradient-to-br from-green-400 to-green-600 rounded-full flex items-center justify-center mx-auto mb-4">
                            <i class="fas fa-fingerprint text-white text-2xl"></i>
                        </div>
                        <h4 class="text-lg font-bold text-gray-800 mb-2">Absensi Digital</h4>
                        <p class="text-sm text-gray-600">Scan kartu RFID untuk mencatat kehadiran</p>
                    </div>
                    <div class="mt-4 flex justify-center">
                        <span class="text-xs bg-green-100 text-green-700 px-3 py-1 rounded-full pulse-animation">
                            <i class="fas fa-circle text-green-500 mr-1" style="font-size: 6px;"></i>
                            Aktif 24/7
                        </span>
                    </div>
                </div>

                <!-- Login Siswa Card -->
                <div class="bg-white rounded-xl shadow-lg p-6 card-hover cursor-pointer" onclick="openStudentLogin()">
                    <div class="text-center mb-4">
                        <div class="w-16 h-16 bg-gradient-to-br from-blue-400 to-blue-600 rounded-full flex items-center justify-center mx-auto mb-4">
                            <i class="fas fa-user-graduate text-white text-2xl"></i>
                        </div>
                        <h4 class="text-lg font-bold text-gray-800 mb-2">Portal Siswa</h4>
                        <p class="text-sm text-gray-600">Akses nilai, jurnal, dan tugas</p>
                    </div>
                    <div class="mt-4 flex justify-center">
                        <span class="text-xs bg-blue-100 text-blue-700 px-3 py-1 rounded-full">
                            <i class="fas fa-lock mr-1"></i>
                            Login Required
                        </span>
                    </div>
                </div>

                <!-- Library Card -->
                <div class="bg-white rounded-xl shadow-lg p-6 card-hover cursor-pointer" onclick="openLibrary()">
                    <div class="text-center mb-4">
                        <div class="w-16 h-16 bg-gradient-to-br from-purple-400 to-purple-600 rounded-full flex items-center justify-center mx-auto mb-4">
                            <i class="fas fa-book-open text-white text-2xl"></i>
                        </div>
                        <h4 class="text-lg font-bold text-gray-800 mb-2">E-Library</h4>
                        <p class="text-sm text-gray-600">Akses buku digital dan materi</p>
                    </div>
                    <div class="mt-4 flex justify-center">
                        <span class="text-xs bg-purple-100 text-purple-700 px-3 py-1 rounded-full">
                            <i class="fas fa-download mr-1"></i>
                            1000+ Buku
                        </span>
                    </div>
                </div>

                <!-- Info Card -->
                <div class="bg-white rounded-xl shadow-lg p-6 card-hover cursor-pointer" onclick="showInfo()">
                    <div class="text-center mb-4">
                        <div class="w-16 h-16 bg-gradient-to-br from-orange-400 to-orange-600 rounded-full flex items-center justify-center mx-auto mb-4">
                            <i class="fas fa-info-circle text-white text-2xl"></i>
                        </div>
                        <h4 class="text-lg font-bold text-gray-800 mb-2">Info Sekolah</h4>
                        <p class="text-sm text-gray-600">Pengumuman dan kegiatan</p>
                    </div>
                    <div class="mt-4 flex justify-center">
                        <span class="text-xs bg-orange-100 text-orange-700 px-3 py-1 rounded-full">
                            <i class="fas fa-bell mr-1"></i>
                            Update Terbaru
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </section>

    

    <!-- Footer -->
    <footer class="bg-[#002147] text-white py-8">
        <div class="container mx-auto px-4">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                <div>
                    <h4 class="text-lg font-bold mb-4 heading-oswald"><?= htmlspecialchars($schoolName) ?></h4>
                    <p class="text-gray-300 mb-2">Sistem Informasi Sekolah Terpadu</p>
                    <p class="text-sm text-gray-400">Membangun generasi berprestasi melalui teknologi pendidikan modern.</p>
                </div>
                <div class="text-right">
                    <p class="text-sm text-gray-400 mb-2">Portal Siswa © <?= date('Y') ?></p>
                    <div class="flex justify-end gap-4">
                        <a href="#" class="text-gray-400 hover:text-[#ffae01] transition-colors">
                            <i class="fab fa-facebook text-xl"></i>
                        </a>
                        <a href="#" class="text-gray-400 hover:text-[#ffae01] transition-colors">
                            <i class="fab fa-instagram text-xl"></i>
                        </a>
                        <a href="#" class="text-gray-400 hover:text-[#ffae01] transition-colors">
                            <i class="fab fa-youtube text-xl"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </footer>

    <!-- Modal for Info -->
    <div id="infoModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="bg-white rounded-xl shadow-2xl max-w-md w-full p-6">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-xl font-bold text-gray-800">Informasi Portal</h3>
                    <button onclick="closeInfoModal()" class="text-gray-500 hover:text-gray-700">
                        <i class="fas fa-times text-xl"></i>
                    </button>
                </div>
                <div class="space-y-4">
                    <div class="flex items-center gap-3">
                        <i class="fas fa-clock text-[#002147]"></i>
                        <div>
                            <p class="font-medium text-gray-800">Jam Operasional</p>
                            <p class="text-sm text-gray-600">Portal tersedia 24/7 untuk absensi digital</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-3">
                        <i class="fas fa-shield-alt text-[#002147]"></i>
                        <div>
                            <p class="font-medium text-gray-800">Keamanan</p>
                            <p class="text-sm text-gray-600">Data siswa terenkripsi dan aman</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-3">
                        <i class="fas fa-mobile-alt text-[#002147]"></i>
                        <div>
                            <p class="font-medium text-gray-800">Mobile Friendly</p>
                            <p class="text-sm text-gray-600">Akses dari smartphone atau tablet</p>
                        </div>
                    </div>
                </div>
                <div class="mt-6 text-center">
                    <button onclick="closeInfoModal()" class="bg-[#002147] hover:bg-[#001a35] text-white px-6 py-3 rounded-lg font-medium transition-colors">
                        Tutup
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Update current time
        function updateCurrentTime() {
            const now = new Date();
            const timeString = now.toLocaleTimeString('id-ID', {
                hour: '2-digit',
                minute: '2-digit',
                second: '2-digit'
            });
            document.getElementById('currentTime').textContent = timeString;
        }
        
        // Open attendance page
        function openAttendance() {
            window.open('rfid-attendance-view.php', '_blank');
        }
        
        // Open student login
        function openStudentLogin() {
            window.location.href = 'index.php?page=login';
        }
        
        // Open library (placeholder)
        function openLibrary() {
            showNotification('info', 'E-Library', 'Fitur perpustakaan digital akan segera tersedia');
        }
        
        // Show info modal
        function showInfo() {
            document.getElementById('infoModal').classList.remove('hidden');
        }
        
        // Close info modal
        function closeInfoModal() {
            document.getElementById('infoModal').classList.add('hidden');
        }
        
        // Show notification
        function showNotification(type, title, message) {
            // Create notification element
            const notification = document.createElement('div');
            notification.className = `fixed top-4 right-4 z-50 bg-white rounded-lg shadow-lg border-l-4 p-4 max-w-sm transform transition-all duration-300`;
            
            if (type === 'info') {
                notification.classList.add('border-blue-500');
            } else if (type === 'success') {
                notification.classList.add('border-green-500');
            } else {
                notification.classList.add('border-red-500');
            }
            
            notification.innerHTML = `
                <div class="flex items-center">
                    <i class="fas fa-info-circle text-blue-500 text-xl mr-3"></i>
                    <div>
                        <p class="font-bold">${title}</p>
                        <p class="text-sm text-gray-600">${message}</p>
                    </div>
                </div>
            `;
            
            document.body.appendChild(notification);
            
            // Auto remove after 3 seconds
            setTimeout(() => {
                notification.style.transform = 'translateX(400px)';
                setTimeout(() => {
                    document.body.removeChild(notification);
                }, 300);
            }, 3000);
        }
        
        // Initialize
        document.addEventListener('DOMContentLoaded', () => {
            updateCurrentTime();
            setInterval(updateCurrentTime, 1000);
            
            // Add keyboard shortcuts
            document.addEventListener('keydown', (e) => {
                if (e.key === '1') {
                    openAttendance();
                } else if (e.key === '2') {
                    openStudentLogin();
                } else if (e.key === '3') {
                    openLibrary();
                } else if (e.key === 'Escape') {
                    closeInfoModal();
                }
            });
            
            // Close modal on background click
            document.getElementById('infoModal').addEventListener('click', (e) => {
                if (e.target.id === 'infoModal') {
                    closeInfoModal();
                }
            });
        });
    </script>
</body>
</html>
