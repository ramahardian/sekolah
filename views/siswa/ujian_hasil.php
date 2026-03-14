<?php
if (!isset($pdo)) {
    exit;
}

$userId = $_SESSION['user_id'] ?? 0;
$attemptId = isset($_GET['attempt']) ? (int) $_GET['attempt'] : 0;

$attemptStmt = $pdo->prepare("SELECT a.*, uj.judul, m.nama_mapel
                              FROM ujian_attempt a
                              JOIN ujian uj ON a.ujian_id = uj.id
                              JOIN mapel m ON uj.mapel_id = m.id
                              WHERE a.id = ? AND a.siswa_user_id = ?");
$attemptStmt->execute([$attemptId, $userId]);
$attempt = $attemptStmt->fetch(PDO::FETCH_ASSOC);

if (!$attempt) {
    echo "<div class='bg-red-50 border border-red-200 text-red-700 p-4 rounded-lg'>Hasil ujian tidak ditemukan.</div>";
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
?>

<div class="mb-6">
    <h1 class="text-2xl font-bold text-gray-800">Hasil Ujian</h1>
    <p class="text-gray-500 text-sm">
        <?= htmlspecialchars($attempt['judul'], ENT_QUOTES, 'UTF-8') ?> ·
        <?= htmlspecialchars($attempt['nama_mapel'], ENT_QUOTES, 'UTF-8') ?>
    </p>
</div>

<?php display_flash_message(); ?>

<div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5 mb-6">
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3">
        <div class="text-sm text-gray-600">
            Status: <span class="font-semibold text-gray-800"><?= $attempt['status'] === 'submitted' ? 'Selesai' : 'Diproses' ?></span>
        </div>
        <div class="text-sm text-gray-600">
            Skor total: <span class="font-semibold text-indigo-700"><?= (int) $attempt['skor_total'] ?></span>
        </div>
    </div>
</div>

<div class="space-y-4">
    <?php foreach ($jawaban as $index => $j): ?>
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
            <div class="flex items-center justify-between">
                <p class="text-sm text-gray-500">Soal <?= $index + 1 ?> · <?= (int) $j['poin'] ?> poin</p>
                <span class="text-xs px-2 py-1 rounded-full bg-indigo-50 text-indigo-700">
                    <?= $j['tipe'] === 'pg' ? 'Pilihan Ganda' : 'Essay' ?>
                </span>
            </div>
            <h4 class="text-base font-semibold text-gray-800 mt-2">
                <?= htmlspecialchars($j['pertanyaan'], ENT_QUOTES, 'UTF-8') ?>
            </h4>

            <?php if ($j['tipe'] === 'pg'): ?>
                <div class="mt-4 space-y-2 text-sm">
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
                <div class="mt-4">
                    <p class="text-sm text-gray-600 mb-2">Jawaban Anda:</p>
                    <div class="bg-gray-50 border border-gray-200 rounded-lg p-3 text-sm text-gray-700 whitespace-pre-line">
                        <?= htmlspecialchars((string) $j['jawaban_teks'], ENT_QUOTES, 'UTF-8') ?: '<em class="text-gray-400">Tidak ada jawaban</em>' ?>
                    </div>
                </div>
                <div class="mt-3 text-sm text-gray-600">
                    Skor: <span class="font-semibold"><?= $j['skor'] === null ? 'Menunggu dinilai' : (int) $j['skor'] ?></span>
                </div>
            <?php endif; ?>
        </div>
    <?php endforeach; ?>
</div>

<div class="mt-6">
    <a href="index.php?page=siswa-ujian" class="text-gray-600 hover:underline">Kembali ke daftar ujian</a>
</div>
