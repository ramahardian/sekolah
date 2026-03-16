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
    FOREIGN KEY (`class_id`) REFERENCES `classes`(`id`) ON DELETE CASCADE,
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
