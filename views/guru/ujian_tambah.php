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

$mapelStmt = $pdo->prepare("SELECT m.id, m.nama_mapel
                            FROM mapel m
                            JOIN mapel_guru mg ON mg.mapel_id = m.id
                            WHERE mg.guru_id = ?
                            ORDER BY m.nama_mapel ASC");
$mapelStmt->execute([$guru['id']]);
$mapelList = $mapelStmt->fetchAll(PDO::FETCH_ASSOC);
$mapelCount = count($mapelList);
$assignedMapelId = $mapelCount === 1 ? (int) $mapelList[0]['id'] : 0;
$assignedMapelName = $mapelCount === 1 ? $mapelList[0]['nama_mapel'] : '';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $mapel_id = $assignedMapelId ?: (int) ($_POST['mapel_id'] ?? 0);
    $judul = trim((string) ($_POST['judul'] ?? ''));
    $deskripsi = trim((string) ($_POST['deskripsi'] ?? ''));
    $mulai_raw = trim((string) ($_POST['mulai'] ?? ''));
    $selesai_raw = trim((string) ($_POST['selesai'] ?? ''));
    $durasi = (int) ($_POST['durasi_menit'] ?? 0);
    $status = ($_POST['status'] ?? 'draft') === 'published' ? 'published' : 'draft';
    $password = trim((string) ($_POST['password'] ?? ''));
    $password_hash = $password !== '' ? password_hash($password, PASSWORD_DEFAULT) : null;

    $mulai = $mulai_raw ? str_replace('T', ' ', $mulai_raw) . ':00' : null;
    $selesai = $selesai_raw ? str_replace('T', ' ', $selesai_raw) . ':00' : null;

    $mapelIds = array_map('intval', array_column($mapelList, 'id'));
    if ($mapelCount === 0) {
        $error = "Mapel guru belum ditetapkan. Hubungi admin.";
    } elseif ($mapelCount > 1) {
        $error = "Guru memiliki lebih dari satu mapel. Tetapkan satu mapel agar ujian otomatis.";
    } elseif (!in_array($mapel_id, $mapelIds, true)) {
        $error = "Mata pelajaran tidak valid.";
    } elseif ($judul === '') {
        $error = "Judul ujian wajib diisi.";
    } elseif ($mulai && $selesai && $selesai < $mulai) {
        $error = "Waktu selesai tidak boleh lebih awal dari waktu mulai.";
    } else {
        $stmt = $pdo->prepare("INSERT INTO ujian (mapel_id, judul, deskripsi, mulai, selesai, durasi_menit, password_hash, status, created_by)
                               VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $mapel_id,
            $judul,
            $deskripsi ?: null,
            $mulai,
            $selesai,
            $durasi > 0 ? $durasi : null,
            $password_hash,
            $status,
            $userId
        ]);

        $ujianId = (int) $pdo->lastInsertId();
        set_flash_message('success', 'Ujian berhasil dibuat. Silakan tambahkan soal.');
        header("Location: index.php?page=guru-ujian-soal&id=$ujianId");
        exit;
    }
}
?>

<div class="max-w-100">
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-800">Buat Ujian</h1>
        <p class="text-gray-500 text-sm">Buat ujian untuk mata pelajaran yang Anda ampu.</p>
    </div>

    <?php if ($error): ?>
        <div class="mb-4 p-3 bg-red-100 border-l-4 border-red-500 text-red-700 text-sm">
            <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
        </div>
    <?php endif; ?>

    <form method="POST" class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 space-y-5">
        <div>
            <label class="block text-sm font-semibold text-gray-700 mb-2">Mata Pelajaran</label>
            <?php if ($mapelCount === 1): ?>
                <input type="hidden" name="mapel_id" value="<?= $assignedMapelId ?>">
                <input type="text" value="<?= htmlspecialchars($assignedMapelName, ENT_QUOTES, 'UTF-8') ?>" readonly
                    class="w-full px-4 py-3 border border-gray-200 rounded-xl bg-gray-50 text-gray-700">
            <?php elseif ($mapelCount === 0): ?>
                <div class="p-3 rounded-xl bg-yellow-50 border border-yellow-200 text-yellow-700 text-sm">
                    Mapel belum ditetapkan untuk guru ini. Hubungi admin.
                </div>
            <?php else: ?>
                <div class="p-3 rounded-xl bg-yellow-50 border border-yellow-200 text-yellow-700 text-sm">
                    Guru memiliki lebih dari satu mapel. Tetapkan satu mapel agar ujian otomatis.
                </div>
            <?php endif; ?>
        </div>

        <div>
            <label class="block text-sm font-semibold text-gray-700 mb-2">Judul Ujian</label>
            <input type="text" name="judul" required
                class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-indigo-500 outline-none transition"
                placeholder="Contoh: UTS Matematika">
        </div>

        <div>
            <label class="block text-sm font-semibold text-gray-700 mb-2">Deskripsi (Opsional)</label>
            <textarea name="deskripsi" rows="3"
                class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-indigo-500 outline-none transition"
                placeholder="Petunjuk ujian..."></textarea>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">Mulai</label>
                <input type="datetime-local" name="mulai"
                    class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-indigo-500 outline-none transition">
            </div>
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">Selesai</label>
                <input type="datetime-local" name="selesai"
                    class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-indigo-500 outline-none transition">
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">Durasi (Menit)</label>
                <input type="number" name="durasi_menit" min="1"
                    class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-indigo-500 outline-none transition"
                    placeholder="Contoh: 90">
            </div>
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">Status</label>
                <select name="status"
                    class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-indigo-500 outline-none transition">
                    <option value="draft">Draft</option>
                    <option value="published">Published</option>
                </select>
            </div>
        </div>

        <div>
            <label class="block text-sm font-semibold text-gray-700 mb-2">Password Ujian (Opsional)</label>
            <input type="password" name="password"
                class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-indigo-500 outline-none transition"
                placeholder="Kosongkan jika tidak ingin pakai password">
            <p class="text-xs text-gray-400 mt-2">Jika diisi, siswa harus memasukkan password sebelum mengerjakan.</p>
        </div>

        <div class="flex items-center gap-3">
            <button type="submit"
                class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-3 px-6 rounded-xl shadow-lg transition <?= $mapelCount === 1 ? '' : 'opacity-50 pointer-events-none' ?>">
                Simpan & Tambah Soal
            </button>
            <a href="index.php?page=guru-ujian" class="text-gray-600 hover:underline">Kembali</a>
        </div>
    </form>
</div>
