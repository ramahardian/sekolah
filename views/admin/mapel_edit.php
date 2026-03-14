<?php
if (!isset($pdo)) {
    exit;
}

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

$stmt = $pdo->prepare("SELECT * FROM mapel WHERE id = ?");
$stmt->execute([$id]);
$mapel = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$mapel) {
    echo "<div class='bg-red-50 border border-red-200 text-red-700 p-4 rounded-lg'>Mapel tidak ditemukan.</div>";
    return;
}

$listGuru = $pdo->query("SELECT id, nama_guru FROM guru ORDER BY nama_guru ASC")->fetchAll(PDO::FETCH_ASSOC);
$error = '';

$selectedGuruIds = [];
$assignStmt = $pdo->prepare("SELECT guru_id FROM mapel_guru WHERE mapel_id = ?");
$assignStmt->execute([$id]);
$selectedGuruIds = array_map('intval', $assignStmt->fetchAll(PDO::FETCH_COLUMN));

if (!$selectedGuruIds && !empty($mapel['guru_id'])) {
    $selectedGuruIds = [(int) $mapel['guru_id']];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $kode_mapel = input($_POST['kode_mapel'] ?? '');
    $nama_mapel = input($_POST['nama_mapel'] ?? '');
    $guru_ids = (array) ($_POST['guru_ids'] ?? []);
    $selectedGuruIds = array_values(array_unique(array_filter(array_map('intval', $guru_ids))));

    $guruIds = array_map('intval', array_column($listGuru, 'id'));

    if ($kode_mapel === '' || $nama_mapel === '') {
        $error = "Kode dan nama mapel wajib diisi.";
    } else {
        foreach ($selectedGuruIds as $gid) {
            if (!in_array($gid, $guruIds, true)) {
                $error = "Guru tidak valid.";
                break;
            }
        }
    }

    if ($error === '') {
        try {
            $pdo->beginTransaction();

            $primaryGuruId = $selectedGuruIds[0] ?? null;
            $stmt = $pdo->prepare("UPDATE mapel SET kode_mapel = ?, nama_mapel = ?, guru_id = ? WHERE id = ?");
            $stmt->execute([$kode_mapel, $nama_mapel, $primaryGuruId, $id]);

            $pdo->prepare("DELETE FROM mapel_guru WHERE mapel_id = ?")->execute([$id]);
            if ($selectedGuruIds) {
                $insert = $pdo->prepare("INSERT INTO mapel_guru (mapel_id, guru_id) VALUES (?, ?)");
                foreach ($selectedGuruIds as $gid) {
                    $insert->execute([$id, $gid]);
                }
            }

            $pdo->commit();

            set_flash_message('success', 'Mapel berhasil diperbarui.');
            echo "<script>window.location.href='index.php?page=mapel';</script>";
            exit;
        } catch (PDOException $e) {
            $pdo->rollBack();
            $error = "Gagal memperbarui mapel: " . $e->getMessage();
        }
    }
}
?>

<div class="mb-6">
    <a href="index.php?page=mapel"
        class="text-orange-600 hover:text-orange-800 text-sm font-medium flex items-center mb-2">
        <i class="fas fa-arrow-left mr-2"></i> Kembali ke Mapel
    </a>
    <h1 class="text-2xl font-bold text-gray-800">Edit Mata Pelajaran</h1>
</div>

<?php if ($error): ?>
    <div class="mb-4 p-3 bg-red-100 border-l-4 border-red-500 text-red-700 text-sm">
        <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
    </div>
<?php endif; ?>

<form action="" method="POST" class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
    <div class="p-8 space-y-6">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">Kode Mapel</label>
                <input type="text" name="kode_mapel" required value="<?= htmlspecialchars($mapel['kode_mapel'], ENT_QUOTES, 'UTF-8') ?>"
                    class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-orange-500 focus:bg-white outline-none transition">
            </div>
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">Nama Mapel</label>
                <input type="text" name="nama_mapel" required value="<?= htmlspecialchars($mapel['nama_mapel'], ENT_QUOTES, 'UTF-8') ?>"
                    class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-orange-500 focus:bg-white outline-none transition">
            </div>
        </div>

        <div>
            <label class="block text-sm font-semibold text-gray-700 mb-2">Guru Pengampu (Opsional)</label>
            <div class="relative" data-guru-picker>
                <button type="button" data-guru-toggle
                    class="w-full text-left px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-orange-500 focus:bg-white outline-none transition">
                    Pilih guru
                </button>
                <div data-guru-panel
                    class="hidden absolute z-10 mt-2 w-full bg-white border border-gray-200 rounded-xl shadow-lg p-3">
                    <input type="text" data-guru-search placeholder="Cari nama guru..."
                        class="w-full mb-3 px-3 py-2 bg-gray-50 border border-gray-200 rounded-lg focus:ring-2 focus:ring-orange-500 focus:bg-white outline-none transition text-sm">
                    <div class="max-h-56 overflow-y-auto space-y-2">
                        <?php foreach ($listGuru as $g): ?>
                            <?php $gid = (int) $g['id']; ?>
                            <label class="flex items-center gap-2 text-sm text-gray-700">
                                <input type="checkbox" name="guru_ids[]" value="<?= $gid ?>" data-guru-label="<?= htmlspecialchars($g['nama_guru'], ENT_QUOTES, 'UTF-8') ?>"
                                    class="guru-checkbox text-orange-600" <?= in_array($gid, $selectedGuruIds, true) ? 'checked' : '' ?>>
                                <?= htmlspecialchars($g['nama_guru'], ENT_QUOTES, 'UTF-8') ?>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            <p class="text-xs text-gray-400 mt-2">Cari dan pilih bisa lebih dari satu guru.</p>
        </div>
    </div>

    <div class="bg-gray-50 p-6 flex justify-end gap-3 border-t border-gray-100">
        <button type="submit"
            class="bg-orange-600 hover:bg-orange-700 text-white px-8 py-2.5 rounded-xl font-bold shadow-lg transition-all transform hover:-translate-y-1">
            <i class="fas fa-save mr-2"></i> Simpan Perubahan
        </button>
    </div>
</form>

<script>
    (function () {
        const root = document.querySelector('[data-guru-picker]');
        if (!root) return;
        const toggle = root.querySelector('[data-guru-toggle]');
        const panel = root.querySelector('[data-guru-panel]');
        const search = root.querySelector('[data-guru-search]');
        const checkboxes = Array.from(root.querySelectorAll('input[type="checkbox"]'));

        function updateLabel() {
            const selected = checkboxes.filter(cb => cb.checked).map(cb => cb.dataset.guruLabel || cb.nextSibling.textContent.trim());
            if (selected.length === 0) {
                toggle.textContent = 'Pilih guru';
            } else if (selected.length <= 2) {
                toggle.textContent = selected.join(', ');
            } else {
                toggle.textContent = selected.slice(0, 2).join(', ') + ' +' + (selected.length - 2);
            }
        }

        toggle.addEventListener('click', function () {
            panel.classList.toggle('hidden');
            if (!panel.classList.contains('hidden')) {
                search.focus();
            }
        });

        document.addEventListener('click', function (e) {
            if (!root.contains(e.target)) {
                panel.classList.add('hidden');
            }
        });

        search.addEventListener('input', function () {
            const term = this.value.trim().toLowerCase();
            checkboxes.forEach(cb => {
                const label = cb.dataset.guruLabel ? cb.dataset.guruLabel.toLowerCase() : '';
                const wrapper = cb.closest('label');
                if (!wrapper) return;
                wrapper.classList.toggle('hidden', term !== '' && !label.includes(term));
            });
        });

        checkboxes.forEach(cb => cb.addEventListener('change', updateLabel));
        updateLabel();
    })();
</script>
