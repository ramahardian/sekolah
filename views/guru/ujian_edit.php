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

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$stmt = $pdo->prepare("SELECT u.*, m.nama_mapel
                       FROM ujian u
                       JOIN mapel m ON u.mapel_id = m.id
                       JOIN mapel_guru mg ON mg.mapel_id = m.id
                       WHERE u.id = ? AND mg.guru_id = ?");
$stmt->execute([$id, $guru['id']]);
$ujian = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$ujian) {
    echo "<div class='bg-red-50 border border-red-200 text-red-700 p-4 rounded-lg'>Ujian tidak ditemukan.</div>";
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

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $mapel_id = (int) $ujian['mapel_id'];
    $judul = trim((string) ($_POST['judul'] ?? ''));
    $deskripsi = trim((string) ($_POST['deskripsi'] ?? ''));
    $mulai_raw = trim((string) ($_POST['mulai'] ?? ''));
    $selesai_raw = trim((string) ($_POST['selesai'] ?? ''));
    $durasi = (int) ($_POST['durasi_menit'] ?? 0);
    $status = ($_POST['status'] ?? 'draft') === 'published' ? 'published' : 'draft';
    $password = trim((string) ($_POST['password'] ?? ''));
    $removePassword = isset($_POST['remove_password']);
    $password_hash = null;
    if (!$removePassword && $password !== '') {
        $password_hash = password_hash($password, PASSWORD_DEFAULT);
    }

    $mulai = $mulai_raw ? str_replace('T', ' ', $mulai_raw) . ':00' : null;
    $selesai = $selesai_raw ? str_replace('T', ' ', $selesai_raw) . ':00' : null;

    $mapelIds = array_map('intval', array_column($mapelList, 'id'));
    if ($mapelCount === 0) {
        $error = "Mapel guru belum ditetapkan. Hubungi admin.";
    } elseif ($mapelCount > 1 && !in_array($mapel_id, $mapelIds, true)) {
        $error = "Mata pelajaran tidak valid.";
    } elseif ($judul === '') {
        $error = "Judul ujian wajib diisi.";
    } elseif ($mulai && $selesai && $selesai < $mulai) {
        $error = "Waktu selesai tidak boleh lebih awal dari waktu mulai.";
    } else {
        $sql = "UPDATE ujian 
                SET mapel_id = ?, judul = ?, deskripsi = ?, mulai = ?, selesai = ?, durasi_menit = ?, status = ?";
        $params = [
            $mapel_id,
            $judul,
            $deskripsi ?: null,
            $mulai,
            $selesai,
            $durasi > 0 ? $durasi : null,
            $status
        ];

        if ($removePassword) {
            $sql .= ", password_hash = NULL";
        } elseif ($password_hash !== null) {
            $sql .= ", password_hash = ?";
            $params[] = $password_hash;
        }

        $sql .= " WHERE id = ?";
        $params[] = $id;

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        set_flash_message('success', 'Ujian berhasil diperbarui.');
        header("Location: index.php?page=guru-ujian");
        exit;
    }
}

$mulaiValue = $ujian['mulai'] ? date('Y-m-d\TH:i', strtotime($ujian['mulai'])) : '';
$selesaiValue = $ujian['selesai'] ? date('Y-m-d\TH:i', strtotime($ujian['selesai'])) : '';
?>

<div class="max-w-3xl">
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-800">Edit Ujian</h1>
        <p class="text-gray-500 text-sm">Perbarui detail ujian Anda.</p>
    </div>

    <?php if ($error): ?>
        <div class="mb-4 p-3 bg-red-100 border-l-4 border-red-500 text-red-700 text-sm">
            <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
        </div>
    <?php endif; ?>

    <form method="POST" class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 space-y-5">
        <div>
            <label class="block text-sm font-semibold text-gray-700 mb-2">Mata Pelajaran</label>
            <input type="text" value="<?= htmlspecialchars($ujian['nama_mapel'], ENT_QUOTES, 'UTF-8') ?>" readonly
                class="w-full px-4 py-3 border border-gray-200 rounded-xl bg-gray-50 text-gray-700">
        </div>

        <div>
            <label class="block text-sm font-semibold text-gray-700 mb-2">Judul Ujian</label>
            <input type="text" name="judul" required value="<?= htmlspecialchars($ujian['judul'], ENT_QUOTES, 'UTF-8') ?>"
                class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-indigo-500 outline-none transition">
        </div>

        <div>
            <label class="block text-sm font-semibold text-gray-700 mb-2">Deskripsi (Opsional)</label>
            <textarea name="deskripsi" rows="3"
                class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-indigo-500 outline-none transition"><?= htmlspecialchars((string) $ujian['deskripsi'], ENT_QUOTES, 'UTF-8') ?></textarea>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">Mulai</label>
                <input type="datetime-local" name="mulai" value="<?= $mulaiValue ?>"
                    class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-indigo-500 outline-none transition">
            </div>
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">Selesai</label>
                <input type="datetime-local" name="selesai" value="<?= $selesaiValue ?>"
                    class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-indigo-500 outline-none transition">
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">Durasi (Menit)</label>
                <input type="number" name="durasi_menit" min="1" value="<?= (int) $ujian['durasi_menit'] ?>"
                    class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-indigo-500 outline-none transition">
            </div>
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">Status</label>
                <select name="status"
                    class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-indigo-500 outline-none transition">
                    <option value="draft" <?= $ujian['status'] === 'draft' ? 'selected' : '' ?>>Draft</option>
                    <option value="published" <?= $ujian['status'] === 'published' ? 'selected' : '' ?>>Published</option>
                </select>
            </div>
        </div>

        <div>
            <label class="block text-sm font-semibold text-gray-700 mb-2">Password Ujian (Opsional)</label>
            <input type="password" name="password"
                class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-indigo-500 outline-none transition"
                placeholder="Kosongkan jika tidak ingin mengubah password">
            <label class="mt-3 flex items-center gap-2 text-sm text-gray-600">
                <input type="checkbox" name="remove_password" class="text-indigo-600">
                Hapus password ujian
            </label>
            <p class="text-xs text-gray-400 mt-2">Jika password diisi, siswa wajib memasukkan password sebelum ujian.</p>
        </div>

        <div class="flex items-center gap-3">
            <button type="submit"
                class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-3 px-6 rounded-xl shadow-lg transition <?= $mapelCount === 0 ? 'opacity-50 pointer-events-none' : '' ?>">
                Simpan Perubahan
            </button>
            <a href="index.php?page=guru-ujian" class="text-gray-600 hover:underline">Kembali</a>
        </div>
    </form>
</div>
