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

$search = isset($_GET['search']) ? input($_GET['search']) : '';
$query_sql = "SELECT u.*, m.nama_mapel
              FROM ujian u
              JOIN mapel m ON u.mapel_id = m.id
              JOIN mapel_guru mg ON mg.mapel_id = m.id
              WHERE mg.guru_id = :guru_id";

if ($search) {
    $query_sql .= " AND (u.judul LIKE :search OR m.nama_mapel LIKE :search)";
}

$query_sql .= " ORDER BY u.created_at DESC";

$stmt = $pdo->prepare($query_sql);
$stmt->bindValue(':guru_id', $guru['id'], PDO::PARAM_INT);
if ($search) {
    $stmt->bindValue(':search', "%$search%", PDO::PARAM_STR);
}
$stmt->execute();
$ujian = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="flex flex-col md:flex-row md:items-center justify-between mb-6 gap-4">
    <div>
        <h1 class="text-2xl font-bold text-gray-800">Ujian Online</h1>
        <p class="text-gray-500 text-sm">Kelola ujian untuk mata pelajaran yang Anda ampu.</p>
    </div>
    <a href="index.php?page=guru-ujian-tambah"
        class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-lg flex items-center shadow-md transition">
        <i class="fas fa-plus mr-2 text-sm"></i> Buat Ujian
    </a>
</div>

<?php display_flash_message(); ?>

<div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
    <div class="p-4 border-b border-gray-100 flex flex-col md:flex-row justify-between gap-4 bg-gray-50/50">
        <form action="index.php" method="GET" class="relative w-full md:w-80">
            <input type="hidden" name="page" value="guru-ujian">
            <input type="text" name="search" value="<?= htmlspecialchars($search, ENT_QUOTES, 'UTF-8') ?>"
                placeholder="Cari judul atau mapel..."
                class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 outline-none transition text-sm">
            <span class="absolute left-3 top-2.5 text-gray-400">
                <i class="fas fa-search"></i>
            </span>
        </form>
        <div class="flex items-center text-sm text-gray-500">
            Total ujian <span class="font-bold text-gray-800 mx-1"><?= count($ujian) ?></span>
        </div>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="bg-gray-50 text-gray-600 text-xs uppercase tracking-widest border-b">
                    <th class="px-6 py-4 font-semibold">Judul</th>
                    <th class="px-6 py-4 font-semibold">Mapel</th>
                    <th class="px-6 py-4 font-semibold">Jadwal</th>
                    <th class="px-6 py-4 font-semibold">Status</th>
                    <th class="px-6 py-4 font-semibold text-center">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                <?php if (count($ujian) > 0): ?>
                    <?php foreach ($ujian as $u): ?>
                        <tr class="hover:bg-indigo-50/30 transition">
                            <td class="px-6 py-4">
                                <div class="text-sm font-bold text-gray-800">
                                    <?= htmlspecialchars($u['judul'], ENT_QUOTES, 'UTF-8') ?>
                                </div>
                                <div class="text-xs text-gray-500">
                                    Durasi: <?= $u['durasi_menit'] ? (int) $u['durasi_menit'] . ' menit' : '-' ?>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-600">
                                <?= htmlspecialchars($u['nama_mapel'], ENT_QUOTES, 'UTF-8') ?>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-600">
                                <?php if ($u['mulai'] && $u['selesai']): ?>
                                    <div><?= htmlspecialchars($u['mulai'], ENT_QUOTES, 'UTF-8') ?></div>
                                    <div class="text-xs text-gray-400">s.d <?= htmlspecialchars($u['selesai'], ENT_QUOTES, 'UTF-8') ?></div>
                                <?php else: ?>
                                    <span class="text-xs text-gray-400 italic">Tanpa jadwal</span>
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-4">
                                <span class="px-2.5 py-0.5 rounded-full text-xs font-medium
                                    <?= $u['status'] === 'published' ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-700' ?>">
                                    <?= $u['status'] === 'published' ? 'Published' : 'Draft' ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 text-center">
                                <div class="flex justify-center gap-2">
                                    <a href="index.php?page=guru-ujian-edit&id=<?= (int) $u['id'] ?>"
                                        class="p-2 text-blue-600 hover:bg-blue-50 rounded-lg transition" title="Edit">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <a href="index.php?page=guru-ujian-soal&id=<?= (int) $u['id'] ?>"
                                        class="p-2 text-indigo-600 hover:bg-indigo-50 rounded-lg transition" title="Soal">
                                        <i class="fas fa-list"></i>
                                    </a>
                                    <a href="index.php?page=guru-ujian-hasil&id=<?= (int) $u['id'] ?>"
                                        class="p-2 text-emerald-600 hover:bg-emerald-50 rounded-lg transition" title="Hasil">
                                        <i class="fas fa-chart-column"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="5" class="px-6 py-10 text-center text-gray-500">
                            Belum ada ujian. Silakan buat ujian baru.
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
