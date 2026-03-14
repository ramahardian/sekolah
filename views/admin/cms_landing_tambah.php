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
    $section_key = input($_POST['section_key'] ?? '');
    $layout = input($_POST['layout'] ?? 'content');
    $title = input($_POST['title'] ?? '');
    $subtitle = input($_POST['subtitle'] ?? '');
    $body = trim((string) ($_POST['body'] ?? ''));
    $hero_desc = input($_POST['hero_desc'] ?? '');
    $hero_titles = array_map('input', (array) ($_POST['hero_title'] ?? []));
    $hero_descs = array_map('input', (array) ($_POST['hero_desc_item'] ?? []));
    $hero_icons = array_map('input', (array) ($_POST['hero_icon'] ?? []));
    $image_url = input($_POST['image_url'] ?? '');
    $button_text = input($_POST['button_text'] ?? '');
    $button_link = input($_POST['button_link'] ?? '');
    $sort_order = (int) ($_POST['sort_order'] ?? 0);
    $is_active = isset($_POST['is_active']) ? 1 : 0;

    if ($section_key === '') {
        $error = "Section key wajib diisi.";
    } else {
        $hero_has_input = $hero_desc !== '';
        foreach ($hero_titles as $idx => $val) {
            if ($val !== '' || ($hero_descs[$idx] ?? '') !== '' || ($hero_icons[$idx] ?? '') !== '') {
                $hero_has_input = true;
                break;
            }
        }

        if ($layout === 'hero' && $hero_has_input) {
            $lines = [];
            if ($hero_desc !== '') {
                $lines[] = $hero_desc;
            }
            foreach ($hero_titles as $i => $t) {
                $d = $hero_descs[$i] ?? '';
                $ic = $hero_icons[$i] ?? '';
                if ($t === '' || $d === '') {
                    continue;
                }
                $line = $t . '|' . $d;
                if ($ic !== '') {
                    $line .= '|' . $ic;
                }
                $lines[] = $line;
            }
            $body = trim(implode("\n", $lines));
        }

        if (isset($_FILES['image_file']) && $_FILES['image_file']['error'] === UPLOAD_ERR_OK) {
            $allowed = ['jpg', 'jpeg', 'png', 'webp'];
            $ext = strtolower(pathinfo($_FILES['image_file']['name'], PATHINFO_EXTENSION));
            if (!in_array($ext, $allowed, true)) {
                $error = "Format gambar tidak didukung (jpg, jpeg, png, webp).";
            } else {
                $dir = __DIR__ . '/../../public/uploads/cms';
                if (!is_dir($dir)) {
                    mkdir($dir, 0775, true);
                }
                $filename = 'cms_' . date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
                $path = $dir . '/' . $filename;
                if (move_uploaded_file($_FILES['image_file']['tmp_name'], $path)) {
                    $image_url = 'public/uploads/cms/' . $filename;
                } else {
                    $error = "Gagal menyimpan gambar.";
                }
            }
        }
    }

    if ($error === '') {
        try {
            $stmt = $pdo->prepare("INSERT INTO cms_section
                (page_id, section_key, layout, title, subtitle, body, image_url, button_text, button_link, sort_order, is_active)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $pageId,
                $section_key,
                $layout,
                $title ?: null,
                $subtitle ?: null,
                $body ?: null,
                $image_url ?: null,
                $button_text ?: null,
                $button_link ?: null,
                $sort_order,
                $is_active
            ]);
            set_flash_message('success', 'Section berhasil ditambahkan.');
            header("Location: index.php?page=cms-landing");
            exit;
        } catch (PDOException $e) {
            $error = "Gagal menambah section: " . $e->getMessage();
        }
    }
}
?>

<div class="mb-6">
    <a href="index.php?page=cms-landing"
        class="text-indigo-600 hover:text-indigo-800 text-sm font-medium flex items-center mb-2">
        <i class="fas fa-arrow-left mr-2"></i> Kembali ke CMS
    </a>
    <h1 class="text-2xl font-bold text-gray-800">Tambah Section Landing</h1>
</div>

<?php if ($error): ?>
    <div class="mb-4 p-3 bg-red-100 border-l-4 border-red-500 text-red-700 text-sm">
        <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
    </div>
<?php endif; ?>

<form method="POST" enctype="multipart/form-data" class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
    <div class="p-8 space-y-6">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">Section Key</label>
                <input type="text" name="section_key" required
                    class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-indigo-500 outline-none transition"
                    placeholder="contoh: hero, stats, features">
            </div>
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">Layout</label>
                <select name="layout"
                    class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-indigo-500 outline-none transition">
                    <option value="content">Content</option>
                    <option value="hero">Hero</option>
                    <option value="stats">Stats</option>
                    <option value="features">Features</option>
                    <option value="testimonials">Testimonials</option>
                    <option value="cta">CTA</option>
                    <option value="contact">Contact</option>
                </select>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">Judul</label>
                <input type="text" name="title"
                    class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-indigo-500 outline-none transition">
            </div>
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">Subjudul</label>
                <input type="text" name="subtitle"
                    class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-indigo-500 outline-none transition">
            </div>
        </div>

        <div>
            <label class="block text-sm font-semibold text-gray-700 mb-2">Konten</label>
            <textarea name="body" rows="5"
                class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-indigo-500 outline-none transition"></textarea>
            <p class="text-xs text-gray-400 mt-2">Stats/Contact: Label|Value per baris. Hero: baris 1 = deskripsi, baris berikutnya = Judul|Deskripsi|Icon (fa-...).</p>
        </div>

        <div id="hero-editor" class="space-y-4 border border-indigo-100 rounded-xl p-4 bg-indigo-50/40">
            <div>
                <p class="text-sm font-semibold text-indigo-700">Hero Card Editor (opsional)</p>
                <p class="text-xs text-gray-500">Isi agar tidak perlu format dengan tanda |.</p>
            </div>
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">Deskripsi Hero</label>
                <input type="text" name="hero_desc"
                    class="w-full px-4 py-3 bg-white border border-gray-200 rounded-xl focus:ring-2 focus:ring-indigo-500 outline-none transition">
            </div>
            <?php for ($i = 0; $i < 4; $i++): ?>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                    <input type="text" name="hero_title[]" placeholder="Judul kartu"
                        class="w-full px-4 py-2.5 bg-white border border-gray-200 rounded-xl focus:ring-2 focus:ring-indigo-500 outline-none transition text-sm">
                    <input type="text" name="hero_desc_item[]" placeholder="Deskripsi kartu"
                        class="w-full px-4 py-2.5 bg-white border border-gray-200 rounded-xl focus:ring-2 focus:ring-indigo-500 outline-none transition text-sm">
                    <input type="text" name="hero_icon[]" placeholder="Icon (fa-graduation-cap)"
                        class="w-full px-4 py-2.5 bg-white border border-gray-200 rounded-xl focus:ring-2 focus:ring-indigo-500 outline-none transition text-sm">
                </div>
            <?php endfor; ?>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">Image URL (opsional)</label>
                <input type="text" name="image_url"
                    class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-indigo-500 outline-none transition">
            </div>
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">Upload Gambar (opsional)</label>
                <input type="file" name="image_file" accept=".jpg,.jpeg,.png,.webp"
                    class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-indigo-500 outline-none transition">
            </div>
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">Urutan</label>
                <input type="number" name="sort_order" value="0"
                    class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-indigo-500 outline-none transition">
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">Button Text</label>
                <input type="text" name="button_text"
                    class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-indigo-500 outline-none transition">
            </div>
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">Button Link</label>
                <input type="text" name="button_link"
                    class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-indigo-500 outline-none transition">
            </div>
        </div>

        <label class="flex items-center gap-2 text-sm text-gray-700">
            <input type="checkbox" name="is_active" checked class="text-indigo-600">
            Aktifkan section
        </label>
    </div>

    <div class="bg-gray-50 p-6 flex justify-end">
        <button type="submit"
            class="bg-indigo-600 hover:bg-indigo-700 text-white px-8 py-2.5 rounded-xl font-bold shadow-lg transition-all">
            Simpan Section
        </button>
    </div>
</form>

<script>
    (function () {
        const layoutSelect = document.querySelector('select[name="layout"]');
        const heroEditor = document.getElementById('hero-editor');
        const bodyField = document.querySelector('textarea[name="body"]');

        function toggleHeroEditor() {
            const isHero = layoutSelect && layoutSelect.value === 'hero';
            if (heroEditor) heroEditor.style.display = isHero ? 'block' : 'none';
            if (bodyField) bodyField.parentElement.style.display = isHero ? 'none' : 'block';
        }

        if (layoutSelect) {
            layoutSelect.addEventListener('change', toggleHeroEditor);
            toggleHeroEditor();
        }
    })();
</script>
