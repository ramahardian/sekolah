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
    <title>Sign In - <?= htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8') ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap');
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
        .bg-custom-blue { background-color: #3b71fe; }
    </style>
</head>

<body class="bg-slate-100 min-h-screen flex items-center justify-center p-4">

    <div class="bg-white rounded-[2.5rem] shadow-2xl overflow-hidden max-w-5xl w-full flex flex-col md:flex-row min-h-[700px]">
        
        <div class="w-full md:w-1/2 p-10 md:p-16 flex flex-col justify-center">
            <div class="mb-8 flex items-center gap-2">
                <div class="bg-blue-600 p-2 rounded-lg text-white">
                    <i class="fas fa-chart-line fa-lg"></i>
                </div>
                <span class="text-2xl font-bold text-slate-800">Filuick <span class="text-blue-600 font-medium text-lg italic">Pay</span></span>
            </div>

            <div class="mb-8 text-center">
                <h2 class="text-3xl font-bold text-gray-800">Sign In</h2>
                <p class="text-gray-400 mt-2">Welcome back! Please enter your details</p>
            </div>

            <?php if (!empty($error)): ?>
                <div class="mb-4 p-3 bg-red-50 border-l-4 border-red-500 text-red-700 text-sm rounded-r-lg" role="alert">
                    <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
                </div>
            <?php endif; ?>

            <form action="" method="POST" class="space-y-5">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8') ?>">
                
                <div>
                    <label class="block text-sm font-medium text-gray-500 mb-1.5 ml-1">Email / Username</label>
                    <input type="text" name="username" required 
                        class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 outline-none transition-all placeholder:text-gray-300"
                        placeholder="Enter your email">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-500 mb-1.5 ml-1">Password</label>
                    <div class="relative">
                        <input type="password" name="password" required
                            class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 outline-none transition-all placeholder:text-gray-300"
                            placeholder="••••••••">
                        <button type="button" class="absolute right-4 top-3.5 text-gray-400 hover:text-gray-600">
                            <i class="far fa-eye-slash"></i>
                        </button>
                    </div>
                </div>

                <div class="flex items-center justify-between text-sm">
                    <label class="flex items-center text-gray-600 cursor-pointer">
                        <input type="checkbox" class="rounded border-gray-300 text-blue-600 mr-2">
                        Remember for 30 Days
                    </label>
                    <a href="#" class="text-blue-600 font-semibold hover:underline">Forgot password</a>
                </div>

                <button type="submit"
                    class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-3.5 rounded-xl shadow-lg shadow-blue-200 transition-all active:scale-[0.98]">
                    Sign in
                </button>
            </form>

            <div class="relative my-8">
                <div class="absolute inset-0 flex items-center"><div class="w-full border-t border-gray-100"></div></div>
                <div class="relative flex justify-center text-xs uppercase"><span class="bg-white px-4 text-gray-400">OR</span></div>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <button class="flex items-center justify-center gap-2 py-3 border border-gray-200 rounded-xl hover:bg-gray-50 transition text-sm font-semibold text-gray-700">
                    <img src="https://www.svgrepo.com/show/475656/google-color.svg" class="h-5" alt=""> Google
                </button>
                <button class="flex items-center justify-center gap-2 py-3 border border-gray-200 rounded-xl hover:bg-gray-50 transition text-sm font-semibold text-gray-700">
                    <i class="fab fa-facebook text-blue-600 text-xl"></i> Facebook
                </button>
            </div>

            <p class="mt-8 text-center text-gray-500 text-sm">
                Don't have an account? <a href="#" class="text-blue-600 font-bold hover:underline">Sign up</a>
            </p>
        </div>

        <div class="hidden md:flex md:w-1/2 bg-custom-blue p-12 flex-col justify-center items-center text-white relative overflow-hidden">
            <div class="absolute -bottom-20 -left-20 w-64 h-64 bg-white/10 rounded-full blur-3xl"></div>
            
            <div class="z-10 text-center max-w-sm mb-12">
                <h1 class="text-4xl font-bold mb-6 leading-tight">Welcome back! Please sign in to your Filuick pay account</h1>
                <p class="text-blue-100 opacity-80 leading-relaxed">
                    Manage your school finances and data with our most advanced dashboard yet. Fast, secure, and transparent.
                </p>
            </div>

            <div class="relative z-10 w-full max-w-md bg-white rounded-2xl p-6 shadow-2xl text-slate-800">
                <div class="flex justify-between items-center mb-6">
                    <h3 class="font-bold text-lg">Sales Report</h3>
                    <div class="flex gap-4 text-[10px] text-gray-400">
                        <span class="flex items-center gap-1"><div class="w-2 h-2 rounded-full bg-blue-600"></div> Profit</span>
                        <span class="flex items-center gap-1"><div class="w-2 h-2 rounded-full bg-gray-200"></div> Expenses</span>
                    </div>
                </div>
                <div class="flex items-end justify-between h-32 gap-2">
                    <div class="w-full bg-gray-100 rounded-t-sm h-24 relative overflow-hidden">
                        <div class="absolute bottom-0 w-full bg-blue-500 h-12"></div>
                    </div>
                    <div class="w-full bg-gray-100 rounded-t-sm h-28 relative overflow-hidden">
                        <div class="absolute bottom-0 w-full bg-blue-500 h-16"></div>
                    </div>
                    <div class="w-full bg-gray-100 rounded-t-sm h-20 relative overflow-hidden">
                        <div class="absolute bottom-0 w-full bg-blue-500 h-8"></div>
                    </div>
                    <div class="w-full bg-gray-100 rounded-t-sm h-32 relative overflow-hidden">
                        <div class="absolute bottom-0 w-full bg-blue-500 h-20"></div>
                    </div>
                </div>
                <div class="flex justify-between mt-2 text-[10px] text-gray-400 uppercase">
                    <span>Jan</span><span>Feb</span><span>Mar</span><span>Apr</span>
                </div>

                <div class="absolute -top-10 -right-10 bg-white p-4 rounded-2xl shadow-xl w-32 text-center border border-gray-50">
                    <p class="text-[10px] text-gray-400 font-bold mb-2">Categories</p>
                    <div class="w-16 h-16 rounded-full border-[6px] border-blue-500 border-t-gray-100 mx-auto flex items-center justify-center">
                        <span class="text-[10px] font-bold">82%</span>
                    </div>
                </div>
            </div>

            <div class="mt-12 flex gap-2">
                <div class="w-8 h-1.5 bg-white rounded-full"></div>
                <div class="w-2 h-1.5 bg-white/40 rounded-full"></div>
                <div class="w-2 h-1.5 bg-white/40 rounded-full"></div>
            </div>
        </div>
    </div>

</body>
</html>
