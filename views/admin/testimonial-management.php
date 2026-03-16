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
            $name = $_POST['name'] ?? null;
            $role = $_POST['role'] ?? null;
            $content = $_POST['content'] ?? null;
            $rating = $_POST['rating'] ?? 5;
            $is_active = $_POST['is_active'] ?? 1;
            $image_url = $_POST['image_url'] ?? null;
            
            if ($name && $content) {
                $stmt = $pdo->prepare("
                    INSERT INTO testimonials (name, role, content, rating, is_active, image_url, created_by) 
                    VALUES (?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([$name, $role, $content, $rating, $is_active, $image_url, $_SESSION['user_id']]);
                $_SESSION['success'] = 'Testimonial berhasil ditambahkan';
            }
            break;
            
        case 'update':
            $id = $_POST['id'] ?? null;
            $name = $_POST['name'] ?? null;
            $role = $_POST['role'] ?? null;
            $content = $_POST['content'] ?? null;
            $rating = $_POST['rating'] ?? 5;
            $is_active = $_POST['is_active'] ?? 1;
            $image_url = $_POST['image_url'] ?? null;
            
            if ($id && $name && $content) {
                $stmt = $pdo->prepare("
                    UPDATE testimonials 
                    SET name = ?, role = ?, content = ?, rating = ?, is_active = ?, image_url = ? 
                    WHERE id = ?
                ");
                $stmt->execute([$name, $role, $content, $rating, $is_active, $image_url, $id]);
                $_SESSION['success'] = 'Testimonial berhasil diperbarui';
            }
            break;
            
        case 'delete':
            $id = $_POST['id'] ?? null;
            if ($id) {
                $stmt = $pdo->prepare("DELETE FROM testimonials WHERE id = ?");
                $stmt->execute([$id]);
                $_SESSION['success'] = 'Testimonial berhasil dihapus';
            }
            break;
            
        case 'toggle_status':
            $id = $_POST['id'] ?? null;
            if ($id) {
                $stmt = $pdo->prepare("UPDATE testimonials SET is_active = NOT is_active WHERE id = ?");
                $stmt->execute([$id]);
                $_SESSION['success'] = 'Status testimonial berhasil diubah';
            }
            break;
    }
    
    header("Location: index.php?page=admin-testimonial-management");
    exit;
}

// Get all testimonials
$stmt = $pdo->prepare("
    SELECT t.*, u.username as created_by_name 
    FROM testimonials t 
    LEFT JOIN users u ON t.created_by = u.id 
    ORDER BY t.created_at DESC
");
$stmt->execute();
$testimonials = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get statistics
$stmt = $pdo->prepare("SELECT COUNT(*) FROM testimonials WHERE is_active = 1");
$totalActive = $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COUNT(*) FROM testimonials WHERE is_active = 0");
$totalInactive = $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT AVG(rating) as avg_rating FROM testimonials WHERE is_active = 1");
$avgRating = $stmt->fetchColumn();
?>

<div class="mb-8">
    <h1 class="text-2xl font-bold text-gray-800">Manajemen Testimonial</h1>
    <p class="text-gray-500">Kelola testimonial siswa dan alumni</p>
</div>

<!-- Statistics Cards -->
<div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
    <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100 flex items-center">
        <div class="p-3 rounded-full bg-green-100 text-green-600 mr-4">
            <i class="fas fa-comments fa-2x"></i>
        </div>
        <div>
            <p class="text-sm text-gray-500 font-medium uppercase">Aktif</p>
            <h3 class="text-2xl font-bold text-gray-800"><?= $totalActive ?></h3>
        </div>
    </div>
    <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100 flex items-center">
        <div class="p-3 rounded-full bg-gray-100 text-gray-600 mr-4">
            <i class="fas fa-eye-slash fa-2x"></i>
        </div>
        <div>
            <p class="text-sm text-gray-500 font-medium uppercase">Tidak Aktif</p>
            <h3 class="text-2xl font-bold text-gray-800"><?= $totalInactive ?></h3>
        </div>
    </div>
    <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100 flex items-center">
        <div class="p-3 rounded-full bg-yellow-100 text-yellow-600 mr-4">
            <i class="fas fa-star fa-2x"></i>
        </div>
        <div>
            <p class="text-sm text-gray-500 font-medium uppercase">Rating Rata-rata</p>
            <h3 class="text-2xl font-bold text-gray-800"><?= number_format($avgRating, 1) ?></h3>
        </div>
    </div>
    <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100 flex items-center">
        <div class="p-3 rounded-full bg-blue-100 text-blue-600 mr-4">
            <i class="fas fa-chart-line fa-2x"></i>
        </div>
        <div>
            <p class="text-sm text-gray-500 font-medium uppercase">Total</p>
            <h3 class="text-2xl font-bold text-gray-800"><?= $totalActive + $totalInactive ?></h3>
        </div>
    </div>
</div>

<!-- Add New Testimonial -->
<div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 mb-8">
    <div class="flex justify-between items-center mb-4">
        <h3 class="text-lg font-bold text-gray-800">Tambah Testimonial Baru</h3>
        <button onclick="toggleAddForm()" class="bg-[#002147] hover:bg-[#001a35] text-white px-4 py-2 rounded-lg text-sm font-medium transition-colors">
            <i class="fas fa-plus mr-2"></i>Tambah Testimonial
        </button>
    </div>
    
    <form id="addForm" class="hidden">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Nama Lengkap</label>
                <input type="text" name="name" required 
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#002147] focus:border-transparent outline-none"
                       placeholder="Masukkan nama lengkap">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Peran/Jabatan</label>
                <input type="text" name="role" 
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#002147] focus:border-transparent outline-none"
                       placeholder="Contoh: Alumni 2023, Orang Tua Siswa">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Rating</label>
                <select name="rating" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#002147] focus:border-transparent outline-none">
                    <option value="5">⭐⭐⭐⭐⭐ (5 Sangat Baik)</option>
                    <option value="4">⭐⭐⭐⭐ (4 Baik)</option>
                    <option value="3">⭐⭐⭐ (3 Cukup)</option>
                    <option value="2">⭐⭐ (2 Kurang)</option>
                    <option value="1">⭐ (1 Sangat Kurang)</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">URL Foto (Opsional)</label>
                <input type="url" name="image_url" 
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#002147] focus:border-transparent outline-none"
                       placeholder="https://example.com/photo.jpg">
            </div>
        </div>
        <div class="mt-4">
            <label class="block text-sm font-medium text-gray-700 mb-1">Isi Testimonial</label>
            <textarea name="content" rows="4" required
                      class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#002147] focus:border-transparent outline-none"
                      placeholder="Tuliskan testimonial lengkap..."></textarea>
        </div>
        <div class="mt-4">
            <label class="flex items-center">
                <input type="checkbox" name="is_active" value="1" checked class="mr-2">
                <span class="text-sm text-gray-700">Tampilkan di website</span>
            </label>
        </div>
        <div class="mt-6 flex gap-2">
            <button type="submit" formaction="index.php?page=admin-testimonial-management" formmethod="POST"
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

<!-- Testimonials List -->
<div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
    <div class="flex justify-between items-center mb-4">
        <h3 class="text-lg font-bold text-gray-800">Daftar Testimonial</h3>
        <div class="flex items-center gap-2">
            <input type="text" id="searchInput" placeholder="Cari testimonial..." 
                   class="px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-[#002147] focus:border-transparent outline-none">
            <button onclick="exportData()" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg text-sm font-medium transition-colors">
                <i class="fas fa-download mr-2"></i>Export
            </button>
        </div>
    </div>
    
    <div class="overflow-x-auto">
        <table class="w-full text-left" id="testimonialTable">
            <thead>
                <tr class="text-gray-400 text-sm uppercase tracking-wider border-b">
                    <th class="pb-3 font-medium">Nama</th>
                    <th class="pb-3 font-medium">Peran</th>
                    <th class="pb-3 font-medium">Rating</th>
                    <th class="pb-3 font-medium">Isi Testimonial</th>
                    <th class="pb-3 font-medium">Status</th>
                    <th class="pb-3 font-medium">Tanggal</th>
                    <th class="pb-3 font-medium">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                <?php if ($testimonials): ?>
                    <?php foreach ($testimonials as $testimonial): ?>
                        <tr class="hover:bg-gray-50">
                            <td class="py-4">
                                <div class="flex items-center gap-3">
                                    <?php if ($testimonial['image_url']): ?>
                                        <img src="<?= htmlspecialchars($testimonial['image_url']) ?>" alt="<?= htmlspecialchars($testimonial['name']) ?>" 
                                             class="w-10 h-10 rounded-full object-cover">
                                    <?php else: ?>
                                        <div class="w-10 h-10 bg-gray-200 rounded-full flex items-center justify-center">
                                            <i class="fas fa-user text-gray-500"></i>
                                        </div>
                                    <?php endif; ?>
                                    <div>
                                        <div class="font-medium text-gray-900"><?= htmlspecialchars($testimonial['name']) ?></div>
                                        <div class="text-xs text-gray-500">oleh <?= htmlspecialchars($testimonial['created_by_name']) ?></div>
                                    </div>
                                </div>
                            </td>
                            <td class="py-4 text-sm text-gray-600"><?= htmlspecialchars($testimonial['role'] ?? '-') ?></td>
                            <td class="py-4">
                                <div class="flex items-center gap-1">
                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                        <i class="fas fa-star text-sm <?= $i <= $testimonial['rating'] ? 'text-yellow-400' : 'text-gray-300' ?>"></i>
                                    <?php endfor; ?>
                                    <span class="ml-2 text-sm text-gray-600">(<?= $testimonial['rating'] ?>/5)</span>
                                </div>
                            </td>
                            <td class="py-4">
                                <p class="text-sm text-gray-800 max-w-xs truncate" title="<?= htmlspecialchars($testimonial['content']) ?>">
                                    <?= htmlspecialchars($testimonial['content']) ?>
                                </p>
                            </td>
                            <td class="py-4">
                                <span class="px-2 py-1 text-xs font-bold rounded-full <?= 
                                    $testimonial['is_active'] ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-700'
                                ?>">
                                    <?= $testimonial['is_active'] ? 'Aktif' : 'Tidak Aktif' ?>
                                </span>
                            </td>
                            <td class="py-4 text-sm text-gray-500">
                                <?= date('d M Y', strtotime($testimonial['created_at'])) ?>
                            </td>
                            <td class="py-4">
                                <div class="flex items-center gap-2">
                                    <button onclick="editTestimonial(<?= $testimonial['id'] ?>)" 
                                            class="text-blue-600 hover:text-blue-800 text-sm font-medium" title="Edit">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button onclick="toggleStatus(<?= $testimonial['id'] ?>)" 
                                            class="text-yellow-600 hover:text-yellow-800 text-sm font-medium" 
                                            title="<?= $testimonial['is_active'] ? 'Non-aktifkan' : 'Aktifkan' ?>">
                                        <i class="fas <?= $testimonial['is_active'] ? 'fa-eye-slash' : 'fa-eye' ?>"></i>
                                    </button>
                                    <button onclick="deleteTestimonial(<?= $testimonial['id'] ?>)" 
                                            class="text-red-600 hover:text-red-800 text-sm font-medium" title="Hapus">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="7" class="py-8 text-center text-gray-500">
                            <i class="fas fa-comments text-4xl mb-2"></i>
                            <p>Belum ada testimonial</p>
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

function editTestimonial(id) {
    // Implementation for edit modal
    alert('Edit functionality would open a modal with testimonial details');
}

function toggleStatus(id) {
    if (confirm('Apakah Anda yakin ingin mengubah status testimonial ini?')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = 'index.php?page=admin-testimonial-management';
        
        const actionInput = document.createElement('input');
        actionInput.type = 'hidden';
        actionInput.name = 'action';
        actionInput.value = 'toggle_status';
        
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

function deleteTestimonial(id) {
    if (confirm('Apakah Anda yakin ingin menghapus testimonial ini?')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = 'index.php?page=admin-testimonial-management';
        
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
    const table = document.getElementById('testimonialTable');
    const rows = table.querySelectorAll('tbody tr');
    
    let csv = 'Nama,Peran,Rating,Isi Testimonial,Status,Tanggal\n';
    
    rows.forEach(row => {
        const cells = row.querySelectorAll('td');
        if (cells.length >= 7) {
            const name = cells[0].textContent.trim();
            const role = cells[1].textContent.trim();
            const rating = cells[2].textContent.trim();
            const content = cells[3].textContent.trim();
            const status = cells[4].textContent.trim();
            const date = cells[5].textContent.trim();
            
            csv += `"${name}","${role}","${rating}","${content}","${status}","${date}"\n`;
        }
    });
    
    const blob = new Blob([csv], { type: 'text/csv' });
    const url = window.URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = 'testimonials_' + new Date().toISOString().split('T')[0] + '.csv';
    a.click();
    window.URL.revokeObjectURL(url);
}

// Search functionality
document.getElementById('searchInput').addEventListener('input', function(e) {
    const searchTerm = e.target.value.toLowerCase();
    const rows = document.querySelectorAll('#testimonialTable tbody tr');
    
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
