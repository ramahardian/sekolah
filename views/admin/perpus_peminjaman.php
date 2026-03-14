<?php
if (!isset($pdo)) {
    exit;
}

$status = isset($_GET['status']) ? input($_GET['status']) : '';
$search = isset($_GET['search']) ? input($_GET['search']) : '';

$query = "SELECT p.*, b.judul, b.kode_buku, u.username,
        COALESCE(s.nama_siswa, u.username) AS nama_siswa
    FROM perpustakaan_peminjaman p
    JOIN perpustakaan_buku b ON p.buku_id = b.id
    JOIN users u ON p.siswa_user_id = u.id
    LEFT JOIN siswa s ON s.user_id = u.id";

$conditions = [];
$params = [];

if ($status === 'dipinjam' || $status === 'kembali') {
    $conditions[] = "p.status = :status";
    $params['status'] = $status;
}
if ($search) {
    $conditions[] = "(b.judul LIKE :search OR b.kode_buku LIKE :search OR s.nama_siswa LIKE :search OR u.username LIKE :search)";
    $params['search'] = "%$search%";
}

if ($conditions) {
    $query .= " WHERE " . implode(" AND ", $conditions);
}

$query .= " ORDER BY p.tanggal_pinjam DESC";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $pinjamId = (int) ($_POST['pinjam_id'] ?? 0);

    if ($action === 'kembalikan' && $pinjamId > 0) {
        $pdo->beginTransaction();
        $stmt = $pdo->prepare("SELECT * FROM perpustakaan_peminjaman WHERE id = ? FOR UPDATE");
        $stmt->execute([$pinjamId]);
        $pinjam = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($pinjam && $pinjam['status'] === 'dipinjam') {
            $pdo->prepare("UPDATE perpustakaan_peminjaman SET status = 'kembali', tanggal_kembali = NOW() WHERE id = ?")
                ->execute([$pinjamId]);
            $pdo->prepare("UPDATE perpustakaan_buku SET stok_tersedia = stok_tersedia + 1 WHERE id = ?")
                ->execute([(int) $pinjam['buku_id']]);
            $pdo->commit();
            set_flash_message('success', 'Buku dikembalikan.');
        } else {
            $pdo->rollBack();
            set_flash_message('error', 'Data peminjaman tidak valid.');
        }

        header("Location: index.php?page=perpus-peminjaman");
        exit;
    }
}

$stmt = $pdo->prepare($query);
foreach ($params as $k => $v) {
    $stmt->bindValue(':' . $k, $v, PDO::PARAM_STR);
}
$stmt->execute();
$peminjaman = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="flex flex-col md:flex-row md:items-center justify-between mb-6 gap-4">
    <div>
        <h1 class="text-2xl font-bold text-gray-800">Peminjaman Buku</h1>
        <p class="text-gray-500 text-sm">Kelola status peminjaman buku siswa.</p>
    </div>
    <a href="index.php?page=perpus-buku"
        class="bg-emerald-600 hover:bg-emerald-700 text-white px-4 py-2 rounded-lg flex items-center shadow-md transition">
        <i class="fas fa-book mr-2 text-sm"></i> Kelola Buku
    </a>
</div>

<?php display_flash_message(); ?>

<div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
    <div class="p-4 border-b border-gray-100 flex flex-col md:flex-row justify-between gap-4 bg-gray-50/50">
        <form action="index.php" method="GET" class="flex flex-col md:flex-row gap-3 w-full">
            <input type="hidden" name="page" value="perpus-peminjaman">
            <div class="relative w-full md:w-72">
                <input type="text" name="search" value="<?= htmlspecialchars($search, ENT_QUOTES, 'UTF-8') ?>"
                    placeholder="Cari buku atau siswa..."
                    class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 outline-none transition text-sm">
                <span class="absolute left-3 top-2.5 text-gray-400">
                    <i class="fas fa-search"></i>
                </span>
            </div>
            <select name="status"
                class="w-full md:w-48 px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-emerald-500 outline-none transition">
                <option value="">Semua Status</option>
                <option value="dipinjam" <?= $status === 'dipinjam' ? 'selected' : '' ?>>Dipinjam</option>
                <option value="kembali" <?= $status === 'kembali' ? 'selected' : '' ?>>Kembali</option>
            </select>
            <button type="submit"
                class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg text-sm font-medium transition">
                Terapkan
            </button>
        </form>
        <div class="flex items-center text-sm text-gray-500">
            Total <span class="font-bold text-gray-800 mx-1"><?= count($peminjaman) ?></span> data
        </div>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="bg-gray-50 text-gray-600 text-xs uppercase tracking-widest border-b">
                    <th class="px-6 py-4 font-semibold">Buku</th>
                    <th class="px-6 py-4 font-semibold">Siswa</th>
                    <th class="px-6 py-4 font-semibold">Pinjam</th>
                    <th class="px-6 py-4 font-semibold">Kembali</th>
                    <th class="px-6 py-4 font-semibold">Status</th>
                    <th class="px-6 py-4 font-semibold text-center">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                <?php if ($peminjaman): ?>
                    <?php foreach ($peminjaman as $p): ?>
                        <tr class="hover:bg-emerald-50/30 transition">
                            <td class="px-6 py-4">
                                <div class="text-sm font-semibold text-gray-800">
                                    <?= htmlspecialchars($p['judul'], ENT_QUOTES, 'UTF-8') ?>
                                </div>
                                <div class="text-xs text-gray-500"><?= htmlspecialchars($p['kode_buku'], ENT_QUOTES, 'UTF-8') ?></div>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-600">
                                <?= htmlspecialchars($p['nama_siswa'], ENT_QUOTES, 'UTF-8') ?>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-600">
                                <?= htmlspecialchars($p['tanggal_pinjam'], ENT_QUOTES, 'UTF-8') ?>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-600">
                                <?= htmlspecialchars($p['tanggal_kembali'] ?: '-', ENT_QUOTES, 'UTF-8') ?>
                            </td>
                            <td class="px-6 py-4">
                                <span class="px-2.5 py-0.5 rounded-full text-xs font-medium
                                    <?= $p['status'] === 'dipinjam' ? 'bg-yellow-100 text-yellow-700' : 'bg-green-100 text-green-700' ?>">
                                    <?= $p['status'] === 'dipinjam' ? 'Dipinjam' : 'Kembali' ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 text-center">
                                <?php if ($p['status'] === 'dipinjam'): ?>
                                    <form method="POST" class="inline">
                                        <input type="hidden" name="action" value="kembalikan">
                                        <input type="hidden" name="pinjam_id" value="<?= (int) $p['id'] ?>">
                                        <button type="submit"
                                            class="px-3 py-2 rounded-lg bg-emerald-600 text-white text-sm font-medium hover:bg-emerald-700 transition">
                                            Tandai Kembali
                                        </button>
                                    </form>
                                <?php else: ?>
                                    <span class="text-xs text-gray-400">Selesai</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6" class="px-6 py-10 text-center text-gray-500">Belum ada peminjaman.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
