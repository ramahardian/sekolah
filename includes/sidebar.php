<?php
$activePage = $page ?? '';
$role = $_SESSION['role'] ?? 'user';

$menuItems = [
    [
        'label' => 'Dashboard',
        'page' => 'dashboard',
        'icon' => 'fa-house'
    ],
    [
        'label' => 'Forum',
        'page' => ['forum', 'forum-kategori', 'forum-thread', 'forum-thread-tambah', 'forum-kategori-tambah'],
        'icon' => 'fa-comments'
    ],
];

if ($role === 'admin') {
    $menuItems = array_merge($menuItems, [
        [
            'label' => 'Data Siswa',
            'page' => ['siswa', 'siswa-tambah', 'siswa-edit'],
            'icon' => 'fa-user-graduate'
        ],
        [
            'label' => 'Data Guru',
            'page' => ['guru', 'guru-tambah', 'guru-edit'],
            'icon' => 'fa-chalkboard-teacher'
        ],
        [
            'label' => 'Data Kelas',
            'page' => ['kelas', 'kelas-tambah', 'kelas-edit'],
            'icon' => 'fa-door-open'
        ],
        [
            'label' => 'Mata Pelajaran',
            'page' => ['mapel', 'mapel-tambah', 'mapel-edit'],
            'icon' => 'fa-book'
        ],
        [
            'label' => 'Jadwal Pelajaran',
            'page' => ['jadwal', 'jadwal-tambah', 'jadwal-edit'],
            'icon' => 'fa-calendar-days'
        ],
        [
            'label' => 'Perpustakaan',
            'page' => ['perpus-buku', 'perpus-buku-tambah', 'perpus-buku-edit', 'perpus-peminjaman'],
            'icon' => 'fa-book-open'
        ],
        [
            'label' => 'Absensi',
            'page' => ['absensi'],
            'icon' => 'fa-calendar-check'
        ],
        [
            'label' => 'CMS Landing',
            'page' => ['cms-landing', 'cms-landing-tambah', 'cms-landing-edit'],
            'icon' => 'fa-pen-nib'
        ],
        [
            'label' => 'CMS Navigasi',
            'page' => ['cms-nav', 'cms-nav-tambah', 'cms-nav-edit'],
            'icon' => 'fa-bars'
        ],
    ]);
} elseif ($role === 'guru') {
    $menuItems = array_merge($menuItems, [
        [
            'label' => 'Video Chat',
            'page' => ['video-classes', 'video-chat'],
            'icon' => 'fa-video'
        ],
        [
            'label' => 'Absensi',
            'page' => ['absensi'],
            'icon' => 'fa-calendar-check'
        ],
        [
            'label' => 'Jadwal Mengajar',
            'page' => ['guru-jadwal'],
            'icon' => 'fa-calendar-check'
        ],
        [
            'label' => 'Ujian Online',
            'page' => [
                'guru-ujian',
                'guru-ujian-tambah',
                'guru-ujian-edit',
                'guru-ujian-soal',
                'guru-ujian-hasil',
                'guru-ujian-nilai'
            ],
            'icon' => 'fa-pen-to-square'
        ],
    ]);
} elseif ($role === 'siswa') {
    $menuItems = array_merge($menuItems, [
        [
            'label' => 'Video Chat',
            'page' => ['video-classes', 'video-chat'],
            'icon' => 'fa-video'
        ],
        [
            'label' => 'Profil',
            'page' => ['profil-siswa'],
            'icon' => 'fa-id-card'
        ],
        [
            'label' => 'Nilai',
            'page' => ['nilai-siswa'],
            'icon' => 'fa-chart-line'
        ],
        [
            'label' => 'Absensi',
            'page' => ['absensi'],
            'icon' => 'fa-calendar-check'
        ],
        [
            'label' => 'Jadwal Pelajaran',
            'page' => ['siswa-jadwal'],
            'icon' => 'fa-calendar-days'
        ],
        [
            'label' => 'Perpustakaan',
            'page' => ['siswa-perpus', 'siswa-perpus-pinjam', 'siswa-perpus-riwayat'],
            'icon' => 'fa-book-open'
        ],
        [
            'label' => 'Ujian Online',
            'page' => [
                'siswa-ujian',
                'siswa-ujian-kerjakan',
                'siswa-ujian-hasil'
            ],
            'icon' => 'fa-pen-to-square'
        ],
    ]);
}

function is_active_page($activePage, $pages)
{
    $pages = (array) $pages;
    return in_array($activePage, $pages, true);
}

$username = $_SESSION['username'] ?? 'User';
$role = $_SESSION['role'] ?? 'user';
$displayName = $username;

if ($role === 'guru' && isset($pdo)) {
    $stmt = $pdo->prepare("SELECT nama_guru FROM guru WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id'] ?? 0]);
    $guruRow = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!empty($guruRow['nama_guru'])) {
        $displayName = $guruRow['nama_guru'];
    }
}
?>

<aside id="sidebar"
    class="w-64 bg-white shadow-lg h-screen fixed left-0 top-0 z-40 flex flex-col overflow-y-auto transform -translate-x-full md:translate-x-0 transition-transform duration-200">
    <div class="px-6 py-6 border-b">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-indigo-600 text-white flex items-center justify-center font-bold">
                SIS
            </div>
            <div>
                <p class="text-lg font-bold text-gray-800 leading-tight">SIS Mealify</p>
                <p class="text-xs text-gray-500">School Management</p>
            </div>
        </div>
    </div>

    <nav class="flex-1 px-4 py-6 space-y-2">
        <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider px-2 mb-2">Menu</p>
        <?php foreach ($menuItems as $item):
            $active = is_active_page($activePage, $item['page']);
            $classes = $active
                ? 'bg-indigo-600 text-white shadow-md'
                : 'text-gray-700 hover:bg-indigo-50 hover:text-indigo-700';
            $iconClass = $active ? 'text-white' : 'text-indigo-500';
            $pageParam = is_array($item['page']) ? $item['page'][0] : $item['page'];
            ?>
            <a href="index.php?page=<?= htmlspecialchars($pageParam, ENT_QUOTES, 'UTF-8') ?>"
                class="flex items-center gap-3 px-4 py-3 rounded-xl transition <?= $classes ?>">
                <i class="fas <?= htmlspecialchars($item['icon'], ENT_QUOTES, 'UTF-8') ?> <?= $iconClass ?>"></i>
                <span class="font-medium"><?= htmlspecialchars($item['label'], ENT_QUOTES, 'UTF-8') ?></span>
            </a>
        <?php endforeach; ?>
    </nav>

    <div class="px-4 py-4 border-t">
        <div class="flex items-center gap-3 px-2">
            <div class="w-10 h-10 rounded-full bg-gray-100 text-gray-700 flex items-center justify-center font-bold">
                <?= htmlspecialchars(strtoupper(substr($displayName, 0, 2)), ENT_QUOTES, 'UTF-8') ?>
            </div>
            <div class="min-w-0">
                <p class="text-sm font-semibold text-gray-800 truncate">
                    <?= htmlspecialchars($displayName, ENT_QUOTES, 'UTF-8') ?>
                </p>
                <p class="text-xs text-gray-500 capitalize"><?= htmlspecialchars($role, ENT_QUOTES, 'UTF-8') ?></p>
            </div>
        </div>
        <a href="index.php?page=logout"
            class="mt-4 flex items-center gap-3 px-4 py-2 rounded-xl text-gray-600 hover:bg-red-50 hover:text-red-600 transition">
            <i class="fas fa-right-from-bracket"></i>
            <span class="font-medium">Logout</span>
        </a>
    </div>
</aside>
