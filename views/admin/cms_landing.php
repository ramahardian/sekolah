<?php
if (!isset($pdo)) {
    exit;
}

// Ensure landing page exists
$pageStmt = $pdo->prepare("SELECT id FROM cms_page WHERE slug = ?");
$pageStmt->execute(['landing']);
$pageId = $pageStmt->fetchColumn();

if (!$pageId) {
    $pdo->prepare("INSERT INTO cms_page (slug, title) VALUES ('landing', 'Landing Page')")->execute();
    $pageId = (int) $pdo->lastInsertId();
}

// Seed default sections if empty
$countStmt = $pdo->prepare("SELECT COUNT(*) FROM cms_section WHERE page_id = ?");
$countStmt->execute([$pageId]);
$sectionCount = (int) $countStmt->fetchColumn();

if ($sectionCount === 0) {
    $defaults = [
        ['hero', 'hero', 'SIS Pro', 'Sistem Informasi Sekolah Terpadu', 'Kelola akademik, absensi, ujian, perpustakaan, dan komunikasi sekolah dalam satu platform yang rapi dan cepat.', '', 'Masuk Portal', 'index.php?page=login', 1, 1],
        ['stats', 'stats', 'Ringkas & Terukur', 'Data sekolah selalu up to date', "Siswa|1200\nGuru|85\nKelas|36\nMapel|42", '', '', '', 2, 1],
        ['features', 'features', 'Fitur Utama', 'Semua kebutuhan sekolah dalam satu tempat', "Absensi digital harian\nUjian online & penilaian\nManajemen kelas, guru, siswa\nPerpustakaan & peminjaman\nForum komunikasi sekolah", '', '', '', 3, 1],
        ['testimonials', 'testimonials', 'Testimoni', 'Suara dari guru dan siswa', "Ibu Rina|Guru Matematika|Sistem ini membuat rekap nilai dan absensi jadi jauh lebih cepat.\nAndi Pratama|Siswa Kelas XI|Ujian online jadi rapi dan tidak bikin bingung.\nBudi Santoso|Wali Kelas|Monitoring siswa lebih mudah dan transparan.", '', '', '', 4, 1],
        ['about', 'content', 'Tentang Sekolah', 'Membangun generasi berprestasi', 'Sekolah kami berkomitmen menghadirkan pendidikan yang adaptif, berbasis teknologi, dan berorientasi karakter.', '', '', '', 5, 1],
        ['cta', 'cta', 'Siap Bertransformasi?', 'Mulai kelola sekolah dengan lebih rapi hari ini.', '', '', 'Masuk ke Sistem', 'index.php?page=login', 6, 1],
        ['contact', 'contact', 'Kontak', 'Hubungi kami', "Alamat|Jl. Pendidikan No. 10\nEmail|info@sekolah.sch.id\nTelepon|021-123456", '', '', '', 7, 1],
    ];
    $insert = $pdo->prepare("INSERT INTO cms_section 
        (page_id, section_key, layout, title, subtitle, body, image_url, button_text, button_link, sort_order, is_active)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    foreach ($defaults as $d) {
        $insert->execute(array_merge([$pageId], $d));
    }
}

$stmt = $pdo->prepare("SELECT * FROM cms_section WHERE page_id = ? ORDER BY sort_order ASC");
$stmt->execute([$pageId]);
$sections = $stmt->fetchAll(PDO::FETCH_ASSOC);

$settings = [];
$settingsStmt = $pdo->query("SELECT setting_key, setting_value FROM cms_setting");
foreach ($settingsStmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
    $settings[$row['setting_key']] = $row['setting_value'];
}

$headerPhone = $settings['header_phone'] ?? '+62 812 3456 789';
$headerEmail = $settings['header_email'] ?? 'info@sekolah.sch.id';
$headerTitle = $settings['header_title'] ?? 'SIS Management';
$headerLogo = $settings['header_logo'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_header_settings'])) {
    $headerPhone = input($_POST['header_phone'] ?? '');
    $headerEmail = input($_POST['header_email'] ?? '');
    $headerTitle = input($_POST['header_title'] ?? 'SIS Management');
    $headerLogo = input($_POST['header_logo'] ?? '');

    try {
        $upsert = $pdo->prepare("INSERT INTO cms_setting (setting_key, setting_value)
            VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)");
        $upsert->execute(['header_phone', $headerPhone]);
        $upsert->execute(['header_email', $headerEmail]);
        $upsert->execute(['header_title', $headerTitle]);
        $upsert->execute(['header_logo', $headerLogo]);

        set_flash_message('success', 'Pengaturan header berhasil disimpan.');
        header("Location: index.php?page=cms-landing");
        exit;
    } catch (PDOException $e) {
        set_flash_message('error', 'Gagal menyimpan pengaturan: ' . $e->getMessage());
    }
}
?>

<div class="flex flex-col md:flex-row md:items-center justify-between mb-6 gap-4">
    <div>
        <h1 class="text-2xl font-bold text-gray-800">CMS Landing Page</h1>
        <p class="text-gray-500 text-sm">Kelola konten halaman depan sekolah.</p>
    </div>
    <div class="flex gap-2">
        <a href="index.php?page=landing" target="_blank"
            class="bg-gray-700 hover:bg-gray-800 text-white px-4 py-2 rounded-lg flex items-center shadow-md transition">
            <i class="fas fa-eye mr-2 text-sm"></i> Lihat Landing
        </a>
        <a href="index.php?page=cms-nav"
            class="bg-slate-600 hover:bg-slate-700 text-white px-4 py-2 rounded-lg flex items-center shadow-md transition">
            <i class="fas fa-bars mr-2 text-sm"></i> Kelola Navigasi
        </a>
        <a href="index.php?page=cms-landing-tambah"
            class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-lg flex items-center shadow-md transition">
            <i class="fas fa-plus mr-2 text-sm"></i> Tambah Section
        </a>
    </div>
</div>

<?php display_flash_message(); ?>

<div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden mb-6">
    <div class="p-6 border-b border-gray-100">
        <h2 class="text-lg font-bold text-gray-800">Pengaturan Header</h2>
        <p class="text-sm text-gray-500">Nomor telepon dan email di bagian atas landing page.</p>
    </div>
    <form method="POST" class="p-6 grid grid-cols-1 md:grid-cols-2 gap-4">
        <input type="hidden" name="save_header_settings" value="1">
        <div>
            <label class="block text-sm font-semibold text-gray-700 mb-2">Nomor Telepon</label>
            <input type="text" name="header_phone" value="<?= htmlspecialchars($headerPhone, ENT_QUOTES, 'UTF-8') ?>"
                class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-indigo-500 outline-none transition">
        </div>
        <div>
            <label class="block text-sm font-semibold text-gray-700 mb-2">Email</label>
            <input type="email" name="header_email" value="<?= htmlspecialchars($headerEmail, ENT_QUOTES, 'UTF-8') ?>"
                class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-indigo-500 outline-none transition">
        </div>
        <div>
            <label class="block text-sm font-semibold text-gray-700 mb-2">Meta Title</label>
            <input type="text" name="header_title" value="<?= htmlspecialchars($headerTitle, ENT_QUOTES, 'UTF-8') ?>"
                class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-indigo-500 outline-none transition" placeholder="Contoh: SIS Management">
        </div>
        <div>
            <label class="block text-sm font-semibold text-gray-700 mb-2">URL Logo</label>
            <input type="text" name="header_logo" value="<?= htmlspecialchars($headerLogo, ENT_QUOTES, 'UTF-8') ?>"
                class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-indigo-500 outline-none transition" placeholder="Contoh: /assets/images/logo.png">
        </div>
        <div class="md:col-span-2 flex justify-end">
            <button type="submit"
                class="bg-indigo-600 hover:bg-indigo-700 text-white px-6 py-2.5 rounded-xl font-bold shadow-lg transition-all">
                Simpan Pengaturan
            </button>
        </div>
    </form>
</div>

<div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="bg-gray-50 text-gray-600 text-xs uppercase tracking-widest border-b">
                    <th class="px-6 py-4 font-semibold">Section</th>
                    <th class="px-6 py-4 font-semibold">Layout</th>
                    <th class="px-6 py-4 font-semibold">Status</th>
                    <th class="px-6 py-4 font-semibold">Urutan</th>
                    <th class="px-6 py-4 font-semibold text-center">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                <?php foreach ($sections as $s): ?>
                    <tr class="hover:bg-indigo-50/30 transition">
                        <td class="px-6 py-4">
                            <div class="text-sm font-semibold text-gray-800"><?= htmlspecialchars($s['title'] ?? $s['section_key'], ENT_QUOTES, 'UTF-8') ?></div>
                            <div class="text-xs text-gray-500"><?= htmlspecialchars($s['section_key'], ENT_QUOTES, 'UTF-8') ?></div>
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-600"><?= htmlspecialchars($s['layout'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td class="px-6 py-4">
                            <span class="px-2.5 py-0.5 rounded-full text-xs font-medium
                                <?= (int) $s['is_active'] === 1 ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-700' ?>">
                                <?= (int) $s['is_active'] === 1 ? 'Aktif' : 'Nonaktif' ?>
                            </span>
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-600"><?= (int) $s['sort_order'] ?></td>
                        <td class="px-6 py-4 text-center">
                            <div class="flex items-center justify-center gap-2">
                                <a href="index.php?page=cms-landing-edit&id=<?= (int) $s['id'] ?>"
                                    class="text-indigo-600 hover:bg-indigo-50 p-2 rounded-lg transition">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <button onclick="confirmDelete('index.php?page=cms-landing-hapus&id=<?= (int) $s['id'] ?>')"
                                    class="text-red-600 hover:bg-red-50 p-2 rounded-lg transition">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
