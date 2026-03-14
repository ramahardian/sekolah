<?php
// Cek jika file ini dipanggil tanpa index.php
if (!isset($pdo)) {
    header("Location: ../../index.php");
    exit;
}

// Filter & Pencarian
$search = isset($_GET['search']) ? input($_GET['search']) : '';
$gender = isset($_GET['gender']) ? input($_GET['gender']) : '';

$perPage = 5;
$currentPage = isset($_GET['p']) ? max(1, (int) $_GET['p']) : 1;
$offset = ($currentPage - 1) * $perPage;

// Query untuk mengambil data guru beserta kelas yang mereka wali-kan (jika ada)
$query_sql = "SELECT g.*, k.nama_kelas 
              FROM guru g 
              LEFT JOIN kelas k ON g.id = k.wali_id";
$count_sql = "SELECT COUNT(*) 
              FROM guru g 
              LEFT JOIN kelas k ON g.id = k.wali_id";

$conditions = [];
$params = [];

if ($search) {
    $conditions[] = "(g.nama_guru LIKE :search OR g.nip LIKE :search)";
    $params['search'] = "%$search%";
}
if ($gender === 'L' || $gender === 'P') {
    $conditions[] = "g.jenis_kelamin = :gender";
    $params['gender'] = $gender;
}

if ($conditions) {
    $where = " WHERE " . implode(" AND ", $conditions);
    $query_sql .= $where;
    $count_sql .= $where;
}

$query_sql .= " ORDER BY g.nama_guru ASC LIMIT :limit OFFSET :offset";

// Total data
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
$guru = $stmt->fetchAll();
?>

<div class="flex flex-col md:flex-row md:items-center justify-between mb-6 gap-4">
    <div>
        <h1 class="text-2xl font-bold text-gray-800">Data Guru</h1>
        <p class="text-gray-500 text-sm">Manajemen tenaga pendidik dan penugasan wali kelas.</p>
    </div>
    <a href="index.php?page=guru-tambah"
        class="bg-emerald-600 hover:bg-emerald-700 text-white px-4 py-2 rounded-lg flex items-center shadow-md transition">
        <i class="fas fa-plus mr-2 text-sm"></i> Tambah Guru
    </a>
</div>

<?php display_flash_message(); ?>

<div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
    <div class="p-4 border-b border-gray-100 flex flex-col md:flex-row justify-between gap-4 bg-gray-50/50">
        <form action="index.php" method="GET" class="flex flex-col md:flex-row gap-3 w-full">
            <input type="hidden" name="page" value="guru">
            <div class="relative w-full md:w-72">
                <input type="text" name="search" value="<?= $search ?>" placeholder="Cari nama atau NIP..."
                    class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 outline-none transition text-sm">
                <span class="absolute left-3 top-2.5 text-gray-400">
                    <i class="fas fa-search"></i>
                </span>
            </div>
            <select name="gender"
                class="w-full md:w-52 px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-emerald-500 outline-none transition">
                <option value="">Semua Gender</option>
                <option value="L" <?= $gender === 'L' ? 'selected' : '' ?>>Laki-laki</option>
                <option value="P" <?= $gender === 'P' ? 'selected' : '' ?>>Perempuan</option>
            </select>
            <button type="submit"
                class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg text-sm font-medium transition">
                Terapkan
            </button>
        </form>
        <div class="flex items-center text-sm text-gray-500">
            Menampilkan <span class="font-bold text-gray-800 mx-1">
                <?= count($guru) ?>
            </span> dari <span class="font-bold text-gray-800 mx-1">
                <?= $totalRows ?>
            </span>
        </div>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="bg-gray-50 text-gray-600 text-xs uppercase tracking-widest border-b">
                    <th class="px-6 py-4 font-semibold">Nama Guru</th>
                    <th class="px-6 py-4 font-semibold">NIP</th>
                    <th class="px-6 py-4 font-semibold">Jenis Kelamin</th>
                    <th class="px-6 py-4 font-semibold">Wali Kelas</th>
                    <th class="px-6 py-4 font-semibold text-center">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                <?php if (count($guru) > 0): ?>
                    <?php foreach ($guru as $g): ?>
                        <tr class="hover:bg-emerald-50/30 transition">
                            <td class="px-6 py-4">
                                <div class="flex items-center">
                                    <div
                                        class="h-10 w-10 rounded-full bg-emerald-100 text-emerald-600 flex items-center justify-center font-bold mr-3">
                                        <?= get_initials($g['nama_guru']) ?>
                                    </div>
                                    <div class="text-sm font-bold text-gray-800">
                                        <?= $g['nama_guru'] ?>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-600 font-mono">
                                <?= $g['nip'] ?>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-600">
                                <?= $g['jenis_kelamin'] == 'L' ? 'Laki-laki' : 'Perempuan' ?>
                            </td>
                            <td class="px-6 py-4">
                                <?php if ($g['nama_kelas']): ?>
                                    <span class="px-2 py-1 rounded bg-blue-100 text-blue-700 text-xs font-bold uppercase">
                                        Wali
                                        <?= $g['nama_kelas'] ?>
                                    </span>
                                <?php else: ?>
                                    <span class="text-gray-400 text-xs italic italic italic">-</span>
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-4 text-center">
                                <div class="flex justify-center gap-2">
                                    <a href="index.php?page=guru-edit&id=<?= $g['id'] ?>"
                                        class="text-emerald-600 hover:bg-emerald-50 p-2 rounded-lg transition">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <button onclick="confirmDelete('views/admin/guru_hapus.php?id=<?= $g['id'] ?>')"
                                        class="text-red-600 hover:bg-red-50 p-2 rounded-lg transition">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="5" class="px-6 py-10 text-center text-gray-500 italic">Data guru belum tersedia.</td>
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
            $baseParams = ['page' => 'guru'];
            if ($search) {
                $baseParams['search'] = $search;
            }
            if ($gender) {
                $baseParams['gender'] = $gender;
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
            <span class="px-3 py-2 rounded-lg bg-emerald-600 text-white font-semibold">
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