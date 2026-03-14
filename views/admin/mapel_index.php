<?php
if (!isset($pdo)) {
    exit;
}

$search = isset($_GET['search']) ? input($_GET['search']) : '';
$guruId = isset($_GET['guru_id']) ? (int) $_GET['guru_id'] : 0;

$listGuru = $pdo->query("SELECT id, nama_guru FROM guru ORDER BY nama_guru ASC")->fetchAll(PDO::FETCH_ASSOC);

$query_sql = "SELECT m.id, m.kode_mapel, m.nama_mapel,
                     GROUP_CONCAT(DISTINCT g.nama_guru ORDER BY g.nama_guru SEPARATOR ', ') AS nama_guru
              FROM mapel m
              LEFT JOIN mapel_guru mg ON mg.mapel_id = m.id
              LEFT JOIN guru g ON mg.guru_id = g.id";

$conditions = [];
$params = [];

if ($search) {
    $conditions[] = "(m.kode_mapel LIKE :search OR m.nama_mapel LIKE :search)";
    $params['search'] = "%$search%";
}
if ($guruId > 0) {
    $conditions[] = "mg.guru_id = :guru_id";
    $params['guru_id'] = $guruId;
}

if ($conditions) {
    $query_sql .= " WHERE " . implode(" AND ", $conditions);
}

$query_sql .= " GROUP BY m.id, m.kode_mapel, m.nama_mapel
               ORDER BY m.nama_mapel ASC";

$stmt = $pdo->prepare($query_sql);
foreach ($params as $key => $value) {
    $stmt->bindValue(':' . $key, $value, PDO::PARAM_STR);
}
$stmt->execute();
$mapel = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="flex flex-col md:flex-row md:items-center justify-between mb-6 gap-4">
    <div>
        <h1 class="text-2xl font-bold text-gray-800">Mata Pelajaran</h1>
        <p class="text-gray-500 text-sm">Kelola data mata pelajaran dan guru pengampu.</p>
    </div>
    <a href="index.php?page=mapel-tambah"
        class="bg-orange-600 hover:bg-orange-700 text-white px-4 py-2 rounded-lg flex items-center shadow-md transition">
        <i class="fas fa-plus mr-2 text-sm"></i> Tambah Mapel
    </a>
</div>

<?php display_flash_message(); ?>

<div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
    <div class="p-4 border-b border-gray-100 flex flex-col md:flex-row justify-between gap-4 bg-gray-50/50">
        <form action="index.php" method="GET" class="flex flex-col md:flex-row gap-3 w-full">
            <input type="hidden" name="page" value="mapel">
            <div class="relative w-full md:w-72">
                <input type="text" name="search" value="<?= htmlspecialchars($search, ENT_QUOTES, 'UTF-8') ?>"
                    placeholder="Cari kode atau nama mapel..."
                    class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 outline-none transition text-sm">
                <span class="absolute left-3 top-2.5 text-gray-400">
                    <i class="fas fa-search"></i>
                </span>
            </div>
            <select name="guru_id"
                class="w-full md:w-64 px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-orange-500 outline-none transition">
                <option value="">Semua Guru</option>
                <?php foreach ($listGuru as $g): ?>
                    <option value="<?= (int) $g['id'] ?>" <?= $guruId === (int) $g['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($g['nama_guru'], ENT_QUOTES, 'UTF-8') ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <button type="submit"
                class="px-4 py-2 bg-orange-600 hover:bg-orange-700 text-white rounded-lg text-sm font-medium transition">
                Terapkan
            </button>
        </form>
        <div class="flex items-center text-sm text-gray-500">
            Total mapel <span class="font-bold text-gray-800 mx-1"><?= count($mapel) ?></span>
        </div>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="bg-gray-50 text-gray-600 text-xs uppercase tracking-widest border-b">
                    <th class="px-6 py-4 font-semibold">Kode</th>
                    <th class="px-6 py-4 font-semibold">Nama Mapel</th>
                    <th class="px-6 py-4 font-semibold">Guru Pengampu</th>
                    <th class="px-6 py-4 font-semibold text-center">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                <?php if (count($mapel) > 0): ?>
                    <?php foreach ($mapel as $m): ?>
                        <tr class="hover:bg-orange-50/30 transition">
                            <td class="px-6 py-4 text-sm font-mono text-gray-700">
                                <?= htmlspecialchars($m['kode_mapel'], ENT_QUOTES, 'UTF-8') ?>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-800 font-semibold">
                                <?= htmlspecialchars($m['nama_mapel'], ENT_QUOTES, 'UTF-8') ?>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-600">
                                <?= htmlspecialchars($m['nama_guru'] ?? '-', ENT_QUOTES, 'UTF-8') ?>
                            </td>
                            <td class="px-6 py-4 text-center">
                                <div class="flex justify-center gap-2">
                                    <a href="index.php?page=mapel-edit&id=<?= (int) $m['id'] ?>"
                                        class="text-orange-600 hover:bg-orange-50 p-2 rounded-lg transition">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <button onclick="confirmDelete('index.php?page=mapel-hapus&id=<?= (int) $m['id'] ?>')"
                                        class="text-red-600 hover:bg-red-50 p-2 rounded-lg transition">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="4" class="px-6 py-10 text-center text-gray-500">
                            Data mapel belum tersedia.
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
