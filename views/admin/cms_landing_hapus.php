<?php
if (!isset($pdo)) {
    exit;
}

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

if ($id > 0) {
    $stmt = $pdo->prepare("DELETE FROM cms_section WHERE id = ?");
    $stmt->execute([$id]);
    set_flash_message('success', 'Section berhasil dihapus.');
}

header("Location: index.php?page=cms-landing");
exit;
