<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Absensi RFID - Portal Siswa</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&family=Oswald:wght@500;600;700&display=swap');
        body { font-family: 'Roboto', sans-serif; }
        .heading-oswald { font-family: 'Oswald', sans-serif; }
        
        .hero-gradient {
            background: linear-gradient(135deg, #002147 0%, #001a35 100%);
        }
        
        .pulse-animation {
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0% { transform: scale(1); opacity: 1; }
            50% { transform: scale(1.05); opacity: 0.8; }
            100% { transform: scale(1); opacity: 1; }
        }
        
        .success-animation {
            animation: successPulse 0.5s ease-out;
        }
        
        @keyframes successPulse {
            0% { transform: scale(1); background-color: rgb(34, 197, 94); }
            50% { transform: scale(1.1); background-color: rgb(74, 222, 128); }
            100% { transform: scale(1); background-color: rgb(34, 197, 94); }
        }
        
        .error-animation {
            animation: errorShake 0.5s ease-out;
        }
        
        @keyframes errorShake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-10px); }
            75% { transform: translateX(10px); }
        }
        
        .scan-animation {
            animation: scan 2s ease-in-out infinite;
        }
        
        @keyframes scan {
            0% { transform: scale(1); }
            50% { transform: scale(1.1); }
            100% { transform: scale(1); }
        }
        
        .float-animation {
            animation: float 3s ease-in-out infinite;
        }
        
        @keyframes float {
            0% { transform: translateY(0px); }
            50% { transform: translateY(-10px); }
            100% { transform: translateY(0px); }
        }
        
        .gradient-text {
            background: linear-gradient(135deg, #ffae01 0%, #ffd700 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        .glass-effect {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
    </style>
</head>
<body class="hero-gradient min-h-screen text-white">
    <!-- Header -->
    <header class="glass-effect border-b border-white/20">
        <div class="container mx-auto px-4 py-4">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-[#ffae01] rounded-lg flex items-center justify-center">
                        <i class="fas fa-id-card text-[#002147]"></i>
                    </div>
                    <div>
                        <h1 class="text-xl font-bold heading-oswald">Absensi RFID</h1>
                        <p class="text-sm text-gray-300">Portal Digital Siswa</p>
                    </div>
                </div>
                <div class="flex items-center gap-4">
                    <div class="text-right">
                        <p class="text-sm text-gray-300" id="currentDate"></p>
                        <p class="text-lg font-bold" id="currentTime"></p>
                    </div>
                    <button onclick="toggleRecentScans()" class="text-white hover:text-[#ffae01] transition-colors">
                        <i class="fas fa-history text-xl"></i>
                    </button>
                </div>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="container mx-auto px-4 py-8">
        <!-- Stats Cards -->
        <section class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-12">
            <div class="glass-effect rounded-xl p-4 text-center">
                <div class="w-12 h-12 bg-green-500 rounded-full flex items-center justify-center mx-auto mb-2">
                    <i class="fas fa-fingerprint text-white"></i>
                </div>
                <p class="text-2xl font-bold" id="totalScans">0</p>
                <p class="text-sm text-gray-300">Total Scan Hari Ini</p>
            </div>
            
            <div class="glass-effect rounded-xl p-4 text-center">
                <div class="w-12 h-12 bg-blue-500 rounded-full flex items-center justify-center mx-auto mb-2">
                    <i class="fas fa-sign-in-alt text-white"></i>
                </div>
                <p class="text-2xl font-bold" id="checkIns">0</p>
                <p class="text-sm text-gray-300">Check In</p>
            </div>
            
            <div class="glass-effect rounded-xl p-4 text-center">
                <div class="w-12 h-12 bg-orange-500 rounded-full flex items-center justify-center mx-auto mb-2">
                    <i class="fas fa-sign-out-alt text-white"></i>
                </div>
                <p class="text-2xl font-bold" id="checkOuts">0</p>
                <p class="text-sm text-gray-300">Check Out</p>
            </div>
            
            <div class="glass-effect rounded-xl p-4 text-center">
                <div class="w-12 h-12 bg-purple-500 rounded-full flex items-center justify-center mx-auto mb-2">
                    <i class="fas fa-users text-white"></i>
                </div>
                <p class="text-2xl font-bold" id="uniqueStudents">0</p>
                <p class="text-sm text-gray-300">Siswa Unik</p>
            </div>
        </section>

        <!-- Scan Section -->
        <section class="max-w-2xl mx-auto mb-12">
            <div class="glass-effect rounded-2xl p-8 text-center">
                <div class="mb-8">
                    <div id="scanIcon" class="w-32 h-32 bg-[#ffae01] rounded-full flex items-center justify-center mx-auto mb-6 scan-animation">
                        <i class="fas fa-wifi text-5xl text-[#002147]"></i>
                    </div>
                    <h2 class="text-3xl font-bold heading-oswald mb-4">
                        <span class="gradient-text">Silakan Tap Kartu RFID</span>
                    </h2>
                    <p class="text-lg text-gray-300 mb-2">Dekatkan kartu RFID Anda ke reader</p>
                    <p class="text-sm text-gray-400">System akan otomatis mendeteksi kartu Anda</p>
                </div>
                
                <!-- Manual Input (for testing) -->
                <div class="mt-8">
                    <label class="block text-sm font-medium text-gray-300 mb-2">Input Manual (Testing)</label>
                    <div class="flex gap-2 max-w-md mx-auto">
                        <input type="text" id="manualRFID" placeholder="Masukkan kode RFID" 
                               class="flex-1 px-4 py-3 bg-white/20 border border-white/30 rounded-lg text-white placeholder-gray-400 focus:ring-2 focus:ring-[#ffae01] focus:border-transparent outline-none">
                        <button onclick="manualScan()" class="bg-[#ffae01] hover:bg-[#ffae01]/80 text-[#002147] px-6 py-3 rounded-lg font-bold transition-colors">
                            <i class="fas fa-qrcode mr-2"></i>Scan
                        </button>
                    </div>
                </div>
            </div>
        </section>

        <!-- Status Display -->
        <section id="statusSection" class="max-w-2xl mx-auto hidden">
            <div id="statusCard" class="glass-effect rounded-2xl p-6">
                <div class="flex items-center gap-4">
                    <div id="statusIcon" class="w-16 h-16 rounded-full flex items-center justify-center">
                        <i class="fas fa-check text-2xl text-white"></i>
                    </div>
                    <div class="flex-1">
                        <h3 id="statusTitle" class="text-xl font-bold mb-1">Scan Berhasil</h3>
                        <p id="statusMessage" class="text-gray-300">Absensi berhasil dicatat</p>
                        <div id="studentInfo" class="mt-4 p-4 bg-white/10 rounded-lg hidden">
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                <div>
                                    <p class="text-sm text-gray-400">Nama</p>
                                    <p class="font-bold text-white" id="studentName"></p>
                                </div>
                                <div>
                                    <p class="text-sm text-gray-400">NIS</p>
                                    <p class="font-bold text-white" id="studentNIS"></p>
                                </div>
                                <div>
                                    <p class="text-sm text-gray-400">Kelas</p>
                                    <p class="font-bold text-white" id="studentClass"></p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Quick Access -->
        <section class="max-w-4xl mx-auto">
            <div class="glass-effect rounded-2xl p-6">
                <h3 class="text-xl font-bold heading-oswald mb-6 text-center">Akses Cepat</h3>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <a href="siswa-portal.php" class="text-center p-4 bg-white/10 rounded-xl hover:bg-white/20 transition-colors">
                        <i class="fas fa-home text-3xl mb-2"></i>
                        <p class="font-medium">Portal Siswa</p>
                    </a>
                    <a href="index.php?page=login" class="text-center p-4 bg-white/10 rounded-xl hover:bg-white/20 transition-colors">
                        <i class="fas fa-sign-in-alt text-3xl mb-2"></i>
                        <p class="font-medium">Login Siswa</p>
                    </a>
                    <button onclick="showHelp()" class="text-center p-4 bg-white/10 rounded-xl hover:bg-white/20 transition-colors">
                        <i class="fas fa-question-circle text-3xl mb-2"></i>
                        <p class="font-medium">Bantuan</p>
                    </button>
                </div>
            </div>
        </section>
    </main>

    <!-- Recent Scans Sidebar -->
    <aside id="recentScansSidebar" class="fixed right-0 top-0 h-full w-80 bg-white shadow-2xl transform translate-x-full transition-transform duration-300 z-50">
        <div class="p-4 border-b bg-[#002147] text-white">
            <div class="flex items-center justify-between">
                <h3 class="font-bold text-lg heading-oswald">Scan Terbaru</h3>
                <button onclick="toggleRecentScans()" class="text-white hover:text-[#ffae01] transition-colors">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
        </div>
        <div id="recentScansList" class="overflow-y-auto" style="height: calc(100vh - 80px);">
            <div class="p-4 text-center text-gray-500">
                <i class="fas fa-spinner fa-spin text-2xl mb-2"></i>
                <p>Memuat data...</p>
            </div>
        </div>
    </aside>

    <!-- Help Modal -->
    <div id="helpModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="bg-white rounded-xl shadow-2xl max-w-md w-full p-6">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-xl font-bold text-gray-800">Panduan Absensi</h3>
                    <button onclick="closeHelpModal()" class="text-gray-500 hover:text-gray-700">
                        <i class="fas fa-times text-xl"></i>
                    </button>
                </div>
                <div class="space-y-4">
                    <div class="flex items-start gap-3">
                        <div class="w-8 h-8 bg-green-100 rounded-full flex items-center justify-center flex-shrink-0">
                            <i class="fas fa-check text-green-600 text-sm"></i>
                        </div>
                        <div>
                            <p class="font-medium text-gray-800">1. Siapkan Kartu RFID</p>
                            <p class="text-sm text-gray-600">Pastikan kartu RFID Anda aktif dan tidak rusak</p>
                        </div>
                    </div>
                    <div class="flex items-start gap-3">
                        <div class="w-8 h-8 bg-blue-100 rounded-full flex items-center justify-center flex-shrink-0">
                            <i class="fas fa-wifi text-blue-600 text-sm"></i>
                        </div>
                        <div>
                            <p class="font-medium text-gray-800">2. Tap Kartu</p>
                            <p class="text-sm text-gray-600">Dekatkan kartu ke RFID reader hingga terdengar bunyi</p>
                        </div>
                    </div>
                    <div class="flex items-start gap-3">
                        <div class="w-8 h-8 bg-purple-100 rounded-full flex items-center justify-center flex-shrink-0">
                            <i class="fas fa-check-circle text-purple-600 text-sm"></i>
                        </div>
                        <div>
                            <p class="font-medium text-gray-800">3. Konfirmasi</p>
                            <p class="text-sm text-gray-600">Tunggu hingga status scan muncul di layar</p>
                        </div>
                    </div>
                </div>
                <div class="mt-6 p-4 bg-yellow-50 rounded-lg">
                    <p class="text-sm text-yellow-800">
                        <i class="fas fa-info-circle mr-2"></i>
                        <strong>Catatan:</strong> Jangan scan dalam waktu kurang dari 5 menit untuk menghindari duplikasi.
                    </p>
                </div>
                <div class="mt-6 text-center">
                    <button onclick="closeHelpModal()" class="bg-[#002147] hover:bg-[#001a35] text-white px-6 py-3 rounded-lg font-medium transition-colors">
                        Mengerti
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Global variables
        let isScanning = false;
        
        // Update current time and date
        function updateCurrentTime() {
            const now = new Date();
            const timeString = now.toLocaleTimeString('id-ID', {
                hour: '2-digit',
                minute: '2-digit',
                second: '2-digit'
            });
            const dateString = now.toLocaleDateString('id-ID', {
                weekday: 'long',
                year: 'numeric',
                month: 'long',
                day: 'numeric'
            });
            
            document.getElementById('currentTime').textContent = timeString;
            document.getElementById('currentDate').textContent = dateString;
        }
        
        // Load statistics
        async function loadStats() {
            try {
                const response = await fetch('public-rfid-scan.php?stats');
                const result = await response.json();
                
                if (result.status === 'success') {
                    document.getElementById('totalScans').textContent = result.data.total_scans;
                    document.getElementById('checkIns').textContent = result.data.check_ins;
                    document.getElementById('checkOuts').textContent = result.data.check_outs;
                    document.getElementById('uniqueStudents').textContent = result.data.unique_students;
                }
            } catch (error) {
                console.error('Error loading stats:', error);
            }
        }
        
        // Manual scan
        async function manualScan() {
            const rfidCode = document.getElementById('manualRFID').value.trim();
            if (!rfidCode) {
                showNotification('error', 'Error', 'Masukkan kode RFID');
                return;
            }
            
            await performScan(rfidCode);
        }
        
        // Perform scan
        async function performScan(rfidCode) {
            if (isScanning) return;
            isScanning = true;
            
            // Show scanning animation
            const scanIcon = document.getElementById('scanIcon');
            scanIcon.classList.add('success-animation');
            
            try {
                const response = await fetch('public-rfid-scan.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        rfid_code: rfidCode
                    })
                });
                
                const result = await response.json();
                
                // Remove animation
                scanIcon.classList.remove('success-animation');
                
                // Show result
                showScanResult(result);
                
                // Clear manual input
                document.getElementById('manualRFID').value = '';
                
                // Refresh stats and recent scans
                loadStats();
                loadRecentScans();
                
            } catch (error) {
                console.error('Scan error:', error);
                scanIcon.classList.remove('success-animation');
                scanIcon.classList.add('error-animation');
                
                showNotification('error', 'Error', 'Gagal terhubung ke server');
                
                setTimeout(() => {
                    scanIcon.classList.remove('error-animation');
                }, 500);
            } finally {
                isScanning = false;
            }
        }
        
        // Show scan result
        function showScanResult(result) {
            const statusSection = document.getElementById('statusSection');
            const statusCard = document.getElementById('statusCard');
            const statusIcon = document.getElementById('statusIcon');
            const statusTitle = document.getElementById('statusTitle');
            const statusMessage = document.getElementById('statusMessage');
            const studentInfo = document.getElementById('studentInfo');
            
            statusSection.classList.remove('hidden');
            
            // Set colors based on status
            let bgColor, iconClass;
            switch (result.status) {
                case 'success':
                    bgColor = 'bg-green-500';
                    iconClass = 'fa-check';
                    statusTitle.textContent = 'Scan Berhasil';
                    break;
                case 'invalid_card':
                    bgColor = 'bg-red-500';
                    iconClass = 'fa-times';
                    statusTitle.textContent = 'Kartu Tidak Valid';
                    break;
                case 'student_not_found':
                    bgColor = 'bg-orange-500';
                    iconClass = 'fa-user-slash';
                    statusTitle.textContent = 'Siswa Tidak Ditemukan';
                    break;
                case 'duplicate_scan':
                    bgColor = 'bg-yellow-500';
                    iconClass = 'fa-exclamation-triangle';
                    statusTitle.textContent = 'Scan Ganda';
                    break;
                default:
                    bgColor = 'bg-gray-500';
                    iconClass = 'fa-question';
                    statusTitle.textContent = 'Error';
            }
            
            statusIcon.className = `w-16 h-16 rounded-full flex items-center justify-center ${bgColor}`;
            statusIcon.innerHTML = `<i class="fas ${iconClass} text-2xl text-white"></i>`;
            statusMessage.textContent = result.message;
            
            // Show student info if available
            if (result.student) {
                studentInfo.classList.remove('hidden');
                document.getElementById('studentName').textContent = result.student.nama;
                document.getElementById('studentNIS').textContent = result.student.nis;
                document.getElementById('studentClass').textContent = result.student.kelas || 'Tidak ada kelas';
            } else {
                studentInfo.classList.add('hidden');
            }
            
            // Auto hide after 5 seconds
            setTimeout(() => {
                statusSection.classList.add('hidden');
            }, 5000);
        }
        
        // Toggle recent scans sidebar
        function toggleRecentScans() {
            const sidebar = document.getElementById('recentScansSidebar');
            sidebar.classList.toggle('translate-x-full');
            
            if (!sidebar.classList.contains('translate-x-full')) {
                loadRecentScans();
            }
        }
        
        // Load recent scans
        async function loadRecentScans() {
            try {
                const response = await fetch('public-rfid-scan.php?recent&limit=20');
                const result = await response.json();
                
                const scansList = document.getElementById('recentScansList');
                
                if (result.status === 'success' && result.data.length > 0) {
                    scansList.innerHTML = result.data.map(scan => `
                        <div class="p-4 border-b hover:bg-gray-50 transition-colors">
                            <div class="flex items-start gap-3">
                                <div class="w-10 h-10 rounded-full flex items-center justify-center ${
                                    scan.status === 'success' ? 'bg-green-100 text-green-600' :
                                    scan.status === 'invalid_card' ? 'bg-red-100 text-red-600' :
                                    scan.status === 'student_not_found' ? 'bg-orange-100 text-orange-600' :
                                    'bg-gray-100 text-gray-600'
                                }">
                                    <i class="fas ${
                                        scan.status === 'success' ? 'fa-check' :
                                        scan.status === 'invalid_card' ? 'fa-times' :
                                        scan.status === 'student_not_found' ? 'fa-user-slash' :
                                        'fa-question'
                                    } text-sm"></i>
                                </div>
                                <div class="flex-1">
                                    <div class="flex items-center justify-between mb-1">
                                        <span class="font-medium text-gray-800">${scan.nama_siswa || 'Unknown'}</span>
                                        <span class="text-xs text-gray-500">${formatTime(scan.scan_time)}</span>
                                    </div>
                                    <div class="text-sm text-gray-600">
                                        ${scan.nis ? `NIS: ${scan.nis}` : ''}
                                        ${scan.class_name ? ` • ${scan.class_name}` : ''}
                                    </div>
                                    <div class="text-xs text-gray-500 mt-1">
                                        ${scan.scan_type === 'check_in' ? 'Check In' : 'Check Out'} • 
                                        ${scan.device_location || 'Unknown Location'}
                                    </div>
                                </div>
                            </div>
                        </div>
                    `).join('');
                } else {
                    scansList.innerHTML = `
                        <div class="p-4 text-center text-gray-500">
                            <i class="fas fa-inbox text-2xl mb-2"></i>
                            <p>Belum ada data scan</p>
                        </div>
                    `;
                }
                
            } catch (error) {
                console.error('Error loading recent scans:', error);
                document.getElementById('recentScansList').innerHTML = `
                    <div class="p-4 text-center text-red-500">
                        <i class="fas fa-exclamation-triangle text-2xl mb-2"></i>
                        <p>Gagal memuat data</p>
                    </div>
                `;
            }
        }
        
        // Format time
        function formatTime(timeString) {
            const date = new Date(timeString);
            return date.toLocaleString('id-ID', {
                month: 'short',
                day: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            });
        }
        
        // Show help modal
        function showHelp() {
            document.getElementById('helpModal').classList.remove('hidden');
        }
        
        // Close help modal
        function closeHelpModal() {
            document.getElementById('helpModal').classList.add('hidden');
        }
        
        // Show notification
        function showNotification(type, title, message) {
            const notification = document.createElement('div');
            notification.className = `fixed top-4 right-4 z-50 bg-white rounded-lg shadow-lg border-l-4 p-4 max-w-sm transform transition-all duration-300`;
            
            if (type === 'success') {
                notification.classList.add('border-green-500');
            } else if (type === 'error') {
                notification.classList.add('border-red-500');
            } else {
                notification.classList.add('border-blue-500');
            }
            
            notification.innerHTML = `
                <div class="flex items-center">
                    <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-circle'} text-${type === 'success' ? 'green' : 'red'}-500 text-xl mr-3"></i>
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
            
            // Load initial stats
            loadStats();
            setInterval(loadStats, 30000); // Refresh every 30 seconds
            
            // Focus on manual input
            document.getElementById('manualRFID').focus();
            
            // Add enter key support
            document.getElementById('manualRFID').addEventListener('keypress', (e) => {
                if (e.key === 'Enter') {
                    manualScan();
                }
            });
            
            // Close modal on background click
            document.getElementById('helpModal').addEventListener('click', (e) => {
                if (e.target.id === 'helpModal') {
                    closeHelpModal();
                }
            });
        });
    </script>
</body>
</html>
