<?php
// Cek jika file ini dipanggil tanpa index.php
if (!isset($pdo)) {
    header("Location: ../../index.php");
    exit;
}

// 1. Ambil ID dari URL
$id = isset($_GET['id']) ? $_GET['id'] : null;

if (!$id) {
    header("Location: index.php?page=siswa");
    exit;
}

// 2. Ambil data siswa lama
$stmt = $pdo->prepare("SELECT * FROM siswa WHERE id = ?");
$stmt->execute([$id]);
$s = $stmt->fetch();

if (!$s) {
    echo "Data siswa tidak ditemukan!";
    exit;
}

// 3. Ambil data kelas untuk dropdown
$data_kelas = $pdo->query("SELECT * FROM kelas ORDER BY nama_kelas ASC")->fetchAll();

// 4. Logika Update Data
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
    $status_siswa = input($_POST['status_siswa']);

    try {
        $sql = "UPDATE siswa SET 
                nis = ?, nama_siswa = ?, jenis_kelamin = ?, tempat_lahir = ?, 
                tanggal_lahir = ?, alamat = ?, no_hp = ?, nama_ayah = ?, 
                nama_ibu = ?, no_hp_orangtua = ?, kelas_id = ?, status_siswa = ?
                WHERE id = ?";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $nis,
            $nama_siswa,
            $jenis_kelamin,
            $tempat_lahir,
            $tanggal_lahir,
            $alamat,
            $no_hp,
            $nama_ayah,
            $nama_ibu,
            $no_hp_orangtua,
            $kelas_id,
            $status_siswa,
            $id
        ]);

        set_flash_message('success', 'Data siswa berhasil diperbarui!');
        echo "<script>window.location.href='index.php?page=siswa';</script>";
        exit;
    } catch (PDOException $e) {
        set_flash_message('error', 'Gagal memperbarui data: ' . $e->getMessage());
    }
}
?>

<div class="mb-6">
    <a href="index.php?page=siswa" class="text-indigo-600 hover:text-indigo-800 text-sm font-medium flex items-center">
        <i class="fas fa-arrow-left mr-2"></i> Kembali ke Daftar
    </a>
    <h1 class="text-2xl font-bold text-gray-800 mt-2">Edit Data Siswa: <span class="text-indigo-600">
            <?= $s['nama_siswa'] ?>
        </span></h1>
</div>

<form action="" method="POST" class="space-y-6 pb-10">
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

        <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100">
            <h3 class="text-lg font-bold text-gray-700 mb-4 border-b pb-2">Informasi Pribadi</h3>
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700">NIS</label>
                    <input type="text" name="nis" value="<?= $s['nis'] ?>" required
                        class="mt-1 w-full p-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 outline-none">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Nama Lengkap</label>
                    <input type="text" name="nama_siswa" value="<?= $s['nama_siswa'] ?>" required
                        class="mt-1 w-full p-2 border border-gray-300 rounded-lg">
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Jenis Kelamin</label>
                        <select name="jenis_kelamin" class="mt-1 w-full p-2 border border-gray-300 rounded-lg">
                            <option value="L" <?= $s['jenis_kelamin'] == 'L' ? 'selected' : '' ?>>Laki-laki</option>
                            <option value="P" <?= $s['jenis_kelamin'] == 'P' ? 'selected' : '' ?>>Perempuan</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Status</label>
                        <select name="status_siswa"
                            class="mt-1 w-full p-2 border border-gray-300 rounded-lg bg-yellow-50 font-bold">
                            <option value="aktif" <?= $s['status_siswa'] == 'aktif' ? 'selected' : '' ?>>Aktif</option>
                            <option value="lulus" <?= $s['status_siswa'] == 'lulus' ? 'selected' : '' ?>>Lulus</option>
                            <option value="pindah" <?= $s['status_siswa'] == 'pindah' ? 'selected' : '' ?>>Pindah</option>
                        </select>
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Kelas</label>
                    <select name="kelas_id" class="mt-1 w-full p-2 border border-gray-300 rounded-lg">
                        <option value="">Pilih Kelas</option>
                        <?php foreach ($data_kelas as $k): ?>
                            <option value="<?= $k['id'] ?>" <?= $s['kelas_id'] == $k['id'] ? 'selected' : '' ?>>
                                <?= $k['nama_kelas'] ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
        </div>

        <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100">
            <h3 class="text-lg font-bold text-gray-700 mb-4 border-b pb-2">Keluarga & Kontak</h3>
            <div class="space-y-4">
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Nama Ayah</label>
                        <input type="text" name="nama_ayah" value="<?= $s['nama_ayah'] ?>"
                            class="mt-1 w-full p-2 border border-gray-300 rounded-lg">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Nama Ibu</label>
                        <input type="text" name="nama_ibu" value="<?= $s['nama_ibu'] ?>"
                            class="mt-1 w-full p-2 border border-gray-300 rounded-lg">
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">No. HP Orang Tua</label>
                    <input type="text" name="no_hp_orangtua" value="<?= $s['no_hp_orangtua'] ?>"
                        class="mt-1 w-full p-2 border border-gray-300 rounded-lg">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Alamat</label>
                    <textarea name="alamat" rows="3"
                        class="mt-1 w-full p-2 border border-gray-300 rounded-lg"><?= $s['alamat'] ?></textarea>
                </div>
            </div>
        </div>
    </div>

    <div class="flex justify-end gap-4">
        <button type="submit"
            class="bg-indigo-600 text-white px-8 py-3 rounded-lg font-bold shadow-lg hover:bg-indigo-700 transition">
            <i class="fas fa-check-circle mr-2"></i> Simpan Perubahan
        </button>
    </div>
</form>