<?php
if (!isset($pdo)) {
    exit;
}

// 1. Validasi ID
$id = isset($_GET['id']) ? (int) $_GET['id'] : null;
if (!$id) {
    header("Location: index.php?page=guru");
    exit;
}

// 2. Ambil data guru dan gabungkan dengan tabel users untuk mendapatkan username
$stmt = $pdo->prepare("
    SELECT g.*, u.username 
    FROM guru g 
    JOIN users u ON g.user_id = u.id 
    WHERE g.id = ?
");
$stmt->execute([$id]);
$g = $stmt->fetch();

if (!$g) {
    echo "<div class='p-4 bg-red-100 text-red-700 rounded-lg'>Data guru tidak ditemukan!</div>";
    exit;
}

// 3. Logika Update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nip = input($_POST['nip']);
    $nama_guru = input($_POST['nama_guru']);
    $jenis_kelamin = input($_POST['jenis_kelamin']);
    $no_hp = input($_POST['no_hp']);
    $email = input($_POST['email']);
    $username = input($_POST['username']);
    $password_baru = $_POST['password'];

    try {
        $pdo->beginTransaction();

        // Update Profil Guru
        $sql_guru = "UPDATE guru SET nip = ?, nama_guru = ?, jenis_kelamin = ?, no_hp = ?, email = ? WHERE id = ?";
        $pdo->prepare($sql_guru)->execute([$nip, $nama_guru, $jenis_kelamin, $no_hp, $email, $id]);

        // Update Akun User (Username saja secara default)
        if (empty($password_baru)) {
            $sql_user = "UPDATE users SET username = ? WHERE id = ?";
            $pdo->prepare($sql_user)->execute([$username, $g['user_id']]);
        } else {
            // Update Username DAN Password jika password diisi
            $hash_baru = password_hash($password_baru, PASSWORD_BCRYPT);
            $sql_user = "UPDATE users SET username = ?, password = ? WHERE id = ?";
            $pdo->prepare($sql_user)->execute([$username, $hash_baru, $g['user_id']]);
        }

        $pdo->commit();
        set_flash_message('success', 'Profil dan akun guru berhasil diperbarui!');
        echo "<script>window.location.href='index.php?page=guru';</script>";
        exit;
    } catch (PDOException $e) {
        $pdo->rollBack();
        set_flash_message('error', 'Gagal memperbarui: ' . $e->getMessage());
    }
}
?>

<div class="mb-6">
    <a href="index.php?page=guru"
        class="text-emerald-600 hover:text-emerald-800 text-sm font-medium flex items-center mb-2 transition">
        <i class="fas fa-arrow-left mr-2"></i> Kembali ke Daftar Guru
    </a>
    <h1 class="text-2xl font-bold text-gray-800">Edit Profil Guru</h1>
</div>

<form action="" method="POST" class="grid grid-cols-1 lg:grid-cols-3 gap-6">

    <div class="lg:col-span-2 space-y-6">
        <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100">
            <h3 class="text-lg font-bold text-gray-700 mb-6 flex items-center">
                <i class="fas fa-id-card mr-3 text-emerald-500"></i> Informasi Personal
            </h3>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                <div class="md:col-span-2">
                    <label class="block text-sm font-semibold text-gray-600 mb-2">Nama Lengkap & Gelar</label>
                    <input type="text" name="nama_guru" value="<?= $g['nama_guru'] ?>" required
                        class="w-full p-3 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-emerald-500 outline-none transition">
                </div>

                <div>
                    <label class="block text-sm font-semibold text-gray-600 mb-2">NIP</label>
                    <input type="text" name="nip" value="<?= $g['nip'] ?>" required
                        class="w-full p-3 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-emerald-500 outline-none transition">
                </div>

                <div>
                    <label class="block text-sm font-semibold text-gray-600 mb-2">Jenis Kelamin</label>
                    <select name="jenis_kelamin"
                        class="w-full p-3 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-emerald-500 outline-none transition">
                        <option value="L" <?= $g['jenis_kelamin'] == 'L' ? 'selected' : '' ?>>Laki-laki</option>
                        <option value="P" <?= $g['jenis_kelamin'] == 'P' ? 'selected' : '' ?>>Perempuan</option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-semibold text-gray-600 mb-2">No. WhatsApp</label>
                    <input type="text" name="no_hp" value="<?= $g['no_hp'] ?>"
                        class="w-full p-3 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-emerald-500 outline-none transition">
                </div>

                <div>
                    <label class="block text-sm font-semibold text-gray-600 mb-2">Email Aktif</label>
                    <input type="email" name="email" value="<?= $g['email'] ?>"
                        class="w-full p-3 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-emerald-500 outline-none transition">
                </div>
            </div>
        </div>
    </div>

    <div class="space-y-6">
        <div class="bg-slate-800 p-6 rounded-2xl shadow-xl text-white">
            <h3 class="text-lg font-bold mb-6 flex items-center border-b border-slate-700 pb-3">
                <i class="fas fa-user-lock mr-3 text-emerald-400"></i> Kredensial Login
            </h3>

            <div class="space-y-5">
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-400 mb-2">Username</label>
                    <input type="text" name="username" value="<?= $g['username'] ?>" required
                        class="w-full p-3 bg-slate-700 border border-slate-600 rounded-xl text-white focus:ring-2 focus:ring-emerald-400 outline-none transition">
                </div>

                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-400 mb-2">Ganti
                        Password</label>
                    <input type="password" name="password"
                        class="w-full p-3 bg-slate-700 border border-slate-600 rounded-xl text-white focus:ring-2 focus:ring-emerald-400 outline-none transition"
                        placeholder="••••••••">
                    <p class="text-[10px] text-slate-400 mt-2 italic">
                        *Kosongkan jika tidak ingin mengubah password lama.
                    </p>
                </div>

                <div class="pt-4">
                    <button type="submit"
                        class="w-full bg-emerald-500 hover:bg-emerald-600 text-white font-bold py-3 rounded-xl shadow-lg transition-all transform hover:-translate-y-1">
                        <i class="fas fa-save mr-2"></i> Perbarui Data
                    </button>
                </div>
            </div>
        </div>

        <div class="p-4 bg-amber-50 border border-amber-100 rounded-xl flex gap-3">
            <i class="fas fa-exclamation-triangle text-amber-500 mt-1"></i>
            <p class="text-xs text-amber-800 leading-relaxed">
                Perubahan username akan langsung berdampak pada sesi login guru yang bersangkutan.
            </p>
        </div>
    </div>
</form>