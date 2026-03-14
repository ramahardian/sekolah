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

$ujianId = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$ujianStmt = $pdo->prepare("SELECT u.*, m.nama_mapel
                            FROM ujian u
                            JOIN mapel m ON u.mapel_id = m.id
                            JOIN mapel_guru mg ON mg.mapel_id = m.id
                            WHERE u.id = ? AND mg.guru_id = ?");
$ujianStmt->execute([$ujianId, $guru['id']]);
$ujian = $ujianStmt->fetch(PDO::FETCH_ASSOC);

if (!$ujian) {
    echo "<div class='bg-red-50 border border-red-200 text-red-700 p-4 rounded-lg'>Ujian tidak ditemukan.</div>";
    return;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? 'add';

    if ($action === 'delete') {
        $soalId = (int) ($_POST['soal_id'] ?? 0);
        if ($soalId > 0) {
            $delStmt = $pdo->prepare("DELETE FROM ujian_soal WHERE id = ? AND ujian_id = ?");
            $delStmt->execute([$soalId, $ujianId]);
            set_flash_message('success', 'Soal berhasil dihapus.');
            header("Location: index.php?page=guru-ujian-soal&id=$ujianId");
            exit;
        }
    } else {
        $tipe = ($_POST['tipe'] ?? 'pg') === 'essay' ? 'essay' : 'pg';
        $pertanyaan = trim((string) ($_POST['pertanyaan'] ?? ''));
        $poin = (int) ($_POST['poin'] ?? 1);

        if ($pertanyaan === '') {
            $error = "Pertanyaan wajib diisi.";
        } elseif ($poin < 1) {
            $error = "Poin minimal 1.";
        } else {
            $opsi = [];
            $opsiBenar = -1;

            if ($tipe === 'pg') {
                $opsiRaw = $_POST['opsi'] ?? [];
                foreach ($opsiRaw as $value) {
                    $value = trim((string) $value);
                    if ($value !== '') {
                        $opsi[] = $value;
                    }
                }
                $opsiBenar = (int) ($_POST['opsi_benar'] ?? -1);

                if (count($opsi) < 2) {
                    $error = "Pilihan ganda minimal 2 opsi.";
                } elseif ($opsiBenar < 0 || $opsiBenar >= count($opsi)) {
                    $error = "Pilih jawaban yang benar.";
                }
            }

            if (!$error) {
                $stmt = $pdo->prepare("INSERT INTO ujian_soal (ujian_id, tipe, pertanyaan, poin) VALUES (?, ?, ?, ?)");
                $stmt->execute([$ujianId, $tipe, $pertanyaan, $poin]);
                $soalId = (int) $pdo->lastInsertId();

                if ($tipe === 'pg') {
                    $insertOpsi = $pdo->prepare("INSERT INTO ujian_opsi (soal_id, label, is_benar) VALUES (?, ?, ?)");
                    foreach ($opsi as $index => $label) {
                        $insertOpsi->execute([$soalId, $label, $index === $opsiBenar ? 1 : 0]);
                    }
                }

                set_flash_message('success', 'Soal berhasil ditambahkan.');
                header("Location: index.php?page=guru-ujian-soal&id=$ujianId");
                exit;
            }
        }
    }
}

$soalStmt = $pdo->prepare("SELECT * FROM ujian_soal WHERE ujian_id = ? ORDER BY id DESC");
$soalStmt->execute([$ujianId]);
$soal = $soalStmt->fetchAll(PDO::FETCH_ASSOC);

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
?>

<div class="flex flex-col md:flex-row md:items-center justify-between mb-6 gap-4">
    <div>
        <h1 class="text-2xl font-bold text-gray-800">Kelola Soal</h1>
        <p class="text-gray-500 text-sm">
            <?= htmlspecialchars($ujian['judul'], ENT_QUOTES, 'UTF-8') ?> ·
            <?= htmlspecialchars($ujian['nama_mapel'], ENT_QUOTES, 'UTF-8') ?>
        </p>
    </div>
    <a href="index.php?page=guru-ujian" class="text-gray-600 hover:underline">Kembali ke daftar ujian</a>
</div>

<?php display_flash_message(); ?>

<?php if ($error): ?>
    <div class="mb-4 p-3 bg-red-100 border-l-4 border-red-500 text-red-700 text-sm">
        <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
    </div>
<?php endif; ?>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <div class="lg:col-span-1">
        <form method="POST" class="bg-white rounded-xl shadow-sm border border-gray-100 p-5 space-y-4">
            <h3 class="text-lg font-bold text-gray-800">Tambah Soal</h3>
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">Tipe Soal</label>
                <select name="tipe"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 outline-none transition">
                    <option value="pg">Pilihan Ganda</option>
                    <option value="essay">Essay</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">Pertanyaan</label>
                <textarea name="pertanyaan" rows="4" required
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 outline-none transition"></textarea>
            </div>
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">Poin</label>
                <input type="number" name="poin" min="1" value="1"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 outline-none transition">
            </div>

            <div class="space-y-2">
                <p class="text-sm font-semibold text-gray-700">Opsi Jawaban (untuk PG)</p>
                <?php for ($i = 0; $i < 4; $i++): ?>
                    <div class="flex items-center gap-2">
                        <input type="radio" name="opsi_benar" value="<?= $i ?>" class="text-indigo-600">
                        <input type="text" name="opsi[]"
                            class="flex-1 px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 outline-none transition"
                            placeholder="Opsi <?= $i + 1 ?>">
                    </div>
                <?php endfor; ?>
                <p class="text-xs text-gray-400">Untuk essay, opsi boleh dikosongkan.</p>
            </div>

            <button type="submit"
                class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2 rounded-lg transition">
                Simpan Soal
            </button>
        </form>
    </div>

    <div class="lg:col-span-2 space-y-4">
        <?php if (count($soal) > 0): ?>
            <?php foreach ($soal as $s): ?>
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <p class="text-sm text-gray-500">
                                <?= $s['tipe'] === 'pg' ? 'Pilihan Ganda' : 'Essay' ?> · <?= (int) $s['poin'] ?> poin
                            </p>
                            <h4 class="text-base font-semibold text-gray-800 mt-1">
                                <?= htmlspecialchars($s['pertanyaan'], ENT_QUOTES, 'UTF-8') ?>
                            </h4>
                        </div>
                        <form method="POST">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="soal_id" value="<?= (int) $s['id'] ?>">
                            <button type="submit"
                                class="text-red-600 hover:bg-red-50 px-3 py-1 rounded-lg text-sm transition">
                                Hapus
                            </button>
                        </form>
                    </div>

                    <?php if ($s['tipe'] === 'pg'): ?>
                        <div class="mt-3 space-y-1 text-sm">
                            <?php foreach ($opsiBySoal[$s['id']] ?? [] as $o): ?>
                                <div class="flex items-center gap-2">
                                    <span
                                        class="w-2 h-2 rounded-full <?= $o['is_benar'] ? 'bg-green-500' : 'bg-gray-300' ?>"></span>
                                    <span class="<?= $o['is_benar'] ? 'font-semibold text-green-700' : 'text-gray-600' ?>">
                                        <?= htmlspecialchars($o['label'], ENT_QUOTES, 'UTF-8') ?>
                                    </span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <p class="mt-3 text-sm text-gray-500 italic">Essay — jawaban siswa akan dinilai manual.</p>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 text-center text-gray-500">
                Belum ada soal pada ujian ini.
            </div>
        <?php endif; ?>
    </div>
</div>
