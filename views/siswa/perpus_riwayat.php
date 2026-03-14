<?php
if (!isset($pdo)) {
    exit;
}

$userId = $_SESSION['user_id'] ?? 0;

$stmt = $pdo->prepare("SELECT p.*, b.judul, b.kode_buku
                       FROM perpustakaan_peminjaman p
                       JOIN perpustakaan_buku b ON p.buku_id = b.id
                       WHERE p.siswa_user_id = ?
                       ORDER BY p.tanggal_pinjam DESC");
$stmt->execute([$userId]);
$riwayat = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="flex flex-col md:flex-row md:items-center justify-between mb-6 gap-4">
    <div>
        <h1 class="text-2xl font-bold text-gray-800">Riwayat Peminjaman</h1>
        <p class="text-gray-500 text-sm">Daftar peminjaman buku Anda.</p>
    </div>
    <a href="index.php?page=siswa-perpus"
        class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-lg flex items-center shadow-md transition">
        <i class="fas fa-book mr-2 text-sm"></i> Kembali ke Buku
    </a>
</div>

<?php display_flash_message(); ?>

<div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="bg-gray-50 text-gray-600 text-xs uppercase tracking-widest border-b">
                    <th class="px-6 py-4 font-semibold">Buku</th>
                    <th class="px-6 py-4 font-semibold">Tanggal Pinjam</th>
                    <th class="px-6 py-4 font-semibold">Tanggal Kembali</th>
                    <th class="px-6 py-4 font-semibold">Status</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                <?php if ($riwayat): ?>
                    <?php foreach ($riwayat as $r): ?>
                        <tr class="hover:bg-indigo-50/30 transition">
                            <td class="px-6 py-4">
                                <div class="text-sm font-semibold text-gray-800">
                                    <?= htmlspecialchars($r['judul'], ENT_QUOTES, 'UTF-8') ?>
                                </div>
                                <div class="text-xs text-gray-500"><?= htmlspecialchars($r['kode_buku'], ENT_QUOTES, 'UTF-8') ?></div>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-600">
                                <?= htmlspecialchars($r['tanggal_pinjam'], ENT_QUOTES, 'UTF-8') ?>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-600">
                                <?= htmlspecialchars($r['tanggal_kembali'] ?: '-', ENT_QUOTES, 'UTF-8') ?>
                            </td>
                            <td class="px-6 py-4">
                                <span class="px-2.5 py-0.5 rounded-full text-xs font-medium
                                    <?= $r['status'] === 'dipinjam' ? 'bg-yellow-100 text-yellow-700' : 'bg-green-100 text-green-700' ?>">
                                    <?= $r['status'] === 'dipinjam' ? 'Dipinjam' : 'Kembali' ?>
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="4" class="px-6 py-10 text-center text-gray-500">Belum ada peminjaman.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
