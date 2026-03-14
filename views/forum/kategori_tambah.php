<?php
if (!isset($pdo)) {
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama = trim((string) ($_POST['nama'] ?? ''));
    $deskripsi = trim((string) ($_POST['deskripsi'] ?? ''));

    if ($nama === '') {
        $error = "Nama kategori wajib diisi.";
    } else {
        $stmt = $pdo->prepare("INSERT INTO forum_kategori (nama, deskripsi) VALUES (?, ?)");
        $stmt->execute([$nama, $deskripsi ?: null]);
        set_flash_message('success', 'Kategori berhasil ditambahkan.');
        header("Location: index.php?page=forum");
        exit;
    }
}
?>

<div class="max-w-100">
    <div class="mb-6">
        <a href="index.php?page=forum" class="text-indigo-600 hover:underline text-sm">← Kembali ke Forum</a>
        <h1 class="text-2xl font-bold text-gray-800 mt-2">Tambah Kategori Forum</h1>
    </div>

    <?php if ($error): ?>
        <div class="mb-4 p-3 bg-red-100 border-l-4 border-red-500 text-red-700 text-sm">
            <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
        </div>
    <?php endif; ?>

    <form method="POST" class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 space-y-5">
        <div>
            <label class="block text-sm font-semibold text-gray-700 mb-2">Nama Kategori</label>
            <input type="text" name="nama" required
                class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-indigo-500 outline-none transition"
                placeholder="Contoh: Pengumuman Sekolah">
        </div>
        <div>
            <label class="block text-sm font-semibold text-gray-700 mb-2">Deskripsi</label>
            <textarea name="deskripsi" rows="3"
                class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-indigo-500 outline-none transition"
                placeholder="Deskripsi singkat kategori..."></textarea>
        </div>
        <div class="flex items-center gap-3">
            <button type="submit"
                class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-3 px-6 rounded-xl shadow-lg transition">
                Simpan Kategori
            </button>
            <a href="index.php?page=forum" class="text-gray-600 hover:underline">Batal</a>
        </div>
    </form>
</div>