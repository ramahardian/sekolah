<?php
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../config/database.php';

$stmtSettings = $pdo->query("SELECT setting_key, setting_value FROM cms_setting WHERE setting_key IN ('header_title', 'header_logo')");
$headerSettings = [];
if ($stmtSettings) {
    while ($row = $stmtSettings->fetch(PDO::FETCH_ASSOC)) {
        $headerSettings[$row['setting_key']] = $row['setting_value'];
    }
}
$pageTitle = $headerSettings['header_title'] ?? 'SIS Pro';
$pageLogo = $headerSettings['header_logo'] ?? '';

$error = '';

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION['csrf_token'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $posted_token = $_POST['csrf_token'] ?? '';
    if (!hash_equals($_SESSION['csrf_token'] ?? '', $posted_token)) {
        $error = "Permintaan tidak valid. Silakan coba lagi.";
    } else {
        $username = trim((string)($_POST['username'] ?? ''));
        $password = (string)($_POST['password'] ?? '');

        if ($username !== '' && $password !== '') {
            // 1. Cari user berdasarkan username
            $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
            $stmt->execute([$username]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            // 2. Verifikasi user dan password (hash)
            if ($user && password_verify($password, $user['password'])) {

                // 3. Buat Session
                session_regenerate_id(true);
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role'] = $user['role'];

                // Update waktu login terakhir (opsional)
                $pdo->prepare("UPDATE users SET last_login = NOW() WHERE id = ?")->execute([$user['id']]);

                // 4. Redirect ke dashboard
                header("Location: index.php?page=dashboard");
                exit;
            } else {
                $error = "Username atau password salah!";
            }
        } else {
            $error = "Semua kolom wajib diisi!";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - <?= htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8') ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>

<body class="bg-slate-100">

    <div class="min-h-screen flex items-center justify-center p-4">
        <div class="flex flex-col md:flex-row bg-white rounded-2xl shadow-2xl overflow-hidden max-w-4xl w-full">

            <div class="hidden md:flex md:w-1/2 bg-indigo-600 p-12 flex-col justify-between text-white relative">
                <div class="z-10">
                    <?php if (!empty($pageLogo)): ?>
                        <img src="<?= htmlspecialchars($pageLogo, ENT_QUOTES, 'UTF-8') ?>" alt="Logo" class="max-h-16 object-contain mb-4">
                    <?php elseif (!empty($pageTitle)): ?>
                        <h1 class="text-4xl font-bold mb-4"><?= htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8') ?></h1>
                    <?php endif; ?>
                    <p class="text-indigo-100 italic text-lg">Manajemen sekolah jadi lebih mudah, cepat, dan transparan.
                    </p>
                </div>
                <div class="z-10 bg-white/10 p-4 rounded-lg backdrop-blur-md">
                    <p class="text-sm italic">"Teknologi adalah alat, tapi guru adalah jantungnya."</p>
                </div>
                <div class="absolute -top-10 -right-10 w-40 h-40 bg-white/10 rounded-full"></div>
            </div>

            <div class="w-full md:w-1/2 p-8 md:p-12">
                <div class="mb-10 text-center md:text-left">
                    <h2 class="text-3xl font-bold text-gray-800">Login</h2>
                    <p class="text-gray-500 mt-2">Masuk untuk mengelola data sekolah</p>
                </div>

                <?php if (!empty($error)): ?>
                    <div class="mb-4 p-3 bg-red-100 border-l-4 border-red-500 text-red-700 text-sm" role="alert"
                        aria-live="polite">
                        <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
                    </div>
                <?php endif; ?>

                <form action="" method="POST" class="space-y-6">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8') ?>">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Username</label>
                        <input type="text" name="username" required autocomplete="username" autofocus
                            class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-indigo-500 outline-none transition"
                            placeholder="admin">
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Password</label>
                        <input type="password" name="password" required autocomplete="current-password"
                            class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-indigo-500 outline-none transition"
                            placeholder="••••••••">
                    </div>

                    <button type="submit"
                        class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-3 rounded-xl shadow-lg transition duration-200">
                        Masuk
                    </button>
                </form>
            </div>
        </div>
    </div>

</body>

</html>
