<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Absensi RFID - <?= htmlspecialchars($pageTitle ?? 'SIS Pro') ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&family=Oswald:wght@500;600&display=swap');
        body { font-family: 'Roboto', sans-serif; }
        .heading-oswald { font-family: 'Oswald', sans-serif; }
        
        .pulse-animation {
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.05); }
            100% { transform: scale(1); }
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
    </style>
</head>
<body class="bg-gradient-to-br from-[#002147] to-[#001a35] min-h-screen">
    <!-- Header -->
    <header class="bg-white/10 backdrop-blur-sm border-b border-white/20">
        <div class="container mx-auto px-4 py-4">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-[#ffae01] rounded-lg flex items-center justify-center">
                        <i class="fas fa-id-card text-[#002147]"></i>
                    </div>
                    <div>
                        <h1 class="text-xl font-bold text-white heading-oswald">Absensi RFID</h1>
                        <p class="text-sm text-gray-300">Sistem Informasi Sekolah</p>
                    </div>
                </div>
                <div class="flex items-center gap-4">
                    <span class="text-white text-sm">
                        <i class="fas fa-clock mr-2"></i>
                        <span id="currentTime"></span>
                    </span>
                    <button onclick="toggleRecentScans()" class="text-white hover:text-[#ffae01] transition-colors">
                        <i class="fas fa-history text-xl"></i>
                    </button>
                </div>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="container mx-auto px-4 py-8">
        <!-- Scan Section -->
        <section class="max-w-md mx-auto mb-8">
            <div class="bg-white/10 backdrop-blur-md rounded-2xl p-8 border border-white/20">
                <div class="text-center mb-6">
                    <div id="scanIcon" class="w-24 h-24 bg-[#ffae01] rounded-full flex items-center justify-center mx-auto mb-4 pulse-animation">
                        <i class="fas fa-wifi text-4xl text-[#002147]"></i>
                    </div>
                    <h2 class="text-2xl font-bold text-white mb-2">Silakan Tap Kartu RFID</h2>
                    <p class="text-gray-300">Dekatkan kartu RFID Anda ke reader</p>
                </div>
                
                <!-- Manual Input (for testing) -->
                <div class="mt-6">
                    <label class="block text-sm font-medium text-gray-300 mb-2">Input Manual (Testing)</label>
                    <div class="flex gap-2">
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
        <section id="statusSection" class="max-w-md mx-auto hidden">
            <div id="statusCard" class="bg-white/10 backdrop-blur-md rounded-2xl p-6 border border-white/20">
                <div class="flex items-center gap-4">
                    <div id="statusIcon" class="w-16 h-16 rounded-full flex items-center justify-center">
                        <i class="fas fa-check text-2xl text-white"></i>
                    </div>
                    <div class="flex-1">
                        <h3 id="statusTitle" class="text-lg font-bold text-white mb-1">Scan Berhasil</h3>
                        <p id="statusMessage" class="text-gray-300">Absensi berhasil dicatat</p>
                        <div id="studentInfo" class="mt-3 p-3 bg-white/10 rounded-lg hidden">
                            <p class="text-sm text-white"><strong>Nama:</strong> <span id="studentName"></span></p>
                            <p class="text-sm text-white"><strong>NIS:</strong> <span id="studentNIS"></span></p>
                            <p class="text-sm text-white"><strong>Kelas:</strong> <span id="studentClass"></span></p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

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
        // Global variables
        let isScanning = false;
        
        // Update current time
        function updateCurrentTime() {
            const now = new Date();
            document.getElementById('currentTime').textContent = now.toLocaleString('id-ID', {
                weekday: 'long',
                year: 'numeric',
                month: 'long',
                day: 'numeric',
                hour: '2-digit',
                minute: '2-digit',
                second: '2-digit'
            });
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
                const response = await fetch('rfid-attendance.php', {
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
                
                // Refresh recent scans
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
                const response = await fetch('rfid-attendance.php?recent&limit=20');
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
        
        // Show notification
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
        
        // Initialize
        document.addEventListener('DOMContentLoaded', () => {
            updateCurrentTime();
            setInterval(updateCurrentTime, 1000);
            
            // Focus on manual input
            document.getElementById('manualRFID').focus();
            
            // Add enter key support
            document.getElementById('manualRFID').addEventListener('keypress', (e) => {
                if (e.key === 'Enter') {
                    manualScan();
                }
            });
        });
    </script>
</body>
</html>
