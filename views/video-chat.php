<?php
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../config/database.php';

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Ambil parameter kelas
$classId = $_GET['class_id'] ?? null;
$roomId  = $_GET['room_id'] ?? null;

// Debug info
$debugInfo = [
    'class_id'    => $classId,
    'room_id'     => $roomId,
    'user_id'     => $_SESSION['user_id'] ?? null,
    'user_role'   => $_SESSION['role'] ?? '',
    'access_granted' => false
];

if (!$classId) {
    header("Location: index.php?page=dashboard");
    exit;
}

// Verifikasi user memiliki akses ke kelas ini
$userRole = $_SESSION['role'] ?? '';
$userId   = $_SESSION['user_id'] ?? 0;

// Cek apakah user adalah guru atau siswa di kelas ini
$classAccess = false;
if ($userRole === 'admin' || $userRole === 'teacher' || $userRole === 'guru') {
    $classAccess = true;
    $debugInfo['access_reason'] = 'Admin/Teacher/Guru access';
} else {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM student_classes WHERE student_id = ? AND class_id = ?");
    $stmt->execute([$userId, $classId]);
    $classAccess = $stmt->fetchColumn() > 0;
    $debugInfo['access_reason'] = 'student_classes check';

    if (!$classAccess) {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM siswa WHERE user_id = ? AND kelas_id = ?");
        $stmt->execute([$userId, $classId]);
        $classAccess = $stmt->fetchColumn() > 0;
        $debugInfo['access_reason'] = 'siswa.kelas_id fallback';
    }
}

$debugInfo['access_granted'] = $classAccess;

if (!$classAccess) {
    header("Location: index.php?page=dashboard");
    exit;
}

// Ambil info kelas
$stmt = $pdo->prepare("SELECT id, nama_kelas as class_name FROM kelas WHERE id = ?");
$stmt->execute([$classId]);
$class = $stmt->fetch(PDO::FETCH_ASSOC);

$debugInfo['class_found'] = !!$class;

if (!$class) {
    header("Location: index.php?page=dashboard");
    exit;
}

// Cari atau buat room chat untuk kelas ini
$stmt = $pdo->prepare("SELECT * FROM chat_rooms WHERE class_id = ?");
$stmt->execute([$classId]);
$room = $stmt->fetch(PDO::FETCH_ASSOC);

$debugInfo['room_found'] = !!$room;

if (!$room) {
    $roomCode = 'CLASS_' . $classId . '_' . strtoupper(substr(md5(time()), 0, 8));
    $stmt = $pdo->prepare("INSERT INTO chat_rooms (class_id, room_name, room_code, created_by) VALUES (?, ?, ?, ?)");
    $stmt->execute([$classId, 'Kelas ' . $class['class_name'], $roomCode, $userId]);

    $stmt = $pdo->prepare("SELECT * FROM chat_rooms WHERE class_id = ?");
    $stmt->execute([$classId]);
    $room = $stmt->fetch(PDO::FETCH_ASSOC);
    $debugInfo['room_created'] = true;
} else {
    $debugInfo['room_created'] = false;
}

$debugInfo['final_room_id'] = $room['id'];

// Tambahkan user sebagai participant jika belum ada
try {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM room_participants WHERE room_id = ? AND user_id = ?");
    $stmt->execute([$room['id'], $userId]);
    if ($stmt->fetchColumn() == 0) {
        $role = ($userRole === 'teacher' || $userRole === 'admin') ? 'teacher' : 'student';
        $stmt = $pdo->prepare("INSERT INTO room_participants (room_id, user_id, role) VALUES (?, ?, ?)");
        $stmt->execute([$room['id'], $userId, $role]);
        $debugInfo['participant_added'] = true;
    } else {
        $debugInfo['participant_added'] = false;
    }
} catch (Exception $e) {
    $debugInfo['participant_error']  = $e->getMessage();
    $debugInfo['participant_added']  = false;
}

// Update last seen
try {
    $stmt = $pdo->prepare("UPDATE room_participants SET last_seen_at = NOW(), is_online = 1 WHERE room_id = ? AND user_id = ?");
    $stmt->execute([$room['id'], $userId]);
    $debugInfo['last_seen_updated'] = true;
} catch (Exception $e) {
    $debugInfo['last_seen_error']   = $e->getMessage();
    $debugInfo['last_seen_updated'] = false;
}

// Ambil pesan terakhir
try {
    $stmt = $pdo->prepare("
        SELECT m.*, u.username,
               CASE
                   WHEN u.role = 'guru'  THEN (SELECT g.nama_guru  FROM guru  g WHERE g.user_id = u.id)
                   WHEN u.role = 'siswa' THEN (SELECT s.nama_siswa FROM siswa s WHERE s.user_id = u.id)
                   ELSE u.username
               END as full_name,
               u.role as user_role
        FROM chat_messages m
        JOIN users u ON m.user_id = u.id
        WHERE m.room_id = ? AND m.is_deleted = 0
        ORDER BY m.created_at DESC
        LIMIT 50
    ");
    $stmt->execute([$room['id']]);
    $messages = array_reverse($stmt->fetchAll(PDO::FETCH_ASSOC));
    $debugInfo['messages_loaded'] = count($messages);
} catch (Exception $e) {
    $debugInfo['messages_error']  = $e->getMessage();
    $messages = [];
    $debugInfo['messages_loaded'] = 0;
}

// Ambil participant yang online
try {
    $stmt = $pdo->prepare("
        SELECT p.*, u.username,
               CASE
                   WHEN u.role = 'guru'  THEN (SELECT g.nama_guru  FROM guru  g WHERE g.user_id = u.id)
                   WHEN u.role = 'siswa' THEN (SELECT s.nama_siswa FROM siswa s WHERE s.user_id = u.id)
                   ELSE u.username
               END as full_name,
               u.role as user_role
        FROM room_participants p
        JOIN users u ON p.user_id = u.id
        WHERE p.room_id = ? AND p.is_online = 1
        ORDER BY p.joined_at
    ");
    $stmt->execute([$room['id']]);
    $participants = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $debugInfo['participants_loaded'] = count($participants);
} catch (Exception $e) {
    $debugInfo['participants_error']  = $e->getMessage();
    $participants = [];
    $debugInfo['participants_loaded'] = 0;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Video Chat – <?= htmlspecialchars($class['class_name']) ?></title>
<script src="https://cdn.tailwindcss.com"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<style>
/* ===== RESET & BASE ===== */
*, *::before, *::after { box-sizing: border-box; }
body {
    font-family: 'Inter', sans-serif;
    margin: 0; padding: 0;
    background: #111;
    overflow: hidden;
    height: 100vh;
}

/* ===== SCROLLBAR ===== */
.scroll::-webkit-scrollbar { width: 5px; }
.scroll::-webkit-scrollbar-track { background: transparent; }
.scroll::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.15); border-radius: 99px; }
.scroll::-webkit-scrollbar-thumb:hover { background: rgba(255,255,255,0.25); }
.scroll-light::-webkit-scrollbar-thumb { background: #dde1e7; }
.scroll-light::-webkit-scrollbar-thumb:hover { background: #c5c9d0; }

/* ===== APP SHELL ===== */
.app-shell {
    display: flex;
    flex-direction: column;
    height: 100vh;
    overflow: hidden;
}

/* ===== HEADER ===== */
.app-header {
    height: 52px;
    min-height: 52px;
    background: #1a1a1a;
    border-bottom: 1px solid #2a2a2a;
    display: flex;
    align-items: center;
    padding: 0 14px;
    gap: 12px;
    color: #fff;
    flex-shrink: 0;
}

.header-back {
    color: rgba(255,255,255,0.6);
    width: 32px; height: 32px;
    display: flex; align-items: center; justify-content: center;
    border-radius: 6px;
    transition: background 0.15s, color 0.15s;
    flex-shrink: 0;
    text-decoration: none;
}
.header-back:hover { background: rgba(255,255,255,0.08); color: #fff; }

.header-title { flex: 1; min-width: 0; }
.header-title h1 {
    font-size: 14px; font-weight: 600;
    color: #fff;
    white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
    display: flex; align-items: center; gap: 6px;
}
.header-title p {
    font-size: 11px; color: rgba(255,255,255,0.45);
    display: flex; align-items: center; gap: 4px;
    margin-top: 1px;
}

.live-dot {
    display: inline-block;
    width: 7px; height: 7px;
    background: #22c55e;
    border-radius: 50%;
    animation: blink 2s ease infinite;
}
@keyframes blink {
    0%,100%{ opacity:1; } 50%{ opacity:0.4; }
}

.header-actions {
    display: flex; align-items: center; gap: 4px;
    flex-shrink: 0;
}

.hbtn {
    display: flex; align-items: center; gap: 5px;
    color: rgba(255,255,255,0.6);
    font-size: 13px;
    padding: 5px 8px;
    border-radius: 6px;
    background: none; border: none; cursor: pointer;
    transition: background 0.15s, color 0.15s;
    white-space: nowrap;
}
.hbtn:hover { background: rgba(255,255,255,0.08); color: #fff; }
.hbtn.active { color: #3b82f6; background: rgba(59,130,246,0.12); }

.room-code-badge {
    display: flex; align-items: center; gap: 5px;
    background: rgba(255,255,255,0.07);
    border: 1px solid rgba(255,255,255,0.1);
    border-radius: 6px;
    padding: 4px 10px;
    font-size: 11px; color: rgba(255,255,255,0.5);
    font-family: 'Courier New', monospace;
}
.room-code-badge span { color: #86efac; }
.room-code-badge button {
    background: none; border: none; cursor: pointer;
    color: rgba(255,255,255,0.35);
    transition: color 0.15s; padding: 0; line-height: 1;
}
.room-code-badge button:hover { color: #fff; }

/* ===== CONTENT AREA ===== */
.content-area {
    flex: 1;
    display: flex;
    min-height: 0;
    overflow: hidden;
}

/* ===== VIDEO SECTION ===== */
.video-section {
    flex: 1;
    min-width: 0;
    display: flex;
    flex-direction: column;
    background: #111;
    min-height: 0;
}

.video-canvas {
    flex: 1;
    position: relative;
    padding: 0;
    min-height: 0;
    display: flex;
    align-items: stretch;
    overflow: hidden;
}

/* Main remote video */
.remote-video-wrap {
    flex: 1;
    background: #1e1e1e;
    border-radius: 0;
    overflow: hidden;
    position: relative;
    border: none;
}
.remote-video-wrap video {
    width: 100%; height: 100%; object-fit: cover;
    display: block;
}

/* Waiting overlay */
.waiting-overlay {
    position: absolute; inset: 0;
    display: flex; flex-direction: column;
    align-items: center; justify-content: center;
    background: #1e1e1e;
    color: #fff;
    text-align: center;
    padding: 24px;
}
.waiting-overlay .icon-ring {
    width: 72px; height: 72px;
    background: rgba(255,255,255,0.06);
    border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    margin-bottom: 16px;
}
.waiting-overlay h3 { font-size: 16px; font-weight: 600; margin-bottom: 6px; }
.waiting-overlay p { font-size: 13px; color: rgba(255,255,255,0.45); margin-bottom: 20px; }
.code-box {
    background: rgba(255,255,255,0.08);
    border: 1px solid rgba(255,255,255,0.1);
    border-radius: 8px;
    padding: 10px 18px;
    display: flex; align-items: center; gap: 10px;
}
.code-box code { font-size: 13px; color: #86efac; font-family: 'Courier New', monospace; }
.code-box button {
    background: none; border: none; cursor: pointer;
    color: rgba(255,255,255,0.35);
    transition: color 0.15s;
}
.code-box button:hover { color: #fff; }

/* Name overlay on remote video */
.remote-name-tag {
    position: absolute;
    bottom: 10px; left: 12px;
    background: rgba(0,0,0,0.65);
    color: #fff;
    font-size: 11px; font-weight: 500;
    padding: 3px 10px;
    border-radius: 20px;
    backdrop-filter: blur(4px);
}

/* Local PiP video */
.local-pip {
    position: absolute;
    bottom: 14px; right: 14px;
    width: 176px; height: 110px;
    background: #2a2a2a;
    border-radius: 10px;
    overflow: hidden;
    border: 2px solid rgba(255,255,255,0.12);
    box-shadow: 0 8px 24px rgba(0,0,0,0.5);
    z-index: 10;
    cursor: grab;
    transition: border-color 0.2s, box-shadow 0.2s;
}
.local-pip:hover {
    border-color: rgba(59,130,246,0.6);
    box-shadow: 0 8px 32px rgba(0,0,0,0.6);
}
.local-pip video { width: 100%; height: 100%; object-fit: cover; display: block; }
.local-pip .you-tag {
    position: absolute; top: 6px; left: 6px;
    background: rgba(0,0,0,0.65);
    color: #fff; font-size: 10px; font-weight: 500;
    padding: 2px 7px; border-radius: 20px;
    backdrop-filter: blur(4px);
}
.local-pip .video-off-cover {
    position: absolute; inset: 0;
    background: #1e1e1e;
    display: flex; align-items: center; justify-content: center;
    display: none;
}

/* ===== CONTROLS BAR ===== */
.controls-bar {
    background: #1a1a1a;
    border-top: 1px solid #2a2a2a;
    padding: 10px 16px;
    flex-shrink: 0;
}

.controls-inner {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    flex-wrap: wrap;
}

.ctrl-btn {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 3px;
    background: #252525;
    border: 1px solid rgba(255,255,255,0.07);
    border-radius: 10px;
    padding: 9px 16px;
    color: #fff;
    cursor: pointer;
    transition: background 0.15s, border-color 0.15s, transform 0.12s;
    min-width: 62px;
    white-space: nowrap;
}
.ctrl-btn:hover { background: #2f2f2f; border-color: rgba(255,255,255,0.15); transform: translateY(-1px); }
.ctrl-btn:active { transform: translateY(0); }
.ctrl-btn i { font-size: 18px; line-height: 1; }
.ctrl-btn .lbl { font-size: 10px; color: rgba(255,255,255,0.55); font-weight: 500; }
.ctrl-btn.off { background: #7f1d1d; border-color: #991b1b; }
.ctrl-btn.off .lbl { color: rgba(255,255,255,0.75); }
.ctrl-btn.end-btn { background: #dc2626; border-color: #b91c1c; }
.ctrl-btn.end-btn:hover { background: #b91c1c; }

/* ===== CHAT PANEL ===== */
.chat-panel {
    width: 320px;
    flex-shrink: 0;
    display: flex;
    flex-direction: column;
    background: #fff;
    border-left: 1px solid #e5e7eb;
    transition: width 0.3s cubic-bezier(0.4,0,0.2,1);
    overflow: hidden;
}
.chat-panel.closed { width: 0; }

.panel-header {
    padding: 13px 16px;
    background: #f9fafb;
    border-bottom: 1px solid #e5e7eb;
    display: flex; align-items: center; justify-content: space-between;
    flex-shrink: 0;
}
.panel-header h3 { font-size: 13px; font-weight: 600; color: #111827; display: flex; align-items: center; gap: 7px; }
.panel-close-btn {
    width: 28px; height: 28px;
    display: flex; align-items: center; justify-content: center;
    background: none; border: none; cursor: pointer;
    color: #9ca3af; border-radius: 6px;
    transition: background 0.15s, color 0.15s;
}
.panel-close-btn:hover { background: #f3f4f6; color: #374151; }

.messages-area {
    flex: 1;
    overflow-y: auto;
    padding: 12px;
    background: #f5f6fa;
    min-height: 0;
    display: flex;
    flex-direction: column;
    gap: 2px;
}

.msg-row {
    display: flex;
    gap: 6px;
    animation: slideIn 0.2s ease-out;
}
.msg-row.own { flex-direction: row-reverse; }

@keyframes slideIn {
    from { opacity:0; transform:translateY(8px); }
    to   { opacity:1; transform:translateY(0); }
}

.msg-avatar {
    width: 26px; height: 26px;
    background: linear-gradient(135deg, #3b82f6, #1d4ed8);
    border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    color: #fff; font-size: 10px; font-weight: 700;
    flex-shrink: 0; align-self: flex-end;
}

.msg-content { max-width: 72%; }
.msg-sender { font-size: 11px; color: #6b7280; margin-bottom: 3px; padding: 0 4px; }
.msg-bubble {
    padding: 8px 12px;
    border-radius: 16px;
    font-size: 13px;
    line-height: 1.5;
    word-break: break-word;
}
.msg-bubble.own-bubble {
    background: #3b82f6;
    color: #fff;
    border-bottom-right-radius: 4px;
}
.msg-bubble.other-bubble {
    background: #fff;
    color: #1f2937;
    border: 1px solid #e5e7eb;
    border-bottom-left-radius: 4px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.05);
}
.msg-time {
    font-size: 10px; color: #9ca3af;
    margin-top: 3px; padding: 0 4px;
    display: flex; align-items: center; gap: 3px;
}
.msg-row.own .msg-time { justify-content: flex-end; }

.msg-delete-btn {
    background: none;
    border: none;
    cursor: pointer;
    padding: 2px 4px;
    border-radius: 3px;
    transition: background 0.15s;
    opacity: 0.6;
}
.msg-delete-btn:hover {
    background: rgba(239, 68, 68, 0.1);
    opacity: 1;
}

.empty-chat {
    flex: 1;
    display: flex; flex-direction: column;
    align-items: center; justify-content: center;
    color: #9ca3af; text-align: center; padding: 24px;
}
.empty-chat i { font-size: 36px; margin-bottom: 10px; opacity: 0.4; }
.empty-chat p { font-size: 13px; }

.chat-input-wrap {
    padding: 10px 12px;
    border-top: 1px solid #e5e7eb;
    background: #fff;
    flex-shrink: 0;
}
.chat-form { display: flex; align-items: center; gap: 8px; }
.msg-input {
    flex: 1;
    background: #f3f4f6;
    border: 1.5px solid transparent;
    border-radius: 22px;
    padding: 8px 14px;
    font-size: 13px;
    font-family: inherit;
    outline: none;
    transition: border-color 0.2s, background 0.2s;
    color: #1f2937;
}
.msg-input:focus { border-color: #3b82f6; background: #fff; }
.send-btn {
    width: 36px; height: 36px;
    background: #3b82f6;
    border: none; border-radius: 50%;
    color: #fff; cursor: pointer; flex-shrink: 0;
    display: flex; align-items: center; justify-content: center;
    transition: background 0.15s, transform 0.12s;
    box-shadow: 0 2px 8px rgba(59,130,246,0.35);
}
.send-btn:hover { background: #2563eb; transform: scale(1.05); }
.send-btn:active { transform: scale(0.97); }

/* ===== PARTICIPANTS SIDEBAR ===== */
.participants-panel {
    width: 0;
    overflow: hidden;
    flex-shrink: 0;
    display: flex;
    flex-direction: column;
    background: #fff;
    border-left: 1px solid #e5e7eb;
    transition: width 0.3s cubic-bezier(0.4,0,0.2,1);
}
.participants-panel.open { width: 256px; }

.participant-item {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 10px 14px;
    border-bottom: 1px solid #f3f4f6;
    transition: background 0.15s;
}
.participant-item:hover { background: #f9fafb; }

.p-avatar {
    width: 36px; height: 36px;
    background: linear-gradient(135deg, #3b82f6, #1d4ed8);
    border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    color: #fff; font-size: 14px; font-weight: 600;
    position: relative; flex-shrink: 0;
}
.online-dot {
    position: absolute; bottom: 0; right: 0;
    width: 9px; height: 9px;
    background: #22c55e;
    border: 2px solid #fff;
    border-radius: 50%;
}

.p-info { flex: 1; min-width: 0; }
.p-name { font-size: 13px; font-weight: 500; color: #111827; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.p-role { font-size: 11px; color: #6b7280; display: flex; align-items: center; gap: 4px; margin-top: 1px; }
.you-badge {
    font-size: 10px;
    background: rgba(59,130,246,0.1);
    color: #3b82f6;
    padding: 1px 6px; border-radius: 20px;
}

/* ===== MOBILE BOTTOM TABS ===== */
.mobile-tabbar {
    display: none;
    background: #1a1a1a;
    border-top: 1px solid #2a2a2a;
    flex-shrink: 0;
}
.tab-btn {
    flex: 1;
    display: flex; flex-direction: column;
    align-items: center; gap: 3px;
    padding: 9px 6px;
    background: none; border: none; cursor: pointer;
    color: rgba(255,255,255,0.45);
    font-size: 10px; font-weight: 500;
    border-top: 2px solid transparent;
    transition: color 0.15s, border-color 0.15s;
}
.tab-btn i { font-size: 16px; }
.tab-btn.active { color: #60a5fa; border-top-color: #3b82f6; }

/* ===== TOAST ===== */
.toast {
    position: fixed; top: 16px; right: 16px;
    background: #1f2937;
    color: #fff;
    font-size: 13px;
    padding: 10px 16px;
    border-radius: 10px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.35);
    display: flex; align-items: center; gap: 8px;
    opacity: 0;
    transform: translateY(-8px);
    transition: opacity 0.25s, transform 0.25s;
    pointer-events: none;
    z-index: 9999;
    white-space: nowrap;
}
.toast.show { opacity: 1; transform: translateY(0); }

/* ===== RESPONSIVE ===== */
@media (max-width: 900px) {
    .chat-panel { width: 280px; }
    .participants-panel.open { width: 220px; }
    .ctrl-btn { padding: 8px 12px; min-width: 54px; }
}

@media (max-width: 768px) {
    .room-code-badge { display: none; }

    /* Stack vertically, allow body to scroll on mobile */
    body { overflow: auto; height: auto; }
    .app-shell { height: auto; min-height: 100vh; overflow: visible; }

    .content-area {
        flex-direction: column;
        position: relative;
        overflow: visible;
        height: auto;
    }

    .video-section {
        flex: none;
        height: 55vw;
        min-height: 220px;
        max-height: 56vh;
    }

    /* Chat panel full-width, height auto so content shows */
    .chat-panel {
        width: 100% !important;
        border-left: none;
        border-top: 1px solid #e5e7eb;
        height: auto;
        min-height: 320px;
        display: none;
    }
    .chat-panel.mobile-visible { display: flex; }

    /* Messages area gets fixed height on mobile */
    .messages-area {
        height: 220px;
        flex: none;
        overflow-y: auto;
    }

    /* Participants slide from right, relative to viewport */
    .participants-panel {
        position: fixed;
        top: 0; right: 0;
        height: 100vh;
        border-left: 1px solid #e5e7eb;
        box-shadow: -4px 0 24px rgba(0,0,0,0.2);
        z-index: 999;
    }
    .participants-panel.open { width: 260px; }

    .local-pip { width: 96px; height: 68px; bottom: 12px; right: 12px; }

    .mobile-tabbar { display: flex; }

    .ctrl-hide-mobile { display: none !important; }

    .ctrl-btn { padding: 8px 10px; min-width: 50px; }
    .ctrl-btn i { font-size: 16px; }
}

@media (max-width: 480px) {
    .video-section { height: 56vw; min-height: 180px; }
    .local-pip { width: 76px; height: 54px; }
    .ctrl-btn { padding: 7px 8px; min-width: 44px; }
    .ctrl-btn i { font-size: 14px; }
    .messages-area { height: 200px; }
}
</style>
</head>
<body>
<div class="app-shell">

    <!-- ======== HEADER ======== -->
    <header class="app-header">
        <a href="index.php?page=video-classes" class="header-back" title="Kembali">
            <i class="fas fa-arrow-left text-sm"></i>
        </a>

        <div class="header-title">
            <h1>
                <i class="fas fa-video" style="color:#60a5fa;font-size:13px;"></i>
                <?= htmlspecialchars($class['class_name']) ?>
            </h1>
            <p>
                <span class="live-dot"></span>
                Live Meeting
            </p>
        </div>

        <div class="header-actions">
            <!-- Participant count -->
            <div class="hbtn" style="gap:4px;cursor:default;">
                <i class="fas fa-users" style="color:#22c55e;font-size:12px;"></i>
                <span id="participantCount" style="font-size:13px;"><?= count($participants) ?></span>
            </div>

            <!-- Toggle chat (desktop) -->
            <button id="chatToggleHeaderBtn" class="hbtn" onclick="toggleChat()" title="Toggle Chat">
                <i class="fas fa-comments" style="font-size:14px;"></i>
                <span class="hidden sm:inline">Chat</span>
            </button>

            <!-- Toggle participants -->
            <button id="sidebarHeaderBtn" class="hbtn" onclick="toggleParticipants()" title="Peserta">
                <i class="fas fa-user-friends" style="font-size:14px;"></i>
                <span class="hidden sm:inline">Peserta</span>
            </button>

            <!-- Room code -->
            <div class="room-code-badge">
                <i class="fas fa-key" style="color:#fbbf24;font-size:11px;"></i>
                <span><?= htmlspecialchars($room['room_code']) ?></span>
                <button onclick="copyRoomCode()" title="Salin kode"><i class="fas fa-copy" style="font-size:11px;"></i></button>
            </div>
        </div>
    </header>

    <!-- ======== CONTENT AREA ======== -->
    <div class="content-area">

        <!-- ======== VIDEO SECTION ======== -->
        <section class="video-section">
            <div class="video-canvas">
                <!-- Remote video -->
                <div class="remote-video-wrap">
                    <video id="remoteVideo" autoplay playsinline></video>

                    <!-- Waiting overlay -->
                    <div id="waitingOverlay" class="waiting-overlay">
                        <div class="icon-ring">
                            <i class="fas fa-video-slash" style="font-size:28px;color:rgba(255,255,255,0.3);"></i>
                        </div>
                        <h3>Menunggu peserta lain…</h3>
                        <p>Bagikan kode berikut untuk bergabung</p>
                        <div class="code-box">
                            <code><?= htmlspecialchars($room['room_code']) ?></code>
                            <button onclick="copyRoomCode()" title="Salin"><i class="fas fa-copy"></i></button>
                        </div>
                    </div>

                    <div class="remote-name-tag">
                        <i class="fas fa-user" style="font-size:9px;margin-right:4px;"></i>Remote Participant
                    </div>
                </div>

                <!-- Local PiP -->
                <div class="local-pip" id="localPip">
                    <video id="localVideo" autoplay muted playsinline></video>
                    <div class="you-tag">You</div>
                    <div class="video-off-cover" id="videoOffCover">
                        <i class="fas fa-video-slash" style="color:rgba(255,255,255,0.5);font-size:20px;"></i>
                    </div>
                </div>
            </div>

            <!-- ======== CONTROLS BAR ======== -->
            <div class="controls-bar">
                <div class="controls-inner">
                    <!-- Mute -->
                    <button id="muteBtn" class="ctrl-btn" onclick="toggleMute()">
                        <i class="fas fa-microphone"></i>
                        <span class="lbl">Mute</span>
                    </button>

                    <!-- Camera -->
                    <button id="videoToggleBtn" class="ctrl-btn" onclick="toggleVideo()">
                        <i class="fas fa-video"></i>
                        <span class="lbl">Stop Video</span>
                    </button>

                    <!-- End call -->
                    <button class="ctrl-btn end-btn" onclick="endCall()">
                        <i class="fas fa-phone-slash"></i>
                        <span class="lbl" style="color:rgba(255,255,255,0.8);">Keluar</span>
                    </button>
                </div>
            </div>
        </section>

        <!-- ======== CHAT PANEL ======== -->
        <section id="chatPanel" class="chat-panel">
            <div class="panel-header">
                <h3>
                    <i class="fas fa-comments" style="color:#3b82f6;"></i>
                    Chat
                </h3>
                <div class="flex items-center gap-1">
                    <?php if ($userRole === 'teacher' || $userRole === 'admin' || $userRole === 'guru'): ?>
                        <button class="hbtn" onclick="clearChat()" title="Bersihkan Semua Pesan" style="padding:4px 10px;font-size:11px;color:#fff;background:#ef4444;border-radius:4px;margin-right:8px;">
                            <i class="fas fa-trash-alt"></i>
                            <span class="ml-1">Bersihkan Chat</span>
                        </button>
                    <?php endif; ?>
                    <button class="panel-close-btn" onclick="toggleChat()">
                        <i class="fas fa-times" style="font-size:13px;"></i>
                    </button>
                </div>
            </div>

            <div id="messagesArea" class="messages-area scroll scroll-light">
                <?php if (empty($messages)): ?>
                    <div class="empty-chat">
                        <i class="fas fa-comment-dots"></i>
                        <p>Belum ada pesan. Mulai percakapan!</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($messages as $msg): ?>
                        <?php
                            $isOwn   = $msg['user_id'] == $userId;
                            $name    = htmlspecialchars($msg['full_name'] ?? $msg['username']);
                            $initial = strtoupper(mb_substr($msg['full_name'] ?? $msg['username'], 0, 1));
                            $time    = date('H:i', strtotime($msg['created_at']));
                        ?>
                        <div class="msg-row <?= $isOwn ? 'own' : '' ?>" data-message-id="<?= $msg['id'] ?>">
                            <?php if (!$isOwn): ?>
                                <div class="msg-avatar"><?= $initial ?></div>
                            <?php endif; ?>
                            <div class="msg-content">
                                <?php if (!$isOwn): ?>
                                    <p class="msg-sender"><?= $name ?></p>
                                <?php endif; ?>
                                <div class="msg-bubble <?= $isOwn ? 'own-bubble' : 'other-bubble' ?>">
                                    <?= htmlspecialchars($msg['message']) ?>
                                </div>
                                <div class="msg-time">
                                    <?= $time ?>
                                    <?php if ($isOwn): ?><i class="fas fa-check" style="font-size:9px;color:#93c5fd;"></i><?php endif; ?>
                                    <?php if ($isOwn || $userRole === 'admin' || $userRole === 'teacher' || $userRole === 'guru'): ?>
                                        <button class="msg-delete-btn" onclick="deleteMessage(<?= $msg['id'] ?>)" title="Hapus pesan">
                                            <i class="fas fa-trash" style="font-size:9px;color:#ef4444;"></i>
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <div class="chat-input-wrap">
                <form id="chatForm" class="chat-form">
                    <input type="text" id="messageInput" class="msg-input" placeholder="Tulis pesan…" autocomplete="off">
                    <button type="submit" class="send-btn" title="Kirim">
                        <i class="fas fa-paper-plane" style="font-size:14px;"></i>
                    </button>
                </form>
            </div>
        </section>

        <!-- ======== PARTICIPANTS SIDEBAR ======== -->
        <aside id="participantsSidebar" class="participants-panel">
            <div class="panel-header">
                <h3>
                    <i class="fas fa-users" style="color:#3b82f6;"></i>
                    Peserta (<span id="pCount"><?= count($participants) ?></span>)
                </h3>
                <button class="panel-close-btn" onclick="toggleParticipants()">
                    <i class="fas fa-times" style="font-size:13px;"></i>
                </button>
            </div>
            <div class="flex-1 overflow-y-auto scroll scroll-light" style="flex:1;" id="participantsList">
                <?php if (empty($participants)): ?>
                    <div style="text-align:center;padding:40px 20px;color:#9ca3af;">
                        <i class="fas fa-user-slash" style="font-size:32px;margin-bottom:10px;opacity:0.4;display:block;"></i>
                        <p style="font-size:13px;">Belum ada peserta</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($participants as $p): ?>
                        <?php
                            $pName    = htmlspecialchars($p['full_name'] ?? $p['username']);
                            $pInitial = strtoupper(mb_substr($p['full_name'] ?? $p['username'], 0, 1));
                            $isYou    = $p['user_id'] == $userId;
                            $isHost   = $p['user_role'] === 'teacher';
                        ?>
                        <div class="participant-item">
                            <div class="p-avatar">
                                <?= $pInitial ?>
                                <span class="online-dot"></span>
                            </div>
                            <div class="p-info">
                                <p class="p-name"><?= $pName ?> <?= $isYou ? '<span class="you-badge">Anda</span>' : '' ?></p>
                                <p class="p-role">
                                    <?php if ($isHost): ?>
                                        <i class="fas fa-crown" style="color:#f59e0b;font-size:10px;"></i> Host
                                    <?php else: ?>
                                        <i class="fas fa-user" style="font-size:10px;"></i> Peserta
                                    <?php endif; ?>
                                </p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </aside>

    </div><!-- /content-area -->

    <!-- ======== MOBILE BOTTOM TABS ======== -->
    <div class="mobile-tabbar">
        <button class="tab-btn active" id="tabVideo" onclick="mobileTab('video')">
            <i class="fas fa-video"></i>
            <span>Video</span>
        </button>
        <button class="tab-btn" id="tabChat" onclick="mobileTab('chat')">
            <i class="fas fa-comments"></i>
            <span>Chat</span>
        </button>
        <button class="tab-btn" id="tabPeople" onclick="toggleParticipants()">
            <i class="fas fa-users"></i>
            <span>Peserta</span>
        </button>
    </div>

</div><!-- /app-shell -->

<!-- Toast -->
<div class="toast" id="toast">
    <i class="fas fa-check-circle" id="toastIcon" style="color:#22c55e;"></i>
    <span id="toastMsg"></span>
</div>

<script>
/* ============================================================
   STATE
============================================================ */
let localStream      = null;
let peerConnection   = null;
let isMuted          = false;
let isVideoOff       = false;
let isChatOpen       = true;   // desktop default open
let isSidebarOpen    = false;
const USER_ID        = <?= (int)$userId ?>;
const ROOM_ID        = <?= (int)$room['id'] ?>;
const ROOM_CODE      = '<?= htmlspecialchars($room['room_code'], ENT_QUOTES) ?>';
const API_BASE       = '<?= rtrim(dirname($_SERVER['SCRIPT_NAME']), '/') ?>';

/* ============================================================
   DOM
============================================================ */
const $localVideo     = document.getElementById('localVideo');
const $remoteVideo    = document.getElementById('remoteVideo');
const $messagesArea   = document.getElementById('messagesArea');
const $messageInput   = document.getElementById('messageInput');
const $chatForm       = document.getElementById('chatForm');
const $chatPanel      = document.getElementById('chatPanel');
const $sidebar        = document.getElementById('participantsSidebar');
const $waitingOverlay = document.getElementById('waitingOverlay');
const $muteBtn        = document.getElementById('muteBtn');
const $videoBtn       = document.getElementById('videoToggleBtn');
const $videoOffCover  = document.getElementById('videoOffCover');

/* ============================================================
   WEBRTC
============================================================ */
let isInitiator = false;
let signalingInterval = null;
let lastSignalId = 0;

async function initWebRTC() {
    try {
        // Get user media
        localStream = await navigator.mediaDevices.getUserMedia({ video: true, audio: true });
        $localVideo.srcObject = localStream;
        
        // Build peer connection
        buildPeerConnection();
        
        // Start signaling
        startSignaling();
        
        // Check if we're the first participant (become initiator)
        checkIfInitiator();
        
    } catch (err) {
        console.warn('Camera/mic error:', err);
        showToast('Tidak dapat mengakses kamera/mikrofon. Pastikan browser memiliki izin.', 'error');
        
        // Still try to join as audio-only if video fails
        try {
            localStream = await navigator.mediaDevices.getUserMedia({ video: false, audio: true });
            $localVideo.srcObject = localStream;
            buildPeerConnection();
            startSignaling();
            checkIfInitiator();
            showToast('Hanya audio yang diaktifkan', 'warning');
        } catch (audioErr) {
            console.warn('Audio also failed:', audioErr);
            showToast('Tidak dapat mengakses mikrofon', 'error');
        }
    }
}

function buildPeerConnection() {
    const cfg = { 
        iceServers: [
            { urls: 'stun:stun.l.google.com:19302' },
            { urls: 'stun:stun1.l.google.com:19302' }
        ] 
    };
    
    peerConnection = new RTCPeerConnection(cfg);
    
    // Handle incoming tracks
    peerConnection.ontrack = e => {
        console.log('Received track:', e.streams[0]);
        $remoteVideo.srcObject = e.streams[0];
        $waitingOverlay.style.display = 'none';
        
        // Show toast when remote video starts
        showToast('Peserta lain bergabung!', 'success');
    };
    
    // Handle ICE candidates
    peerConnection.onicecandidate = e => {
        if (e.candidate) {
            sendSignal('ice-candidate', e.candidate);
        }
    };
    
    // Handle connection state changes
    peerConnection.onconnectionstatechange = e => {
        console.log('Connection state:', peerConnection.connectionState);
        if (peerConnection.connectionState === 'connected') {
            showToast('Terhubung dengan peserta lain', 'success');
        } else if (peerConnection.connectionState === 'disconnected') {
            $waitingOverlay.style.display = 'flex';
            showToast('Peserta lain terputus', 'warning');
        }
    };
    
    // Add local tracks
    if (localStream) {
        localStream.getTracks().forEach(t => peerConnection.addTrack(t, localStream));
    }
}

async function checkIfInitiator() {
    try {
        // Simple check: if we're the only participant, become initiator
        const participants = await getParticipants();
        if (participants.length <= 1) {
            isInitiator = true;
            console.log('We are the initiator');
            // Create offer after a short delay
            setTimeout(createOffer, 1000);
        }
    } catch (err) {
        console.warn('Error checking initiator:', err);
    }
}

async function createOffer() {
    if (!isInitiator || !peerConnection) return;
    
    try {
        const offer = await peerConnection.createOffer();
        await peerConnection.setLocalDescription(offer);
        sendSignal('offer', offer);
        console.log('Offer sent');
    } catch (err) {
        console.warn('Error creating offer:', err);
    }
}

async function handleOffer(offer) {
    if (isInitiator) return; // Don't handle offers if we're initiator
    
    try {
        await peerConnection.setRemoteDescription(offer);
        const answer = await peerConnection.createAnswer();
        await peerConnection.setLocalDescription(answer);
        sendSignal('answer', answer);
        console.log('Answer sent');
    } catch (err) {
        console.warn('Error handling offer:', err);
    }
}

async function handleAnswer(answer) {
    if (!isInitiator) return; // Only initiator handles answers
    
    try {
        await peerConnection.setRemoteDescription(answer);
        console.log('Answer received');
    } catch (err) {
        console.warn('Error handling answer:', err);
    }
}

async function handleIceCandidate(candidate) {
    try {
        await peerConnection.addIceCandidate(candidate);
        console.log('ICE candidate added');
    } catch (err) {
        console.warn('Error adding ICE candidate:', err);
    }
}

function sendSignal(type, data) {
    // For now, use localStorage as a simple signaling mechanism
    // In production, this should use WebSocket or server-side signaling
    const signal = {
        type: type,
        data: data,
        userId: USER_ID,
        roomId: ROOM_ID,
        timestamp: Date.now()
    };
    
    // Store in localStorage for other tabs in same room
    const key = `webrtc_signal_${ROOM_ID}`;
    const existing = localStorage.getItem(key);
    const signals = existing ? JSON.parse(existing) : [];
    signals.push(signal);
    
    // Keep only last 50 signals
    if (signals.length > 50) {
        signals.splice(0, signals.length - 50);
    }
    
    localStorage.setItem(key, JSON.stringify(signals));
    
    // Also try server-side signaling if available
    try {
        fetch(`${API_BASE}/api/webrtc_signal.php`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: new URLSearchParams({
                room_id: ROOM_ID,
                type: type,
                data: JSON.stringify(data)
            })
        }).catch(e => console.log('Server signaling not available:', e));
    } catch (e) {
        console.log('Server signaling failed:', e);
    }
}

function startSignaling() {
    // Poll for signals every 2 seconds
    signalingInterval = setInterval(() => {
        pollSignals();
    }, 2000);
}

function pollSignals() {
    try {
        // Check localStorage for signals
        const key = `webrtc_signal_${ROOM_ID}`;
        const existing = localStorage.getItem(key);
        
        if (existing) {
            const signals = JSON.parse(existing);
            const newSignals = signals.filter(s => s.userId !== USER_ID && s.timestamp > lastSignalId);
            
            newSignals.forEach(signal => {
                if (signal.type === 'offer') {
                    handleOffer(signal.data);
                } else if (signal.type === 'answer') {
                    handleAnswer(signal.data);
                } else if (signal.type === 'ice-candidate') {
                    handleIceCandidate(signal.data);
                }
                lastSignalId = Math.max(lastSignalId, signal.timestamp);
            });
        }
        
        // Also try server-side polling
        fetch(`${API_BASE}/api/webrtc_signal.php?room_id=${ROOM_ID}&last_id=0`)
            .then(res => res.json())
            .then(signals => {
                if (Array.isArray(signals)) {
                    signals.forEach(signal => {
                        if (signal.user_id != USER_ID) {
                            const data = JSON.parse(signal.signal_data);
                            if (signal.signal_type === 'offer') {
                                handleOffer(data);
                            } else if (signal.signal_type === 'answer') {
                                handleAnswer(data);
                            } else if (signal.signal_type === 'ice-candidate') {
                                handleIceCandidate(data);
                            }
                        }
                    });
                }
            })
            .catch(e => console.log('Server polling failed:', e));
            
    } catch (err) {
        console.warn('Error polling signals:', err);
    }
}

async function getParticipants() {
    try {
        const response = await fetch(`${API_BASE}/api/get_participants.php?room_id=${ROOM_ID}`);
        if (response.ok) {
            return await response.json();
        }
    } catch (err) {
        console.warn('Error getting participants:', err);
    }
    return [];
}

/* ============================================================
   CONTROLS
============================================================ */
function toggleMute() {
    if (!localStream) return;
    isMuted = !isMuted;
    localStream.getAudioTracks().forEach(t => t.enabled = !isMuted);
    $muteBtn.classList.toggle('off', isMuted);
    $muteBtn.innerHTML = isMuted
        ? '<i class="fas fa-microphone-slash"></i><span class="lbl">Unmute</span>'
        : '<i class="fas fa-microphone"></i><span class="lbl">Mute</span>';
}

function toggleVideo() {
    if (!localStream) return;
    isVideoOff = !isVideoOff;
    localStream.getVideoTracks().forEach(t => t.enabled = !isVideoOff);
    $videoBtn.classList.toggle('off', isVideoOff);
    $videoOffCover.style.display = isVideoOff ? 'flex' : 'none';
    $videoBtn.innerHTML = isVideoOff
        ? '<i class="fas fa-video-slash"></i><span class="lbl">Start Video</span>'
        : '<i class="fas fa-video"></i><span class="lbl">Stop Video</span>';
}

async function shareScreen() {
    try {
        const ss = await navigator.mediaDevices.getDisplayMedia({ video: true });
        const vt = ss.getVideoTracks()[0];
        $localVideo.srcObject = ss;
        if (peerConnection) {
            const sender = peerConnection.getSenders().find(s => s.track?.kind === 'video');
            if (sender) sender.replaceTrack(vt);
        }
        const $shareBtn = document.getElementById('shareBtn');
        if ($shareBtn) {
            $shareBtn.classList.add('off');
            $shareBtn.innerHTML = '<i class="fas fa-stop-circle"></i><span class="lbl">Stop Share</span>';
        }
        vt.onended = () => {
            if (localStream) $localVideo.srcObject = localStream;
            if ($shareBtn) {
                $shareBtn.classList.remove('off');
                $shareBtn.innerHTML = '<i class="fas fa-desktop"></i><span class="lbl">Share Screen</span>';
            }
        };
    } catch (e) { console.warn('Screen share:', e); }
}

async function endCall() {
    const result = await Swal.fire({
        title: 'Keluar dari Meeting?',
        text: 'Anda akan meninggalkan sesi video chat ini.',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#dc2626',
        cancelButtonColor: '#6b7280',
        confirmButtonText: 'Ya, keluar',
        cancelButtonText: 'Batal'
    });
    
    if (!result.isConfirmed) return;
    
    // Stop signaling
    if (signalingInterval) {
        clearInterval(signalingInterval);
        signalingInterval = null;
    }
    
    // Clean up localStorage signals
    const key = `webrtc_signal_${ROOM_ID}`;
    localStorage.removeItem(key);
    
    // Stop media streams
    localStream?.getTracks().forEach(t => t.stop());
    peerConnection?.close();
    
    // Redirect
    window.location.href = 'index.php?page=dashboard';
}

/* ============================================================
   PANEL TOGGLES
============================================================ */
function toggleChat() {
    isChatOpen = !isChatOpen;
    $chatPanel.classList.toggle('closed', !isChatOpen);
    document.getElementById('chatToggleHeaderBtn').classList.toggle('active', isChatOpen);
    if (isChatOpen) scrollBottom();
}

function toggleParticipants() {
    isSidebarOpen = !isSidebarOpen;
    $sidebar.classList.toggle('open', isSidebarOpen);
    document.getElementById('sidebarHeaderBtn').classList.toggle('active', isSidebarOpen);
}

/* ============================================================
   MOBILE TABS
============================================================ */
function mobileTab(tab) {
    document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
    if (tab === 'video') {
        document.getElementById('tabVideo').classList.add('active');
        $chatPanel.classList.remove('mobile-visible');
    } else if (tab === 'chat') {
        document.getElementById('tabChat').classList.add('active');
        $chatPanel.classList.add('mobile-visible');
        scrollBottom();
    }
}

/* ============================================================
   CHAT
============================================================ */
$chatForm.addEventListener('submit', async e => {
    e.preventDefault();
    const msg = $messageInput.value.trim();
    if (!msg) return;

    // Optimistically show message immediately
    const tempMsg = {
        id: 'temp_' + Date.now(),
        user_id: USER_ID,
        message: msg,
        full_name: null,
        username: '',
        created_at: new Date().toISOString()
    };
    const emptyState = $messagesArea.querySelector('.empty-chat');
    if (emptyState) emptyState.remove();
    appendMessage(tempMsg);
    scrollBottom();
    $messageInput.value = '';

    try {
        const res = await fetch(`${API_BASE}/api/send_message.php`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ room_id: ROOM_ID, message: msg, user_id: USER_ID })
        });
        if (!res.ok) {
            console.warn('Send failed:', res.status, await res.text());
        }
    } catch (err) { console.warn('Send msg error:', err); }
});

function scrollBottom() {
    $messagesArea.scrollTop = $messagesArea.scrollHeight;
}

function getLastMsgId() {
    // Only get numeric IDs, ignore temp_ IDs
    const rows = Array.from($messagesArea.querySelectorAll('[data-message-id]'))
        .filter(row => !row.dataset.messageId.startsWith('temp_'));
    return rows.length > 0 ? rows[rows.length - 1].dataset.messageId : 0;
}

async function deleteMessage(messageId) {
    const result = await Swal.fire({
        title: 'Hapus Pesan?',
        text: 'Pesan ini akan dihapus secara permanen.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#ef4444',
        cancelButtonColor: '#6b7280',
        confirmButtonText: 'Ya, hapus!',
        cancelButtonText: 'Batal'
    });
    
    if (!result.isConfirmed) return;
    
    try {
        const res = await fetch(`${API_BASE}/api/delete_message.php`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ message_id: messageId, room_id: ROOM_ID })
        });
        
        const data = await res.json();
        if (data.success) {
            // Remove message from DOM with animation
            const msgElement = document.querySelector(`[data-message-id="${messageId}"]`);
            if (msgElement) {
                msgElement.style.transition = 'opacity 0.3s, transform 0.3s';
                msgElement.style.opacity = '0';
                msgElement.style.transform = 'translateY(10px)';
                setTimeout(() => msgElement.remove(), 300);
            }
            
            Swal.fire({
                icon: 'success',
                title: 'Berhasil!',
                text: 'Pesan berhasil dihapus',
                timer: 2000,
                showConfirmButton: false
            });
        } else {
            Swal.fire({
                icon: 'error',
                title: 'Gagal!',
                text: data.error || 'Gagal menghapus pesan'
            });
        }
    } catch (err) {
        console.warn('Delete message error:', err);
        Swal.fire({
            icon: 'error',
            title: 'Kesalahan!',
            text: 'Kesalahan koneksi'
        });
    }
}

async function clearChat() {
    const result = await Swal.fire({
        title: 'Bersihkan Semua Chat?',
        text: 'Semua pesan di room ini akan dihapus. Peserta lain juga tidak akan melihat pesan lama.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#ef4444',
        cancelButtonColor: '#6b7280',
        confirmButtonText: 'Ya, bersihkan!',
        cancelButtonText: 'Batal'
    });
    
    if (!result.isConfirmed) return;
    
    try {
        const res = await fetch(`${API_BASE}/api/clear_chat.php`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ room_id: ROOM_ID })
        });
        
        const data = await res.json();
        if (data.success) {
            $messagesArea.innerHTML = `
                <div class="empty-chat">
                    <i class="fas fa-comment-dots"></i>
                    <p>Chat telah dibersihkan.</p>
                </div>
            `;
            
            Swal.fire({
                icon: 'success',
                title: 'Berhasil!',
                text: 'Chat berhasil dibersihkan',
                timer: 2000,
                showConfirmButton: false
            });
        } else {
            Swal.fire({
                icon: 'error',
                title: 'Gagal!',
                text: data.error || 'Gagal menghapus chat'
            });
        }
    } catch (err) {
        console.warn('Clear chat error:', err);
        Swal.fire({
            icon: 'error',
            title: 'Kesalahan!',
            text: 'Kesalahan koneksi'
        });
    }
}

function pollMessages() {
    // Find all temp messages to check against incoming real ones
    const tempMessages = Array.from($messagesArea.querySelectorAll('[data-message-id^="temp_"]'));
    
    fetch(`${API_BASE}/api/get_messages.php?room_id=${ROOM_ID}&last_id=${getLastMsgId()}`)
        .then(res => res.json())
        .then(msgs => {
            if (Array.isArray(msgs) && msgs.length > 0) {
                const emptyState = $messagesArea.querySelector('.empty-chat');
                if (emptyState) emptyState.remove();

                msgs.forEach(msg => {
                    // Prevent duplicate if ID already exists
                    if (!$messagesArea.querySelector(`[data-message-id="${msg.id}"]`)) {
                        // Check if this server message matches any of our temp messages (by content)
                        const matchingTemp = tempMessages.find(t => {
                            const bubble = t.querySelector('.msg-bubble');
                            return bubble && bubble.textContent === msg.message && t.classList.contains('own');
                        });
                        
                        if (matchingTemp) {
                            // If found a match, remove the temp one before adding real one
                            matchingTemp.remove();
                        }
                        
                        appendMessage(msg);
                    }
                });
                scrollBottom();
            }
        })
        .catch(e => console.warn('Poll error:', e));
}

function appendMessage(msg) {
    const isOwn   = msg.user_id == USER_ID;
    const name    = msg.full_name || msg.username;
    const initial = (name || '?').charAt(0).toUpperCase();
    const time    = new Date(msg.created_at).toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' });

    const row = document.createElement('div');
    row.className = `msg-row ${isOwn ? 'own' : ''}`;
    row.dataset.messageId = msg.id;

    row.innerHTML = `
        ${!isOwn ? `<div class="msg-avatar">${initial}</div>` : ''}
        <div class="msg-content">
            ${!isOwn ? `<p class="msg-sender">${escHtml(name)}</p>` : ''}
            <div class="msg-bubble ${isOwn ? 'own-bubble' : 'other-bubble'}">${escHtml(msg.message)}</div>
            <div class="msg-time">
                ${time}
                ${isOwn ? '<i class="fas fa-check" style="font-size:9px;color:#93c5fd;"></i>' : ''}
                <button class="msg-delete-btn" onclick="deleteMessage(${msg.id})" title="Hapus pesan">
                    <i class="fas fa-trash" style="font-size:9px;color:#ef4444;"></i>
                </button>
            </div>
        </div>
    `;
    $messagesArea.appendChild(row);
}

function escHtml(str) {
    const d = document.createElement('div');
    d.textContent = str;
    return d.innerHTML;
}

/* ============================================================
   UTILITIES
============================================================ */
function copyRoomCode() {
    navigator.clipboard.writeText(ROOM_CODE)
        .then(() => showToast('Kode room berhasil disalin!'))
        .catch(() => showToast('Gagal menyalin kode.', 'error'));
}

let toastTimer;
function showToast(msg, type = 'success') {
    const toast    = document.getElementById('toast');
    const toastMsg = document.getElementById('toastMsg');
    const toastIcon= document.getElementById('toastIcon');
    toastMsg.textContent = msg;
    toastIcon.className  = type === 'error'
        ? 'fas fa-exclamation-circle'
        : 'fas fa-check-circle';
    toastIcon.style.color = type === 'error' ? '#f87171' : '#22c55e';
    toast.classList.add('show');
    clearTimeout(toastTimer);
    toastTimer = setTimeout(() => toast.classList.remove('show'), 3000);
}

/* ============================================================
   DRAGGABLE PiP (optional)
============================================================ */
(function() {
    const pip = document.getElementById('localPip');
    let dragging = false, ox = 0, oy = 0;
    pip.addEventListener('mousedown', e => {
        dragging = true; ox = e.clientX - pip.offsetLeft; oy = e.clientY - pip.offsetTop;
        pip.style.cursor = 'grabbing';
    });
    document.addEventListener('mousemove', e => {
        if (!dragging) return;
        pip.style.right = 'auto'; pip.style.bottom = 'auto';
        pip.style.left = (e.clientX - ox) + 'px';
        pip.style.top  = (e.clientY - oy) + 'px';
    });
    document.addEventListener('mouseup', () => { dragging = false; pip.style.cursor = 'grab'; });
})();

/* ============================================================
   INIT
============================================================ */
document.addEventListener('DOMContentLoaded', () => {
    initWebRTC();
    scrollBottom();

    // Default mobile tab
    if (window.innerWidth <= 768) {
        mobileTab('video');
    }

    // Poll every 3s
    setInterval(pollMessages, 3000);
});

window.addEventListener('beforeunload', () => {
    // Stop signaling
    if (signalingInterval) {
        clearInterval(signalingInterval);
    }
    
    // Clean up localStorage signals
    const key = `webrtc_signal_${ROOM_ID}`;
    localStorage.removeItem(key);
    
    // Stop media streams
    localStream?.getTracks().forEach(t => t.stop());
    peerConnection?.close();
});
</script>
</body>
</html>
