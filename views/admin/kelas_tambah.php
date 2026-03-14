<?php
if (!isset($pdo)) {
    exit;
}

// Ambil semua data guru untuk dipilih sebagai Wali Kelas
$stmt_guru = $pdo->query("SELECT id, nama_guru FROM guru ORDER BY nama_guru ASC");
$list_guru = $stmt_guru->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama_kelas = input($_POST['nama_kelas']);
    $wali_id = !empty($_POST['wali_id']) ? $_POST['wali_id'] : null;

    try {
        $stmt = $pdo->prepare("INSERT INTO kelas (nama_kelas, wali_id) VALUES (?, ?)");
        $stmt->execute([$nama_kelas, $wali_id]);

        set_flash_message('success', "Kelas $nama_kelas berhasil dibuat!");
        echo "<script>window.location.href='index.php?page=kelas';</script>";
        exit;
    } catch (PDOException $e) {
        set_flash_message('error', 'Gagal menambah kelas: ' . $e->getMessage());
    }
}
?>

<div class="mb-6">
    <a href="index.php?page=kelas"
        class="text-purple-600 hover:text-purple-800 text-sm font-medium flex items-center mb-2">
        <i class="fas fa-arrow-left mr-2"></i> Kembali ke Daftar Kelas
    </a>
    <h1 class="text-2xl font-bold text-gray-800">Tambah Kelas Baru</h1>
</div>

<div class="max-w-100">
    <form action="" method="POST" class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="p-8 space-y-6">
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">Nama Kelas</label>
                <div class="relative">
                    <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-gray-400">
                        <i class="fas fa-door-open"></i>
                    </span>
                    <input type="text" name="nama_kelas" required placeholder="Contoh: X RPL 1 atau XII IPA 2"
                        class="w-full pl-10 pr-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-purple-500 focus:bg-white outline-none transition">
                </div>
                <p class="text-xs text-gray-400 mt-2 italic">*Gunakan penamaan yang konsisten untuk mempermudah
                    pencarian.</p>
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
                            <option value="<?= $g['id'] ?>">
                                <?= $g['nama_guru'] ?>
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
            <button type="reset" class="px-6 py-2.5 text-sm font-bold text-gray-500 hover:text-gray-700 transition">
                Reset
            </button>
            <button type="submit"
                class="bg-purple-600 hover:bg-purple-700 text-white px-8 py-2.5 rounded-xl font-bold shadow-lg shadow-purple-200 transition-all transform hover:-translate-y-1">
                <i class="fas fa-save mr-2"></i> Simpan Kelas
            </button>
        </div>
    </form>

    <div class="mt-6 flex gap-4 p-4 bg-blue-50 rounded-xl border border-blue-100 text-blue-700">
        <i class="fas fa-info-circle mt-1"></i>
        <p class="text-sm italic">
            Setelah membuat kelas, Anda bisa pergi ke menu <b>Data Siswa</b> untuk memasukkan siswa ke dalam kelas ini.
        </p>
    </div>
</div>