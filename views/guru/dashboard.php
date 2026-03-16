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

$mapelCountStmt = $pdo->prepare("SELECT COUNT(DISTINCT mg.mapel_id) FROM mapel_guru mg WHERE mg.guru_id = ?");
$mapelCountStmt->execute([$guru['id']]);
$totalMapel = (int) $mapelCountStmt->fetchColumn();

$ujianCountStmt = $pdo->prepare("SELECT COUNT(*) FROM ujian u
    JOIN mapel m ON u.mapel_id = m.id
    JOIN mapel_guru mg ON mg.mapel_id = m.id
    WHERE mg.guru_id = ?");
$ujianCountStmt->execute([$guru['id']]);
$totalUjian = (int) $ujianCountStmt->fetchColumn();

$publishedStmt = $pdo->prepare("SELECT COUNT(*) FROM ujian u
    JOIN mapel m ON u.mapel_id = m.id
    JOIN mapel_guru mg ON mg.mapel_id = m.id
    WHERE mg.guru_id = ? AND u.status = 'published'");
$publishedStmt->execute([$guru['id']]);
$totalPublished = (int) $publishedStmt->fetchColumn();

$jadwalStmt = $pdo->prepare("SELECT COUNT(*) FROM jadwal_pelajaran WHERE guru_id = ?");
$jadwalStmt->execute([$guru['id']]);
$totalJadwal = (int) $jadwalStmt->fetchColumn();

$ujianListStmt = $pdo->prepare("SELECT u.id, u.judul, u.status, u.created_at, m.nama_mapel
    FROM ujian u
    JOIN mapel m ON u.mapel_id = m.id
    JOIN mapel_guru mg ON mg.mapel_id = m.id
    WHERE mg.guru_id = ?
    ORDER BY u.created_at DESC
    LIMIT 5");
$ujianListStmt->execute([$guru['id']]);
$ujianList = $ujianListStmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="mb-8">
    <h1 class="text-2xl font-bold text-gray-800">Dashboard Guru</h1>
    <p class="text-gray-500">Selamat datang, <span class="font-semibold text-indigo-600">
            <?= htmlspecialchars($guru['nama_guru'], ENT_QUOTES, 'UTF-8') ?>
        </span>.</p>
</div>

<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-6 mb-8">
    <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100 flex items-center">
        <div class="p-3 rounded-full bg-blue-100 text-blue-600 mr-4">
            <i class="fas fa-book fa-2x"></i>
        </div>
        <div>
            <p class="text-sm text-gray-500 font-medium uppercase">Mapel Diampu</p>
            <h3 class="text-2xl font-bold text-gray-800"><?= $totalMapel ?></h3>
        </div>
    </div>
    <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100 flex items-center">
        <div class="p-3 rounded-full bg-indigo-100 text-indigo-600 mr-4">
            <i class="fas fa-file-lines fa-2x"></i>
        </div>
        <div>
            <p class="text-sm text-gray-500 font-medium uppercase">Total Ujian</p>
            <h3 class="text-2xl font-bold text-gray-800"><?= $totalUjian ?></h3>
        </div>
    </div>
    <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100 flex items-center">
        <div class="p-3 rounded-full bg-green-100 text-green-600 mr-4">
            <i class="fas fa-circle-check fa-2x"></i>
        </div>
        <div>
            <p class="text-sm text-gray-500 font-medium uppercase">Ujian Published</p>
            <h3 class="text-2xl font-bold text-gray-800"><?= $totalPublished ?></h3>
        </div>
    </div>
    <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100 flex items-center">
        <div class="p-3 rounded-full bg-purple-100 text-purple-600 mr-4">
            <i class="fas fa-calendar-check fa-2x"></i>
        </div>
        <div>
            <p class="text-sm text-gray-500 font-medium uppercase">Jadwal Mengajar</p>
            <h3 class="text-2xl font-bold text-gray-800"><?= $totalJadwal ?></h3>
        </div>
    </div>
    <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100 flex items-center cursor-pointer hover:shadow-md transition-shadow" onclick="window.location.href='index.php?page=video-classes'">
        <div class="p-3 rounded-full bg-red-100 text-red-600 mr-4">
            <i class="fas fa-video fa-2x"></i>
        </div>
        <div>
            <p class="text-sm text-gray-500 font-medium uppercase">Video Chat</p>
            <h3 class="text-lg font-bold text-gray-800">Kelas Online</h3>
        </div>
    </div>
</div>

<div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
    <div class="flex justify-between items-center mb-4">
        <h3 class="text-lg font-bold text-gray-800">Ujian Terbaru</h3>
        <a href="index.php?page=guru-ujian" class="text-sm text-indigo-600 hover:underline">Lihat Semua</a>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-left">
            <thead>
                <tr class="text-gray-400 text-sm uppercase tracking-wider border-b">
                    <th class="pb-3 font-medium">Judul</th>
                    <th class="pb-3 font-medium">Mapel</th>
                    <th class="pb-3 font-medium">Status</th>
                    <th class="pb-3 font-medium">Tanggal</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                <?php if ($ujianList): ?>
                    <?php foreach ($ujianList as $u): ?>
                        <tr>
                            <td class="py-4 text-sm font-semibold text-gray-700">
                                <?= htmlspecialchars($u['judul'], ENT_QUOTES, 'UTF-8') ?>
                            </td>
                            <td class="py-4 text-sm text-gray-500">
                                <?= htmlspecialchars($u['nama_mapel'], ENT_QUOTES, 'UTF-8') ?>
                            </td>
                            <td class="py-4">
                                <span
                                    class="px-2 py-1 text-xs font-bold rounded <?= $u['status'] === 'published' ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-700' ?>">
                                    <?= $u['status'] === 'published' ? 'Published' : 'Draft' ?>
                                </span>
                            </td>
                            <td class="py-4 text-sm text-gray-500">
                                <?= htmlspecialchars($u['created_at'], ENT_QUOTES, 'UTF-8') ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="4" class="py-6 text-center text-gray-500">Belum ada ujian.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
