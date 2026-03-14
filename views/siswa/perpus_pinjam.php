<?php
if (!isset($pdo)) {
    exit;
}

$userId = $_SESSION['user_id'] ?? 0;
$bukuId = isset($_GET['id']) ? (int) $_GET['id'] : 0;

$stmt = $pdo->prepare("SELECT * FROM perpustakaan_buku WHERE id = ?");
$stmt->execute([$bukuId]);
$buku = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$buku) {
    echo "<div class='bg-red-50 border border-red-200 text-red-700 p-4 rounded-lg'>Buku tidak ditemukan.</div>";
    return;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $pdo->beginTransaction();
        $lock = $pdo->prepare("SELECT * FROM perpustakaan_buku WHERE id = ? FOR UPDATE");
        $lock->execute([$bukuId]);
        $current = $lock->fetch(PDO::FETCH_ASSOC);

        if (!$current || (int) $current['stok_tersedia'] <= 0) {
            $pdo->rollBack();
            set_flash_message('error', 'Stok buku habis.');
            header("Location: index.php?page=siswa-perpus");
            exit;
        }

        $pdo->prepare("INSERT INTO perpustakaan_peminjaman (buku_id, siswa_user_id, tanggal_pinjam, status)
                       VALUES (?, ?, NOW(), 'dipinjam')")
            ->execute([$bukuId, $userId]);

        $pdo->prepare("UPDATE perpustakaan_buku SET stok_tersedia = stok_tersedia - 1 WHERE id = ?")
            ->execute([$bukuId]);

        $pdo->commit();
        set_flash_message('success', 'Buku berhasil dipinjam.');
        header("Location: index.php?page=siswa-perpus-riwayat");
        exit;
    } catch (PDOException $e) {
        $pdo->rollBack();
        set_flash_message('error', 'Gagal meminjam buku.');
        header("Location: index.php?page=siswa-perpus");
        exit;
    }
}
?>

<div class="max-w-xl">
    <div class="mb-6">
        <a href="index.php?page=siswa-perpus" class="text-indigo-600 hover:underline text-sm">← Kembali</a>
        <h1 class="text-2xl font-bold text-gray-800 mt-2">Pinjam Buku</h1>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 space-y-3">
        <div>
            <p class="text-xs text-gray-500">Kode</p>
            <p class="text-sm font-semibold text-gray-800"><?= htmlspecialchars($buku['kode_buku'], ENT_QUOTES, 'UTF-8') ?></p>
        </div>
        <div>
            <p class="text-xs text-gray-500">Judul</p>
            <p class="text-sm font-semibold text-gray-800"><?= htmlspecialchars($buku['judul'], ENT_QUOTES, 'UTF-8') ?></p>
        </div>
        <div>
            <p class="text-xs text-gray-500">Penulis</p>
            <p class="text-sm text-gray-700"><?= htmlspecialchars($buku['penulis'] ?? '-', ENT_QUOTES, 'UTF-8') ?></p>
        </div>
        <div>
            <p class="text-xs text-gray-500">Stok tersedia</p>
            <p class="text-sm text-gray-700"><?= (int) $buku['stok_tersedia'] ?></p>
        </div>
        <form method="POST" class="pt-4">
            <button type="submit"
                class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-3 rounded-xl transition">
                Konfirmasi Pinjam
            </button>
        </form>
    </div>
</div>
