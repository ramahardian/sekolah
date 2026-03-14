<?php
/**
 * WEB ROUTES - Router Utama
 * Asumsi: auth_check.php, database.php, functions.php sudah di-include.
 * Variabel $page tersedia dari index.php.
 */

// 1. Halaman yang bisa diakses TANPA Login
if ($page === 'login') {
    sudahLogin(); // Fungsi dari auth_check.php
    include __DIR__ . '/../views/login.php';
    exit;
}

if ($page === 'landing') {
    include __DIR__ . '/../views/landing.php';
    exit;
}

// 2. Proteksi Global: Semua halaman di bawah ini WAJIB Login
cekLogin();

// 3. Logout
if ($page === 'logout') {
    session_destroy();
    header("Location: index.php?page=login");
    exit;
}

// --- RENDER LAYOUT (Header & Sidebar) ---
// Layout ini akan membungkus konten dinamis di bawah
include __DIR__ . '/../views/layouts/header.php';
?>

<div class="container mx-auto">
    <?php
    switch ($page) {
        case 'dashboard':
            $role = $_SESSION['role'] ?? '';
            if ($role === 'guru') {
                include __DIR__ . '/../views/guru/dashboard.php';
            } elseif ($role === 'siswa') {
                include __DIR__ . '/../views/siswa/dashboard.php';
            } else {
                include __DIR__ . '/../views/admin/dashboard.php';
            }
            break;

        // --- MODULE SISWA ---
        case 'siswa':
            wajibAdmin();
            include __DIR__ . '/../views/admin/siswa_index.php';
            break;
        case 'siswa-tambah':
            wajibAdmin();
            include __DIR__ . '/../views/admin/siswa_tambah.php';
            break;
        case 'siswa-edit':
            wajibAdmin();
            include __DIR__ . '/../views/admin/siswa_edit.php';
            break;

        // --- MODULE GURU ---
        case 'guru':
            wajibAdmin();
            include __DIR__ . '/../views/admin/guru_index.php';
            break;
        case 'guru-tambah':
            wajibAdmin();
            include __DIR__ . '/../views/admin/guru_tambah.php';
            break;
        case 'guru-edit':
            wajibAdmin();
            include __DIR__ . '/../views/admin/guru_edit.php';
            break;

        // --- MODULE KELAS ---
        case 'kelas':
            wajibAdmin();
            include __DIR__ . '/../views/admin/kelas_index.php';
            break;
        case 'kelas-tambah':
            wajibAdmin();
            include __DIR__ . '/../views/admin/kelas_tambah.php';
            break;
        case 'kelas-edit':
            wajibAdmin();
            include __DIR__ . '/../views/admin/kelas_edit.php';
            break;

        // --- MODULE MAPEL ---
        case 'mapel':
            wajibAdmin();
            include __DIR__ . '/../views/admin/mapel_index.php';
            break;
        case 'mapel-tambah':
            wajibAdmin();
            include __DIR__ . '/../views/admin/mapel_tambah.php';
            break;
        case 'mapel-edit':
            wajibAdmin();
            include __DIR__ . '/../views/admin/mapel_edit.php';
            break;
        case 'mapel-hapus':
            wajibAdmin();
            include __DIR__ . '/../views/admin/mapel_hapus.php';
            break;

        // --- CMS LANDING ---
        case 'cms-landing':
            wajibAdmin();
            include __DIR__ . '/../views/admin/cms_landing.php';
            break;
        case 'cms-landing-tambah':
            wajibAdmin();
            include __DIR__ . '/../views/admin/cms_landing_tambah.php';
            break;
        case 'cms-landing-edit':
            wajibAdmin();
            include __DIR__ . '/../views/admin/cms_landing_edit.php';
            break;
        case 'cms-landing-hapus':
            wajibAdmin();
            include __DIR__ . '/../views/admin/cms_landing_hapus.php';
            break;
        case 'cms-nav':
            wajibAdmin();
            include __DIR__ . '/../views/admin/cms_nav.php';
            break;
        case 'cms-nav-tambah':
            wajibAdmin();
            include __DIR__ . '/../views/admin/cms_nav_tambah.php';
            break;
        case 'cms-nav-edit':
            wajibAdmin();
            include __DIR__ . '/../views/admin/cms_nav_edit.php';
            break;
        case 'cms-nav-hapus':
            wajibAdmin();
            include __DIR__ . '/../views/admin/cms_nav_hapus.php';
            break;

        // --- MODULE JADWAL ---
        case 'jadwal':
            wajibAdmin();
            include __DIR__ . '/../views/admin/jadwal_index.php';
            break;
        case 'jadwal-tambah':
            wajibAdmin();
            include __DIR__ . '/../views/admin/jadwal_tambah.php';
            break;
        case 'jadwal-edit':
            wajibAdmin();
            include __DIR__ . '/../views/admin/jadwal_edit.php';
            break;
        case 'jadwal-hapus':
            wajibAdmin();
            include __DIR__ . '/../views/admin/jadwal_hapus.php';
            break;

        // --- MODULE PERPUSTAKAAN (ADMIN) ---
        case 'perpus-buku':
            wajibAdmin();
            include __DIR__ . '/../views/admin/perpus_buku_index.php';
            break;
        case 'perpus-buku-tambah':
            wajibAdmin();
            include __DIR__ . '/../views/admin/perpus_buku_tambah.php';
            break;
        case 'perpus-buku-edit':
            wajibAdmin();
            include __DIR__ . '/../views/admin/perpus_buku_edit.php';
            break;
        case 'perpus-buku-hapus':
            wajibAdmin();
            include __DIR__ . '/../views/admin/perpus_buku_hapus.php';
            break;
        case 'perpus-peminjaman':
            wajibAdmin();
            include __DIR__ . '/../views/admin/perpus_peminjaman.php';
            break;

        // --- MODULE FORUM ---
        case 'forum':
            include __DIR__ . '/../views/forum/index.php';
            break;
        case 'forum-kategori':
            include __DIR__ . '/../views/forum/kategori.php';
            break;
        case 'forum-thread':
            include __DIR__ . '/../views/forum/thread.php';
            break;
        case 'forum-thread-tambah':
            include __DIR__ . '/../views/forum/thread_tambah.php';
            break;
        case 'forum-kategori-tambah':
            wajibAdmin();
            include __DIR__ . '/../views/forum/kategori_tambah.php';
            break;

        // --- MODULE UJIAN (GURU) ---
        case 'guru-ujian':
            wajibGuru();
            include __DIR__ . '/../views/guru/ujian_index.php';
            break;
        case 'guru-ujian-tambah':
            wajibGuru();
            include __DIR__ . '/../views/guru/ujian_tambah.php';
            break;
        case 'guru-ujian-edit':
            wajibGuru();
            include __DIR__ . '/../views/guru/ujian_edit.php';
            break;
        case 'guru-ujian-soal':
            wajibGuru();
            include __DIR__ . '/../views/guru/ujian_soal.php';
            break;
        case 'guru-ujian-hasil':
            wajibGuru();
            include __DIR__ . '/../views/guru/ujian_hasil.php';
            break;
        case 'guru-ujian-nilai':
            wajibGuru();
            include __DIR__ . '/../views/guru/ujian_nilai.php';
            break;
        case 'guru-jadwal':
            wajibGuru();
            include __DIR__ . '/../views/guru/jadwal.php';
            break;

        // --- MODULE UJIAN (SISWA) ---
        case 'siswa-ujian':
            wajibSiswa();
            include __DIR__ . '/../views/siswa/ujian_index.php';
            break;
        case 'siswa-ujian-kerjakan':
            wajibSiswa();
            include __DIR__ . '/../views/siswa/ujian_kerjakan.php';
            break;
        case 'siswa-ujian-hasil':
            wajibSiswa();
            include __DIR__ . '/../views/siswa/ujian_hasil.php';
            break;
        case 'siswa-jadwal':
            wajibSiswa();
            include __DIR__ . '/../views/siswa/jadwal.php';
            break;
        case 'siswa-perpus':
            wajibSiswa();
            include __DIR__ . '/../views/siswa/perpus_index.php';
            break;
        case 'siswa-perpus-pinjam':
            wajibSiswa();
            include __DIR__ . '/../views/siswa/perpus_pinjam.php';
            break;
        case 'siswa-perpus-riwayat':
            wajibSiswa();
            include __DIR__ . '/../views/siswa/perpus_riwayat.php';
            break;
        case 'profil-siswa':
            $role = $_SESSION['role'] ?? '';
            if ($role === 'siswa') {
                wajibSiswa();
            } else {
                cekLogin();
            }
            include __DIR__ . '/../views/siswa/profil.php';
            break;
        case 'nilai-siswa':
            $role = $_SESSION['role'] ?? '';
            if ($role === 'siswa') {
                wajibSiswa();
            } else {
                cekLogin();
            }
            include __DIR__ . '/../views/siswa/nilai.php';
            break;
        case 'absensi':
            $role = $_SESSION['role'] ?? '';
            if ($role === 'admin' || $role === 'guru') {
                include __DIR__ . '/../views/admin/absensi_index.php';
            } else {
                wajibSiswa();
                include __DIR__ . '/../views/siswa/absensi.php';
            }
            break;

        // --- HALAMAN 404 ---
        default:
            echo "
            <div class='flex flex-col items-center justify-center h-96'>
                <h1 class='text-6xl font-bold text-gray-300'>404</h1>
                <p class='text-xl text-gray-500'>Ups! Halaman yang Anda cari tidak ditemukan.</p>
                <a href='index.php?page=dashboard' class='mt-4 text-blue-600 hover:underline'>Kembali ke Dashboard</a>
            </div>";
            break;
    }
    ?>
</div>

<?php
// --- RENDER FOOTER ---
include __DIR__ . '/../views/layouts/footer.php';
?>
