<?php
// Cek jika file ini dipanggil tanpa index.php
if (!isset($pdo)) {
    header("Location: ../../index.php");
    exit;
}

// Ambil data statistik dari database
$totalSiswa = count_data($pdo, 'siswa');
$totalGuru = count_data($pdo, 'guru');
$totalKelas = count_data($pdo, 'kelas');
$totalMapel = count_data($pdo, 'mapel');
?>

<div class="mb-8">
    <h1 class="text-2xl font-bold text-gray-800">Dashboard</h1>
    <p class="text-gray-500">Selamat datang kembali, <span class="font-semibold text-indigo-600">
            <?= $_SESSION['username'] ?>
        </span>! Berikut ringkasan data sekolah hari ini.</p>
</div>

<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">

    <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100 flex items-center">
        <div class="p-3 rounded-full bg-blue-100 text-blue-600 mr-4">
            <i class="fas fa-user-graduate fa-2x"></i>
        </div>
        <div>
            <p class="text-sm text-gray-500 font-medium uppercase">Total Siswa</p>
            <h3 class="text-2xl font-bold text-gray-800">
                <?= $totalSiswa ?>
            </h3>
        </div>
    </div>

    <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100 flex items-center">
        <div class="p-3 rounded-full bg-green-100 text-green-600 mr-4">
            <i class="fas fa-chalkboard-teacher fa-2x"></i>
        </div>
        <div>
            <p class="text-sm text-gray-500 font-medium uppercase">Total Guru</p>
            <h3 class="text-2xl font-bold text-gray-800">
                <?= $totalGuru ?>
            </h3>
        </div>
    </div>

    <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100 flex items-center">
        <div class="p-3 rounded-full bg-purple-100 text-purple-600 mr-4">
            <i class="fas fa-school fa-2x"></i>
        </div>
        <div>
            <p class="text-sm text-gray-500 font-medium uppercase">Total Kelas</p>
            <h3 class="text-2xl font-bold text-gray-800">
                <?= $totalKelas ?>
            </h3>
        </div>
    </div>

    <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100 flex items-center">
        <div class="p-3 rounded-full bg-orange-100 text-orange-600 mr-4">
            <i class="fas fa-book fa-2x"></i>
        </div>
        <div>
            <p class="text-sm text-gray-500 font-medium uppercase">Mata Pelajaran</p>
            <h3 class="text-2xl font-bold text-gray-800">
                <?= $totalMapel ?>
            </h3>
        </div>
    </div>

</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

    <div class="lg:col-span-2 bg-white rounded-xl shadow-sm border border-gray-100 p-6">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-bold text-gray-800">Siswa Baru Terdaftar</h3>
            <a href="index.php?page=siswa" class="text-sm text-indigo-600 hover:underline">Lihat Semua</a>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left">
                <thead>
                    <tr class="text-gray-400 text-sm uppercase tracking-wider border-b">
                        <th class="pb-3 font-medium">Nama</th>
                        <th class="pb-3 font-medium">NIS</th>
                        <th class="pb-3 font-medium">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <?php
                    $stmt = $pdo->query("SELECT nama_siswa, nis, status_siswa FROM siswa ORDER BY id DESC LIMIT 5");
                    while ($row = $stmt->fetch()):
                        ?>
                        <tr>
                            <td class="py-4 text-sm font-semibold text-gray-700">
                                <?= $row['nama_siswa'] ?>
                            </td>
                            <td class="py-4 text-sm text-gray-500">
                                <?= $row['nis'] ?>
                            </td>
                            <td class="py-4">
                                <span class="px-2 py-1 text-xs font-bold rounded bg-green-100 text-green-700 uppercase">
                                    <?= $row['status_siswa'] ?>
                                </span>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="bg-indigo-700 rounded-xl shadow-lg p-6 text-white relative overflow-hidden">
        <div class="relative z-10">
            <h3 class="text-lg font-bold mb-2">Informasi Sesi</h3>
            <div class="space-y-3 mt-4">
                <div class="flex justify-between text-sm border-b border-indigo-500 pb-2">
                    <span>Role:</span>
                    <span class="font-mono uppercase">
                        <?= $_SESSION['role'] ?>
                    </span>
                </div>
                <div class="flex justify-between text-sm border-b border-indigo-500 pb-2">
                    <span>Login Sejak:</span>
                    <span>
                        <?= date('H:i') ?> WIB
                    </span>
                </div>
                <div class="flex justify-between text-sm">
                    <span>Server Status:</span>
                    <span class="text-green-300">Online</span>
                </div>
            </div>
            <button
                class="w-full mt-6 bg-white text-indigo-700 font-bold py-2 rounded-lg hover:bg-indigo-50 transition">
                Cetak Laporan
            </button>
        </div>
        <div class="absolute -bottom-10 -right-10 w-32 h-32 bg-indigo-500 rounded-full opacity-20"></div>
    </div>

</div>