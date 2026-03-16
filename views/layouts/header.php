<?php
require_once __DIR__ . '/../../includes/auth_check.php';
cekLogin();

global $pdo;
$stmt = $pdo->query("SELECT setting_key, setting_value FROM cms_setting WHERE setting_key IN ('header_title', 'header_logo')");
$headerSettings = [];
if ($stmt) {
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $headerSettings[$row['setting_key']] = $row['setting_value'];
    }
}
$pageTitle = $headerSettings['header_title'] ?? 'SIS Management';
$pageLogo = $headerSettings['header_logo'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8') ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link
        href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&family=Oswald:wght@500;600&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            font-family: 'Roboto', sans-serif;
        }

        .heading-oswald {
            font-family: 'Oswald', sans-serif;
        }
    </style>
</head>

<body class="bg-gray-50">
    <div
        class="md:hidden fixed top-0 left-0 right-0 h-14 bg-white shadow-sm z-30 flex items-center justify-between px-4">
        <button id="mobileMenuButton"
            class="p-2 rounded-lg hover:bg-gray-100 text-gray-700 focus:outline-none focus:ring-2 focus:ring-indigo-500"
            aria-label="Buka menu">
            <i class="fas fa-bars"></i>
        </button>
        <div class="flex items-center gap-2">
            <?php if (!empty($pageLogo)): ?>
                <img src="<?= htmlspecialchars($pageLogo, ENT_QUOTES, 'UTF-8') ?>" alt="Logo"
                    class="h-8 max-w-[120px] object-contain">
            <?php elseif (!empty($pageTitle)): ?>
                <div class="font-bold text-gray-800"><?= htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8') ?></div>
            <?php endif; ?>
        </div>
        <div class="w-8"></div>
    </div>
    <div id="mobileMenuOverlay" class="md:hidden fixed inset-0 bg-black/40 z-30 hidden"></div>
    <?php include __DIR__ . '/../../includes/sidebar.php'; ?>

    <?php
    $currentPage = $_GET['page'] ?? '';
    $mainClass   = ($currentPage === 'video-chat')
        ? 'p-0 m-0 min-h-screen md:ml-64'
        : 'p-8 min-h-screen md:ml-64 pt-20 md:pt-16';
    ?>
    <main class="<?= $mainClass ?>">