<?php
if (!isset($pdo)) {
    exit;
}

$kategoriId = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$stmtKategori = $pdo->prepare("SELECT * FROM forum_kategori WHERE id = ?");
$stmtKategori->execute([$kategoriId]);
$kategori = $stmtKategori->fetch(PDO::FETCH_ASSOC);

if (!$kategori) {
    echo "<div class='bg-red-50 border border-red-200 text-red-700 p-4 rounded-lg'>Kategori tidak ditemukan.</div>";
    return;
}

$stmt = $pdo->prepare("SELECT t.*, u.username, u.role,
        COALESCE(g.nama_guru, s.nama_siswa, u.username) AS author_name,
        (SELECT COUNT(*) FROM forum_reply r WHERE r.thread_id = t.id) AS total_reply,
        (SELECT MAX(created_at) FROM forum_reply r WHERE r.thread_id = t.id) AS last_reply_at
    FROM forum_thread t
    JOIN users u ON t.user_id = u.id
    LEFT JOIN guru g ON g.user_id = u.id
    LEFT JOIN siswa s ON s.user_id = u.id
    WHERE t.kategori_id = ?
    ORDER BY COALESCE(t.updated_at, t.created_at) DESC");
$stmt->execute([$kategoriId]);
$threads = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="flex flex-col md:flex-row md:items-center justify-between mb-6 gap-4">
    <div>
        <a href="index.php?page=forum" class="text-indigo-600 hover:underline text-sm">← Kembali ke Forum</a>
        <h1 class="text-2xl font-bold text-gray-800 mt-2">
            <?= htmlspecialchars($kategori['nama'], ENT_QUOTES, 'UTF-8') ?>
        </h1>
        <p class="text-gray-500 text-sm">
            <?= htmlspecialchars($kategori['deskripsi'] ?? '-', ENT_QUOTES, 'UTF-8') ?>
        </p>
    </div>
    <a href="index.php?page=forum-thread-tambah&kategori_id=<?= (int) $kategoriId ?>"
        class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-lg flex items-center shadow-md transition">
        <i class="fas fa-plus mr-2 text-sm"></i> Buat Thread
    </a>
</div>

<?php display_flash_message(); ?>

<?php if (count($threads) > 0): ?>
    <div class="grid grid-cols-1 gap-4 md:hidden">
        <?php foreach ($threads as $t): ?>
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4">
                <a href="index.php?page=forum-thread&id=<?= (int) $t['id'] ?>"
                    class="text-base font-semibold text-indigo-700 hover:underline">
                    <?= htmlspecialchars($t['judul'], ENT_QUOTES, 'UTF-8') ?>
                </a>
                <p class="text-xs text-gray-500 mt-1">
                    <?= htmlspecialchars(substr(strip_tags($t['konten']), 0, 100), ENT_QUOTES, 'UTF-8') ?>...
                </p>
                <div class="mt-3 text-xs text-gray-500 flex flex-wrap gap-3">
                    <span><span class="font-semibold">Pembuat:</span>
                        <?= htmlspecialchars($t['author_name'], ENT_QUOTES, 'UTF-8') ?></span>
                    <span><span class="font-semibold">Balasan:</span> <?= (int) $t['total_reply'] ?></span>
                    <span><span class="font-semibold">Update:</span>
                        <?= htmlspecialchars($t['last_reply_at'] ?: $t['created_at'], ENT_QUOTES, 'UTF-8') ?></span>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden hidden md:block">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-gray-50 text-gray-600 text-xs uppercase tracking-widest border-b">
                        <th class="px-6 py-4 font-semibold">Thread</th>
                        <th class="px-6 py-4 font-semibold">Pembuat</th>
                        <th class="px-6 py-4 font-semibold">Balasan</th>
                        <th class="px-6 py-4 font-semibold">Update</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <?php foreach ($threads as $t): ?>
                        <tr class="hover:bg-indigo-50/30 transition">
                            <td class="px-6 py-4">
                                <a href="index.php?page=forum-thread&id=<?= (int) $t['id'] ?>"
                                    class="text-sm font-semibold text-indigo-700 hover:underline">
                                    <?= htmlspecialchars($t['judul'], ENT_QUOTES, 'UTF-8') ?>
                                </a>
                                <div class="text-xs text-gray-500 mt-1">
                                    <?= htmlspecialchars(substr(strip_tags($t['konten']), 0, 80), ENT_QUOTES, 'UTF-8') ?>...
                                </div>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-600">
                                <?= htmlspecialchars($t['author_name'], ENT_QUOTES, 'UTF-8') ?>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-600">
                                <?= (int) $t['total_reply'] ?>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-500">
                                <?= htmlspecialchars($t['last_reply_at'] ?: $t['created_at'], ENT_QUOTES, 'UTF-8') ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php else: ?>
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-8 text-center text-gray-500">
        Belum ada thread.
    </div>
<?php endif; ?>