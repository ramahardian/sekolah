<?php
// Cek jika file ini dipanggil tanpa index.php
if (!isset($pdo)) {
    header("Location: ../../index.php");
    exit;
}

// Ambil data kelas untuk dropdown
$stmt_kelas = $pdo->query("SELECT * FROM kelas ORDER BY nama_kelas ASC");
$data_kelas = $stmt_kelas->fetchAll();

// Logika Simpan Data
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nis = input($_POST['nis']);
    $nama_siswa = input($_POST['nama_siswa']);
    $jenis_kelamin = input($_POST['jenis_kelamin']);
    $tempat_lahir = input($_POST['tempat_lahir']);
    $tanggal_lahir = input($_POST['tanggal_lahir']);
    $alamat = input($_POST['alamat']);
    $no_hp = input($_POST['no_hp']);
    $nama_ayah = input($_POST['nama_ayah']);
    $nama_ibu = input($_POST['nama_ibu']);
    $no_hp_orangtua = input($_POST['no_hp_orangtua']);
    $kelas_id = input($_POST['kelas_id']);

    try {
        $sql = "INSERT INTO siswa (nis, nama_siswa, jenis_kelamin, tempat_lahir, tanggal_lahir, alamat, no_hp, nama_ayah, nama_ibu, no_hp_orangtua, kelas_id) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$nis, $nama_siswa, $jenis_kelamin, $tempat_lahir, $tanggal_lahir, $alamat, $no_hp, $nama_ayah, $nama_ibu, $no_hp_orangtua, $kelas_id]);

        set_flash_message('success', 'Siswa baru berhasil ditambahkan!');
        echo "<script>window.location.href='index.php?page=siswa';</script>";
        exit;
    } catch (PDOException $e) {
        set_flash_message('error', 'Gagal menambah data: ' . $e->getMessage());
    }
}
?>

<div class="mb-6">
    <a href="index.php?page=siswa" class="text-indigo-600 hover:text-indigo-800 text-sm font-medium flex items-center">
        <i class="fas fa-arrow-left mr-2"></i> Kembali ke Daftar Siswa
    </a>
    <h1 class="text-2xl font-bold text-gray-800 mt-2">Tambah Siswa Baru</h1>
</div>

<form action="" method="POST" class="space-y-6">
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

        <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100">
            <h3 class="text-lg font-bold text-gray-700 mb-4 flex items-center border-b pb-2">
                <i class="fas fa-user-circle mr-2 text-indigo-500"></i> Informasi Pribadi
            </h3>
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700">NIS (Nomor Induk Siswa)</label>
                    <input type="text" name="nis" required
                        class="mt-1 w-full p-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 outline-none">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Nama Lengkap</label>
                    <input type="text" name="nama_siswa" required
                        class="mt-1 w-full p-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 outline-none">
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Jenis Kelamin</label>
                        <select name="jenis_kelamin"
                            class="mt-1 w-full p-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 outline-none">
                            <option value="L">Laki-laki</option>
                            <option value="P">Perempuan</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Kelas</label>
                        <select name="kelas_id"
                            class="mt-1 w-full p-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 outline-none">
                            <option value="">Pilih Kelas</option>
                            <?php foreach ($data_kelas as $k): ?>
                                <option value="<?= $k['id'] ?>">
                                
                                    <?= $k['nama_kelas'] ?>
                                    </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Tempat Lahir</label>
                        <input type="text" name="tempat_lahir"
                            class="mt-1 w-full p-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 outline-none">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Tanggal Lahir</label>
                        <input type="date" name="tanggal_lahir"
                            class="mt-1 w-full p-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 outline-none">
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Nomor HP Siswa</label>
                    <input type="text" name="no_hp"
                        class="mt-1 w-full p-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 outline-none"
                        placeholder="08...">
                </div>
            </div>
        </div>

        <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100">
            <h3 class="text-lg font-bold text-gray-700 mb-4 flex items-center border-b pb-2">
                <i class="fas fa-users mr-2 text-indigo-500"></i> Data Keluarga & Alamat
            </h3>
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700">Nama Ayah</label>
                    <input type="text" name="nama_ayah"
                        class="mt-1 w-full p-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 outline-none">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Nama Ibu</label>
                    <input type="text" name="nama_ibu"
                        class="mt-1 w-full p-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 outline-none">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">No. HP Orang Tua / Wali</label>
                    <input type="text" name="no_hp_orangtua" required
                        class="mt-1 w-full p-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 outline-none"
                        placeholder="Penting untuk notifikasi">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Alamat Lengkap</label>
                    <textarea name="alamat" rows="4"
                        class="mt-1 w-full p-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 outline-none"></textarea>
                </div>
            </div>
        </div>
    </div>

    <div class="flex justify-end gap-4 bg-gray-50 p-4 rounded-xl border border-gray-200">
        <button type="reset"
            class="px-6 py-2 border border-gray-300 rounded-lg text-gray-600 hover:bg-gray-100 transition">Reset</button>
        <button type="submit"
            class="px-6 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 shadow-lg shadow-indigo-200 transition">
            <i class="fas fa-save mr-2"></i> Simpan Data Siswa
        </button>
    </div>
</form>