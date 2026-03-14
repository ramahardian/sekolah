<?php
if (!isset($pdo)) {
    exit;
}

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
if ($id > 0) {
    $stmt = $pdo->prepare("DELETE FROM perpustakaan_buku WHERE id = ?");
    $stmt->execute([$id]);
    set_flash_message('success', 'Buku berhasil dihapus.');
}

header("Location: index.php?page=perpus-buku");
exit;
