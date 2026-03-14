<?php
if (!isset($pdo)) {
    exit;
}

$role = $_SESSION['role'] ?? '';
$userId = $_SESSION['user_id'] ?? 0;

if ($role === 'siswa') {
    $stmtSiswa = $pdo->prepare("SELECT s.id, s.nama_siswa, s.kelas_id, k.nama_kelas
                                FROM siswa s
                                LEFT JOIN kelas k ON s.kelas_id = k.id
                                WHERE s.user_id = ?");
    $stmtSiswa->execute([$userId]);
} else {
    $siswaId = isset($_GET['id']) ? (int) $_GET['id'] : 0;
    if ($siswaId <= 0) {
        echo "<div class='bg-red-50 border border-red-200 text-red-700 p-4 rounded-lg'>ID siswa tidak valid.</div>";
        return;
    }
    $stmtSiswa = $pdo->prepare("SELECT s.id, s.nama_siswa, s.kelas_id, k.nama_kelas
                                FROM siswa s
                                LEFT JOIN kelas k ON s.kelas_id = k.id
                                WHERE s.id = ?");
    $stmtSiswa->execute([$siswaId]);
}

$siswa = $stmtSiswa->fetch(PDO::FETCH_ASSOC);

if (!$siswa) {
    echo "<div class='bg-red-50 border border-red-200 text-red-700 p-4 rounded-lg'>Data siswa tidak ditemukan.</div>";
    return;
}

$semester = isset($_GET['semester']) ? input($_GET['semester']) : '';
$tahunAjaran = isset($_GET['tahun_ajaran']) ? input($_GET['tahun_ajaran']) : '';

$query = "SELECT n.*, m.nama_mapel
          FROM nilai n
          JOIN mapel m ON n.mapel_id = m.id
          WHERE n.siswa_id = :siswa_id";
$params = ['siswa_id' => $siswa['id']];

if (in_array($semester, ['1', '2'], true)) {
    $query .= " AND n.semester = :semester";
    $params['semester'] = $semester;
}
if ($tahunAjaran !== '') {
    $query .= " AND n.tahun_ajaran = :tahun_ajaran";
    $params['tahun_ajaran'] = $tahunAjaran;
}

$query .= " ORDER BY n.tahun_ajaran DESC, n.semester DESC, m.nama_mapel ASC";

$stmt = $pdo->prepare($query);
foreach ($params as $k => $v) {
    $stmt->bindValue(':' . $k, $v, PDO::PARAM_STR);
}
$stmt->execute();
$nilai = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="flex flex-col md:flex-row md:items-center justify-between mb-6 gap-4">
    <div>
        <h1 class="text-2xl font-bold text-gray-800">Nilai Siswa</h1>
        <p class="text-gray-500 text-sm">
            <?= htmlspecialchars($siswa['nama_siswa'], ENT_QUOTES, 'UTF-8') ?> ·
            <?= htmlspecialchars($siswa['nama_kelas'] ?? '-', ENT_QUOTES, 'UTF-8') ?>
        </p>
    </div>
    <?php if ($role !== 'siswa'): ?>
        <a href="index.php?page=profil-siswa&id=<?= (int) $siswa['id'] ?>" class="text-indigo-600 hover:underline">Lihat Profil</a>
    <?php endif; ?>
</div>

<div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
    <div class="p-4 border-b border-gray-100 flex flex-col md:flex-row justify-between gap-4 bg-gray-50/50">
        <form action="index.php" method="GET" class="flex flex-col md:flex-row gap-3 w-full">
            <input type="hidden" name="page" value="nilai-siswa">
            <?php if ($role !== 'siswa'): ?>
                <input type="hidden" name="id" value="<?= (int) $siswa['id'] ?>">
            <?php endif; ?>
            <select name="semester"
                class="w-full md:w-40 px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 outline-none transition">
                <option value="">Semua Semester</option>
                <option value="1" <?= $semester === '1' ? 'selected' : '' ?>>Semester 1</option>
                <option value="2" <?= $semester === '2' ? 'selected' : '' ?>>Semester 2</option>
            </select>
            <input type="text" name="tahun_ajaran" value="<?= htmlspecialchars($tahunAjaran, ENT_QUOTES, 'UTF-8') ?>"
                placeholder="Tahun ajaran (contoh 2024/2025)"
                class="w-full md:w-56 px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 outline-none transition">
            <button type="submit"
                class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-sm font-medium transition">
                Terapkan
            </button>
        </form>
        <div class="flex items-center text-sm text-gray-500">
            Total <span class="font-bold text-gray-800 mx-1"><?= count($nilai) ?></span> nilai
        </div>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="bg-gray-50 text-gray-600 text-xs uppercase tracking-widest border-b">
                    <th class="px-6 py-4 font-semibold">Mapel</th>
                    <th class="px-6 py-4 font-semibold">Semester</th>
                    <th class="px-6 py-4 font-semibold">Tahun Ajaran</th>
                    <th class="px-6 py-4 font-semibold">UTS</th>
                    <th class="px-6 py-4 font-semibold">UAS</th>
                    <th class="px-6 py-4 font-semibold">Tugas</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                <?php if ($nilai): ?>
                    <?php foreach ($nilai as $n): ?>
                        <tr class="hover:bg-indigo-50/30 transition">
                            <td class="px-6 py-4 text-sm font-semibold text-gray-800">
                                <?= htmlspecialchars($n['nama_mapel'], ENT_QUOTES, 'UTF-8') ?>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-600"><?= htmlspecialchars($n['semester'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-6 py-4 text-sm text-gray-600"><?= htmlspecialchars($n['tahun_ajaran'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-6 py-4 text-sm text-gray-600"><?= htmlspecialchars((string) $n['nilai_uts'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-6 py-4 text-sm text-gray-600"><?= htmlspecialchars((string) $n['nilai_uas'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-6 py-4 text-sm text-gray-600"><?= htmlspecialchars((string) $n['nilai_tugas'], ENT_QUOTES, 'UTF-8') ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6" class="px-6 py-10 text-center text-gray-500">Belum ada nilai.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
