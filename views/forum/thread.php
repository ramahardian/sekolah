<?php
if (!isset($pdo)) {
    exit;
}

$threadId = isset($_GET['id']) ? (int) $_GET['id'] : 0;

$stmt = $pdo->prepare("SELECT t.*, k.nama AS nama_kategori, u.username, u.role,
        COALESCE(g.nama_guru, s.nama_siswa, u.username) AS author_name
    FROM forum_thread t
    JOIN forum_kategori k ON t.kategori_id = k.id
    JOIN users u ON t.user_id = u.id
    LEFT JOIN guru g ON g.user_id = u.id
    LEFT JOIN siswa s ON s.user_id = u.id
    WHERE t.id = ?");
$stmt->execute([$threadId]);
$thread = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$thread) {
    echo "<div class='bg-red-50 border border-red-200 text-red-700 p-4 rounded-lg'>Thread tidak ditemukan.</div>";
    return;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $konten = trim((string) ($_POST['konten'] ?? ''));
    if ($konten === '') {
        $error = "Konten balasan wajib diisi.";
    } else {
        $stmt = $pdo->prepare("INSERT INTO forum_reply (thread_id, user_id, konten, created_at) VALUES (?, ?, ?, NOW())");
        $stmt->execute([$threadId, $_SESSION['user_id'], $konten]);
        $pdo->prepare("UPDATE forum_thread SET updated_at = NOW() WHERE id = ?")->execute([$threadId]);

        set_flash_message('success', 'Balasan berhasil dikirim.');
        header("Location: index.php?page=forum-thread&id=$threadId");
        exit;
    }
}

$replyStmt = $pdo->prepare("SELECT r.*, u.username, u.role,
        COALESCE(g.nama_guru, s.nama_siswa, u.username) AS author_name
    FROM forum_reply r
    JOIN users u ON r.user_id = u.id
    LEFT JOIN guru g ON g.user_id = u.id
    LEFT JOIN siswa s ON s.user_id = u.id
    WHERE r.thread_id = ?
    ORDER BY r.created_at ASC");
$replyStmt->execute([$threadId]);
$replies = $replyStmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="mb-6">
    <a href="index.php?page=forum-kategori&id=<?= (int) $thread['kategori_id'] ?>"
        class="text-indigo-600 hover:underline text-sm">← Kembali ke kategori</a>
    <h1 class="text-2xl font-bold text-gray-800 mt-2">
        <?= htmlspecialchars($thread['judul'], ENT_QUOTES, 'UTF-8') ?>
    </h1>
    <p class="text-gray-500 text-sm">
        Kategori: <?= htmlspecialchars($thread['nama_kategori'], ENT_QUOTES, 'UTF-8') ?> ·
        Dibuat oleh <?= htmlspecialchars($thread['author_name'], ENT_QUOTES, 'UTF-8') ?>
    </p>
</div>

<?php display_flash_message(); ?>

<div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 mb-6">
    <div class="text-sm text-gray-500 mb-2">
        <?= htmlspecialchars($thread['created_at'], ENT_QUOTES, 'UTF-8') ?>
    </div>
    <div class="text-gray-800 whitespace-pre-line">
        <?= htmlspecialchars($thread['konten'], ENT_QUOTES, 'UTF-8') ?>
    </div>
</div>

<div class="space-y-4">
    <?php if (count($replies) > 0): ?>
        <?php foreach ($replies as $r): ?>
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
                <div class="flex items-center justify-between text-sm text-gray-500 mb-2">
                    <span><?= htmlspecialchars($r['author_name'], ENT_QUOTES, 'UTF-8') ?></span>
                    <span><?= htmlspecialchars($r['created_at'], ENT_QUOTES, 'UTF-8') ?></span>
                </div>
                <div class="text-gray-800 whitespace-pre-line">
                    <?= htmlspecialchars($r['konten'], ENT_QUOTES, 'UTF-8') ?>
                </div>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <div class="bg-gray-50 border border-dashed border-gray-200 rounded-xl p-6 text-center text-gray-500">
            Belum ada balasan.
        </div>
    <?php endif; ?>
</div>

<div class="mt-6 bg-white rounded-xl shadow-sm border border-gray-100 p-6">
    <h3 class="text-lg font-bold text-gray-800 mb-4">Tulis Balasan</h3>

    <?php if ($error): ?>
        <div class="mb-4 p-3 bg-red-100 border-l-4 border-red-500 text-red-700 text-sm">
            <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
        </div>
    <?php endif; ?>

    <form method="POST" class="space-y-4">
        <textarea name="konten" rows="4" required
            class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-indigo-500 outline-none transition"
            placeholder="Tulis balasan..."></textarea>
        <button type="submit"
            class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2 px-6 rounded-lg transition">
            Kirim Balasan
        </button>
    </form>
</div>
