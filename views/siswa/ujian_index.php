<?php
if (!isset($pdo)) {
    exit;
}

$userId = $_SESSION['user_id'] ?? 0;
$search = isset($_GET['search']) ? input($_GET['search']) : '';

$query_sql = "SELECT u.*, m.nama_mapel,
                     (SELECT GROUP_CONCAT(g.nama_guru ORDER BY g.nama_guru SEPARATOR ', ')
                      FROM mapel_guru mg
                      JOIN guru g ON mg.guru_id = g.id
                      WHERE mg.mapel_id = m.id) AS nama_guru
              FROM ujian u
              JOIN mapel m ON u.mapel_id = m.id
              WHERE u.status = 'published'";

if ($search) {
    $query_sql .= " AND (u.judul LIKE :search OR m.nama_mapel LIKE :search)";
}

$query_sql .= " ORDER BY u.created_at DESC";

$stmt = $pdo->prepare($query_sql);
if ($search) {
    $stmt->bindValue(':search', "%$search%", PDO::PARAM_STR);
}
$stmt->execute();
$ujian = $stmt->fetchAll(PDO::FETCH_ASSOC);

$attemptsByUjian = [];
if ($ujian) {
    $ids = array_column($ujian, 'id');
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $attemptStmt = $pdo->prepare("SELECT * FROM ujian_attempt WHERE siswa_user_id = ? AND ujian_id IN ($placeholders)");
    $attemptStmt->execute(array_merge([$userId], $ids));
    $attemptRows = $attemptStmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($attemptRows as $row) {
        $attemptsByUjian[$row['ujian_id']] = $row;
    }
}

$now = new DateTime();
?>

<div class="flex flex-col md:flex-row md:items-center justify-between mb-6 gap-4">
    <div>
        <h1 class="text-2xl font-bold text-gray-800">Ujian Online</h1>
        <p class="text-gray-500 text-sm">Kerjakan ujian sesuai jadwal.</p>
    </div>
</div>

<?php display_flash_message(); ?>

<div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
    <div class="p-4 border-b border-gray-100 flex flex-col md:flex-row justify-between gap-4 bg-gray-50/50">
        <form action="index.php" method="GET" class="relative w-full md:w-80">
            <input type="hidden" name="page" value="siswa-ujian">
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
                        <?php
                        $attempt = $attemptsByUjian[$u['id']] ?? null;
                        $canStart = true;
                        $statusText = 'Siap';
                        $statusClass = 'bg-green-100 text-green-700';

                        if ($u['mulai']) {
                            $mulai = new DateTime($u['mulai']);
                            if ($now < $mulai) {
                                $canStart = false;
                                $statusText = 'Belum Dibuka';
                                $statusClass = 'bg-yellow-100 text-yellow-700';
                            }
                        }
                        if ($u['selesai']) {
                            $selesai = new DateTime($u['selesai']);
                            if ($now > $selesai) {
                                $canStart = false;
                                $statusText = 'Berakhir';
                                $statusClass = 'bg-gray-100 text-gray-700';
                            }
                        }

                        if ($attempt) {
                            if ($attempt['status'] === 'submitted') {
                                $statusText = 'Selesai';
                                $statusClass = 'bg-blue-100 text-blue-700';
                            } else {
                                $statusText = 'Sedang Dikerjakan';
                                $statusClass = 'bg-indigo-100 text-indigo-700';
                            }
                        }
                        ?>
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
                                <div class="text-xs text-gray-400">
                                    <?= htmlspecialchars($u['nama_guru'] ?? '-', ENT_QUOTES, 'UTF-8') ?>
                                </div>
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
                                <span class="px-2.5 py-0.5 rounded-full text-xs font-medium <?= $statusClass ?>">
                                    <?= $statusText ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 text-center">
                                <?php if ($attempt && $attempt['status'] === 'submitted'): ?>
                                    <a href="index.php?page=siswa-ujian-hasil&attempt=<?= (int) $attempt['id'] ?>"
                                        class="px-3 py-2 rounded-lg bg-blue-600 text-white text-sm font-medium hover:bg-blue-700 transition">
                                        Lihat Hasil
                                    </a>
                                <?php elseif ($canStart): ?>
                                    <a href="index.php?page=siswa-ujian-kerjakan&id=<?= (int) $u['id'] ?>"
                                        class="px-3 py-2 rounded-lg bg-indigo-600 text-white text-sm font-medium hover:bg-indigo-700 transition">
                                        <?= $attempt ? 'Lanjutkan' : 'Mulai' ?>
                                    </a>
                                <?php else: ?>
                                    <span class="text-sm text-gray-400">Tidak tersedia</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="5" class="px-6 py-10 text-center text-gray-500">
                            Belum ada ujian tersedia.
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
