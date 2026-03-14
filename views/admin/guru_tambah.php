<?php
if (!isset($pdo)) {
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nip = input($_POST['nip']);
    $nama_guru = input($_POST['nama_guru']);
    $jenis_kelamin = input($_POST['jenis_kelamin']);
    $no_hp = input($_POST['no_hp']);
    $email = input($_POST['email']);
    $username = input($_POST['username']);
    $password = password_hash($_POST['password'], PASSWORD_BCRYPT);

    try {
        // Mulai Transaksi Database (Agar jika salah satu gagal, semua dibatalkan)
        $pdo->beginTransaction();

        // 1. Insert ke tabel users
        $stmt_user = $pdo->prepare("INSERT INTO users (username, password, role) VALUES (?, ?, 'guru')");
        $stmt_user->execute([$username, $password]);
        $user_id = $pdo->lastInsertId();

        // 2. Insert ke tabel guru
        $stmt_guru = $pdo->prepare("INSERT INTO guru (user_id, nip, nama_guru, jenis_kelamin, no_hp, email) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt_guru->execute([$user_id, $nip, $nama_guru, $jenis_kelamin, $no_hp, $email]);

        $pdo->commit();

        set_flash_message('success', 'Data Guru dan Akun Login berhasil dibuat!');
        echo "<script>window.location.href='index.php?page=guru';</script>";
        exit;
    } catch (PDOException $e) {
        $pdo->rollBack();
        set_flash_message('error', 'Gagal: ' . $e->getMessage());
    }
}
?>

<div class="mb-6 flex items-center justify-between">
    <div>
        <a href="index.php?page=guru"
            class="text-indigo-600 hover:text-indigo-800 text-sm font-medium flex items-center mb-2">
            <i class="fas fa-arrow-left mr-2"></i> Kembali
        </a>
        <h1 class="text-2xl font-bold text-gray-800">Tambah Tenaga Pendidik</h1>
    </div>
</div>

<form action="" method="POST" class="grid grid-cols-1 lg:grid-cols-3 gap-6">

    <div class="lg:col-span-2 space-y-6">
        <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100">
            <h3 class="text-lg font-bold text-gray-700 mb-4 border-b pb-2 italic text-emerald-600">Biodata Guru</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="md:col-span-2">
                    <label class="block text-sm font-semibold text-gray-600">Nama Lengkap & Gelar</label>
                    <input type="text" name="nama_guru" required
                        class="w-full mt-1 p-3 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-emerald-500 outline-none transition">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-600">NIP / No. Pegawai</label>
                    <input type="text" name="nip" required
                        class="w-full mt-1 p-3 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-emerald-500 outline-none transition">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-600">Jenis Kelamin</label>
                    <select name="jenis_kelamin"
                        class="w-full mt-1 p-3 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-emerald-500 outline-none transition">
                        <option value="L">Laki-laki</option>
                        <option value="P">Perempuan</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-600">Nomor HP (WhatsApp)</label>
                    <input type="text" name="no_hp"
                        class="w-full mt-1 p-3 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-emerald-500 outline-none transition">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-600">Email</label>
                    <input type="email" name="email"
                        class="w-full mt-1 p-3 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-emerald-500 outline-none transition">
                </div>
            </div>
        </div>
    </div>

    <div class="space-y-6">
        <div class="bg-indigo-900 p-6 rounded-2xl shadow-xl text-white">
            <h3 class="text-lg font-bold mb-4 border-b border-indigo-700 pb-2 flex items-center">
                <i class="fas fa-lock mr-2 text-yellow-400"></i> Akun Sistem
            </h3>
            <div class="space-y-4">
                <div>
                    <label class="block text-xs font-semibold uppercase tracking-wider text-indigo-300">Username</label>
                    <input type="text" name="username" required
                        class="w-full mt-1 p-3 bg-indigo-800 border border-indigo-700 rounded-xl focus:ring-2 focus:ring-yellow-400 outline-none transition placeholder-indigo-400 text-white"
                        placeholder="contoh: budi123">
                </div>
                <div>
                    <label class="block text-xs font-semibold uppercase tracking-wider text-indigo-300">Password</label>
                    <input type="password" name="password" required
                        class="w-full mt-1 p-3 bg-indigo-800 border border-indigo-700 rounded-xl focus:ring-2 focus:ring-yellow-400 outline-none transition text-white"
                        placeholder="••••••••">
                    <p class="text-[10px] mt-2 text-indigo-300 italic">*Password akan di-hash secara otomatis demi
                        keamanan.</p>
                </div>
                <button type="submit"
                    class="w-full bg-yellow-400 hover:bg-yellow-500 text-indigo-900 font-bold py-3 rounded-xl shadow-lg transition transform hover:scale-105 mt-4">
                    Simpan & Buat Akun
                </button>
            </div>
        </div>

        <div class="bg-emerald-50 border border-emerald-100 p-4 rounded-xl">
            <p class="text-xs text-emerald-700">
                <i class="fas fa-info-circle mr-1"></i> Guru yang ditambahkan secara otomatis akan memiliki akses login
                sebagai <b>Role: Guru</b>.
            </p>
        </div>
    </div>
</form>