<?php
if (!isset($pdo)) {
    exit;
}

$kategori = $pdo->query("SELECT k.*,
        (SELECT COUNT(*) FROM forum_thread t WHERE t.kategori_id = k.id) AS total_thread,
        (SELECT MAX(created_at) FROM forum_thread t WHERE t.kategori_id = k.id) AS last_thread_at
    FROM forum_kategori k
    ORDER BY k.nama ASC")->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="flex flex-col md:flex-row md:items-center justify-between mb-6 gap-4">
    <div>
        <h1 class="text-2xl font-bold text-gray-800">Forum</h1>
        <p class="text-gray-500 text-sm">Diskusi dan berbagi informasi.</p>
    </div>
    <?php if (($_SESSION['role'] ?? '') === 'admin'): ?>
        <a href="index.php?page=forum-kategori-tambah"
            class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-lg flex items-center shadow-md transition">
            <i class="fas fa-plus mr-2 text-sm"></i> Tambah Kategori
        </a>
    <?php endif; ?>
</div>

<?php display_flash_message(); ?>

<div class="grid grid-cols-1 md:grid-cols-2 gap-6">
    <?php if (count($kategori) > 0): ?>
        <?php foreach ($kategori as $k): ?>
            <a href="index.php?page=forum-kategori&id=<?= (int) $k['id'] ?>"
                class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5 hover:shadow-md transition">
                <div class="flex items-start justify-between">
                    <div>
                        <h3 class="text-lg font-bold text-gray-800">
                            <?= htmlspecialchars($k['nama'], ENT_QUOTES, 'UTF-8') ?>
                        </h3>
                        <p class="text-sm text-gray-500 mt-1">
                            <?= htmlspecialchars($k['deskripsi'] ?? '-', ENT_QUOTES, 'UTF-8') ?>
                        </p>
                    </div>
                    <div class="text-indigo-600">
                        <i class="fas fa-comments fa-lg opacity-20"></i>
                    </div>
                </div>
                <div class="mt-4 text-xs text-gray-500">
                    <?= (int) $k['total_thread'] ?> thread
                    <?php if (!empty($k['last_thread_at'])): ?>
                        • Update terakhir: <?= htmlspecialchars($k['last_thread_at'], ENT_QUOTES, 'UTF-8') ?>
                    <?php endif; ?>
                </div>
            </a>
        <?php endforeach; ?>
    <?php else: ?>
        <div class="col-span-full bg-gray-50 border-2 border-dashed border-gray-200 rounded-2xl p-10 text-center">
            <p class="text-gray-400 italic">Kategori forum belum tersedia.</p>
        </div>
    <?php endif; ?>
</div>
