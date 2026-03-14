<?php
/**
 * FUNCTIONS.PHP - Helper Functions
 */

// 1. Sanitasi Input (Keamanan dari XSS)
function input($data)
{
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

// 2. Format Tanggal Indonesia (Contoh: 14 Maret 2026)
function tgl_indo($tanggal)
{
    $bulan = [
        1 => 'Januari',
        'Februari',
        'Maret',
        'April',
        'Mei',
        'Juni',
        'Juli',
        'Agustus',
        'September',
        'Oktober',
        'November',
        'Desember'
    ];
    $pecahkan = explode('-', $tanggal);

    // variabel $pecahkan[0] = tahun, [1] = bulan, [2] = tanggal
    return $pecahkan[2] . ' ' . $bulan[(int) $pecahkan[1]] . ' ' . $pecahkan[0];
}

// 3. Sweet Alert / Notifikasi Sederhana dengan Tailwind
function set_flash_message($tipe, $pesan)
{
    $_SESSION['flash'] = [
        'tipe' => $tipe, // success, error, warning
        'pesan' => $pesan
    ];
}

function display_flash_message()
{
    if (isset($_SESSION['flash'])) {
        $tipe = $_SESSION['flash']['tipe'];
        $pesan = $_SESSION['flash']['pesan'];

        $color = ($tipe == 'success') ? 'bg-green-100 border-green-500 text-green-700' : 'bg-red-100 border-red-500 text-red-700';
        $icon = ($tipe == 'success') ? 'fa-check-circle' : 'fa-exclamation-circle';

        echo "
        <div class='alert-box mb-4 p-4 border-l-4 $color rounded-r-lg flex items-center shadow-sm animate-bounce'>
            <i class='fas $icon mr-3'></i>
            <p class='font-medium'>$pesan</p>
        </div>
        ";

        unset($_SESSION['flash']);
    }
}

// 4. Hitung Total Data (Untuk Dashboard)
function count_data($pdo, $table)
{
    $stmt = $pdo->query("SELECT COUNT(*) FROM $table");
    return $stmt->fetchColumn();
}

// 5. Singkatan Nama (Contoh: Budi Santoso -> BS)
function get_initials($nama)
{
    $words = explode(" ", $nama);
    $initials = "";
    foreach ($words as $w) {
        $initials .= $w[0];
    }
    return strtoupper(substr($initials, 0, 2));
}