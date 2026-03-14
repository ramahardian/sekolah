<?php
if (!isset($pdo)) {
    exit;
}

$role = $_SESSION['role'] ?? '';
$userId = $_SESSION['user_id'] ?? 0;

if ($role === 'siswa') {
    $stmt = $pdo->prepare("SELECT s.*, k.nama_kelas, k.tingkat, u.username
                           FROM siswa s
                           LEFT JOIN kelas k ON s.kelas_id = k.id
                           JOIN users u ON s.user_id = u.id
                           WHERE s.user_id = ?");
    $stmt->execute([$userId]);
} else {
    $siswaId = isset($_GET['id']) ? (int) $_GET['id'] : 0;
    if ($siswaId <= 0) {
        echo "<div class='bg-red-50 border border-red-200 text-red-700 p-4 rounded-lg'>ID siswa tidak valid.</div>";
        return;
    }
    $stmt = $pdo->prepare("SELECT s.*, k.nama_kelas, k.tingkat, u.username
                           FROM siswa s
                           LEFT JOIN kelas k ON s.kelas_id = k.id
                           JOIN users u ON s.user_id = u.id
                           WHERE s.id = ?");
    $stmt->execute([$siswaId]);
}

$siswa = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$siswa) {
    echo "<div class='bg-red-50 border border-red-200 text-red-700 p-4 rounded-lg'>Data siswa tidak ditemukan.</div>";
    return;
}
?>

<div class="mb-6">
    <h1 class="text-2xl font-bold text-gray-800">Profil Siswa</h1>
    <p class="text-gray-500 text-sm">Informasi biodata dan orang tua.</p>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
        <div class="flex items-center gap-4">
            <div class="w-14 h-14 rounded-full bg-indigo-100 text-indigo-600 flex items-center justify-center text-xl font-bold">
                <?= htmlspecialchars(get_initials($siswa['nama_siswa']), ENT_QUOTES, 'UTF-8') ?>
            </div>
            <div>
                <h2 class="text-lg font-bold text-gray-800">
                    <?= htmlspecialchars($siswa['nama_siswa'], ENT_QUOTES, 'UTF-8') ?>
                </h2>
                <p class="text-sm text-gray-500">NIS: <?= htmlspecialchars($siswa['nis'], ENT_QUOTES, 'UTF-8') ?></p>
            </div>
        </div>
        <div class="mt-4 text-sm text-gray-600 space-y-2">
            <div><span class="font-semibold">Username:</span> <?= htmlspecialchars($siswa['username'], ENT_QUOTES, 'UTF-8') ?></div>
            <div><span class="font-semibold">Kelas:</span>
                <?= htmlspecialchars(($siswa['tingkat'] ? 'Kelas ' . $siswa['tingkat'] . ' - ' : '') . ($siswa['nama_kelas'] ?? '-'), ENT_QUOTES, 'UTF-8') ?>
            </div>
            <div><span class="font-semibold">Status:</span> <?= htmlspecialchars(ucfirst($siswa['status_siswa']), ENT_QUOTES, 'UTF-8') ?></div>
        </div>
    </div>

    <div class="lg:col-span-2 bg-white rounded-xl shadow-sm border border-gray-100 p-6">
        <h3 class="text-lg font-bold text-gray-800 mb-4">Biodata</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm text-gray-700">
            <div><span class="font-semibold">Jenis Kelamin:</span> <?= htmlspecialchars($siswa['jenis_kelamin'], ENT_QUOTES, 'UTF-8') ?></div>
            <div><span class="font-semibold">Tempat Lahir:</span> <?= htmlspecialchars($siswa['tempat_lahir'] ?? '-', ENT_QUOTES, 'UTF-8') ?></div>
            <div><span class="font-semibold">Tanggal Lahir:</span> <?= htmlspecialchars($siswa['tanggal_lahir'] ?? '-', ENT_QUOTES, 'UTF-8') ?></div>
            <div><span class="font-semibold">No HP:</span> <?= htmlspecialchars($siswa['no_hp'] ?? '-', ENT_QUOTES, 'UTF-8') ?></div>
            <div class="md:col-span-2"><span class="font-semibold">Alamat:</span> <?= htmlspecialchars($siswa['alamat'] ?? '-', ENT_QUOTES, 'UTF-8') ?></div>
        </div>

        <h3 class="text-lg font-bold text-gray-800 mt-8 mb-4">Data Orang Tua</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm text-gray-700">
            <div><span class="font-semibold">Nama Ayah:</span> <?= htmlspecialchars($siswa['nama_ayah'] ?? '-', ENT_QUOTES, 'UTF-8') ?></div>
            <div><span class="font-semibold">Nama Ibu:</span> <?= htmlspecialchars($siswa['nama_ibu'] ?? '-', ENT_QUOTES, 'UTF-8') ?></div>
            <div class="md:col-span-2"><span class="font-semibold">No HP Orang Tua:</span> <?= htmlspecialchars($siswa['no_hp_orangtua'] ?? '-', ENT_QUOTES, 'UTF-8') ?></div>
        </div>
    </div>
</div>

<div class="mt-6 bg-white rounded-xl shadow-sm border border-gray-100 p-6">
    <div class="flex items-center justify-between">
        <div>
            <h3 class="text-lg font-bold text-gray-800">Absensi</h3>
            <p class="text-sm text-gray-500">Ringkasan bulan ini.</p>
        </div>
        <a href="index.php?page=absensi<?= ($role !== 'siswa') ? '&id=' . (int) $siswa['id'] : '' ?>"
            class="text-indigo-600 hover:underline text-sm">Lihat Detail</a>
    </div>
    <?php
    $bulan = date('Y-m');
    $absStmt = $pdo->prepare("SELECT status, COUNT(*) as total
                              FROM absensi
                              WHERE siswa_id = ? AND DATE_FORMAT(tanggal, '%Y-%m') = ?
                              GROUP BY status");
    $absStmt->execute([(int) $siswa['id'], $bulan]);
    $rows = $absStmt->fetchAll(PDO::FETCH_ASSOC);
    $summary = ['hadir' => 0, 'izin' => 0, 'sakit' => 0, 'alpha' => 0];
    foreach ($rows as $r) {
        $summary[$r['status']] = (int) $r['total'];
    }
    ?>
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mt-4">
        <div class="p-4 rounded-lg bg-green-50 text-green-700 text-center">
            <div class="text-xs uppercase">Hadir</div>
            <div class="text-xl font-bold"><?= $summary['hadir'] ?></div>
        </div>
        <div class="p-4 rounded-lg bg-yellow-50 text-yellow-700 text-center">
            <div class="text-xs uppercase">Izin</div>
            <div class="text-xl font-bold"><?= $summary['izin'] ?></div>
        </div>
        <div class="p-4 rounded-lg bg-blue-50 text-blue-700 text-center">
            <div class="text-xs uppercase">Sakit</div>
            <div class="text-xl font-bold"><?= $summary['sakit'] ?></div>
        </div>
        <div class="p-4 rounded-lg bg-red-50 text-red-700 text-center">
            <div class="text-xs uppercase">Alpha</div>
            <div class="text-xl font-bold"><?= $summary['alpha'] ?></div>
        </div>
    </div>
</div>
