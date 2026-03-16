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

-- Tabel untuk hubungan siswa-kelas (many-to-many)
CREATE TABLE IF NOT EXISTS `student_classes` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `student_id` int(11) NOT NULL,
    `class_id` int(11) NOT NULL,
    `academic_year` varchar(9) DEFAULT NULL,
    `semester` enum('1','2') DEFAULT '1',
    `enrollment_date` date DEFAULT NULL,
    `status` enum('active','completed','transferred') DEFAULT 'active',
    `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `unique_student_class` (`student_id`, `class_id`, `academic_year`, `semester`),
    KEY `idx_student_id` (`student_id`),
    KEY `idx_class_id` (`class_id`),
    FOREIGN KEY (`student_id`) REFERENCES `siswa`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`class_id`) REFERENCES `kelas`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabel RFID untuk siswa
CREATE TABLE IF NOT EXISTS `student_rfid` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `siswa_id` int(11) NOT NULL,
    `rfid_code` varchar(100) NOT NULL UNIQUE,
    `card_status` enum('active','inactive','lost','damaged') DEFAULT 'active',
    `issued_date` date DEFAULT NULL,
    `expired_date` date DEFAULT NULL,
    `notes` text DEFAULT NULL,
    `created_by` int(11) NOT NULL,
    `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
    `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `unique_siswa_rfid` (`siswa_id`, `rfid_code`),
    KEY `idx_rfid_code` (`rfid_code`),
    KEY `idx_siswa_id` (`siswa_id`),
    FOREIGN KEY (`siswa_id`) REFERENCES `siswa`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabel log scan RFID
CREATE TABLE IF NOT EXISTS `rfid_logs` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `rfid_code` varchar(100) NOT NULL,
    `siswa_id` int(11) DEFAULT NULL,
    `scan_type` enum('check_in','check_out') NOT NULL,
    `scan_time` timestamp DEFAULT CURRENT_TIMESTAMP,
    `device_location` varchar(100) DEFAULT NULL,
    `status` enum('success','invalid_card','student_not_found','duplicate_scan') NOT NULL,
    `ip_address` varchar(45) DEFAULT NULL,
    `user_agent` text DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `idx_rfid_code` (`rfid_code`),
    KEY `idx_siswa_id` (`siswa_id`),
    KEY `idx_scan_time` (`scan_time`),
    KEY `idx_status` (`status`),
    FOREIGN KEY (`siswa_id`) REFERENCES `siswa`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabel untuk testimonials
CREATE TABLE IF NOT EXISTS `testimonials` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `name` varchar(255) NOT NULL,
    `role` varchar(255) DEFAULT NULL,
    `content` text NOT NULL,
    `rating` int(1) DEFAULT 5,
    `image_url` varchar(500) DEFAULT NULL,
    `is_active` tinyint(1) DEFAULT 1,
    `created_by` int(11) NOT NULL,
    `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
    `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_is_active` (`is_active`),
    KEY `idx_created_at` (`created_at`),
    FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- Tabel untuk room chat per kelas
CREATE TABLE IF NOT EXISTS `chat_rooms` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `class_id` int(11) NOT NULL,
    `room_name` varchar(255) NOT NULL,
    `room_code` varchar(50) NOT NULL UNIQUE,
    `is_active` tinyint(1) DEFAULT 1,
    `created_by` int(11) NOT NULL,
    `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
    `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_class_id` (`class_id`),
    KEY `idx_room_code` (`room_code`),
    FOREIGN KEY (`class_id`) REFERENCES `kelas`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
 
-- Tabel untuk pesan chat
CREATE TABLE IF NOT EXISTS `chat_messages` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `room_id` int(11) NOT NULL,
    `user_id` int(11) NOT NULL,
    `message_type` enum('text','file','image','video_call_start','video_call_end') DEFAULT 'text',
    `message` text DEFAULT NULL,
    `file_url` varchar(500) DEFAULT NULL,
    `file_name` varchar(255) DEFAULT NULL,
    `file_size` int(11) DEFAULT NULL,
    `is_deleted` tinyint(1) DEFAULT 0,
    `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_room_id` (`room_id`),
    KEY `idx_user_id` (`user_id`),
    KEY `idx_created_at` (`created_at`),
    FOREIGN KEY (`room_id`) REFERENCES `chat_rooms`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
 
-- Tabel untuk partisipasi room
CREATE TABLE IF NOT EXISTS `room_participants` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `room_id` int(11) NOT NULL,
    `user_id` int(11) NOT NULL,
    `role` enum('teacher','student') NOT NULL,
    `joined_at` timestamp DEFAULT CURRENT_TIMESTAMP,
    `last_seen_at` timestamp NULL DEFAULT NULL,
    `is_online` tinyint(1) DEFAULT 0,
    PRIMARY KEY (`id`),
    UNIQUE KEY `unique_room_user` (`room_id`,`user_id`),
    KEY `idx_user_id` (`user_id`),
    FOREIGN KEY (`room_id`) REFERENCES `chat_rooms`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
 
-- Tabel untuk sesi video call
CREATE TABLE IF NOT EXISTS `video_sessions` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `room_id` int(11) NOT NULL,
    `session_id` varchar(100) NOT NULL UNIQUE,
    `host_id` int(11) NOT NULL,
    `title` varchar(255) DEFAULT NULL,
    `max_participants` int(11) DEFAULT 50,
    `is_active` tinyint(1) DEFAULT 1,
    `started_at` timestamp NULL DEFAULT NULL,
    `ended_at` timestamp NULL DEFAULT NULL,
    `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_room_id` (`room_id`),
    KEY `idx_session_id` (`session_id`),
    KEY `idx_host_id` (`host_id`),
    FOREIGN KEY (`room_id`) REFERENCES `chat_rooms`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`host_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
 
-- Tabel untuk partisipan video call
CREATE TABLE IF NOT EXISTS `video_participants` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `session_id` int(11) NOT NULL,
    `user_id` int(11) NOT NULL,
    `joined_at` timestamp DEFAULT CURRENT_TIMESTAMP,
    `left_at` timestamp NULL DEFAULT NULL,
    `is_muted` tinyint(1) DEFAULT 0,
    `is_video_on` tinyint(1) DEFAULT 1,
    PRIMARY KEY (`id`),
    KEY `idx_session_id` (`session_id`),
    KEY `idx_user_id` (`user_id`),
    FOREIGN KEY (`session_id`) REFERENCES `video_sessions`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
 

