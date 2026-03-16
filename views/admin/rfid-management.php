<?php
require_once __DIR__ . '/../../includes/auth_check.php';
require_once __DIR__ . '/../../config/database.php';

// Only admin can access
if ($_SESSION['role'] !== 'admin') {
    header("Location: index.php?page=dashboard");
    exit;
}

// Handle CRUD operations
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'add':
            $siswaId = $_POST['siswa_id'] ?? null;
            $rfidCode = $_POST['rfid_code'] ?? null;
            $issuedDate = $_POST['issued_date'] ?? null;
            $expiredDate = $_POST['expired_date'] ?? null;
            $notes = $_POST['notes'] ?? null;
            
            if ($siswaId && $rfidCode) {
                $stmt = $pdo->prepare("
                    INSERT INTO student_rfid (siswa_id, rfid_code, issued_date, expired_date, notes, created_by) 
                    VALUES (?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([$siswaId, $rfidCode, $issuedDate, $expiredDate, $notes, $_SESSION['user_id']]);
                $_SESSION['success'] = 'Kartu RFID berhasil ditambahkan';
            }
            break;
            
        case 'update':
            $id = $_POST['id'] ?? null;
            $rfidCode = $_POST['rfid_code'] ?? null;
            $cardStatus = $_POST['card_status'] ?? null;
            $issuedDate = $_POST['issued_date'] ?? null;
            $expiredDate = $_POST['expired_date'] ?? null;
            $notes = $_POST['notes'] ?? null;
            
            if ($id && $rfidCode) {
                $stmt = $pdo->prepare("
                    UPDATE student_rfid 
                    SET rfid_code = ?, card_status = ?, issued_date = ?, expired_date = ?, notes = ? 
                    WHERE id = ?
                ");
                $stmt->execute([$rfidCode, $cardStatus, $issuedDate, $expiredDate, $notes, $id]);
                $_SESSION['success'] = 'Kartu RFID berhasil diperbarui';
            }
            break;
            
        case 'delete':
            $id = $_POST['id'] ?? null;
            if ($id) {
                $stmt = $pdo->prepare("DELETE FROM student_rfid WHERE id = ?");
                $stmt->execute([$id]);
                $_SESSION['success'] = 'Kartu RFID berhasil dihapus';
            }
            break;
    }
    
    header("Location: index.php?page=admin-rfid-management");
    exit;
}

// Get all RFID cards with student info
$stmt = $pdo->prepare("
    SELECT sr.*, s.nama_siswa, s.nis, c.class_name 
    FROM student_rfid sr 
    JOIN siswa s ON sr.siswa_id = s.id 
    LEFT JOIN classes c ON s.kelas_id = c.id 
    ORDER BY sr.created_at DESC
");
$stmt->execute();
$rfidCards = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get students without RFID cards
$stmt = $pdo->prepare("
    SELECT s.id, s.nama_siswa, s.nis, c.class_name 
    FROM siswa s 
    LEFT JOIN classes c ON s.kelas_id = c.id 
    LEFT JOIN student_rfid sr ON s.id = sr.siswa_id 
    WHERE sr.id IS NULL 
    ORDER BY s.nama_siswa
");
$stmt->execute();
$availableStudents = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get statistics
$stmt = $pdo->prepare("SELECT COUNT(*) FROM student_rfid WHERE card_status = 'active'");
$totalActive = $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COUNT(*) FROM student_rfid WHERE card_status = 'inactive'");
$totalInactive = $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COUNT(*) FROM rfid_logs WHERE DATE(scan_time) = CURDATE()");
$todayScans = $stmt->fetchColumn();
?>

<div class="mb-8">
    <h1 class="text-2xl font-bold text-gray-800">Manajemen Kartu RFID</h1>
    <p class="text-gray-500">Kelola kartu RFID untuk absensi siswa</p>
</div>

<!-- Statistics Cards -->
<div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
    <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100 flex items-center">
        <div class="p-3 rounded-full bg-blue-100 text-blue-600 mr-4">
            <i class="fas fa-id-card fa-2x"></i>
        </div>
        <div>
            <p class="text-sm text-gray-500 font-medium uppercase">Total Aktif</p>
            <h3 class="text-2xl font-bold text-gray-800"><?= $totalActive ?></h3>
        </div>
    </div>
    <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100 flex items-center">
        <div class="p-3 rounded-full bg-gray-100 text-gray-600 mr-4">
            <i class="fas fa-ban fa-2x"></i>
        </div>
        <div>
            <p class="text-sm text-gray-500 font-medium uppercase">Tidak Aktif</p>
            <h3 class="text-2xl font-bold text-gray-800"><?= $totalInactive ?></h3>
        </div>
    </div>
    <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100 flex items-center">
        <div class="p-3 rounded-full bg-green-100 text-green-600 mr-4">
            <i class="fas fa-wifi fa-2x"></i>
        </div>
        <div>
            <p class="text-sm text-gray-500 font-medium uppercase">Scan Hari Ini</p>
            <h3 class="text-2xl font-bold text-gray-800"><?= $todayScans ?></h3>
        </div>
    </div>
    <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100 flex items-center">
        <div class="p-3 rounded-full bg-purple-100 text-purple-600 mr-4">
            <i class="fas fa-users fa-2x"></i>
        </div>
        <div>
            <p class="text-sm text-gray-500 font-medium uppercase">Tanpa Kartu</p>
            <h3 class="text-2xl font-bold text-gray-800"><?= count($availableStudents) ?></h3>
        </div>
    </div>
</div>

<!-- Add New RFID Card -->
<div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 mb-8">
    <div class="flex justify-between items-center mb-4">
        <h3 class="text-lg font-bold text-gray-800">Tambah Kartu RFID Baru</h3>
        <button onclick="toggleAddForm()" class="bg-[#002147] hover:bg-[#001a35] text-white px-4 py-2 rounded-lg text-sm font-medium transition-colors">
            <i class="fas fa-plus mr-2"></i>Tambah Kartu
        </button>
    </div>
    
    <form id="addForm" class="hidden">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Siswa</label>
                <select name="siswa_id" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#002147] focus:border-transparent outline-none">
                    <option value="">Pilih Siswa</option>
                    <?php foreach ($availableStudents as $student): ?>
                        <option value="<?= $student['id'] ?>">
                            <?= htmlspecialchars($student['nama_siswa']) ?> (<?= htmlspecialchars($student['nis'] ?>)
                            <?php if ($student['class_name']): ?> - <?= htmlspecialchars($student['class_name']) ?><?php endif; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Kode RFID</label>
                <input type="text" name="rfid_code" required 
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#002147] focus:border-transparent outline-none"
                       placeholder="Masukkan kode RFID">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Tanggal Terbit</label>
                <input type="date" name="issued_date" 
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#002147] focus:border-transparent outline-none">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Tanggal Kadaluarsa</label>
                <input type="date" name="expired_date" 
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#002147] focus:border-transparent outline-none">
            </div>
        </div>
        <div class="mt-4">
            <label class="block text-sm font-medium text-gray-700 mb-1">Catatan</label>
            <textarea name="notes" rows="3" 
                      class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#002147] focus:border-transparent outline-none"
                      placeholder="Catatan tambahan (opsional)"></textarea>
        </div>
        <div class="mt-4 flex gap-2">
            <button type="submit" formaction="index.php?page=admin-rfid-management" formmethod="POST"
                    class="bg-[#002147] hover:bg-[#001a35] text-white px-6 py-2 rounded-lg font-medium transition-colors">
                <i class="fas fa-save mr-2"></i>Simpan
            </button>
            <button type="button" onclick="toggleAddForm()" 
                    class="bg-gray-500 hover:bg-gray-600 text-white px-6 py-2 rounded-lg font-medium transition-colors">
                <i class="fas fa-times mr-2"></i>Batal
            </button>
            <input type="hidden" name="action" value="add">
        </div>
    </form>
</div>

<!-- RFID Cards List -->
<div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
    <div class="flex justify-between items-center mb-4">
        <h3 class="text-lg font-bold text-gray-800">Daftar Kartu RFID</h3>
        <div class="flex items-center gap-2">
            <input type="text" id="searchInput" placeholder="Cari siswa atau RFID..." 
                   class="px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-[#002147] focus:border-transparent outline-none">
            <button onclick="exportData()" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg text-sm font-medium transition-colors">
                <i class="fas fa-download mr-2"></i>Export
            </button>
        </div>
    </div>
    
    <div class="overflow-x-auto">
        <table class="w-full text-left" id="rfidTable">
            <thead>
                <tr class="text-gray-400 text-sm uppercase tracking-wider border-b">
                    <th class="pb-3 font-medium">Nama Siswa</th>
                    <th class="pb-3 font-medium">NIS</th>
                    <th class="pb-3 font-medium">Kelas</th>
                    <th class="pb-3 font-medium">Kode RFID</th>
                    <th class="pb-3 font-medium">Status</th>
                    <th class="pb-3 font-medium">Terbit</th>
                    <th class="pb-3 font-medium">Kadaluarsa</th>
                    <th class="pb-3 font-medium">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                <?php if ($rfidCards): ?>
                    <?php foreach ($rfidCards as $card): ?>
                        <tr class="hover:bg-gray-50">
                            <td class="py-4">
                                <div class="font-medium text-gray-900"><?= htmlspecialchars($card['nama_siswa']) ?></div>
                            </td>
                            <td class="py-4 text-sm text-gray-500"><?= htmlspecialchars($card['nis']) ?></td>
                            <td class="py-4 text-sm text-gray-500"><?= htmlspecialchars($card['class_name'] ?? '-') ?></td>
                            <td class="py-4">
                                <code class="bg-gray-100 px-2 py-1 rounded text-sm"><?= htmlspecialchars($card['rfid_code']) ?></code>
                            </td>
                            <td class="py-4">
                                <span class="px-2 py-1 text-xs font-bold rounded <?= 
                                    $card['card_status'] === 'active' ? 'bg-green-100 text-green-700' :
                                    ($card['card_status'] === 'inactive' ? 'bg-gray-100 text-gray-700' :
                                    ($card['card_status'] === 'lost' ? 'bg-red-100 text-red-700' :
                                    'bg-orange-100 text-orange-700'))
                                ?>">
                                    <?= ucfirst($card['card_status']) ?>
                                </span>
                            </td>
                            <td class="py-4 text-sm text-gray-500"><?= $card['issued_date'] ?? '-' ?></td>
                            <td class="py-4 text-sm text-gray-500"><?= $card['expired_date'] ?? '-' ?></td>
                            <td class="py-4">
                                <div class="flex items-center gap-2">
                                    <button onclick="editCard(<?= $card['id'] ?>)" 
                                            class="text-blue-600 hover:text-blue-800 text-sm font-medium">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button onclick="deleteCard(<?= $card['id'] ?>)" 
                                            class="text-red-600 hover:text-red-800 text-sm font-medium">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="8" class="py-8 text-center text-gray-500">
                            <i class="fas fa-inbox text-4xl mb-2"></i>
                            <p>Belum ada kartu RFID yang terdaftar</p>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Success/Error Messages -->
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

<script>
function toggleAddForm() {
    const form = document.getElementById('addForm');
    form.classList.toggle('hidden');
}

function editCard(id) {
    // Implementation for edit modal
    alert('Edit functionality would open a modal with card details');
}

function deleteCard(id) {
    if (confirm('Apakah Anda yakin ingin menghapus kartu RFID ini?')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = 'index.php?page=admin-rfid-management';
        
        const actionInput = document.createElement('input');
        actionInput.type = 'hidden';
        actionInput.name = 'action';
        actionInput.value = 'delete';
        
        const idInput = document.createElement('input');
        idInput.type = 'hidden';
        idInput.name = 'id';
        idInput.value = id;
        
        form.appendChild(actionInput);
        form.appendChild(idInput);
        document.body.appendChild(form);
        form.submit();
    }
}

function exportData() {
    const table = document.getElementById('rfidTable');
    const rows = table.querySelectorAll('tbody tr');
    
    let csv = 'Nama Siswa,NIS,Kelas,Kode RFID,Status,Terbit,Kadaluarsa\n';
    
    rows.forEach(row => {
        const cells = row.querySelectorAll('td');
        if (cells.length >= 7) {
            const name = cells[0].textContent.trim();
            const nis = cells[1].textContent.trim();
            const kelas = cells[2].textContent.trim();
            const rfid = cells[3].textContent.trim();
            const status = cells[4].textContent.trim();
            const issued = cells[5].textContent.trim();
            const expired = cells[6].textContent.trim();
            
            csv += `"${name}","${nis}","${kelas}","${rfid}","${status}","${issued}","${expired}"\n`;
        }
    });
    
    const blob = new Blob([csv], { type: 'text/csv' });
    const url = window.URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = 'rfid_cards_' + new Date().toISOString().split('T')[0] + '.csv';
    a.click();
    window.URL.revokeObjectURL(url);
}

// Search functionality
document.getElementById('searchInput').addEventListener('input', function(e) {
    const searchTerm = e.target.value.toLowerCase();
    const rows = document.querySelectorAll('#rfidTable tbody tr');
    
    rows.forEach(row => {
        const text = row.textContent.toLowerCase();
        row.style.display = text.includes(searchTerm) ? '' : 'none';
    });
});

// Auto-hide success messages
setTimeout(() => {
    const successDiv = document.querySelector('.fixed.top-4.right-4');
    if (successDiv) {
        successDiv.style.display = 'none';
    }
}, 5000);
</script>
