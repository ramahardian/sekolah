<?php
if (!isset($pdo)) {
    exit;
}

$pageStmt = $pdo->prepare("SELECT id FROM cms_page WHERE slug = ?");
$pageStmt->execute(['landing']);
$pageId = (int) $pageStmt->fetchColumn();

if (!$pageId) {
    $pdo->prepare("INSERT INTO cms_page (slug, title) VALUES ('landing', 'Landing Page')")->execute();
    $pageId = (int) $pdo->lastInsertId();
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
            $stmt = $pdo->prepare("INSERT INTO cms_nav (page_id, label, url, target_blank, sort_order, is_active)
                VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$pageId, $label, $url, $target_blank, $sort_order, $is_active]);
            set_flash_message('success', 'Menu navigasi berhasil ditambahkan.');
            header("Location: index.php?page=cms-nav");
            exit;
        } catch (PDOException $e) {
            $error = "Gagal menambah menu: " . $e->getMessage();
        }
    }
}
?>

<div class="mb-6">
    <a href="index.php?page=cms-nav"
        class="text-indigo-600 hover:text-indigo-800 text-sm font-medium flex items-center mb-2">
        <i class="fas fa-arrow-left mr-2"></i> Kembali ke CMS Navigasi
    </a>
    <h1 class="text-2xl font-bold text-gray-800">Tambah Menu Navigasi</h1>
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
                    class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-indigo-500 outline-none transition">
            </div>
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">URL / Anchor</label>
                <input type="text" name="url" required placeholder="#hero atau https://"
                    class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-indigo-500 outline-none transition">
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">Urutan</label>
                <input type="number" name="sort_order" value="0"
                    class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-indigo-500 outline-none transition">
            </div>
            <div class="flex items-end gap-6">
                <label class="flex items-center gap-2 text-sm text-gray-700">
                    <input type="checkbox" name="target_blank" class="text-indigo-600">
                    Buka di tab baru
                </label>
                <label class="flex items-center gap-2 text-sm text-gray-700">
                    <input type="checkbox" name="is_active" checked class="text-indigo-600">
                    Aktifkan menu
                </label>
            </div>
        </div>
    </div>

    <div class="bg-gray-50 p-6 flex justify-end">
        <button type="submit"
            class="bg-indigo-600 hover:bg-indigo-700 text-white px-8 py-2.5 rounded-xl font-bold shadow-lg transition-all">
            Simpan Menu
        </button>
    </div>
</form>
