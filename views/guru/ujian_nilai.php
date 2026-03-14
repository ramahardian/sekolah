<?php
if (!isset($pdo)) {
    exit;
}

$userId = $_SESSION['user_id'] ?? 0;
$guruStmt = $pdo->prepare("SELECT id, nama_guru FROM guru WHERE user_id = ?");
$guruStmt->execute([$userId]);
$guru = $guruStmt->fetch(PDO::FETCH_ASSOC);

if (!$guru) {
    echo "<div class='bg-red-50 border border-red-200 text-red-700 p-4 rounded-lg'>Data guru tidak ditemukan.</div>";
    return;
}

$attemptId = isset($_GET['attempt']) ? (int) $_GET['attempt'] : 0;
$attemptStmt = $pdo->prepare("SELECT a.*, u.username, s.nama_siswa, uj.judul, uj.id AS ujian_id, m.nama_mapel
                              FROM ujian_attempt a
                              JOIN ujian uj ON a.ujian_id = uj.id
                              JOIN mapel m ON uj.mapel_id = m.id
                              JOIN mapel_guru mg ON mg.mapel_id = m.id
                              JOIN users u ON a.siswa_user_id = u.id
                              LEFT JOIN siswa s ON s.user_id = u.id
                              WHERE a.id = ? AND mg.guru_id = ?");
$attemptStmt->execute([$attemptId, $guru['id']]);
$attempt = $attemptStmt->fetch(PDO::FETCH_ASSOC);

if (!$attempt) {
    echo "<div class='bg-red-50 border border-red-200 text-red-700 p-4 rounded-lg'>Attempt tidak ditemukan.</div>";
    return;
}

$jawabanStmt = $pdo->prepare("SELECT j.*, s.tipe, s.pertanyaan, s.poin
                              FROM ujian_jawaban j
                              JOIN ujian_soal s ON j.soal_id = s.id
                              WHERE j.attempt_id = ?
                              ORDER BY s.id ASC");
$jawabanStmt->execute([$attemptId]);
$jawaban = $jawabanStmt->fetchAll(PDO::FETCH_ASSOC);

$opsiBySoal = [];
if ($jawaban) {
    $soalIds = array_unique(array_column($jawaban, 'soal_id'));
    if ($soalIds) {
        $placeholders = implode(',', array_fill(0, count($soalIds), '?'));
        $opsiStmt = $pdo->prepare("SELECT * FROM ujian_opsi WHERE soal_id IN ($placeholders) ORDER BY id ASC");
        $opsiStmt->execute($soalIds);
        $opsiRows = $opsiStmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($opsiRows as $row) {
            $opsiBySoal[$row['soal_id']][] = $row;
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $skorInput = $_POST['skor'] ?? [];
    $skorMap = [];
    foreach ($jawaban as $j) {
        if ($j['tipe'] === 'essay') {
            $value = isset($skorInput[$j['id']]) ? (int) $skorInput[$j['id']] : 0;
            $value = max(0, min($value, (int) $j['poin']));
            $skorMap[$j['id']] = $value;
        }
    }

    if ($skorMap) {
        $update = $pdo->prepare("UPDATE ujian_jawaban SET skor = ? WHERE id = ?");
        foreach ($skorMap as $jawabanId => $nilai) {
            $update->execute([$nilai, $jawabanId]);
        }
    }

    $totalStmt = $pdo->prepare("SELECT COALESCE(SUM(skor), 0) FROM ujian_jawaban WHERE attempt_id = ?");
    $totalStmt->execute([$attemptId]);
    $total = (int) $totalStmt->fetchColumn();

    $pdo->prepare("UPDATE ujian_attempt SET skor_total = ?, status = 'submitted' WHERE id = ?")
        ->execute([$total, $attemptId]);

    set_flash_message('success', 'Penilaian berhasil disimpan.');
    header("Location: index.php?page=guru-ujian-nilai&attempt=$attemptId");
    exit;
}
?>

<div class="flex flex-col md:flex-row md:items-center justify-between mb-6 gap-4">
    <div>
        <h1 class="text-2xl font-bold text-gray-800">Penilaian Ujian</h1>
        <p class="text-gray-500 text-sm">
            <?= htmlspecialchars($attempt['judul'], ENT_QUOTES, 'UTF-8') ?> ·
            <?= htmlspecialchars($attempt['nama_mapel'], ENT_QUOTES, 'UTF-8') ?>
        </p>
        <p class="text-sm text-gray-500">
            Siswa: <?= htmlspecialchars($attempt['nama_siswa'] ?: $attempt['username'], ENT_QUOTES, 'UTF-8') ?>
        </p>
    </div>
    <a href="index.php?page=guru-ujian-hasil&id=<?= (int) $attempt['ujian_id'] ?>"
        class="text-gray-600 hover:underline">Kembali</a>
</div>

<?php display_flash_message(); ?>

<form method="POST" class="space-y-4">
    <?php foreach ($jawaban as $j): ?>
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
            <p class="text-sm text-gray-500">
                <?= $j['tipe'] === 'pg' ? 'Pilihan Ganda' : 'Essay' ?> · <?= (int) $j['poin'] ?> poin
            </p>
            <h4 class="text-base font-semibold text-gray-800 mt-1">
                <?= htmlspecialchars($j['pertanyaan'], ENT_QUOTES, 'UTF-8') ?>
            </h4>

            <?php if ($j['tipe'] === 'pg'): ?>
                <div class="mt-3 space-y-1 text-sm">
                    <?php foreach ($opsiBySoal[$j['soal_id']] ?? [] as $o): ?>
                        <?php
                        $isSelected = (int) $j['opsi_id'] === (int) $o['id'];
                        $isCorrect = (int) $o['is_benar'] === 1;
                        ?>
                        <div class="flex items-center gap-2">
                            <span class="w-2 h-2 rounded-full <?= $isCorrect ? 'bg-green-500' : 'bg-gray-300' ?>"></span>
                            <span class="<?= $isSelected ? 'font-semibold' : '' ?>">
                                <?= htmlspecialchars($o['label'], ENT_QUOTES, 'UTF-8') ?>
                            </span>
                            <?php if ($isSelected): ?>
                                <span class="text-xs text-indigo-600">(Dipilih)</span>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
                <div class="mt-3 text-sm text-gray-600">
                    Skor: <span class="font-semibold"><?= (int) ($j['skor'] ?? 0) ?></span>
                </div>
            <?php else: ?>
                <div class="mt-3">
                    <p class="text-sm text-gray-600 mb-2">Jawaban Siswa:</p>
                    <div class="bg-gray-50 border border-gray-200 rounded-lg p-3 text-sm text-gray-700 whitespace-pre-line">
                        <?= htmlspecialchars((string) $j['jawaban_teks'], ENT_QUOTES, 'UTF-8') ?: '<em class="text-gray-400">Tidak ada jawaban</em>' ?>
                    </div>
                </div>
                <div class="mt-3">
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Skor (0 - <?= (int) $j['poin'] ?>)</label>
                    <input type="number" name="skor[<?= (int) $j['id'] ?>]" min="0" max="<?= (int) $j['poin'] ?>"
                        value="<?= (int) ($j['skor'] ?? 0) ?>"
                        class="w-32 px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 outline-none transition">
                </div>
            <?php endif; ?>
        </div>
    <?php endforeach; ?>

    <div class="flex items-center gap-3">
        <button type="submit"
            class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2 px-6 rounded-lg transition">
            Simpan Nilai
        </button>
        <span class="text-sm text-gray-500">Total skor: <?= (int) $attempt['skor_total'] ?></span>
    </div>
</form>
