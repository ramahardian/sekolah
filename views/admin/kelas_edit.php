<?php
if (!isset($pdo)) {
    exit;
}

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
if ($id <= 0) {
    echo "<div class='bg-red-50 border border-red-200 text-red-700 p-4 rounded-lg'>ID kelas tidak valid.</div>";
    return;
}

$stmt = $pdo->prepare("SELECT * FROM kelas WHERE id = ?");
$stmt->execute([$id]);
$kelas = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$kelas) {
    echo "<div class='bg-red-50 border border-red-200 text-red-700 p-4 rounded-lg'>Kelas tidak ditemukan.</div>";
    return;
}

$stmt_guru = $pdo->query("SELECT id, nama_guru FROM guru ORDER BY nama_guru ASC");
$list_guru = $stmt_guru->fetchAll(PDO::FETCH_ASSOC);

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama_kelas = input($_POST['nama_kelas'] ?? '');
    $tingkat = input($_POST['tingkat'] ?? '');
    $wali_id = !empty($_POST['wali_id']) ? (int) $_POST['wali_id'] : null;

    if ($nama_kelas === '') {
        $error = "Nama kelas wajib diisi.";
    } elseif (!in_array($tingkat, ['10', '11', '12'], true)) {
        $error = "Tingkat kelas tidak valid.";
    } else {
        try {
            $stmt = $pdo->prepare("UPDATE kelas SET nama_kelas = ?, tingkat = ?, wali_id = ? WHERE id = ?");
            $stmt->execute([$nama_kelas, $tingkat, $wali_id, $id]);

            set_flash_message('success', "Kelas $nama_kelas berhasil diperbarui!");
            echo "<script>window.location.href='index.php?page=kelas';</script>";
            exit;
        } catch (PDOException $e) {
            $error = "Gagal memperbarui kelas: " . $e->getMessage();
        }
    }
}
?>

<div class="mb-6">
    <a href="index.php?page=kelas"
        class="text-purple-600 hover:text-purple-800 text-sm font-medium flex items-center mb-2">
        <i class="fas fa-arrow-left mr-2"></i> Kembali ke Daftar Kelas
    </a>
    <h1 class="text-2xl font-bold text-gray-800">Edit Kelas</h1>
</div>

<?php if ($error): ?>
    <div class="mb-4 p-3 bg-red-100 border-l-4 border-red-500 text-red-700 text-sm">
        <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
    </div>
<?php endif; ?>

<div class="max-w-100">
    <form action="" method="POST" class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="p-8 space-y-6">
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">Nama Kelas</label>
                <div class="relative">
                    <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-gray-400">
                        <i class="fas fa-door-open"></i>
                    </span>
                    <input type="text" name="nama_kelas" required
                        value="<?= htmlspecialchars($kelas['nama_kelas'], ENT_QUOTES, 'UTF-8') ?>"
                        class="w-full pl-10 pr-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-purple-500 focus:bg-white outline-none transition">
                </div>
            </div>

            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">Tingkat</label>
                <select name="tingkat" required
                    class="w-full pl-4 pr-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-purple-500 focus:bg-white outline-none transition">
                    <option value="10" <?= $kelas['tingkat'] === '10' ? 'selected' : '' ?>>Kelas 10</option>
                    <option value="11" <?= $kelas['tingkat'] === '11' ? 'selected' : '' ?>>Kelas 11</option>
                    <option value="12" <?= $kelas['tingkat'] === '12' ? 'selected' : '' ?>>Kelas 12</option>
                </select>
            </div>

            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">Wali Kelas</label>
                <div class="relative">
                    <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-gray-400">
                        <i class="fas fa-user-tie"></i>
                    </span>
                    <select name="wali_id"
                        class="w-full pl-10 pr-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-purple-500 focus:bg-white outline-none transition appearance-none">
                        <option value="">-- Tanpa Wali Kelas (Opsional) --</option>
                        <?php foreach ($list_guru as $g): ?>
                            <option value="<?= (int) $g['id'] ?>" <?= (int) $kelas['wali_id'] === (int) $g['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($g['nama_guru'], ENT_QUOTES, 'UTF-8') ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none text-gray-400">
                        <i class="fas fa-chevron-down text-xs"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="bg-gray-50 p-6 flex justify-end gap-3 border-t border-gray-100">
            <a href="index.php?page=kelas"
                class="px-6 py-2.5 text-sm font-bold text-gray-500 hover:text-gray-700 transition">
                Batal
            </a>
            <button type="submit"
                class="bg-purple-600 hover:bg-purple-700 text-white px-8 py-2.5 rounded-xl font-bold shadow-lg shadow-purple-200 transition-all transform hover:-translate-y-1">
                <i class="fas fa-save mr-2"></i> Simpan Perubahan
            </button>
        </div>
    </form>
</div>