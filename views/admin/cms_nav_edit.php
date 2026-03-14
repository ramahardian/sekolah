<?php
if (!isset($pdo)) {
    exit;
}

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$stmt = $pdo->prepare("SELECT * FROM cms_nav WHERE id = ?");
$stmt->execute([$id]);
$nav = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$nav) {
    echo "<div class='bg-red-50 border border-red-200 text-red-700 p-4 rounded-lg'>Menu tidak ditemukan.</div>";
    return;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $label = input($_POST['label'] ?? '');
    $url = input($_POST['url'] ?? '');
    $target_blank = isset($_POST['target_blank']) ? 1 : 0;
    $sort_order = (int) ($_POST['sort_order'] ?? 0);
    $is_active = isset($_POST['is_active']) ? 1 : 0;

    if ($label === '' || $url === '') {
        $error = "Label dan URL wajib diisi.";
    } else {
        try {
            $update = $pdo->prepare("UPDATE cms_nav SET label = ?, url = ?, target_blank = ?, sort_order = ?, is_active = ? WHERE id = ?");
            $update->execute([$label, $url, $target_blank, $sort_order, $is_active, $id]);
            set_flash_message('success', 'Menu navigasi berhasil diperbarui.');
            header("Location: index.php?page=cms-nav");
            exit;
        } catch (PDOException $e) {
            $error = "Gagal memperbarui menu: " . $e->getMessage();
        }
    }
}
?>

<div class="mb-6">
    <a href="index.php?page=cms-nav"
        class="text-indigo-600 hover:text-indigo-800 text-sm font-medium flex items-center mb-2">
        <i class="fas fa-arrow-left mr-2"></i> Kembali ke CMS Navigasi
    </a>
    <h1 class="text-2xl font-bold text-gray-800">Edit Menu Navigasi</h1>
</div>

<?php if ($error): ?>
    <div class="mb-4 p-3 bg-red-100 border-l-4 border-red-500 text-red-700 text-sm">
        <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
    </div>
<?php endif; ?>

<form method="POST" class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
    <div class="p-8 space-y-6">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">Label</label>
                <input type="text" name="label" required
                    value="<?= htmlspecialchars($nav['label'], ENT_QUOTES, 'UTF-8') ?>"
                    class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-indigo-500 outline-none transition">
            </div>
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">URL / Anchor</label>
                <input type="text" name="url" required
                    value="<?= htmlspecialchars($nav['url'], ENT_QUOTES, 'UTF-8') ?>"
                    class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-indigo-500 outline-none transition">
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">Urutan</label>
                <input type="number" name="sort_order" value="<?= (int) $nav['sort_order'] ?>"
                    class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-indigo-500 outline-none transition">
            </div>
            <div class="flex items-end gap-6">
                <label class="flex items-center gap-2 text-sm text-gray-700">
                    <input type="checkbox" name="target_blank" class="text-indigo-600"
                        <?= (int) $nav['target_blank'] === 1 ? 'checked' : '' ?>>
                    Buka di tab baru
                </label>
                <label class="flex items-center gap-2 text-sm text-gray-700">
                    <input type="checkbox" name="is_active" class="text-indigo-600"
                        <?= (int) $nav['is_active'] === 1 ? 'checked' : '' ?>>
                    Aktifkan menu
                </label>
            </div>
        </div>
    </div>

    <div class="bg-gray-50 p-6 flex justify-end">
        <button type="submit"
            class="bg-indigo-600 hover:bg-indigo-700 text-white px-8 py-2.5 rounded-xl font-bold shadow-lg transition-all">
            Simpan Perubahan
        </button>
    </div>
</form>
