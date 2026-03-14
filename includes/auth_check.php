<?php
// Pastikan session dimulai hanya jika belum ada session yang berjalan
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Fungsi untuk memproteksi halaman agar wajib login
 */
function cekLogin()
{
    if (!isset($_SESSION['user_id'])) {
        // Jika tidak ada session user_id, tendang ke login
        header("Location: index.php?page=login");
        exit;
    }
}

/**
 * Fungsi untuk membatasi akses berdasarkan role (Admin Only)
 */
function wajibAdmin()
{
    if ($_SESSION['role'] !== 'admin') {
        // Jika bukan admin, arahkan ke dashboard dengan pesan error
        header("Location: index.php?page=dashboard&error=akses_ditolak");
        exit;
    }
}

/**
 * Fungsi untuk membatasi akses berdasarkan role (Guru Only)
 */
function wajibGuru()
{
    if (($_SESSION['role'] ?? '') !== 'guru') {
        header("Location: index.php?page=dashboard&error=akses_ditolak");
        exit;
    }
}

/**
 * Fungsi untuk membatasi akses berdasarkan role (Siswa Only)
 */
function wajibSiswa()
{
    if (($_SESSION['role'] ?? '') !== 'siswa') {
        header("Location: index.php?page=dashboard&error=akses_ditolak");
        exit;
    }
}

/**
 * Fungsi untuk mengecek apakah user sedang login (untuk halaman login)
 * Agar user yang sudah login tidak bisa kembali ke halaman login lagi
 */
function sudahLogin()
{
    if (isset($_SESSION['user_id'])) {
        header("Location: index.php?page=dashboard");
        exit;
    }
}
