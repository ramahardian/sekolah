<?php
if (!isset($pdo)) {
    exit;
}

$userId = $_SESSION['user_id'] ?? 0;
$siswaStmt = $pdo->prepare("SELECT s.id, s.nama_siswa, k.nama_kelas
                            FROM siswa s
                            LEFT JOIN kelas k ON s.kelas_id = k.id
                            WHERE s.user_id = ?");
$siswaStmt->execute([$userId]);
$siswa = $siswaStmt->fetch(PDO::FETCH_ASSOC);

if (!$siswa) {
    echo "<div class='bg-red-50 border border-red-200 text-red-700 p-4 rounded-lg'>Data siswa tidak ditemukan.</div>";
    return;
}

$bulan = isset($_GET['bulan']) ? input($_GET['bulan']) : date('Y-m');

$stmt = $pdo->prepare("SELECT * FROM absensi WHERE siswa_id = ? AND DATE_FORMAT(tanggal, '%Y-%m') = ?
                       ORDER BY tanggal DESC");
$stmt->execute([$siswa['id'], $bulan]);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

$summary = [
    'hadir' => 0,
    'izin' => 0,
    'sakit' => 0,
    'alpha' => 0
];
foreach ($rows as $r) {
    if (isset($summary[$r['status']])) {
        $summary[$r['status']]++;
    }
}
?>

<div class="flex flex-col md:flex-row md:items-center justify-between mb-6 gap-4">
    <div>
        <h1 class="text-2xl font-bold text-gray-800">Absensi</h1>
        <p class="text-gray-500 text-sm"><?= htmlspecialchars($siswa['nama_siswa'], ENT_QUOTES, 'UTF-8') ?> ·
            <?= htmlspecialchars($siswa['nama_kelas'] ?? '-', ENT_QUOTES, 'UTF-8') ?></p>
    </div>
</div>

<div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4 text-center">
        <p class="text-xs text-gray-500 uppercase">Hadir</p>
        <p class="text-xl font-bold text-green-600"><?= $summary['hadir'] ?></p>
    </div>
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4 text-center">
        <p class="text-xs text-gray-500 uppercase">Izin</p>
        <p class="text-xl font-bold text-yellow-600"><?= $summary['izin'] ?></p>
    </div>
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4 text-center">
        <p class="text-xs text-gray-500 uppercase">Sakit</p>
        <p class="text-xl font-bold text-blue-600"><?= $summary['sakit'] ?></p>
    </div>
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4 text-center">
        <p class="text-xs text-gray-500 uppercase">Alpha</p>
        <p class="text-xl font-bold text-red-600"><?= $summary['alpha'] ?></p>
    </div>
</div>

<div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
    <div class="p-4 border-b border-gray-100 flex flex-col md:flex-row justify-between gap-4 bg-gray-50/50">
        <form action="index.php" method="GET" class="flex gap-3">
            <input type="hidden" name="page" value="absensi">
            <input type="month" name="bulan" value="<?= htmlspecialchars($bulan, ENT_QUOTES, 'UTF-8') ?>"
                class="px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 outline-none transition">
            <button type="submit"
                class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-sm font-medium transition">
                Terapkan
            </button>
        </form>
        <div class="text-sm text-gray-500">Total <span class="font-bold text-gray-800 mx-1"><?= count($rows) ?></span> hari</div>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="bg-gray-50 text-gray-600 text-xs uppercase tracking-widest border-b">
                    <th class="px-6 py-4 font-semibold">Tanggal</th>
                    <th class="px-6 py-4 font-semibold">Status</th>
                    <th class="px-6 py-4 font-semibold">Keterangan</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                <?php if ($rows): ?>
                    <?php foreach ($rows as $r): ?>
                        <tr class="hover:bg-indigo-50/30 transition">
                            <td class="px-6 py-4 text-sm text-gray-600"><?= htmlspecialchars($r['tanggal'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-6 py-4">
                                <?php
                                $labelMap = ['hadir' => 'Hadir', 'izin' => 'Izin', 'sakit' => 'Sakit', 'alpha' => 'Alpha'];
                                $colorMap = [
                                    'hadir' => 'bg-green-100 text-green-700',
                                    'izin' => 'bg-yellow-100 text-yellow-700',
                                    'sakit' => 'bg-blue-100 text-blue-700',
                                    'alpha' => 'bg-red-100 text-red-700'
                                ];
                                $st = $r['status'] ?? 'hadir';
                                ?>
                                <span class="px-2.5 py-0.5 rounded-full text-xs font-medium <?= $colorMap[$st] ?? 'bg-gray-100 text-gray-700' ?>">
                                    <?= $labelMap[$st] ?? 'Hadir' ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-600"><?= htmlspecialchars($r['keterangan'] ?? '-', ENT_QUOTES, 'UTF-8') ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="3" class="px-6 py-10 text-center text-gray-500">Belum ada data absensi.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
