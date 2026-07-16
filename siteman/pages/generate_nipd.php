<?php
/**
 * Generate NIS/NIPD Kelas X Tahun Ajaran Aktif
 *
 * Format benar: nomor_global/nomor_tengah.kode_jurusan
 * Contoh: 16721/1303.8.2.1
 *
 * Batasan aman:
 * - Hanya tahun ajaran aktif dari sistem ($tahunAjaran)
 * - Hanya tingkat awal (kelas X) berdasarkan urutan master tingkat
 * - Hanya siswa aktif
 * - Hanya NIS/NIPD yang masih kosong
 * - Nomor tengah diambil dari pengaturan_nipd.urutan_awal per jurusan
 * - Kode jurusan diambil dari jurusan.kode_jurusan spektrum, misalnya 4.1.1, 8.1.1, 8.2.1, 8.3.1, 8.3.3
 * - Urutan generate: jurusan sesuai kode spektrum -> nama siswa A-Z per jurusan
 * - Tidak mengubah pembagian kelas dan tidak mengubah nomor absen
 * - Tidak mengubah Nomor Tengah Awal Jurusan; hanya update riwayat nomor terakhir
 */

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['error'] = 'Akses generate NIS/NIPD tidak sah.';
    header('Location: students');
    exit;
}

$tahun_ajaran = $tahunAjaran ?? '';
$nomor_awal = isset($_POST['nomor_awal']) ? (int)$_POST['nomor_awal'] : 0;

if ($tahun_ajaran === '') {
    $_SESSION['error'] = 'Tahun ajaran aktif tidak ditemukan.';
    header('Location: students');
    exit;
}

if ($nomor_awal <= 0) {
    $_SESSION['error'] = 'Nomor awal global NIS/NIPD harus diisi dengan angka lebih dari 0.';
    header('Location: students');
    exit;
}

try {
    $hasil = generateNIPDKelasXAktif($conn, $tahun_ajaran, $nomor_awal);

    if (!$hasil['ok']) {
        $_SESSION['error'] = $hasil['message'];
        header('Location: students');
        exit;
    }

    $_SESSION['success'] = $hasil['message'];
    header('Location: students?status=nipd_generated&tahun=' . urlencode($tahun_ajaran));
    exit;
} catch (Throwable $e) {
    $_SESSION['error'] = 'Generate NIS/NIPD gagal: ' . $e->getMessage();
    header('Location: students');
    exit;
}
