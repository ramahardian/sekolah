<?php
if (!isset($pdo)) {
    exit;
}

$defaultSections = [
    [
        'section_key' => 'hero',
        'layout' => 'hero',
        'title' => 'SIS Pro',
        'subtitle' => 'Sistem Informasi Sekolah Terpadu',
        'body' => "Kelola akademik, absensi, ujian, perpustakaan, dan komunikasi sekolah dalam satu platform yang rapi dan cepat.\nScholarship Facility|Sistem informasi beasiswa terintegrasi.|fa-graduation-cap\nSkilled Lecturers|Tenaga pendidik profesional dan bersertifikat.|fa-user-tie\nBook Library & Store|Akses ribuan literatur digital & cetak.|fa-book-open",
        'button_text' => 'Masuk Portal',
        'button_link' => 'index.php?page=login',
        'image_url' => ''
    ],
    [
        'section_key' => 'stats',
        'layout' => 'stats',
        'title' => 'Ringkas & Terukur',
        'subtitle' => 'Data sekolah selalu up to date',
        'body' => "Siswa|1200\nGuru|85\nKelas|36\nMapel|42",
        'button_text' => '',
        'button_link' => '',
        'image_url' => ''
    ],
    [
        'section_key' => 'features',
        'layout' => 'features',
        'title' => 'Fitur Utama',
        'subtitle' => 'Semua kebutuhan sekolah dalam satu tempat',
        'body' => "Absensi digital harian\nUjian online & penilaian\nManajemen kelas, guru, siswa\nPerpustakaan & peminjaman\nForum komunikasi sekolah",
        'button_text' => '',
        'button_link' => '',
        'image_url' => ''
    ],
    [
        'section_key' => 'testimonials',
        'layout' => 'testimonials',
        'title' => 'Testimoni',
        'subtitle' => 'Suara dari guru dan siswa',
        'body' => "Ibu Rina|Guru Matematika|Sistem ini membuat rekap nilai dan absensi jadi jauh lebih cepat.\nAndi Pratama|Siswa Kelas XI|Ujian online jadi rapi dan tidak bikin bingung.\nBudi Santoso|Wali Kelas|Monitoring siswa lebih mudah dan transparan.",
        'button_text' => '',
        'button_link' => '',
        'image_url' => ''
    ],
    [
        'section_key' => 'about',
        'layout' => 'content',
        'title' => 'Tentang Sekolah',
        'subtitle' => 'Membangun generasi berprestasi',
        'body' => 'Sekolah kami berkomitmen menghadirkan pendidikan yang adaptif, berbasis teknologi, dan berorientasi karakter.',
        'button_text' => '',
        'button_link' => '',
        'image_url' => 'https://images.unsplash.com/photo-1541339907198-e08759dfeb3f?q=80&w=1000'
    ],
    [
        'section_key' => 'cta',
        'layout' => 'cta',
        'title' => 'Siap Bertransformasi?',
        'subtitle' => 'Mulai kelola sekolah dengan lebih rapi hari ini.',
        'body' => '',
        'button_text' => 'Masuk ke Sistem',
        'button_link' => 'index.php?page=login',
        'image_url' => ''
    ],
    [
        'section_key' => 'contact',
        'layout' => 'contact',
        'title' => 'Kontak',
        'subtitle' => 'Hubungi kami',
        'body' => "Alamat|Jl. Pendidikan No. 10\nEmail|info@sekolah.sch.id\nTelepon|021-123456",
        'button_text' => '',
        'button_link' => '',
        'image_url' => ''
    ],
];

$sections = [];
$pageStmt = $pdo->prepare("SELECT id FROM cms_page WHERE slug = ?");
$pageStmt->execute(['landing']);
$pageId = $pageStmt->fetchColumn();

if ($pageId) {
    $secStmt = $pdo->prepare("SELECT * FROM cms_section WHERE page_id = ? AND is_active = 1 ORDER BY sort_order ASC");
    $secStmt->execute([$pageId]);
    $sections = $secStmt->fetchAll(PDO::FETCH_ASSOC);
}

if (!$sections) {
    $sections = $defaultSections;
}

$navItems = [];
if ($pageId) {
    $navStmt = $pdo->prepare("SELECT label, url, target_blank FROM cms_nav WHERE page_id = ? AND is_active = 1 ORDER BY sort_order ASC");
    $navStmt->execute([$pageId]);
    $navItems = $navStmt->fetchAll(PDO::FETCH_ASSOC);
}

if (!$navItems) {
    $navItems = [
        ['label' => 'Home', 'url' => '#hero', 'target_blank' => 0],
        ['label' => 'Fitur', 'url' => '#features', 'target_blank' => 0],
        ['label' => 'Profil', 'url' => '#about', 'target_blank' => 0],
        ['label' => 'Testimoni', 'url' => '#testimonials', 'target_blank' => 0],
        ['label' => 'Kontak', 'url' => '#contact', 'target_blank' => 0],
    ];
}

function parse_lines($text)
{
    $lines = preg_split("/\\r\\n|\\r|\\n/", (string) $text);
    return array_values(array_filter(array_map('trim', $lines)));
}

$stmtSettings = $pdo->query("SELECT setting_key, setting_value FROM cms_setting WHERE setting_key IN ('header_title', 'header_logo', 'header_phone', 'header_email')");
$headerSettings = [];
if ($stmtSettings) {
    while ($row = $stmtSettings->fetch(PDO::FETCH_ASSOC)) {
        $headerSettings[$row['setting_key']] = $row['setting_value'];
    }
}
$pageTitle = $headerSettings['header_title'] ?? 'SIS Pro';
$pageLogo = $headerSettings['header_logo'] ?? '';
$headerPhone = $headerSettings['header_phone'] ?? '+62 812 3456 789';
$headerEmail = $headerSettings['header_email'] ?? 'info@sekolah.sch.id';
?><!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8') ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link
        href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&family=Oswald:wght@500;600&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            font-family: 'Roboto', sans-serif;
        }

        .heading-oswald {
            font-family: 'Oswald', sans-serif;
        }

        /* Efek Overlay Biru Transparan sesuai gambar */
        .blue-overlay {
            background: rgba(7, 41, 77, 0.85);
            transition: all 0.3s ease;
        }

        .blue-overlay:hover {
            background: #ffae01;
            /* Warna kuning saat hover sesuai tema Eikra */
        }
    </style>
</head>

<body class="bg-white">

    <div class="hidden md:block bg-[#002147] text-white py-2 px-12 text-sm border-b border-white/10">
        <div class="max-w-7xl mx-auto flex justify-between items-center">
            <div class="flex gap-6">
                <span><i class="fas fa-phone mr-2 text-[#ffae01]"></i> <?= htmlspecialchars($headerPhone, ENT_QUOTES, 'UTF-8') ?></span>
                <span><i class="fas fa-envelope mr-2 text-[#ffae01]"></i> <?= htmlspecialchars($headerEmail, ENT_QUOTES, 'UTF-8') ?></span>
            </div>

        </div>
    </div>

    <nav class="bg-white py-4 px-12 sticky top-0 z-50 shadow-sm">
        <div class="max-w-7xl mx-auto flex justify-between items-center">
            <div class="flex items-center gap-2">
                <?php if (!empty($pageLogo)): ?>
                    <img src="<?= htmlspecialchars($pageLogo, ENT_QUOTES, 'UTF-8') ?>" alt="Logo" class="max-h-10 object-contain">
                <?php endif; ?>
                <?php if (!empty($pageTitle)): ?>
                    <span class="text-2xl font-black text-[#002147] tracking-tighter">
                        <?= htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8') ?>
                    </span>
                <?php endif; ?>
            </div>
            <div class="hidden lg:flex gap-8 font-bold text-[#002147] uppercase text-sm">
                <?php foreach ($navItems as $nav): ?>
                    <a href="<?= htmlspecialchars($nav['url'], ENT_QUOTES, 'UTF-8') ?>" <?= !empty($nav['target_blank']) ? 'target="_blank" rel="noopener"' : '' ?> class="hover:text-[#ffae01]">
                        <?= htmlspecialchars($nav['label'], ENT_QUOTES, 'UTF-8') ?>
                    </a>
                <?php endforeach; ?>
            </div>

        </div>
    </nav>

    <main>
        <?php foreach ($sections as $sec):
            $layout = $sec['layout'] ?? 'content';
            ?>

            <?php if ($layout === 'hero'): ?>
                <?php
                $heroLines = parse_lines($sec['body'] ?? '');
                $heroDesc = $heroLines ? array_shift($heroLines) : '';
                $heroDesc = $heroDesc !== '' ? $heroDesc : (string) ($sec['subtitle'] ?? '');

                $heroCards = [];
                foreach ($heroLines as $line) {
                    $parts = array_map('trim', explode('|', $line));
                    if (count($parts) >= 2) {
                        $heroCards[] = [
                            'title' => $parts[0],
                            'desc' => $parts[1],
                            'icon' => $parts[2] ?? 'fa-graduation-cap'
                        ];
                    }
                }

                if (!$heroCards) {
                    $heroCards = [
                        [
                            'title' => 'Scholarship Facility',
                            'desc' => 'Sistem informasi beasiswa terintegrasi.',
                            'icon' => 'fa-graduation-cap'
                        ],
                        [
                            'title' => 'Skilled Lecturers',
                            'desc' => 'Tenaga pendidik profesional dan bersertifikat.',
                            'icon' => 'fa-user-tie'
                        ],
                        [
                            'title' => 'Book Library & Store',
                            'desc' => 'Akses ribuan literatur digital & cetak.',
                            'icon' => 'fa-book-open'
                        ],
                    ];
                }
                ?>
                <section id="<?= htmlspecialchars($sec['section_key'] ?? 'hero', ENT_QUOTES, 'UTF-8') ?>"
                    class="relative h-[600px] overflow-hidden">
                    <img src="<?= !empty($sec['image_url']) ? $sec['image_url'] : 'https://images.unsplash.com/photo-1523050854058-8df90110c9f1?q=80&w=2000' ?>"
                        class="absolute inset-0 w-full h-full object-cover" alt="Hero">

                    <div class="absolute inset-0 bg-black/40"></div>

                    <div class="relative z-10 max-w-7xl mx-auto h-full flex flex-col justify-center px-12">
                        <div class="max-w-2xl text-white fade-up">
                            <h1 class="heading-oswald text-5xl md:text-6xl font-bold uppercase leading-tight mb-4">
                                <?= htmlspecialchars($sec['title']) ?>
                            </h1>
                            <?php if ($heroDesc !== ''): ?>
                                <p class="text-lg opacity-90 mb-8 border-l-4 border-[#ffae01] pl-4">
                                    <?= htmlspecialchars($heroDesc) ?>
                                </p>
                            <?php endif; ?>
                            <a href="<?= htmlspecialchars($sec['button_link']) ?>"
                                class="bg-[#ffae01] text-[#002147] px-8 py-4 font-bold uppercase tracking-wider hover:bg-[#002147] hover:text-white transition-all inline-block">
                                <?= htmlspecialchars($sec['button_text']) ?>
                            </a>
                        </div>
                    </div>

                    <div class="absolute bottom-0 left-0 right-0 z-20 hidden md:block">
                        <div class="max-w-7xl mx-auto grid grid-cols-3">
                            <?php foreach ($heroCards as $i => $card): ?>
                                <div
                                    class="blue-overlay group p-8 text-white flex items-center gap-5 <?= $i < (count($heroCards) - 1) ? 'border-r border-white/10' : '' ?>">
                                    <i
                                        class="fas <?= htmlspecialchars($card['icon'], ENT_QUOTES, 'UTF-8') ?> text-4xl text-[#ffae01] group-hover:text-[#002147] transition-colors duration-300"></i>
                                    <div class="group-hover:text-[#002147] transition-colors duration-300">
                                        <h4 class="font-bold text-lg uppercase">
                                            <?= htmlspecialchars($card['title'], ENT_QUOTES, 'UTF-8') ?>
                                        </h4>
                                        <p class="text-xs opacity-70 group-hover:opacity-100 transition-opacity duration-300">
                                            <?= htmlspecialchars($card['desc'], ENT_QUOTES, 'UTF-8') ?>
                                        </p>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </section>

            <?php elseif ($layout === 'features'): ?>
                <?php
                $featureLines = parse_lines($sec['body'] ?? '');
                $features = [];
                foreach ($featureLines as $line) {
                    if (!empty(trim($line))) {
                        $features[] = [
                            'text' => trim($line),
                            'icon' => 'fa-check-circle'
                        ];
                    }
                }

                if (!$features) {
                    $features = [
                        ['text' => 'Absensi digital harian', 'icon' => 'fa-check-circle'],
                        ['text' => 'Ujian online & penilaian', 'icon' => 'fa-check-circle'],
                        ['text' => 'Manajemen kelas, guru, siswa', 'icon' => 'fa-check-circle'],
                        ['text' => 'Perpustakaan & peminjaman', 'icon' => 'fa-check-circle'],
                        ['text' => 'Forum komunikasi sekolah', 'icon' => 'fa-check-circle']
                    ];
                }
                ?>
                <section id="<?= htmlspecialchars($sec['section_key'] ?? 'features', ENT_QUOTES, 'UTF-8') ?>" class="py-20 px-12 bg-gray-50">
                    <div class="max-w-7xl mx-auto">
                        <div class="text-center mb-16">
                            <h2 class="heading-oswald text-4xl font-bold text-[#002147] uppercase mb-4 tracking-tight">
                                <?= htmlspecialchars($sec['title'] ?? 'Fitur Utama') ?>
                            </h2>
                            <div class="w-20 h-1 bg-[#ffae01] mx-auto mb-6"></div>
                            <p class="text-gray-600 max-w-2xl mx-auto">
                                <?= htmlspecialchars($sec['subtitle'] ?? 'Semua kebutuhan sekolah dalam satu tempat') ?>
                            </p>
                        </div>

                        <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-8">
                            <?php foreach ($features as $feature): ?>
                                <div class="bg-white p-8 rounded-lg shadow-md hover:shadow-xl transition-shadow duration-300">
                                    <div class="flex items-start gap-4">
                                        <i class="fas <?= htmlspecialchars($feature['icon'], ENT_QUOTES, 'UTF-8') ?> text-[#ffae01] text-xl mt-1"></i>
                                        <div>
                                            <h4 class="font-bold text-lg text-[#002147] mb-2">
                                                <?= htmlspecialchars($feature['text'], ENT_QUOTES, 'UTF-8') ?>
                                            </h4>
                                            <p class="text-gray-600 text-sm">
                                                Solusi terintegrasi untuk <?= strtolower(htmlspecialchars($feature['text'], ENT_QUOTES, 'UTF-8')) ?> di sekolah Anda.
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </section>

            <?php elseif ($layout === 'testimonials'): ?>
                <?php
                $testimonialLines = parse_lines($sec['body'] ?? '');
                $testimonials = [];
                foreach ($testimonialLines as $line) {
                    $parts = array_map('trim', explode('|', $line));
                    if (count($parts) >= 3) {
                        $testimonials[] = [
                            'name' => $parts[0],
                            'role' => $parts[1],
                            'content' => $parts[2]
                        ];
                    }
                }

                if (!$testimonials) {
                    $testimonials = [
                        [
                            'name' => 'Ibu Rina',
                            'role' => 'Guru Matematika',
                            'content' => 'Sistem ini membuat rekap nilai dan absensi jadi jauh lebih cepat.'
                        ],
                        [
                            'name' => 'Andi Pratama',
                            'role' => 'Siswa Kelas XI',
                            'content' => 'Ujian online jadi rapi dan tidak bikin bingung.'
                        ],
                        [
                            'name' => 'Budi Santoso',
                            'role' => 'Wali Kelas',
                            'content' => 'Monitoring siswa lebih mudah dan transparan.'
                        ]
                    ];
                }
                ?>
                <section id="<?= htmlspecialchars($sec['section_key'] ?? 'testimonials', ENT_QUOTES, 'UTF-8') ?>" class="py-20 px-12 bg-[#002147]">
                    <div class="max-w-7xl mx-auto">
                        <div class="text-center mb-16">
                            <h2 class="heading-oswald text-4xl font-bold text-white uppercase mb-4 tracking-tight">
                                <?= htmlspecialchars($sec['title'] ?? 'Testimoni') ?>
                            </h2>
                            <div class="w-20 h-1 bg-[#ffae01] mx-auto mb-6"></div>
                            <p class="text-gray-300 max-w-2xl mx-auto">
                                <?= htmlspecialchars($sec['subtitle'] ?? 'Suara dari guru dan siswa') ?>
                            </p>
                        </div>

                        <div class="grid md:grid-cols-3 gap-8">
                            <?php foreach ($testimonials as $testimonial): ?>
                                <div class="bg-white/10 backdrop-blur-sm p-8 rounded-lg border border-white/20 hover:bg-white/20 transition-all duration-300">
                                    <div class="flex items-center mb-6">
                                        <div class="w-16 h-16 bg-[#ffae01] rounded-full flex items-center justify-center text-[#002147] font-bold text-xl">
                                            <?= substr(htmlspecialchars($testimonial['name'], ENT_QUOTES, 'UTF-8'), 0, 1) ?>
                                        </div>
                                        <div class="ml-4">
                                            <h4 class="font-bold text-white text-lg">
                                                <?= htmlspecialchars($testimonial['name'], ENT_QUOTES, 'UTF-8') ?>
                                            </h4>
                                            <p class="text-gray-300 text-sm">
                                                <?= htmlspecialchars($testimonial['role'], ENT_QUOTES, 'UTF-8') ?>
                                            </p>
                                        </div>
                                    </div>
                                    <div class="relative">
                                        <i class="fas fa-quote-left text-[#ffae01] text-2xl mb-4"></i>
                                        <p class="text-gray-200 italic leading-relaxed">
                                            <?= htmlspecialchars($testimonial['content'], ENT_QUOTES, 'UTF-8') ?>
                                        </p>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </section>

            <?php elseif ($layout === 'content'): ?>
                <section id="<?= htmlspecialchars($sec['section_key'] ?? 'section', ENT_QUOTES, 'UTF-8') ?>"
                    class="py-20 px-12 max-w-7xl mx-auto grid md:grid-cols-2 gap-12 items-center">
                    <div>
                        <h2 class="heading-oswald text-4xl font-bold text-[#002147] uppercase mb-6 tracking-tight">
                            <?= $sec['title'] ?? 'Welcome To <span class="text-[#ffae01]">Our Campus</span>' ?>
                        </h2>
                        <div class="w-20 h-1 bg-[#ffae01] mb-6"></div>
                        <p class="text-gray-600 leading-relaxed mb-6">
                            <?= htmlspecialchars($sec['body']) ?>
                        </p>
                        <p class="text-gray-500 text-sm italic border-l-2 border-gray-200 pl-4">
                            <?= htmlspecialchars($sec['subtitle'] ?? '"Membangun masa depan generasi bangsa dengan teknologi dan integritas."', ENT_QUOTES, 'UTF-8') ?>
                        </p>
                    </div>
                    <div class="relative">
                        <img src="<?= !empty($sec['image_url']) ? $sec['image_url'] : 'https://images.unsplash.com/photo-1541339907198-e08759dfeb3f?q=80&w=1000' ?>"
                            class="rounded shadow-2xl" alt="Students">
                        <div class="absolute -bottom-6 -right-6 -z-10 w-full h-full bg-[#ffae01] rounded"></div>
                    </div>
                </section>
            <?php endif; ?>

        <?php endforeach; ?>
    </main>

    <footer class="bg-[#00152e] text-white py-12 text-center text-sm opacity-80">
        &copy; <?= date('Y') ?> SIS Pro - Academic Solution. All Rights Reserved.
    </footer>

</body>

</html>