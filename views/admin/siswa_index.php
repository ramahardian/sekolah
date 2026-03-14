<?php
// Cek jika file ini dipanggil tanpa index.php
if (!isset($pdo)) {
    header("Location: ../../index.php");
    exit;
}

// Logika Pencarian
$search = isset($_GET['search']) ? input($_GET['search']) : '';
$query_sql = "SELECT s.*, k.nama_kelas 
              FROM siswa s 
              LEFT JOIN kelas k ON s.kelas_id = k.id";

$perPage = 5;
$currentPage = isset($_GET['p']) ? max(1, (int) $_GET['p']) : 1;
$offset = ($currentPage - 1) * $perPage;

$count_sql = "SELECT COUNT(*) 
              FROM siswa s 
              LEFT JOIN kelas k ON s.kelas_id = k.id";

if ($search) {
    $query_sql .= " WHERE s.nama_siswa LIKE :search OR s.nis LIKE :search";
    $count_sql .= " WHERE s.nama_siswa LIKE :search OR s.nis LIKE :search";
}
$query_sql .= " ORDER BY s.id DESC LIMIT :limit OFFSET :offset";

// Total data
$count_stmt = $pdo->prepare($count_sql);
if ($search) {
    $count_stmt->execute(['search' => "%$search%"]);
} else {
    $count_stmt->execute();
}
$totalRows = (int) $count_stmt->fetchColumn();
$totalPages = max(1, (int) ceil($totalRows / $perPage));

$stmt = $pdo->prepare($query_sql);
if ($search) {
    $stmt->bindValue(':search', "%$search%", PDO::PARAM_STR);
}
$stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$siswa = $stmt->fetchAll();
?>

<div class="flex flex-col md:flex-row md:items-center justify-between mb-6 gap-4">
    <div>
        <h1 class="text-2xl font-bold text-gray-800">Data Siswa</h1>
        <p class="text-gray-500 text-sm">Kelola informasi biodata siswa dan orang tua.</p>
    </div>
    <a href="index.php?page=siswa-tambah"
        class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-lg flex items-center shadow-md transition">
        <i class="fas fa-plus mr-2 text-sm"></i> Tambah Siswa
    </a>
</div>

<?php display_flash_message(); ?>

<div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
    <div class="p-4 border-b border-gray-100 flex flex-col md:flex-row justify-between gap-4 bg-gray-50/50">
        <form action="index.php" method="GET" class="relative w-full md:w-80">
            <input type="hidden" name="page" value="siswa">
            <input type="text" name="search" value="<?= $search ?>" placeholder="Cari nama atau NIS..."
                class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 outline-none transition text-sm">
            <span class="absolute left-3 top-2.5 text-gray-400">
                <i class="fas fa-search"></i>
            </span>
        </form>
        <div class="flex items-center text-sm text-gray-500">
            Menampilkan <span class="font-bold text-gray-800 mx-1">
                <?= count($siswa) ?>
            </span> dari <span class="font-bold text-gray-800 mx-1">
                <?= $totalRows ?>
            </span>
        </div>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="bg-gray-50 text-gray-600 text-xs uppercase tracking-widest border-b">
                    <th class="px-6 py-4 font-semibold">Siswa</th>
                    <th class="px-6 py-4 font-semibold">Kelas</th>
                    <th class="px-6 py-4 font-semibold">Kontak Orang Tua</th>
                    <th class="px-6 py-4 font-semibold">Status</th>
                    <th class="px-6 py-4 font-semibold text-center">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                <?php if (count($siswa) > 0): ?>
                    <?php foreach ($siswa as $s): ?>
                        <tr class="hover:bg-indigo-50/30 transition">
                            <td class="px-6 py-4">
                                <div class="flex items-center">
                                    <div
                                        class="h-10 w-10 rounded-full bg-indigo-100 text-indigo-600 flex items-center justify-center font-bold mr-3">
                                        <?= get_initials($s['nama_siswa']) ?>
                                    </div>
                                    <div>
                                        <div class="text-sm font-bold text-gray-800">
                                            <?= $s['nama_siswa'] ?>
                                        </div>
                                        <div class="text-xs text-gray-500">NIS:
                                            <?= $s['nis'] ?>
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-600">
                                <?= $s['nama_kelas'] ?? '<span class="text-red-400 italic text-xs">Belum diplot</span>' ?>
                            </td>
                            <td class="px-6 py-4">
                                <div class="text-sm text-gray-700 font-medium">
                                    <?= $s['nama_ayah'] ?>
                                </div>
                                <div class="text-xs text-gray-500"><i class="fas fa-phone-alt mr-1"></i>
                                    <?= $s['no_hp_orangtua'] ?>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <span
                                    class="px-2.5 py-0.5 rounded-full text-xs font-medium 
                                    <?= $s['status_siswa'] == 'aktif' ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-700' ?>">
                                    <?= ucfirst($s['status_siswa']) ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 text-center">
                                <div class="flex justify-center gap-2">
                                    <a href="index.php?page=profil-siswa&id=<?= $s['id'] ?>"
                                        class="p-2 text-indigo-600 hover:bg-indigo-50 rounded-lg transition" title="Profil">
                                        <i class="fas fa-id-card"></i>
                                    </a>
                                    <a href="index.php?page=nilai-siswa&id=<?= $s['id'] ?>"
                                        class="p-2 text-emerald-600 hover:bg-emerald-50 rounded-lg transition" title="Nilai">
                                        <i class="fas fa-chart-line"></i>
                                    </a>
                                    <a href="index.php?page=siswa-edit&id=<?= $s['id'] ?>"
                                        class="p-2 text-blue-600 hover:bg-blue-50 rounded-lg transition" title="Edit">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <button onclick="confirmDelete('views/admin/siswa_hapus.php?id=<?= $s['id'] ?>')"
                                        class="p-2 text-red-600 hover:bg-red-50 rounded-lg transition" title="Hapus">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="5" class="px-6 py-10 text-center text-gray-500">
                            <i class="fas fa-folder-open text-4xl mb-3 block opacity-20"></i>
                            Data tidak ditemukan.
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php if ($totalPages > 1): ?>
    <div class="mt-6 flex flex-col md:flex-row items-center justify-between gap-3 text-sm">
        <div class="text-gray-500">
            Halaman <span class="font-semibold text-gray-800"><?= $currentPage ?></span> dari
            <span class="font-semibold text-gray-800"><?= $totalPages ?></span>
        </div>
        <div class="flex items-center gap-2">
            <?php
            $baseParams = ['page' => 'siswa'];
            if ($search) {
                $baseParams['search'] = $search;
            }

            $prevPage = max(1, $currentPage - 1);
            $nextPage = min($totalPages, $currentPage + 1);

            $prevHref = 'index.php?' . http_build_query($baseParams + ['p' => $prevPage]);
            $nextHref = 'index.php?' . http_build_query($baseParams + ['p' => $nextPage]);
            ?>

            <a href="<?= $prevHref ?>"
                class="px-3 py-2 rounded-lg border text-gray-600 hover:bg-gray-50 transition <?= $currentPage == 1 ? 'pointer-events-none opacity-50' : '' ?>">
                Prev
            </a>
            <a href="<?= $nextHref ?>"
                class="px-3 py-2 rounded-lg border text-gray-600 hover:bg-gray-50 transition <?= $currentPage == $totalPages ? 'pointer-events-none opacity-50' : '' ?>">
                Next
            </a>
        </div>
    </div>
<?php endif; ?>
