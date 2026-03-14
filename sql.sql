CREATE DATABASE IF NOT EXISTS sis_sekolah;
USE sis_sekolah;

-- 1. Tabel Users (Kredensial Login)
CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'guru', 'siswa') NOT NULL,
    last_login DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- 2. Tabel Guru
CREATE TABLE guru (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT UNIQUE,
    nip VARCHAR(20) UNIQUE NOT NULL,
    nama_guru VARCHAR(100) NOT NULL,
    jenis_kelamin ENUM('L', 'P'),
    no_hp VARCHAR(15),
    email VARCHAR(100),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- 3. Tabel Kelas
CREATE TABLE kelas (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nama_kelas VARCHAR(20) NOT NULL,
    tingkat ENUM('10', '11', '12') NOT NULL,
    wali_id INT UNIQUE, -- Satu guru hanya bisa jadi wali di satu kelas
    FOREIGN KEY (wali_id) REFERENCES guru(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- 4. Tabel Siswa (Sesuai request lengkap)
CREATE TABLE siswa (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT UNIQUE,
    kelas_id INT,
    nis VARCHAR(20) UNIQUE NOT NULL,
    nama_siswa VARCHAR(100) NOT NULL,
    jenis_kelamin ENUM('L', 'P') NOT NULL,
    tempat_lahir VARCHAR(50),
    tanggal_lahir DATE,
    alamat TEXT,
    no_hp VARCHAR(15),
    nama_ayah VARCHAR(100),
    nama_ibu VARCHAR(100),
    no_hp_orangtua VARCHAR(15),
    status_siswa ENUM('aktif', 'lulus', 'pindah') DEFAULT 'aktif',
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (kelas_id) REFERENCES kelas(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- 5. Tabel Mata Pelajaran
CREATE TABLE mapel (
    id INT PRIMARY KEY AUTO_INCREMENT,
    kode_mapel VARCHAR(10) UNIQUE NOT NULL,
    nama_mapel VARCHAR(100) NOT NULL,
    guru_id INT,
    FOREIGN KEY (guru_id) REFERENCES guru(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- 5b. Relasi Mapel - Guru (multi guru per mapel)
CREATE TABLE mapel_guru (
    mapel_id INT NOT NULL,
    guru_id INT NOT NULL,
    PRIMARY KEY (mapel_id, guru_id),
    FOREIGN KEY (mapel_id) REFERENCES mapel(id) ON DELETE CASCADE,
    FOREIGN KEY (guru_id) REFERENCES guru(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- 6. Tabel Jadwal Pelajaran
CREATE TABLE jadwal_pelajaran (
    id INT PRIMARY KEY AUTO_INCREMENT,
    kelas_id INT NOT NULL,
    mapel_id INT NOT NULL,
    guru_id INT NOT NULL,
    hari ENUM('Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu') NOT NULL,
    jam_mulai TIME NOT NULL,
    jam_selesai TIME NOT NULL,
    ruang VARCHAR(50) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (kelas_id) REFERENCES kelas(id) ON DELETE CASCADE,
    FOREIGN KEY (mapel_id) REFERENCES mapel(id) ON DELETE CASCADE,
    FOREIGN KEY (guru_id) REFERENCES guru(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- 6. Tabel Nilai
CREATE TABLE nilai (
    id INT PRIMARY KEY AUTO_INCREMENT,
    siswa_id INT NOT NULL,
    mapel_id INT NOT NULL,
    semester ENUM('1', '2') NOT NULL,
    tahun_ajaran VARCHAR(9) NOT NULL, -- Contoh: 2023/2024
    nilai_uts INT CHECK (nilai_uts BETWEEN 0 AND 100),
    nilai_uas INT CHECK (nilai_uas BETWEEN 0 AND 100),
    nilai_tugas INT CHECK (nilai_tugas BETWEEN 0 AND 100),
    FOREIGN KEY (siswa_id) REFERENCES siswa(id) ON DELETE CASCADE,
    FOREIGN KEY (mapel_id) REFERENCES mapel(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- 7. Tabel Ujian Online
CREATE TABLE ujian (
    id INT PRIMARY KEY AUTO_INCREMENT,
    mapel_id INT NOT NULL,
    judul VARCHAR(150) NOT NULL,
    deskripsi TEXT NULL,
    mulai DATETIME NULL,
    selesai DATETIME NULL,
    durasi_menit INT NULL,
    password_hash VARCHAR(255) NULL,
    status ENUM('draft', 'published') DEFAULT 'draft',
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (mapel_id) REFERENCES mapel(id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE ujian_soal (
    id INT PRIMARY KEY AUTO_INCREMENT,
    ujian_id INT NOT NULL,
    tipe ENUM('pg', 'essay') NOT NULL,
    pertanyaan TEXT NOT NULL,
    poin INT NOT NULL DEFAULT 1,
    FOREIGN KEY (ujian_id) REFERENCES ujian(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE ujian_opsi (
    id INT PRIMARY KEY AUTO_INCREMENT,
    soal_id INT NOT NULL,
    label VARCHAR(255) NOT NULL,
    is_benar TINYINT(1) NOT NULL DEFAULT 0,
    FOREIGN KEY (soal_id) REFERENCES ujian_soal(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE ujian_attempt (
    id INT PRIMARY KEY AUTO_INCREMENT,
    ujian_id INT NOT NULL,
    siswa_user_id INT NOT NULL,
    mulai_at DATETIME NULL,
    selesai_at DATETIME NULL,
    skor_total INT DEFAULT 0,
    status ENUM('in_progress', 'submitted') DEFAULT 'in_progress',
    UNIQUE KEY uniq_attempt (ujian_id, siswa_user_id),
    FOREIGN KEY (ujian_id) REFERENCES ujian(id) ON DELETE CASCADE,
    FOREIGN KEY (siswa_user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE ujian_jawaban (
    id INT PRIMARY KEY AUTO_INCREMENT,
    attempt_id INT NOT NULL,
    soal_id INT NOT NULL,
    jawaban_teks TEXT NULL,
    opsi_id INT NULL,
    skor INT NULL,
    FOREIGN KEY (attempt_id) REFERENCES ujian_attempt(id) ON DELETE CASCADE,
    FOREIGN KEY (soal_id) REFERENCES ujian_soal(id) ON DELETE CASCADE,
    FOREIGN KEY (opsi_id) REFERENCES ujian_opsi(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- 8. Tabel Forum
CREATE TABLE forum_kategori (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nama VARCHAR(100) NOT NULL,
    deskripsi VARCHAR(255) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE forum_thread (
    id INT PRIMARY KEY AUTO_INCREMENT,
    kategori_id INT NOT NULL,
    user_id INT NOT NULL,
    judul VARCHAR(150) NOT NULL,
    konten TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL,
    FOREIGN KEY (kategori_id) REFERENCES forum_kategori(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE forum_reply (
    id INT PRIMARY KEY AUTO_INCREMENT,
    thread_id INT NOT NULL,
    user_id INT NOT NULL,
    konten TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (thread_id) REFERENCES forum_thread(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- 8b. CMS Landing Page
CREATE TABLE cms_page (
    id INT PRIMARY KEY AUTO_INCREMENT,
    slug VARCHAR(50) UNIQUE NOT NULL,
    title VARCHAR(150) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE cms_section (
    id INT PRIMARY KEY AUTO_INCREMENT,
    page_id INT NOT NULL,
    section_key VARCHAR(50) NOT NULL,
    layout ENUM('hero', 'stats', 'features', 'content', 'cta', 'contact', 'testimonials') NOT NULL DEFAULT 'content',
    title VARCHAR(150) NULL,
    subtitle VARCHAR(200) NULL,
    body TEXT NULL,
    image_url VARCHAR(255) NULL,
    button_text VARCHAR(80) NULL,
    button_link VARCHAR(255) NULL,
    sort_order INT NOT NULL DEFAULT 0,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    UNIQUE KEY uniq_section (page_id, section_key),
    FOREIGN KEY (page_id) REFERENCES cms_page(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE cms_nav (
    id INT PRIMARY KEY AUTO_INCREMENT,
    page_id INT NOT NULL,
    label VARCHAR(100) NOT NULL,
    url VARCHAR(255) NOT NULL,
    target_blank TINYINT(1) NOT NULL DEFAULT 0,
    sort_order INT NOT NULL DEFAULT 0,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (page_id) REFERENCES cms_page(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE cms_setting (
    id INT PRIMARY KEY AUTO_INCREMENT,
    setting_key VARCHAR(100) UNIQUE NOT NULL,
    setting_value TEXT NULL
) ENGINE=InnoDB;

-- 9. Sistem Perpustakaan
CREATE TABLE perpustakaan_buku (
    id INT PRIMARY KEY AUTO_INCREMENT,
    kode_buku VARCHAR(30) UNIQUE NOT NULL,
    judul VARCHAR(150) NOT NULL,
    penulis VARCHAR(100) NULL,
    penerbit VARCHAR(100) NULL,
    tahun INT NULL,
    stok_total INT NOT NULL DEFAULT 0,
    stok_tersedia INT NOT NULL DEFAULT 0,
    lokasi VARCHAR(50) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE perpustakaan_peminjaman (
    id INT PRIMARY KEY AUTO_INCREMENT,
    buku_id INT NOT NULL,
    siswa_user_id INT NOT NULL,
    tanggal_pinjam DATETIME NOT NULL,
    tanggal_kembali DATETIME NULL,
    status ENUM('dipinjam', 'kembali') DEFAULT 'dipinjam',
    catatan VARCHAR(255) NULL,
    FOREIGN KEY (buku_id) REFERENCES perpustakaan_buku(id) ON DELETE CASCADE,
    FOREIGN KEY (siswa_user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- 10. Absensi Siswa
CREATE TABLE absensi (
    id INT PRIMARY KEY AUTO_INCREMENT,
    siswa_id INT NOT NULL,
    tanggal DATE NOT NULL,
    status ENUM('hadir', 'izin', 'sakit', 'alpha') NOT NULL DEFAULT 'hadir',
    keterangan VARCHAR(255) NULL,
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_absensi (siswa_id, tanggal),
    FOREIGN KEY (siswa_id) REFERENCES siswa(id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;
