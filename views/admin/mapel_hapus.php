<?php
if (!isset($pdo)) {
    exit;
}

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
if ($id > 0) {
    $pdo->prepare("DELETE FROM mapel_guru WHERE mapel_id = ?")->execute([$id]);
    $stmt = $pdo->prepare("DELETE FROM mapel WHERE id = ?");
    $stmt->execute([$id]);
    set_flash_message('success', 'Mapel berhasil dihapus.');
}

header("Location: index.php?page=mapel");
exit;
