<?php
if (!isset($pdo)) {
    exit;
}

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

if ($id > 0) {
    $stmt = $pdo->prepare("DELETE FROM cms_nav WHERE id = ?");
    $stmt->execute([$id]);
    set_flash_message('success', 'Menu navigasi berhasil dihapus.');
}

header("Location: index.php?page=cms-nav");
exit;
