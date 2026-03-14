<?php
if (!isset($pdo)) {
    exit;
}

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

$stmt = $pdo->prepare("SELECT * FROM jadwal_pelajaran WHERE id = ?");
$stmt->execute([$id]);
$jadwal = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$jadwal) {
    echo "<div class='bg-red-50 border border-red-200 text-red-700 p-4 rounded-lg'>Jadwal tidak ditemukan.</div>";
    return;
}

$listKelas = $pdo->query("SELECT id, nama_kelas FROM kelas ORDER BY nama_kelas ASC")->fetchAll(PDO::FETCH_ASSOC);
$listMapel = $pdo->query("SELECT id, nama_mapel FROM mapel ORDER BY nama_mapel ASC")->fetchAll(PDO::FETCH_ASSOC);
$listGuru = $pdo->query("SELECT id, nama_guru FROM guru ORDER BY nama_guru ASC")->fetchAll(PDO::FETCH_ASSOC);

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $kelas_id = (int) ($_POST['kelas_id'] ?? 0);
    $mapel_id = (int) ($_POST['mapel_id'] ?? 0);
    $guru_id = (int) ($_POST['guru_id'] ?? 0);
    $hari = input($_POST['hari'] ?? '');
    $jam_mulai = input($_POST['jam_mulai'] ?? '');
    $jam_selesai = input($_POST['jam_selesai'] ?? '');
    $ruang = input($_POST['ruang'] ?? '');

    $kelasIds = array_map('intval', array_column($listKelas, 'id'));
    $mapelIds = array_map('intval', array_column($listMapel, 'id'));
    $guruIds = array_map('intval', array_column($listGuru, 'id'));

    if (!in_array($kelas_id, $kelasIds, true)) {
        $error = "Kelas tidak valid.";
    } elseif (!in_array($mapel_id, $mapelIds, true)) {
        $error = "Mapel tidak valid.";
    } elseif (!in_array($guru_id, $guruIds, true)) {
        $error = "Guru tidak valid.";
    } elseif (!in_array($hari, ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu'], true)) {
        $error = "Hari tidak valid.";
    } elseif ($jam_mulai === '' || $jam_selesai === '') {
        $error = "Jam mulai dan selesai wajib diisi.";
    } elseif ($jam_selesai <= $jam_mulai) {
        $error = "Jam selesai harus lebih besar dari jam mulai.";
    } else {
        $update = $pdo->prepare("UPDATE jadwal_pelajaran 
                                 SET kelas_id = ?, mapel_id = ?, guru_id = ?, hari = ?, jam_mulai = ?, jam_selesai = ?, ruang = ?
                                 WHERE id = ?");
        $update->execute([
            $kelas_id,
            $mapel_id,
            $guru_id,
            $hari,
            $jam_mulai,
            $jam_selesai,
            $ruang ?: null,
            $id
        ]);

        set_flash_message('success', 'Jadwal berhasil diperbarui.');
        echo "<script>window.location.href='index.php?page=jadwal';</script>";
        exit;
    }
}
?>

<div class="mb-6">
    <a href="index.php?page=jadwal"
        class="text-indigo-600 hover:text-indigo-800 text-sm font-medium flex items-center mb-2">
        <i class="fas fa-arrow-left mr-2"></i> Kembali ke Jadwal
    </a>
    <h1 class="text-2xl font-bold text-gray-800">Edit Jadwal Pelajaran</h1>
</div>

<?php if ($error): ?>
    <div class="mb-4 p-3 bg-red-100 border-l-4 border-red-500 text-red-700 text-sm">
        <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
    </div>
<?php endif; ?>

<form action="" method="POST" class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
    <div class="p-8 space-y-6">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">Kelas</label>
                <select name="kelas_id" required
                    class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:bg-white outline-none transition">
                    <option value="">Pilih kelas</option>
                    <?php foreach ($listKelas as $k): ?>
                        <option value="<?= (int) $k['id'] ?>" <?= (int) $jadwal['kelas_id'] === (int) $k['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($k['nama_kelas'], ENT_QUOTES, 'UTF-8') ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">Mapel</label>
                <select name="mapel_id" required
                    class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:bg-white outline-none transition">
                    <option value="">Pilih mapel</option>
                    <?php foreach ($listMapel as $m): ?>
                        <option value="<?= (int) $m['id'] ?>" <?= (int) $jadwal['mapel_id'] === (int) $m['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($m['nama_mapel'], ENT_QUOTES, 'UTF-8') ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">Guru</label>
                <select name="guru_id" required
                    class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:bg-white outline-none transition">
                    <option value="">Pilih guru</option>
                    <?php foreach ($listGuru as $g): ?>
                        <option value="<?= (int) $g['id'] ?>" <?= (int) $jadwal['guru_id'] === (int) $g['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($g['nama_guru'], ENT_QUOTES, 'UTF-8') ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">Hari</label>
                <select name="hari" required
                    class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:bg-white outline-none transition">
                    <?php foreach (['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu'] as $h): ?>
                        <option value="<?= $h ?>" <?= $jadwal['hari'] === $h ? 'selected' : '' ?>><?= $h ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">Jam Mulai</label>
                <input type="time" name="jam_mulai" required value="<?= htmlspecialchars($jadwal['jam_mulai'], ENT_QUOTES, 'UTF-8') ?>"
                    class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:bg-white outline-none transition">
            </div>
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">Jam Selesai</label>
                <input type="time" name="jam_selesai" required value="<?= htmlspecialchars($jadwal['jam_selesai'], ENT_QUOTES, 'UTF-8') ?>"
                    class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:bg-white outline-none transition">
            </div>
        </div>

        <div>
            <label class="block text-sm font-semibold text-gray-700 mb-2">Ruang (Opsional)</label>
            <input type="text" name="ruang" value="<?= htmlspecialchars((string) $jadwal['ruang'], ENT_QUOTES, 'UTF-8') ?>"
                class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:bg-white outline-none transition">
        </div>
    </div>

    <div class="bg-gray-50 p-6 flex justify-end gap-3 border-t border-gray-100">
        <button type="submit"
            class="bg-indigo-600 hover:bg-indigo-700 text-white px-8 py-2.5 rounded-xl font-bold shadow-lg transition-all transform hover:-translate-y-1">
            <i class="fas fa-save mr-2"></i> Simpan Perubahan
        </button>
    </div>
</form>
