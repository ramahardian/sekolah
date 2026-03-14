<?php
if (!isset($pdo)) {
    exit;
}

$pageStmt = $pdo->prepare("SELECT id FROM cms_page WHERE slug = ?");
$pageStmt->execute(['landing']);
$pageId = $pageStmt->fetchColumn();

if (!$pageId) {
    $pdo->prepare("INSERT INTO cms_page (slug, title) VALUES ('landing', 'Landing Page')")->execute();
    $pageId = (int) $pdo->lastInsertId();
}

$countStmt = $pdo->prepare("SELECT COUNT(*) FROM cms_nav WHERE page_id = ?");
$countStmt->execute([$pageId]);
$navCount = (int) $countStmt->fetchColumn();

if ($navCount === 0) {
    $defaults = [
        ['Home', '#hero', 0, 1, 1],
        ['Fitur', '#features', 0, 2, 1],
        ['Profil', '#about', 0, 3, 1],
        ['Testimoni', '#testimonials', 0, 4, 1],
        ['Kontak', '#contact', 0, 5, 1],
        ['Login', 'index.php?page=login', 0, 6, 1],
    ];
    $insert = $pdo->prepare("INSERT INTO cms_nav (page_id, label, url, target_blank, sort_order, is_active) VALUES (?, ?, ?, ?, ?, ?)");
    foreach ($defaults as $d) {
        $insert->execute(array_merge([$pageId], $d));
    }
}

$stmt = $pdo->prepare("SELECT * FROM cms_nav WHERE page_id = ? ORDER BY sort_order ASC");
$stmt->execute([$pageId]);
$navItems = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="flex flex-col md:flex-row md:items-center justify-between mb-6 gap-4">
    <div>
        <h1 class="text-2xl font-bold text-gray-800">CMS Navigasi</h1>
        <p class="text-gray-500 text-sm">Kelola menu navigasi pada landing page.</p>
    </div>
    <div class="flex gap-2">
        <a href="index.php?page=landing" target="_blank"
            class="bg-gray-700 hover:bg-gray-800 text-white px-4 py-2 rounded-lg flex items-center shadow-md transition">
            <i class="fas fa-eye mr-2 text-sm"></i> Lihat Landing
        </a>
        <a href="index.php?page=cms-nav-tambah"
            class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-lg flex items-center shadow-md transition">
            <i class="fas fa-plus mr-2 text-sm"></i> Tambah Menu
        </a>
    </div>
</div>

<?php display_flash_message(); ?>

<div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="bg-gray-50 text-gray-600 text-xs uppercase tracking-widest border-b">
                    <th class="px-6 py-4 font-semibold">Label</th>
                    <th class="px-6 py-4 font-semibold">URL</th>
                    <th class="px-6 py-4 font-semibold">Target</th>
                    <th class="px-6 py-4 font-semibold">Status</th>
                    <th class="px-6 py-4 font-semibold">Urutan</th>
                    <th class="px-6 py-4 font-semibold text-center">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                <?php if (count($navItems) > 0): ?>
                    <?php foreach ($navItems as $n): ?>
                        <tr class="hover:bg-indigo-50/30 transition">
                            <td class="px-6 py-4 text-sm font-semibold text-gray-800">
                                <?= htmlspecialchars($n['label'], ENT_QUOTES, 'UTF-8') ?>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-600">
                                <?= htmlspecialchars($n['url'], ENT_QUOTES, 'UTF-8') ?>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-600">
                                <?= (int) $n['target_blank'] === 1 ? 'Blank' : 'Same Tab' ?>
                            </td>
                            <td class="px-6 py-4">
                                <span class="px-2.5 py-0.5 rounded-full text-xs font-medium
                                    <?= (int) $n['is_active'] === 1 ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-700' ?>">
                                    <?= (int) $n['is_active'] === 1 ? 'Aktif' : 'Nonaktif' ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-600"><?= (int) $n['sort_order'] ?></td>
                            <td class="px-6 py-4 text-center">
                                <div class="flex items-center justify-center gap-2">
                                    <a href="index.php?page=cms-nav-edit&id=<?= (int) $n['id'] ?>"
                                        class="text-indigo-600 hover:bg-indigo-50 p-2 rounded-lg transition">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <button onclick="confirmDelete('index.php?page=cms-nav-hapus&id=<?= (int) $n['id'] ?>')"
                                        class="text-red-600 hover:bg-red-50 p-2 rounded-lg transition">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6" class="px-6 py-10 text-center text-gray-500">Menu navigasi belum tersedia.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
