<?php
/**
 * Reset NIS/NIPD hanya untuk Kelas X tahun ajaran aktif.
 * Disamakan dengan aturan generate agar data kelas XI/XII dan tahun lama tidak tersentuh.
 */

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['error'] = 'Akses reset NIS/NIPD tidak sah.';
    header('Location: students');
    exit;
}

$tahun_ajaran = $tahunAjaran ?? '';

if ($tahun_ajaran === '') {
    $_SESSION['error'] = 'Tahun ajaran aktif tidak ditemukan.';
    header('Location: students');
    exit;
}

try {
    $jumlah = resetNIPDKelasXAktif($conn, $tahun_ajaran);
    $_SESSION['success'] = 'Reset NIS/NIPD berhasil untuk ' . (int)$jumlah . ' siswa aktif kelas X tahun ajaran ' . htmlspecialchars($tahun_ajaran) . '.';
    header('Location: students?status=nipd_reset&tahun=' . urlencode($tahun_ajaran));
    exit;
} catch (Throwable $e) {
    $_SESSION['error'] = 'Reset NIS/NIPD gagal: ' . $e->getMessage();
    header('Location: students');
    exit;
}
