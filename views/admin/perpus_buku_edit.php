<?php
if (!isset($pdo)) {
    exit;
}

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$stmt = $pdo->prepare("SELECT * FROM perpustakaan_buku WHERE id = ?");
$stmt->execute([$id]);
$buku = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$buku) {
    echo "<div class='bg-red-50 border border-red-200 text-red-700 p-4 rounded-lg'>Buku tidak ditemukan.</div>";
    return;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $kode = input($_POST['kode_buku'] ?? '');
    $judul = input($_POST['judul'] ?? '');
    $penulis = input($_POST['penulis'] ?? '');
    $penerbit = input($_POST['penerbit'] ?? '');
    $tahun = (int) ($_POST['tahun'] ?? 0);
    $stok = (int) ($_POST['stok_total'] ?? 0);
    $lokasi = input($_POST['lokasi'] ?? '');

    if ($kode === '' || $judul === '') {
        $error = "Kode dan judul wajib diisi.";
    } elseif ($stok < 0) {
        $error = "Stok tidak valid.";
    } else {
        try {
            $used = (int) $buku['stok_total'] - (int) $buku['stok_tersedia'];
            $stokTersedia = max(0, $stok - $used);

            $stmt = $pdo->prepare("UPDATE perpustakaan_buku 
                SET kode_buku = ?, judul = ?, penulis = ?, penerbit = ?, tahun = ?, stok_total = ?, stok_tersedia = ?, lokasi = ?
                WHERE id = ?");
            $stmt->execute([
                $kode,
                $judul,
                $penulis ?: null,
                $penerbit ?: null,
                $tahun ?: null,
                $stok,
                $stokTersedia,
                $lokasi ?: null,
                $id
            ]);

            set_flash_message('success', 'Buku berhasil diperbarui.');
            echo "<script>window.location.href='index.php?page=perpus-buku';</script>";
            exit;
        } catch (PDOException $e) {
            $error = "Gagal memperbarui buku: " . $e->getMessage();
        }
    }
}
?>

<div class="mb-6">
    <a href="index.php?page=perpus-buku"
        class="text-emerald-600 hover:text-emerald-800 text-sm font-medium flex items-center mb-2">
        <i class="fas fa-arrow-left mr-2"></i> Kembali ke Perpustakaan
    </a>
    <h1 class="text-2xl font-bold text-gray-800">Edit Buku</h1>
</div>

<?php if ($error): ?>
    <div class="mb-4 p-3 bg-red-100 border-l-4 border-red-500 text-red-700 text-sm">
        <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
    </div>
<?php endif; ?>

<form action="" method="POST" class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
    <div class="p-8 space-y-6">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">Kode Buku</label>
                <input type="text" name="kode_buku" required
                    value="<?= htmlspecialchars($buku['kode_buku'], ENT_QUOTES, 'UTF-8') ?>"
                    class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-emerald-500 outline-none transition">
            </div>
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">Judul</label>
                <input type="text" name="judul" required
                    value="<?= htmlspecialchars($buku['judul'], ENT_QUOTES, 'UTF-8') ?>"
                    class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-emerald-500 outline-none transition">
            </div>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">Penulis</label>
                <input type="text" name="penulis"
                    value="<?= htmlspecialchars((string) $buku['penulis'], ENT_QUOTES, 'UTF-8') ?>"
                    class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-emerald-500 outline-none transition">
            </div>
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">Penerbit</label>
                <input type="text" name="penerbit"
                    value="<?= htmlspecialchars((string) $buku['penerbit'], ENT_QUOTES, 'UTF-8') ?>"
                    class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-emerald-500 outline-none transition">
            </div>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">Tahun</label>
                <input type="number" name="tahun" min="1900" max="2100"
                    value="<?= htmlspecialchars((string) $buku['tahun'], ENT_QUOTES, 'UTF-8') ?>"
                    class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-emerald-500 outline-none transition">
            </div>
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">Stok</label>
                <input type="number" name="stok_total" min="0"
                    value="<?= (int) $buku['stok_total'] ?>"
                    class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-emerald-500 outline-none transition">
            </div>
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">Lokasi</label>
                <input type="text" name="lokasi"
                    value="<?= htmlspecialchars((string) $buku['lokasi'], ENT_QUOTES, 'UTF-8') ?>"
                    class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-emerald-500 outline-none transition">
            </div>
        </div>
    </div>

    <div class="bg-gray-50 p-6 flex justify-end gap-3 border-t border-gray-100">
        <button type="submit"
            class="bg-emerald-600 hover:bg-emerald-700 text-white px-8 py-2.5 rounded-xl font-bold shadow-lg transition-all">
            <i class="fas fa-save mr-2"></i> Simpan Perubahan
        </button>
    </div>
</form>
