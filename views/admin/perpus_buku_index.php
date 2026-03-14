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
        <p class="text-gray-500 text-sm">Kelola data buku perpustakaan.</p>
    </div>
    <a href="index.php?page=perpus-buku-tambah"
        class="bg-emerald-600 hover:bg-emerald-700 text-white px-4 py-2 rounded-lg flex items-center shadow-md transition">
        <i class="fas fa-plus mr-2 text-sm"></i> Tambah Buku
    </a>
</div>

<?php display_flash_message(); ?>

<div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
    <div class="p-4 border-b border-gray-100 flex flex-col md:flex-row justify-between gap-4 bg-gray-50/50">
        <form action="index.php" method="GET" class="relative w-full md:w-80">
            <input type="hidden" name="page" value="perpus-buku">
            <input type="text" name="search" value="<?= htmlspecialchars($search, ENT_QUOTES, 'UTF-8') ?>"
                placeholder="Cari kode/judul/penulis..."
                class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 outline-none transition text-sm">
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
                    <th class="px-6 py-4 font-semibold">Kode</th>
                    <th class="px-6 py-4 font-semibold">Judul</th>
                    <th class="px-6 py-4 font-semibold">Penulis</th>
                    <th class="px-6 py-4 font-semibold">Tahun</th>
                    <th class="px-6 py-4 font-semibold">Stok</th>
                    <th class="px-6 py-4 font-semibold text-center">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                <?php if ($buku): ?>
                    <?php foreach ($buku as $b): ?>
                        <tr class="hover:bg-emerald-50/30 transition">
                            <td class="px-6 py-4 text-sm font-mono text-gray-700">
                                <?= htmlspecialchars($b['kode_buku'], ENT_QUOTES, 'UTF-8') ?>
                            </td>
                            <td class="px-6 py-4 text-sm font-semibold text-gray-800">
                                <?= htmlspecialchars($b['judul'], ENT_QUOTES, 'UTF-8') ?>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-600">
                                <?= htmlspecialchars($b['penulis'] ?? '-', ENT_QUOTES, 'UTF-8') ?>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-600">
                                <?= htmlspecialchars((string) $b['tahun'], ENT_QUOTES, 'UTF-8') ?>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-600">
                                <?= (int) $b['stok_tersedia'] ?> / <?= (int) $b['stok_total'] ?>
                            </td>
                            <td class="px-6 py-4 text-center">
                                <div class="flex justify-center gap-2">
                                    <a href="index.php?page=perpus-buku-edit&id=<?= (int) $b['id'] ?>"
                                        class="text-emerald-600 hover:bg-emerald-50 p-2 rounded-lg transition">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <button onclick="confirmDelete('index.php?page=perpus-buku-hapus&id=<?= (int) $b['id'] ?>')"
                                        class="text-red-600 hover:bg-red-50 p-2 rounded-lg transition">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6" class="px-6 py-10 text-center text-gray-500">Data buku belum tersedia.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
