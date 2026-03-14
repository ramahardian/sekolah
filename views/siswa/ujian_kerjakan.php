<?php
if (!isset($pdo)) {
    exit;
}

$userId = $_SESSION['user_id'] ?? 0;
$ujianId = isset($_GET['id']) ? (int) $_GET['id'] : 0;

$ujianStmt = $pdo->prepare("SELECT u.*, m.nama_mapel
                            FROM ujian u
                            JOIN mapel m ON u.mapel_id = m.id
                            WHERE u.id = ? AND u.status = 'published'");
$ujianStmt->execute([$ujianId]);
$ujian = $ujianStmt->fetch(PDO::FETCH_ASSOC);

if (!$ujian) {
    echo "<div class='bg-red-50 border border-red-200 text-red-700 p-4 rounded-lg'>Ujian tidak tersedia.</div>";
    return;
}

$now = new DateTime();
if ($ujian['mulai'] && $now < new DateTime($ujian['mulai'])) {
    echo "<div class='bg-yellow-50 border border-yellow-200 text-yellow-700 p-4 rounded-lg'>Ujian belum dibuka.</div>";
    return;
}
if ($ujian['selesai'] && $now > new DateTime($ujian['selesai'])) {
    echo "<div class='bg-gray-50 border border-gray-200 text-gray-700 p-4 rounded-lg'>Ujian sudah berakhir.</div>";
    return;
}

$passwordRequired = !empty($ujian['password_hash']);
$passwordVerified = ($_SESSION['ujian_access'][$ujianId] ?? false) === true;

if ($passwordRequired && !$passwordVerified) {
    $passError = '';
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'verify_password') {
        $inputPassword = (string) ($_POST['password'] ?? '');
        if ($inputPassword === '') {
            $passError = "Password wajib diisi.";
        } elseif (!password_verify($inputPassword, $ujian['password_hash'])) {
            $passError = "Password salah.";
        } else {
            $_SESSION['ujian_access'][$ujianId] = true;
            header("Location: index.php?page=siswa-ujian-kerjakan&id=$ujianId");
            exit;
        }
    }
    ?>
    <div class="max-w-md">
        <div class="mb-6">
            <h1 class="text-2xl font-bold text-gray-800">Masukkan Password Ujian</h1>
            <p class="text-gray-500 text-sm">
                <?= htmlspecialchars($ujian['judul'], ENT_QUOTES, 'UTF-8') ?> ·
                <?= htmlspecialchars($ujian['nama_mapel'], ENT_QUOTES, 'UTF-8') ?>
            </p>
        </div>

        <?php if (!empty($passError)): ?>
            <div class="mb-4 p-3 bg-red-100 border-l-4 border-red-500 text-red-700 text-sm">
                <?= htmlspecialchars($passError, ENT_QUOTES, 'UTF-8') ?>
            </div>
        <?php endif; ?>

        <form method="POST" class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 space-y-4">
            <input type="hidden" name="action" value="verify_password">
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">Password</label>
                <input type="password" name="password" required
                    class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-indigo-500 outline-none transition">
            </div>
            <div class="flex items-center gap-3">
                <button type="submit"
                    class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2 px-6 rounded-lg transition">
                    Masuk Ujian
                </button>
                <a href="index.php?page=siswa-ujian" class="text-gray-600 hover:underline">Kembali</a>
            </div>
        </form>
    </div>
    <?php
    return;
}

$attemptStmt = $pdo->prepare("SELECT * FROM ujian_attempt WHERE ujian_id = ? AND siswa_user_id = ?");
$attemptStmt->execute([$ujianId, $userId]);
$attempt = $attemptStmt->fetch(PDO::FETCH_ASSOC);

if ($attempt && $attempt['status'] === 'submitted') {
    header("Location: index.php?page=siswa-ujian-hasil&attempt=" . (int) $attempt['id']);
    exit;
}

if (!$attempt) {
    $insertAttempt = $pdo->prepare("INSERT INTO ujian_attempt (ujian_id, siswa_user_id, mulai_at) VALUES (?, ?, NOW())");
    $insertAttempt->execute([$ujianId, $userId]);
    $attemptId = (int) $pdo->lastInsertId();
    $attempt = [
        'id' => $attemptId,
        'status' => 'in_progress'
    ];
} else {
    $attemptId = (int) $attempt['id'];
}

$soalStmt = $pdo->prepare("SELECT * FROM ujian_soal WHERE ujian_id = ? ORDER BY id ASC");
$soalStmt->execute([$ujianId]);
$soal = $soalStmt->fetchAll(PDO::FETCH_ASSOC);

if (count($soal) === 0) {
    echo "<div class='bg-yellow-50 border border-yellow-200 text-yellow-700 p-4 rounded-lg'>Soal belum tersedia. Silakan hubungi guru.</div>";
    return;
}

$opsiBySoal = [];
if ($soal) {
    $ids = array_column($soal, 'id');
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $opsiStmt = $pdo->prepare("SELECT * FROM ujian_opsi WHERE soal_id IN ($placeholders) ORDER BY id ASC");
    $opsiStmt->execute($ids);
    $opsiRows = $opsiStmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($opsiRows as $row) {
        $opsiBySoal[$row['soal_id']][] = $row;
    }
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $jawabanPg = $_POST['jawaban'] ?? [];
    $jawabanEssay = $_POST['jawaban_essay'] ?? [];

    $pdo->prepare("DELETE FROM ujian_jawaban WHERE attempt_id = ?")->execute([$attemptId]);

    $insertJawaban = $pdo->prepare("INSERT INTO ujian_jawaban (attempt_id, soal_id, jawaban_teks, opsi_id, skor)
                                    VALUES (?, ?, ?, ?, ?)");

    $totalSkor = 0;

    foreach ($soal as $s) {
        $soalId = (int) $s['id'];
        if ($s['tipe'] === 'pg') {
            $opsiId = isset($jawabanPg[$soalId]) ? (int) $jawabanPg[$soalId] : 0;
            $opsiList = $opsiBySoal[$soalId] ?? [];
            $isCorrect = false;
            foreach ($opsiList as $o) {
                if ((int) $o['id'] === $opsiId && (int) $o['is_benar'] === 1) {
                    $isCorrect = true;
                    break;
                }
            }
            $skor = $isCorrect ? (int) $s['poin'] : 0;
            $totalSkor += $skor;
            $insertJawaban->execute([$attemptId, $soalId, null, $opsiId ?: null, $skor]);
        } else {
            $jawaban = trim((string) ($jawabanEssay[$soalId] ?? ''));
            $insertJawaban->execute([$attemptId, $soalId, $jawaban ?: null, null, null]);
        }
    }

    $pdo->prepare("UPDATE ujian_attempt SET selesai_at = NOW(), skor_total = ?, status = 'submitted' WHERE id = ?")
        ->execute([$totalSkor, $attemptId]);

    set_flash_message('success', 'Ujian berhasil dikumpulkan.');
    header("Location: index.php?page=siswa-ujian-hasil&attempt=$attemptId");
    exit;
}
?>

<div class="mb-6">
    <h1 class="text-2xl font-bold text-gray-800">Kerjakan Ujian</h1>
    <p class="text-gray-500 text-sm">
        <?= htmlspecialchars($ujian['judul'], ENT_QUOTES, 'UTF-8') ?> ·
        <?= htmlspecialchars($ujian['nama_mapel'], ENT_QUOTES, 'UTF-8') ?>
    </p>
</div>

<?php if ($error): ?>
    <div class="mb-4 p-3 bg-red-100 border-l-4 border-red-500 text-red-700 text-sm">
        <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
    </div>
<?php endif; ?>

<form method="POST" class="space-y-4">
    <?php foreach ($soal as $index => $s): ?>
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
            <div class="flex items-center justify-between">
                <p class="text-sm text-gray-500">Soal <?= $index + 1 ?> · <?= (int) $s['poin'] ?> poin</p>
                <span class="text-xs px-2 py-1 rounded-full bg-indigo-50 text-indigo-700">
                    <?= $s['tipe'] === 'pg' ? 'Pilihan Ganda' : 'Essay' ?>
                </span>
            </div>
            <h4 class="text-base font-semibold text-gray-800 mt-2">
                <?= htmlspecialchars($s['pertanyaan'], ENT_QUOTES, 'UTF-8') ?>
            </h4>

            <?php if ($s['tipe'] === 'pg'): ?>
                <div class="mt-4 space-y-2">
                    <?php foreach ($opsiBySoal[$s['id']] ?? [] as $o): ?>
                        <label class="flex items-center gap-2 text-sm text-gray-700">
                            <input type="radio" name="jawaban[<?= (int) $s['id'] ?>]" value="<?= (int) $o['id'] ?>"
                                class="text-indigo-600">
                            <?= htmlspecialchars($o['label'], ENT_QUOTES, 'UTF-8') ?>
                        </label>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="mt-4">
                    <textarea name="jawaban_essay[<?= (int) $s['id'] ?>]" rows="4"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 outline-none transition"
                        placeholder="Tulis jawaban Anda..."></textarea>
                </div>
            <?php endif; ?>
        </div>
    <?php endforeach; ?>

    <div class="flex items-center gap-3">
        <button type="submit"
            class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-3 px-6 rounded-xl shadow-lg transition">
            Kumpulkan Ujian
        </button>
        <a href="index.php?page=siswa-ujian" class="text-gray-600 hover:underline">Kembali</a>
    </div>
</form>
