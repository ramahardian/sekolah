<?php
if (!isset($pdo)) {
    exit;
}

$listKelas = $pdo->query("SELECT id, nama_kelas FROM kelas ORDER BY nama_kelas ASC")->fetchAll(PDO::FETCH_ASSOC);
$listMapel = $pdo->query("SELECT id, nama_mapel FROM mapel ORDER BY nama_mapel ASC")->fetchAll(PDO::FETCH_ASSOC);
$listGuru = $pdo->query("SELECT id, nama_guru FROM guru ORDER BY nama_guru ASC")->fetchAll(PDO::FETCH_ASSOC);

$search = isset($_GET['search']) ? input($_GET['search']) : '';
$kelasId = isset($_GET['kelas_id']) ? (int) $_GET['kelas_id'] : 0;
$mapelId = isset($_GET['mapel_id']) ? (int) $_GET['mapel_id'] : 0;
$guruId = isset($_GET['guru_id']) ? (int) $_GET['guru_id'] : 0;
$hari = isset($_GET['hari']) ? input($_GET['hari']) : '';

$query_sql = "SELECT j.*, k.nama_kelas, m.nama_mapel, g.nama_guru
              FROM jadwal_pelajaran j
              JOIN kelas k ON j.kelas_id = k.id
              JOIN mapel m ON j.mapel_id = m.id
              JOIN guru g ON j.guru_id = g.id";

$conditions = [];
$params = [];

if ($search) {
    $conditions[] = "(k.nama_kelas LIKE :search OR m.nama_mapel LIKE :search OR g.nama_guru LIKE :search)";
    $params['search'] = "%$search%";
}
if ($kelasId > 0) {
    $conditions[] = "j.kelas_id = :kelas_id";
    $params['kelas_id'] = $kelasId;
}
if ($mapelId > 0) {
    $conditions[] = "j.mapel_id = :mapel_id";
    $params['mapel_id'] = $mapelId;
}
if ($guruId > 0) {
    $conditions[] = "j.guru_id = :guru_id";
    $params['guru_id'] = $guruId;
}
if (in_array($hari, ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu'], true)) {
    $conditions[] = "j.hari = :hari";
    $params['hari'] = $hari;
}

if ($conditions) {
    $query_sql .= " WHERE " . implode(" AND ", $conditions);
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
        <p class="text-gray-500 text-sm">Kelola jadwal mengajar per kelas.</p>
    </div>
    <a href="index.php?page=jadwal-tambah"
        class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-lg flex items-center shadow-md transition">
        <i class="fas fa-plus mr-2 text-sm"></i> Tambah Jadwal
    </a>
</div>

<?php display_flash_message(); ?>

<div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
    <div class="p-4 border-b border-gray-100 bg-gray-50/50">
        <form action="index.php" method="GET" class="grid grid-cols-1 md:grid-cols-5 gap-3">
            <input type="hidden" name="page" value="jadwal">
            <div class="relative md:col-span-2">
                <input type="text" name="search" value="<?= htmlspecialchars($search, ENT_QUOTES, 'UTF-8') ?>"
                    placeholder="Cari kelas, mapel, atau guru..."
                    class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 outline-none transition text-sm">
                <span class="absolute left-3 top-2.5 text-gray-400">
                    <i class="fas fa-search"></i>
                </span>
            </div>
            <select name="kelas_id"
                class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 outline-none transition">
                <option value="">Semua Kelas</option>
                <?php foreach ($listKelas as $k): ?>
                    <option value="<?= (int) $k['id'] ?>" <?= $kelasId === (int) $k['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($k['nama_kelas'], ENT_QUOTES, 'UTF-8') ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <select name="mapel_id"
                class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 outline-none transition">
                <option value="">Semua Mapel</option>
                <?php foreach ($listMapel as $m): ?>
                    <option value="<?= (int) $m['id'] ?>" <?= $mapelId === (int) $m['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($m['nama_mapel'], ENT_QUOTES, 'UTF-8') ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <select name="guru_id"
                class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 outline-none transition">
                <option value="">Semua Guru</option>
                <?php foreach ($listGuru as $g): ?>
                    <option value="<?= (int) $g['id'] ?>" <?= $guruId === (int) $g['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($g['nama_guru'], ENT_QUOTES, 'UTF-8') ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <select name="hari"
                class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 outline-none transition">
                <option value="">Semua Hari</option>
                <?php foreach (['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu'] as $h): ?>
                    <option value="<?= $h ?>" <?= $hari === $h ? 'selected' : '' ?>><?= $h ?></option>
                <?php endforeach; ?>
            </select>
            <div class="md:col-span-5 flex items-center justify-between gap-3">
                <button type="submit"
                    class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-sm font-medium transition">
                    Terapkan
                </button>
                <div class="text-sm text-gray-500">
                    Total jadwal <span class="font-bold text-gray-800 mx-1"><?= count($jadwal) ?></span>
                </div>
            </div>
        </form>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="bg-gray-50 text-gray-600 text-xs uppercase tracking-widest border-b">
                    <th class="px-6 py-4 font-semibold">Hari</th>
                    <th class="px-6 py-4 font-semibold">Jam</th>
                    <th class="px-6 py-4 font-semibold">Kelas</th>
                    <th class="px-6 py-4 font-semibold">Mapel</th>
                    <th class="px-6 py-4 font-semibold">Guru</th>
                    <th class="px-6 py-4 font-semibold">Ruang</th>
                    <th class="px-6 py-4 font-semibold text-center">Aksi</th>
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
                                <?= htmlspecialchars($j['nama_kelas'], ENT_QUOTES, 'UTF-8') ?>
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
                            <td class="px-6 py-4 text-center">
                                <div class="flex justify-center gap-2">
                                    <a href="index.php?page=jadwal-edit&id=<?= (int) $j['id'] ?>"
                                        class="text-indigo-600 hover:bg-indigo-50 p-2 rounded-lg transition">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <button onclick="confirmDelete('index.php?page=jadwal-hapus&id=<?= (int) $j['id'] ?>')"
                                        class="text-red-600 hover:bg-red-50 p-2 rounded-lg transition">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="7" class="px-6 py-10 text-center text-gray-500">
                            Jadwal belum tersedia.
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
