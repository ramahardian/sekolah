<?php
if (!isset($pdo)) {
    exit;
}

$kategoriId = isset($_GET['kategori_id']) ? (int) $_GET['kategori_id'] : 0;
$listKategori = $pdo->query("SELECT id, nama FROM forum_kategori ORDER BY nama ASC")->fetchAll(PDO::FETCH_ASSOC);

if ($kategoriId > 0) {
    $stmtKategori = $pdo->prepare("SELECT * FROM forum_kategori WHERE id = ?");
    $stmtKategori->execute([$kategoriId]);
    $kategori = $stmtKategori->fetch(PDO::FETCH_ASSOC);
    if (!$kategori) {
        $kategoriId = 0;
    }
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $kategoriId = (int) ($_POST['kategori_id'] ?? 0);
    $judul = trim((string) ($_POST['judul'] ?? ''));
    $konten = trim((string) ($_POST['konten'] ?? ''));

    $kategoriIds = array_map('intval', array_column($listKategori, 'id'));

    if (!in_array($kategoriId, $kategoriIds, true)) {
        $error = "Kategori tidak valid.";
    } elseif ($judul === '' || $konten === '') {
        $error = "Judul dan konten wajib diisi.";
    } else {
        $stmt = $pdo->prepare("INSERT INTO forum_thread (kategori_id, user_id, judul, konten, created_at, updated_at)
                               VALUES (?, ?, ?, ?, NOW(), NOW())");
        $stmt->execute([$kategoriId, $_SESSION['user_id'], $judul, $konten]);

        set_flash_message('success', 'Thread berhasil dibuat.');
        header("Location: index.php?page=forum-kategori&id=$kategoriId");
        exit;
    }
}
?>

<div class="max-w-100">
    <div class="mb-6">
        <a href="index.php?page=forum" class="text-indigo-600 hover:underline text-sm">← Kembali ke Forum</a>
        <h1 class="text-2xl font-bold text-gray-800 mt-2">Buat Thread Baru</h1>
        <p class="text-gray-500 text-sm">Mulai diskusi di kategori yang sesuai.</p>
    </div>

    <?php if ($error): ?>
        <div class="mb-4 p-3 bg-red-100 border-l-4 border-red-500 text-red-700 text-sm">
            <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
        </div>
    <?php endif; ?>

    <form method="POST" class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 space-y-5">
        <div>
            <label class="block text-sm font-semibold text-gray-700 mb-2">Kategori</label>
            <select name="kategori_id" required
                class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-indigo-500 outline-none transition">
                <option value="">Pilih kategori</option>
                <?php foreach ($listKategori as $k): ?>
                    <option value="<?= (int) $k['id'] ?>" <?= $kategoriId === (int) $k['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($k['nama'], ENT_QUOTES, 'UTF-8') ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label class="block text-sm font-semibold text-gray-700 mb-2">Judul</label>
            <input type="text" name="judul" required
                class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-indigo-500 outline-none transition"
                placeholder="Contoh: Info ujian minggu depan">
        </div>
        <div>
            <label class="block text-sm font-semibold text-gray-700 mb-2">Konten</label>
            <textarea name="konten" rows="5" required
                class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-indigo-500 outline-none transition"
                placeholder="Tulis isi thread..."></textarea>
        </div>

        <div class="flex items-center gap-3">
            <button type="submit"
                class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-3 px-6 rounded-xl shadow-lg transition">
                Simpan Thread
            </button>
            <a href="index.php?page=forum" class="text-gray-600 hover:underline">Batal</a>
        </div>
    </form>
</div>