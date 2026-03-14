<?php
if (!isset($pdo)) {
    exit;
}

$tanggal = isset($_GET['tanggal']) ? input($_GET['tanggal']) : date('Y-m-d');
$kelasId = isset($_GET['kelas_id']) ? (int) $_GET['kelas_id'] : 0;
$status = isset($_GET['status']) ? input($_GET['status']) : '';
$search = isset($_GET['search']) ? input($_GET['search']) : '';

$listKelas = $pdo->query("SELECT id, nama_kelas FROM kelas ORDER BY nama_kelas ASC")->fetchAll(PDO::FETCH_ASSOC);

$perPage = 5;
$currentPage = isset($_GET['p']) ? max(1, (int) $_GET['p']) : 1;
$offset = ($currentPage - 1) * $perPage;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tanggalPost = input($_POST['tanggal'] ?? date('Y-m-d'));
    $records = $_POST['absensi'] ?? [];

    if ($records) {
        $pdo->beginTransaction();
        $stmt = $pdo->prepare("INSERT INTO absensi (siswa_id, tanggal, status, keterangan, created_by)
                               VALUES (?, ?, ?, ?, ?)
                               ON DUPLICATE KEY UPDATE status = VALUES(status), keterangan = VALUES(keterangan)");

        foreach ($records as $siswaId => $row) {
            $siswaId = (int) $siswaId;
            $st = $row['status'] ?? 'hadir';
            if (!in_array($st, ['hadir', 'izin', 'sakit', 'alpha'], true)) {
                $st = 'hadir';
            }
            $ket = trim((string) ($row['keterangan'] ?? ''));
            $stmt->execute([$siswaId, $tanggalPost, $st, $ket ?: null, $_SESSION['user_id']]);
        }
        $pdo->commit();
        set_flash_message('success', 'Absensi berhasil disimpan.');
        header("Location: index.php?page=absensi&tanggal=$tanggalPost&kelas_id=$kelasId");
        exit;
    }
}

$query = "SELECT s.id, s.nama_siswa, s.nis, k.nama_kelas, a.status, a.keterangan
          FROM siswa s
          LEFT JOIN kelas k ON s.kelas_id = k.id
          LEFT JOIN absensi a ON a.siswa_id = s.id AND a.tanggal = :tanggal";
$countQuery = "SELECT COUNT(*)
               FROM siswa s
               LEFT JOIN kelas k ON s.kelas_id = k.id
               LEFT JOIN absensi a ON a.siswa_id = s.id AND a.tanggal = :tanggal";
$params = ['tanggal' => $tanggal];
$conditions = [];

if ($kelasId > 0) {
    $conditions[] = "s.kelas_id = :kelas_id";
    $params['kelas_id'] = $kelasId;
}
if ($search) {
    $conditions[] = "(s.nama_siswa LIKE :search OR s.nis LIKE :search)";
    $params['search'] = "%$search%";
}
if ($status && in_array($status, ['hadir', 'izin', 'sakit', 'alpha'], true)) {
    $conditions[] = "a.status = :status";
    $params['status'] = $status;
}

if ($conditions) {
    $query .= " WHERE " . implode(" AND ", $conditions);
    $countQuery .= " WHERE " . implode(" AND ", $conditions);
}

$query .= " ORDER BY s.nama_siswa ASC LIMIT :limit OFFSET :offset";

$countStmt = $pdo->prepare($countQuery);
foreach ($params as $k => $v) {
    $countStmt->bindValue(':' . $k, $v, PDO::PARAM_STR);
}
$countStmt->execute();
$totalRows = (int) $countStmt->fetchColumn();
$totalPages = max(1, (int) ceil($totalRows / $perPage));

$stmt = $pdo->prepare($query);
foreach ($params as $k => $v) {
    $stmt->bindValue(':' . $k, $v, PDO::PARAM_STR);
}
$stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="flex flex-col md:flex-row md:items-center justify-between mb-6 gap-4">
    <div>
        <h1 class="text-2xl font-bold text-gray-800">Absensi Siswa</h1>
        <p class="text-gray-500 text-sm">Input dan lihat absensi harian.</p>
    </div>
</div>

<?php display_flash_message(); ?>

<div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden mb-6">
    <form action="index.php" method="GET"
        class="p-4 border-b border-gray-100 flex flex-col md:flex-row gap-3 bg-gray-50/50">
        <input type="hidden" name="page" value="absensi">
        <input type="date" name="tanggal" value="<?= htmlspecialchars($tanggal, ENT_QUOTES, 'UTF-8') ?>"
            class="w-full md:w-48 px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 outline-none transition">
        <select name="kelas_id"
            class="w-full md:w-52 px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 outline-none transition">
            <option value="">Semua Kelas</option>
            <?php foreach ($listKelas as $k): ?>
                <option value="<?= (int) $k['id'] ?>" <?= $kelasId === (int) $k['id'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($k['nama_kelas'], ENT_QUOTES, 'UTF-8') ?>
                </option>
            <?php endforeach; ?>
        </select>
        <select name="status"
            class="w-full md:w-40 px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 outline-none transition">
            <option value="">Semua Status</option>
            <option value="hadir" <?= $status === 'hadir' ? 'selected' : '' ?>>Hadir</option>
            <option value="izin" <?= $status === 'izin' ? 'selected' : '' ?>>Izin</option>
            <option value="sakit" <?= $status === 'sakit' ? 'selected' : '' ?>>Sakit</option>
            <option value="alpha" <?= $status === 'alpha' ? 'selected' : '' ?>>Alpha</option>
        </select>
        <input type="text" name="search" value="<?= htmlspecialchars($search, ENT_QUOTES, 'UTF-8') ?>"
            placeholder="Cari nama/NIS..."
            class="w-full md:w-64 px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 outline-none transition">
        <button type="submit"
            class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-sm font-medium transition">
            Terapkan
        </button>
    </form>

    <form method="POST">
        <input type="hidden" name="tanggal" value="<?= htmlspecialchars($tanggal, ENT_QUOTES, 'UTF-8') ?>">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-gray-50 text-gray-600 text-xs uppercase tracking-widest border-b">
                        <th class="px-6 py-4 font-semibold">Siswa</th>
                        <th class="px-6 py-4 font-semibold">Kelas</th>
                        <th class="px-6 py-4 font-semibold">Status</th>
                        <th class="px-6 py-4 font-semibold">Keterangan</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <?php if ($rows): ?>
                        <?php foreach ($rows as $r): ?>
                            <tr class="hover:bg-indigo-50/30 transition">
                                <td class="px-6 py-4">
                                    <div class="text-sm font-semibold text-gray-800">
                                        <?= htmlspecialchars($r['nama_siswa'], ENT_QUOTES, 'UTF-8') ?>
                                    </div>
                                    <div class="text-xs text-gray-500">NIS:
                                        <?= htmlspecialchars($r['nis'], ENT_QUOTES, 'UTF-8') ?>
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-600">
                                    <?= htmlspecialchars($r['nama_kelas'] ?? '-', ENT_QUOTES, 'UTF-8') ?>
                                </td>
                                <td class="px-6 py-4">
                                    <select name="absensi[<?= (int) $r['id'] ?>][status]"
                                        class="px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 outline-none transition">
                                        <?php
                                        $current = $r['status'] ?? 'hadir';
                                        foreach (['hadir' => 'Hadir', 'izin' => 'Izin', 'sakit' => 'Sakit', 'alpha' => 'Alpha'] as $val => $label):
                                            ?>
                                            <option value="<?= $val ?>" <?= $current === $val ? 'selected' : '' ?>><?= $label ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </td>
                                <td class="px-6 py-4">
                                    <input type="text" name="absensi[<?= (int) $r['id'] ?>][keterangan]"
                                        value="<?= htmlspecialchars($r['keterangan'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 outline-none transition"
                                        placeholder="Opsional">
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="4" class="px-6 py-10 text-center text-gray-500">Data siswa belum tersedia.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <div class="p-4 border-t border-gray-100 flex justify-end">
            <button type="submit"
                class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-sm font-medium transition">
                Simpan Absensi
            </button>
        </div>
    </form>
</div>

<?php if ($totalPages > 1): ?>
    <div class="mt-6 flex flex-col md:flex-row items-center justify-between gap-3 text-sm">
        <div class="text-gray-500">
            Halaman <span class="font-semibold text-gray-800"><?= $currentPage ?></span> dari
            <span class="font-semibold text-gray-800"><?= $totalPages ?></span>
        </div>
        <div class="flex items-center gap-2">
            <?php
            $baseParams = [
                'page' => 'absensi',
                'tanggal' => $tanggal
            ];
            if ($kelasId > 0) {
                $baseParams['kelas_id'] = $kelasId;
            }
            if ($status) {
                $baseParams['status'] = $status;
            }
            if ($search) {
                $baseParams['search'] = $search;
            }

            $prevPage = max(1, $currentPage - 1);
            $nextPage = min($totalPages, $currentPage + 1);

            $prevHref = 'index.php?' . http_build_query($baseParams + ['p' => $prevPage]);
            $nextHref = 'index.php?' . http_build_query($baseParams + ['p' => $nextPage]);
            ?>
            <a href="<?= $prevHref ?>"
                class="px-3 py-2 rounded-lg border text-gray-600 hover:bg-gray-50 transition <?= $currentPage == 1 ? 'pointer-events-none opacity-50' : '' ?>">
                Prev
            </a>
            <a href="<?= $nextHref ?>"
                class="px-3 py-2 rounded-lg border text-gray-600 hover:bg-gray-50 transition <?= $currentPage == $totalPages ? 'pointer-events-none opacity-50' : '' ?>">
                Next
            </a>
        </div>
    </div>
<?php endif; ?>