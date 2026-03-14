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

if (!$siswa || !$siswa['kelas_id']) {
    echo "<div class='bg-yellow-50 border border-yellow-200 text-yellow-700 p-4 rounded-lg'>Kelas siswa belum ditentukan.</div>";
    return;
}

$hari = isset($_GET['hari']) ? input($_GET['hari']) : '';

$query_sql = "SELECT j.*, m.nama_mapel, g.nama_guru
              FROM jadwal_pelajaran j
              JOIN mapel m ON j.mapel_id = m.id
              JOIN guru g ON j.guru_id = g.id
              WHERE j.kelas_id = :kelas_id";

$params = ['kelas_id' => $siswa['kelas_id']];

if (in_array($hari, ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu'], true)) {
    $query_sql .= " AND j.hari = :hari";
    $params['hari'] = $hari;
}

$query_sql .= " ORDER BY FIELD(j.hari,'Senin','Selasa','Rabu','Kamis','Jumat','Sabtu','Minggu'), j.jam_mulai ASC";

$stmt = $pdo->prepare($query_sql);
foreach ($params as $key => $value) {
    $stmt->bindValue(':' . $key, $value, PDO::PARAM_STR);
}
$stmt->execute();
$jadwal = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="flex flex-col md:flex-row md:items-center justify-between mb-6 gap-4">
    <div>
        <h1 class="text-2xl font-bold text-gray-800">Jadwal Pelajaran</h1>
        <p class="text-gray-500 text-sm">
            Kelas <?= htmlspecialchars($siswa['nama_kelas'] ?? '-', ENT_QUOTES, 'UTF-8') ?>
        </p>
    </div>
</div>

<?php display_flash_message(); ?>

<div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
    <div class="p-4 border-b border-gray-100 bg-gray-50/50">
        <form action="index.php" method="GET" class="flex flex-col md:flex-row gap-3">
            <input type="hidden" name="page" value="siswa-jadwal">
            <select name="hari"
                class="w-full md:w-52 px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 outline-none transition">
                <option value="">Semua Hari</option>
                <?php foreach (['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu'] as $h): ?>
                    <option value="<?= $h ?>" <?= $hari === $h ? 'selected' : '' ?>><?= $h ?></option>
                <?php endforeach; ?>
            </select>
            <button type="submit"
                class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-sm font-medium transition">
                Terapkan
            </button>
            <div class="flex items-center text-sm text-gray-500 md:ml-auto">
                Total jadwal <span class="font-bold text-gray-800 mx-1"><?= count($jadwal) ?></span>
            </div>
        </form>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="bg-gray-50 text-gray-600 text-xs uppercase tracking-widest border-b">
                    <th class="px-6 py-4 font-semibold">Hari</th>
                    <th class="px-6 py-4 font-semibold">Jam</th>
                    <th class="px-6 py-4 font-semibold">Mapel</th>
                    <th class="px-6 py-4 font-semibold">Guru</th>
                    <th class="px-6 py-4 font-semibold">Ruang</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                <?php if (count($jadwal) > 0): ?>
                    <?php foreach ($jadwal as $j): ?>
                        <tr class="hover:bg-indigo-50/30 transition">
                            <td class="px-6 py-4 text-sm font-semibold text-gray-700">
                                <?= htmlspecialchars($j['hari'], ENT_QUOTES, 'UTF-8') ?>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-600">
                                <?= htmlspecialchars(substr($j['jam_mulai'], 0, 5), ENT_QUOTES, 'UTF-8') ?> -
                                <?= htmlspecialchars(substr($j['jam_selesai'], 0, 5), ENT_QUOTES, 'UTF-8') ?>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-700">
                                <?= htmlspecialchars($j['nama_mapel'], ENT_QUOTES, 'UTF-8') ?>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-700">
                                <?= htmlspecialchars($j['nama_guru'], ENT_QUOTES, 'UTF-8') ?>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-600">
                                <?= htmlspecialchars($j['ruang'] ?: '-', ENT_QUOTES, 'UTF-8') ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="5" class="px-6 py-10 text-center text-gray-500">
                            Jadwal belum tersedia.
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
