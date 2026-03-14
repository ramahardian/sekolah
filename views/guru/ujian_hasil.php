<?php
if (!isset($pdo)) {
    exit;
}

$userId = $_SESSION['user_id'] ?? 0;
$guruStmt = $pdo->prepare("SELECT id, nama_guru FROM guru WHERE user_id = ?");
$guruStmt->execute([$userId]);
$guru = $guruStmt->fetch(PDO::FETCH_ASSOC);

if (!$guru) {
    echo "<div class='bg-red-50 border border-red-200 text-red-700 p-4 rounded-lg'>Data guru tidak ditemukan.</div>";
    return;
}

$ujianId = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$ujianStmt = $pdo->prepare("SELECT u.*, m.nama_mapel
                            FROM ujian u
                            JOIN mapel m ON u.mapel_id = m.id
                            JOIN mapel_guru mg ON mg.mapel_id = m.id
                            WHERE u.id = ? AND mg.guru_id = ?");
$ujianStmt->execute([$ujianId, $guru['id']]);
$ujian = $ujianStmt->fetch(PDO::FETCH_ASSOC);

if (!$ujian) {
    echo "<div class='bg-red-50 border border-red-200 text-red-700 p-4 rounded-lg'>Ujian tidak ditemukan.</div>";
    return;
}

$attemptStmt = $pdo->prepare("SELECT a.*, u.username, s.nama_siswa
                              FROM ujian_attempt a
                              JOIN users u ON a.siswa_user_id = u.id
                              LEFT JOIN siswa s ON s.user_id = u.id
                              WHERE a.ujian_id = ?
                              ORDER BY a.selesai_at DESC, a.id DESC");
$attemptStmt->execute([$ujianId]);
$attempts = $attemptStmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="flex flex-col md:flex-row md:items-center justify-between mb-6 gap-4">
    <div>
        <h1 class="text-2xl font-bold text-gray-800">Hasil Ujian</h1>
        <p class="text-gray-500 text-sm">
            <?= htmlspecialchars($ujian['judul'], ENT_QUOTES, 'UTF-8') ?> ·
            <?= htmlspecialchars($ujian['nama_mapel'], ENT_QUOTES, 'UTF-8') ?>
        </p>
    </div>
    <a href="index.php?page=guru-ujian" class="text-gray-600 hover:underline">Kembali</a>
</div>

<?php display_flash_message(); ?>

<div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="bg-gray-50 text-gray-600 text-xs uppercase tracking-widest border-b">
                    <th class="px-6 py-4 font-semibold">Siswa</th>
                    <th class="px-6 py-4 font-semibold">Mulai</th>
                    <th class="px-6 py-4 font-semibold">Selesai</th>
                    <th class="px-6 py-4 font-semibold">Skor</th>
                    <th class="px-6 py-4 font-semibold text-center">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                <?php if (count($attempts) > 0): ?>
                    <?php foreach ($attempts as $a): ?>
                        <tr class="hover:bg-emerald-50/30 transition">
                            <td class="px-6 py-4">
                                <div class="text-sm font-bold text-gray-800">
                                    <?= htmlspecialchars($a['nama_siswa'] ?: $a['username'], ENT_QUOTES, 'UTF-8') ?>
                                </div>
                                <div class="text-xs text-gray-500"><?= htmlspecialchars($a['username'], ENT_QUOTES, 'UTF-8') ?></div>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-600">
                                <?= $a['mulai_at'] ? htmlspecialchars($a['mulai_at'], ENT_QUOTES, 'UTF-8') : '-' ?>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-600">
                                <?= $a['selesai_at'] ? htmlspecialchars($a['selesai_at'], ENT_QUOTES, 'UTF-8') : '-' ?>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-600">
                                <?= (int) $a['skor_total'] ?>
                            </td>
                            <td class="px-6 py-4 text-center">
                                <a href="index.php?page=guru-ujian-nilai&attempt=<?= (int) $a['id'] ?>"
                                    class="px-3 py-2 rounded-lg bg-emerald-600 text-white text-sm font-medium hover:bg-emerald-700 transition">
                                    Nilai
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="5" class="px-6 py-10 text-center text-gray-500">
                            Belum ada siswa yang mengikuti ujian ini.
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
