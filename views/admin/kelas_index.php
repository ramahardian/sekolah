<?php
if (!isset($pdo)) {
    exit;
}

// Query lengkap: Ambil Kelas + Nama Guru (Wali) + Hitung Jumlah Siswa
$search = isset($_GET['search']) ? input($_GET['search']) : '';
$tingkat = isset($_GET['tingkat']) ? input($_GET['tingkat']) : '';
$wali = isset($_GET['wali']) ? input($_GET['wali']) : '';

$perPage = 5;
$currentPage = isset($_GET['p']) ? max(1, (int) $_GET['p']) : 1;
$offset = ($currentPage - 1) * $perPage;

$query_sql = "SELECT 
                k.id, 
                k.nama_kelas, 
                k.tingkat,
                k.wali_id,
                g.nama_guru, 
                (SELECT COUNT(*) FROM siswa WHERE kelas_id = k.id) as total_siswa
              FROM kelas k
              LEFT JOIN guru g ON k.wali_id = g.id";

$count_sql = "SELECT COUNT(*)
              FROM kelas k
              LEFT JOIN guru g ON k.wali_id = g.id";

$conditions = [];
$params = [];

if ($search) {
    $conditions[] = "k.nama_kelas LIKE :search";
    $params['search'] = "%$search%";
}
if (in_array($tingkat, ['10', '11', '12'], true)) {
    $conditions[] = "k.tingkat = :tingkat";
    $params['tingkat'] = $tingkat;
}
if ($wali === 'assigned') {
    $conditions[] = "k.wali_id IS NOT NULL";
} elseif ($wali === 'unassigned') {
    $conditions[] = "k.wali_id IS NULL";
}

if ($conditions) {
    $where = " WHERE " . implode(" AND ", $conditions);
    $query_sql .= $where;
    $count_sql .= $where;
}

$query_sql .= " ORDER BY k.nama_kelas ASC LIMIT :limit OFFSET :offset";

$count_stmt = $pdo->prepare($count_sql);
foreach ($params as $key => $value) {
    $count_stmt->bindValue(':' . $key, $value, PDO::PARAM_STR);
}
$count_stmt->execute();
$totalRows = (int) $count_stmt->fetchColumn();
$totalPages = max(1, (int) ceil($totalRows / $perPage));

$stmt = $pdo->prepare($query_sql);
foreach ($params as $key => $value) {
    $stmt->bindValue(':' . $key, $value, PDO::PARAM_STR);
}
$stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$kelas = $stmt->fetchAll();
?>

<div class="flex flex-col md:flex-row md:items-center justify-between mb-6 gap-4">
    <div>
        <h1 class="text-2xl font-bold text-gray-800">Manajemen Kelas</h1>
        <p class="text-gray-500 text-sm">Kelola daftar kelas dan penugasan wali kelas.</p>
    </div>
    <a href="index.php?page=kelas-tambah"
        class="bg-purple-600 hover:bg-purple-700 text-white px-4 py-2 rounded-lg flex items-center shadow-md transition">
        <i class="fas fa-plus mr-2 text-sm"></i> Tambah Kelas
    </a>
</div>

<?php display_flash_message(); ?>

<div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4 mb-6">
    <form action="index.php" method="GET" class="flex flex-col md:flex-row gap-3">
        <input type="hidden" name="page" value="kelas">
        <div class="relative w-full md:w-72">
            <input type="text" name="search" value="<?= $search ?>" placeholder="Cari nama kelas..."
                class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 outline-none transition text-sm">
            <span class="absolute left-3 top-2.5 text-gray-400">
                <i class="fas fa-search"></i>
            </span>
        </div>
        <select name="tingkat"
            class="w-full md:w-44 px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-purple-500 outline-none transition">
            <option value="">Semua Tingkat</option>
            <option value="10" <?= $tingkat === '10' ? 'selected' : '' ?>>Kelas 10</option>
            <option value="11" <?= $tingkat === '11' ? 'selected' : '' ?>>Kelas 11</option>
            <option value="12" <?= $tingkat === '12' ? 'selected' : '' ?>>Kelas 12</option>
        </select>
        <select name="wali"
            class="w-full md:w-56 px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-purple-500 outline-none transition">
            <option value="">Semua Wali</option>
            <option value="assigned" <?= $wali === 'assigned' ? 'selected' : '' ?>>Sudah Ada Wali</option>
            <option value="unassigned" <?= $wali === 'unassigned' ? 'selected' : '' ?>>Belum Ada Wali</option>
        </select>
        <button type="submit"
            class="px-4 py-2 bg-purple-600 hover:bg-purple-700 text-white rounded-lg text-sm font-medium transition">
            Terapkan
        </button>
        <div class="flex items-center text-sm text-gray-500 md:ml-auto">
            Menampilkan <span class="font-bold text-gray-800 mx-1">
                <?= count($kelas) ?>
            </span> dari <span class="font-bold text-gray-800 mx-1">
                <?= $totalRows ?>
            </span>
        </div>
    </form>
</div>

<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
    <?php if (count($kelas) > 0): ?>
        <?php foreach ($kelas as $k): ?>
            <div
                class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5 hover:shadow-md transition relative overflow-hidden group">
                <div
                    class="absolute top-0 right-0 w-16 h-16 bg-purple-50 rounded-bl-full -mr-4 -mt-4 group-hover:bg-purple-100 transition">
                </div>

                <div class="flex justify-between items-start mb-4">
                    <div>
                        <h3 class="text-xl font-bold text-gray-800">
                            <?= $k['nama_kelas'] ?>
                        </h3>
                        <p class="text-xs text-gray-400 uppercase tracking-widest font-semibold">Ruang Belajar</p>
                    </div>
                    <div class="text-purple-600">
                        <i class="fas fa-school fa-2x opacity-20"></i>
                    </div>
                </div>

                <div class="space-y-3 mb-6">
                    <div class="flex items-center text-sm text-gray-600">
                        <i class="fas fa-layer-group w-6 text-purple-400"></i>
                        <span class="font-medium">Tingkat: </span>
                        <span class="ml-1"><?= htmlspecialchars($k['tingkat'], ENT_QUOTES, 'UTF-8') ?></span>
                    </div>
                    <div class="flex items-center text-sm text-gray-600">
                        <i class="fas fa-chalkboard-teacher w-6 text-purple-400"></i>
                        <span class="font-medium">Wali: </span>
                        <span class="ml-1">
                            <?= $k['nama_guru'] ?? '<em class="text-red-400">Belum ditentukan</em>' ?>
                        </span>
                    </div>
                    <div class="flex items-center text-sm text-gray-600">
                        <i class="fas fa-users w-6 text-purple-400"></i>
                        <span class="font-medium">Siswa: </span>
                        <span class="ml-1">
                            <?= $k['total_siswa'] ?> Orang
                        </span>
                    </div>
                </div>

                <div class="flex border-t pt-4 gap-2">
                    <a href="index.php?page=kelas-edit&id=<?= $k['id'] ?>"
                        class="flex-1 text-center py-2 text-sm font-bold text-purple-600 hover:bg-purple-50 rounded-lg transition">
                        <i class="fas fa-edit mr-1"></i> Edit
                    </a>
                    <button onclick="confirmDelete('index.php?page=kelas-hapus&id=<?= $k['id'] ?>')"
                        class="flex-1 text-center py-2 text-sm font-bold text-red-500 hover:bg-red-50 rounded-lg transition">
                        <i class="fas fa-trash mr-1"></i> Hapus
                    </button>
                </div>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <div class="col-span-full bg-gray-50 border-2 border-dashed border-gray-200 rounded-2xl p-10 text-center">
            <p class="text-gray-400 italic">Belum ada data kelas. Silakan tambah kelas baru.</p>
        </div>
    <?php endif; ?>
</div>

<?php if ($totalPages > 1): ?>
    <div class="mt-6 flex flex-col md:flex-row items-center justify-between gap-3 text-sm">
        <div class="text-gray-500">
            Halaman <span class="font-semibold text-gray-800"><?= $currentPage ?></span> dari
            <span class="font-semibold text-gray-800"><?= $totalPages ?></span>
        </div>
        <div class="flex items-center gap-2">
            <?php
            $baseParams = ['page' => 'kelas'];
            if ($search) {
                $baseParams['search'] = $search;
            }
            if ($tingkat) {
                $baseParams['tingkat'] = $tingkat;
            }
            if ($wali) {
                $baseParams['wali'] = $wali;
            }

            $prevPage = max(1, $currentPage - 1);
            $nextPage = min($totalPages, $currentPage + 1);

            $firstHref = 'index.php?' . http_build_query($baseParams + ['p' => 1]);
            $prevHref = 'index.php?' . http_build_query($baseParams + ['p' => $prevPage]);
            $nextHref = 'index.php?' . http_build_query($baseParams + ['p' => $nextPage]);
            $lastHref = 'index.php?' . http_build_query($baseParams + ['p' => $totalPages]);
            ?>

            <a href="<?= $firstHref ?>"
                class="px-3 py-2 rounded-lg border text-gray-600 hover:bg-gray-50 transition <?= $currentPage == 1 ? 'pointer-events-none opacity-50' : '' ?>">
                First
            </a>
            <a href="<?= $prevHref ?>"
                class="px-3 py-2 rounded-lg border text-gray-600 hover:bg-gray-50 transition <?= $currentPage == 1 ? 'pointer-events-none opacity-50' : '' ?>">
                Prev
            </a>
            <span class="px-3 py-2 rounded-lg bg-purple-600 text-white font-semibold">
                <?= $currentPage ?>
            </span>
            <a href="<?= $nextHref ?>"
                class="px-3 py-2 rounded-lg border text-gray-600 hover:bg-gray-50 transition <?= $currentPage == $totalPages ? 'pointer-events-none opacity-50' : '' ?>">
                Next
            </a>
            <a href="<?= $lastHref ?>"
                class="px-3 py-2 rounded-lg border text-gray-600 hover:bg-gray-50 transition <?= $currentPage == $totalPages ? 'pointer-events-none opacity-50' : '' ?>">
                Last
            </a>
        </div>
    </div>
<?php endif; ?>