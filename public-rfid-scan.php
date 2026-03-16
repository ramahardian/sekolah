<?php
require_once __DIR__ . '/config/database.php';

// Enable CORS for public access
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

class PublicRFIDScanner {
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    public function scanRFID($rfidCode) {
        try {
            // Get client info
            $ipAddress = $_SERVER['REMOTE_ADDR'] ?? null;
            $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? null;
            $deviceLocation = $_GET['location'] ?? 'Gerbang Utama';
            
            // Validate RFID code
            if (empty($rfidCode) || strlen($rfidCode) < 4) {
                return $this->logScan($rfidCode, null, 'check_in', 'invalid_card', $ipAddress, $userAgent, $deviceLocation);
            }
            
            // Find student by RFID
            $stmt = $this->pdo->prepare("
                SELECT sr.*, s.nama_siswa, s.nis, s.kelas_id, c.class_name 
                FROM student_rfid sr 
                JOIN siswa s ON sr.siswa_id = s.id 
                LEFT JOIN classes c ON s.kelas_id = c.id 
                WHERE sr.rfid_code = ? AND sr.card_status = 'active'
            ");
            $stmt->execute([$rfidCode]);
            $student = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$student) {
                return $this->logScan($rfidCode, null, 'check_in', 'student_not_found', $ipAddress, $userAgent, $deviceLocation);
            }
            
            // Check for duplicate scan (within 5 minutes)
            $stmt = $this->pdo->prepare("
                SELECT id, scan_type FROM rfid_logs 
                WHERE siswa_id = ? AND scan_time > DATE_SUB(NOW(), INTERVAL 5 MINUTE)
                ORDER BY scan_time DESC LIMIT 1
            ");
            $stmt->execute([$student['siswa_id']]);
            $lastScan = $stmt->fetch(PDO::FETCH_ASSOC);
            
            $scanType = 'check_in';
            if ($lastScan) {
                // If last scan was within 5 minutes, it's a duplicate
                if (strtotime($lastScan['scan_time']) > strtotime('-5 minutes')) {
                    return $this->logScan($rfidCode, $student['siswa_id'], $lastScan['scan_type'], 'duplicate_scan', $ipAddress, $userAgent, $deviceLocation);
                }
                // Alternate between check-in and check-out
                $scanType = $lastScan['scan_type'] === 'check_in' ? 'check_out' : 'check_in';
            }
            
            // Log successful scan
            $result = $this->logScan($rfidCode, $student['siswa_id'], $scanType, 'success', $ipAddress, $userAgent, $deviceLocation);
            
            // Update attendance if successful
            if ($result['status'] === 'success') {
                $this->updateAttendance($student['siswa_id'], $scanType);
            }
            
            return array_merge($result, [
                'student' => [
                    'id' => $student['siswa_id'],
                    'nama' => $student['nama_siswa'],
                    'nis' => $student['nis'],
                    'kelas' => $student['class_name']
                ]
            ]);
            
        } catch (Exception $e) {
            return [
                'status' => 'error',
                'message' => 'Database error: ' . $e->getMessage()
            ];
        }
    }
    
    private function logScan($rfidCode, $studentId, $scanType, $status, $ipAddress, $userAgent, $deviceLocation) {
        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO rfid_logs (rfid_code, siswa_id, scan_type, scan_time, device_location, status, ip_address, user_agent) 
                VALUES (?, ?, ?, NOW(), ?, ?, ?, ?)
            ");
            $stmt->execute([$rfidCode, $studentId, $scanType, $deviceLocation, $status, $ipAddress, $userAgent]);
            
            $message = '';
            switch ($status) {
                case 'success':
                    $message = 'Scan berhasil';
                    break;
                case 'invalid_card':
                    $message = 'Kartu tidak valid';
                    break;
                case 'student_not_found':
                    $message = 'Siswa tidak ditemukan';
                    break;
                case 'duplicate_scan':
                    $message = 'Scan ganda (dalam 5 menit)';
                    break;
            }
            
            return [
                'status' => $status,
                'message' => $message,
                'scan_type' => $scanType,
                'timestamp' => date('Y-m-d H:i:s')
            ];
            
        } catch (Exception $e) {
            return [
                'status' => 'error',
                'message' => 'Gagal menyimpan log: ' . $e->getMessage()
            ];
        }
    }
    
    private function updateAttendance($studentId, $scanType) {
        try {
            $today = date('Y-m-d');
            
            // Check if attendance record exists for today
            $stmt = $this->pdo->prepare("
                SELECT id, status FROM absensi 
                WHERE siswa_id = ? AND tanggal = ?
            ");
            $stmt->execute([$studentId, $today]);
            $attendance = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($attendance) {
                // Update existing record
                if ($scanType === 'check_in' && $attendance['status'] === 'alpha') {
                    $stmt = $this->pdo->prepare("
                        UPDATE absensi SET status = 'hadir', updated_at = NOW() 
                        WHERE id = ?
                    ");
                    $stmt->execute([$attendance['id']]);
                }
            } else {
                // Create new record
                $status = $scanType === 'check_in' ? 'hadir' : 'alpha';
                $stmt = $this->pdo->prepare("
                    INSERT INTO absensi (siswa_id, tanggal, status, created_at) 
                    VALUES (?, ?, ?, NOW())
                ");
                $stmt->execute([$studentId, $today, $status]);
            }
            
        } catch (Exception $e) {
            // Log error but don't fail the scan
            error_log("Attendance update error: " . $e->getMessage());
        }
    }
    
    public function getRecentScans($limit = 10) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT rl.*, s.nama_siswa, s.nis, c.class_name 
                FROM rfid_logs rl 
                LEFT JOIN siswa s ON rl.siswa_id = s.id 
                LEFT JOIN classes c ON s.kelas_id = c.id 
                ORDER BY rl.scan_time DESC 
                LIMIT ?
            ");
            $stmt->execute([$limit]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (Exception $e) {
            return [];
        }
    }
    
    public function getTodayStats() {
        try {
            $stmt = $this->pdo->prepare("
                SELECT 
                    COUNT(CASE WHEN status = 'success' THEN 1 END) as total_scans,
                    COUNT(CASE WHEN scan_type = 'check_in' THEN 1 END) as check_ins,
                    COUNT(CASE WHEN scan_type = 'check_out' THEN 1 END) as check_outs,
                    COUNT(DISTINCT siswa_id) as unique_students
                FROM rfid_logs 
                WHERE DATE(scan_time) = CURDATE()
            ");
            $stmt->execute();
            return $stmt->fetch(PDO::FETCH_ASSOC);
            
        } catch (Exception $e) {
            return [
                'total_scans' => 0,
                'check_ins' => 0,
                'check_outs' => 0,
                'unique_students' => 0
            ];
        }
    }
}

// Main execution
$rfid = new PublicRFIDScanner($pdo);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Handle RFID scan
    $input = json_decode(file_get_contents('php://input'), true);
    $rfidCode = $input['rfid_code'] ?? $_POST['rfid_code'] ?? null;
    
    if ($rfidCode) {
        $result = $rfid->scanRFID($rfidCode);
        echo json_encode($result);
    } else {
        http_response_code(400);
        echo json_encode([
            'status' => 'error',
            'message' => 'RFID code required'
        ]);
    }
    
} elseif ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Handle requests for recent scans and stats
    if (isset($_GET['recent'])) {
        $limit = min(intval($_GET['limit'] ?? 10), 50);
        $scans = $rfid->getRecentScans($limit);
        echo json_encode([
            'status' => 'success',
            'data' => $scans
        ]);
    } elseif (isset($_GET['stats'])) {
        $stats = $rfid->getTodayStats();
        echo json_encode([
            'status' => 'success',
            'data' => $stats
        ]);
    } else {
        // Show public scan interface
        include 'views/public-rfid-scan-view.php';
    }
    
} else {
    http_response_code(405);
    echo json_encode([
        'status' => 'error',
        'message' => 'Method not allowed'
    ]);
}
?>
