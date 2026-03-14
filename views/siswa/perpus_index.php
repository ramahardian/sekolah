<?php
if (!isset($pdo)) {
    exit;
}

$search = isset($_GET['search']) ? input($_GET['search']) : '';
$query = "SELECT * FROM perpustakaan_buku";
$params = [];

if ($search) {
    $query .= " WHERE kode_buku LIKE :search OR judul LIKE :search OR penulis LIKE :search";
    $params['search'] = "%$search%";
}
$query .= " ORDER BY judul ASC";

$stmt = $pdo->prepare($query);
foreach ($params as $k => $v) {
    $stmt->bindValue(':' . $k, $v, PDO::PARAM_STR);
}
$stmt->execute();
$buku = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="flex flex-col md:flex-row md:items-center justify-between mb-6 gap-4">
    <div>
        <h1 class="text-2xl font-bold text-gray-800">Perpustakaan</h1>
        <p class="text-gray-500 text-sm">Cari dan pinjam buku.</p>
    </div>
    <a href="index.php?page=siswa-perpus-riwayat"
        class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-lg flex items-center shadow-md transition">
        <i class="fas fa-clock-rotate-left mr-2 text-sm"></i> Riwayat Peminjaman
    </a>
</div>

<?php display_flash_message(); ?>

<div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
    <div class="p-4 border-b border-gray-100 flex flex-col md:flex-row justify-between gap-4 bg-gray-50/50">
        <form action="index.php" method="GET" class="relative w-full md:w-80">
            <input type="hidden" name="page" value="siswa-perpus">
            <input type="text" name="search" value="<?= htmlspecialchars($search, ENT_QUOTES, 'UTF-8') ?>"
                placeholder="Cari buku..."
                class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 outline-none transition text-sm">
            <span class="absolute left-3 top-2.5 text-gray-400">
                <i class="fas fa-search"></i>
            </span>
        </form>
        <div class="flex items-center text-sm text-gray-500">
            Total buku <span class="font-bold text-gray-800 mx-1"><?= count($buku) ?></span>
        </div>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="bg-gray-50 text-gray-600 text-xs uppercase tracking-widest border-b">
                    <th class="px-6 py-4 font-semibold">Buku</th>
                    <th class="px-6 py-4 font-semibold">Penulis</th>
                    <th class="px-6 py-4 font-semibold">Stok</th>
                    <th class="px-6 py-4 font-semibold text-center">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                <?php if ($buku): ?>
                    <?php foreach ($buku as $b): ?>
                        <tr class="hover:bg-indigo-50/30 transition">
                            <td class="px-6 py-4">
                                <div class="text-sm font-semibold text-gray-800">
                                    <?= htmlspecialchars($b['judul'], ENT_QUOTES, 'UTF-8') ?>
                                </div>
                                <div class="text-xs text-gray-500"><?= htmlspecialchars($b['kode_buku'], ENT_QUOTES, 'UTF-8') ?></div>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-600">
                                <?= htmlspecialchars($b['penulis'] ?? '-', ENT_QUOTES, 'UTF-8') ?>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-600">
                                <?= (int) $b['stok_tersedia'] ?> / <?= (int) $b['stok_total'] ?>
                            </td>
                            <td class="px-6 py-4 text-center">
                                <?php if ((int) $b['stok_tersedia'] > 0): ?>
                                    <a href="index.php?page=siswa-perpus-pinjam&id=<?= (int) $b['id'] ?>"
                                        class="px-3 py-2 rounded-lg bg-indigo-600 text-white text-sm font-medium hover:bg-indigo-700 transition">
                                        Pinjam
                                    </a>
                                <?php else: ?>
                                    <span class="text-xs text-gray-400">Stok habis</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="4" class="px-6 py-10 text-center text-gray-500">Buku belum tersedia.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
