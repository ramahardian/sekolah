<?php
if (!isset($pdo)) {
    exit;
}

$userId = $_SESSION['user_id'] ?? 0;

$siswaStmt = $pdo->prepare("SELECT s.id, s.nama_siswa, s.kelas_id, k.nama_kelas
                            FROM siswa s
                            LEFT JOIN kelas k ON s.kelas_id = k.id
                            WHERE s.user_id = ?");
$siswaStmt->execute([$userId]);
$siswa = $siswaStmt->fetch(PDO::FETCH_ASSOC);

if (!$siswa) {
    echo "<div class='bg-red-50 border border-red-200 text-red-700 p-4 rounded-lg'>Data siswa tidak ditemukan.</div>";
    return;
}

$totalUjianStmt = $pdo->query("SELECT COUNT(*) FROM ujian WHERE status = 'published'");
$totalUjian = (int) $totalUjianStmt->fetchColumn();

$selesaiStmt = $pdo->prepare("SELECT COUNT(*) FROM ujian_attempt WHERE siswa_user_id = ? AND status = 'submitted'");
$selesaiStmt->execute([$userId]);
$totalSelesai = (int) $selesaiStmt->fetchColumn();

$totalBelum = max(0, $totalUjian - $totalSelesai);

$dayMap = [
    1 => 'Senin',
    2 => 'Selasa',
    3 => 'Rabu',
    4 => 'Kamis',
    5 => 'Jumat',
    6 => 'Sabtu',
    7 => 'Minggu',
];
$hariIni = $dayMap[(int) date('N')] ?? 'Senin';

$jadwalStmt = $pdo->prepare("SELECT j.*, m.nama_mapel, g.nama_guru
                             FROM jadwal_pelajaran j
                             JOIN mapel m ON j.mapel_id = m.id
                             JOIN guru g ON j.guru_id = g.id
                             WHERE j.kelas_id = ? AND j.hari = ?
                             ORDER BY j.jam_mulai ASC");
$jadwalStmt->execute([(int) $siswa['kelas_id'], $hariIni]);
$jadwalHariIni = $jadwalStmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="mb-8">
    <h1 class="text-2xl font-bold text-gray-800">Dashboard Siswa</h1>
    <p class="text-gray-500">Selamat datang, <span class="font-semibold text-indigo-600">
            <?= htmlspecialchars($siswa['nama_siswa'], ENT_QUOTES, 'UTF-8') ?>
        </span>.</p>
    <p class="text-sm text-gray-500">Kelas: <?= htmlspecialchars($siswa['nama_kelas'] ?? '-', ENT_QUOTES, 'UTF-8') ?></p>
</div>

<div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
    <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100 flex items-center">
        <div class="p-3 rounded-full bg-indigo-100 text-indigo-600 mr-4">
            <i class="fas fa-clipboard-list fa-2x"></i>
        </div>
        <div>
            <p class="text-sm text-gray-500 font-medium uppercase">Ujian Tersedia</p>
            <h3 class="text-2xl font-bold text-gray-800"><?= $totalUjian ?></h3>
        </div>
    </div>
    <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100 flex items-center">
        <div class="p-3 rounded-full bg-green-100 text-green-600 mr-4">
            <i class="fas fa-circle-check fa-2x"></i>
        </div>
        <div>
            <p class="text-sm text-gray-500 font-medium uppercase">Ujian Selesai</p>
            <h3 class="text-2xl font-bold text-gray-800"><?= $totalSelesai ?></h3>
        </div>
    </div>
    <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100 flex items-center">
        <div class="p-3 rounded-full bg-orange-100 text-orange-600 mr-4">
            <i class="fas fa-hourglass-half fa-2x"></i>
        </div>
        <div>
            <p class="text-sm text-gray-500 font-medium uppercase">Belum Dikerjakan</p>
            <h3 class="text-2xl font-bold text-gray-800"><?= $totalBelum ?></h3>
        </div>
    </div>
</div>

<div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
    <div class="flex justify-between items-center mb-4">
        <h3 class="text-lg font-bold text-gray-800">Jadwal Hari Ini (<?= $hariIni ?>)</h3>
        <a href="index.php?page=siswa-jadwal" class="text-sm text-indigo-600 hover:underline">Lihat Semua</a>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-left">
            <thead>
                <tr class="text-gray-400 text-sm uppercase tracking-wider border-b">
                    <th class="pb-3 font-medium">Jam</th>
                    <th class="pb-3 font-medium">Mapel</th>
                    <th class="pb-3 font-medium">Guru</th>
                    <th class="pb-3 font-medium">Ruang</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                <?php if ($jadwalHariIni): ?>
                    <?php foreach ($jadwalHariIni as $j): ?>
                        <tr>
                            <td class="py-4 text-sm text-gray-600">
                                <?= htmlspecialchars(substr($j['jam_mulai'], 0, 5), ENT_QUOTES, 'UTF-8') ?> -
                                <?= htmlspecialchars(substr($j['jam_selesai'], 0, 5), ENT_QUOTES, 'UTF-8') ?>
                            </td>
                            <td class="py-4 text-sm font-semibold text-gray-700">
                                <?= htmlspecialchars($j['nama_mapel'], ENT_QUOTES, 'UTF-8') ?>
                            </td>
                            <td class="py-4 text-sm text-gray-500">
                                <?= htmlspecialchars($j['nama_guru'], ENT_QUOTES, 'UTF-8') ?>
                            </td>
                            <td class="py-4 text-sm text-gray-500">
                                <?= htmlspecialchars($j['ruang'] ?: '-', ENT_QUOTES, 'UTF-8') ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="4" class="py-6 text-center text-gray-500">Tidak ada jadwal hari ini.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
