<?php
include 'inc/fungsi.php';
require_once __DIR__ . '/../../config/perpus.php';
sds_perpus_ensure_schema($conn);

function backUrl(): string
{
    $page   = isset($_POST['back_page']) ? (int)$_POST['back_page'] : 1;
    $search = trim((string)($_POST['back_search'] ?? ''));

    $page = max(1, $page);

    $params = ['page' => $page];
    if ($search !== '') $params['search'] = $search;

    return 'siswa.php?' . http_build_query($params);
}

// Validasi input
if (!isset($_POST['siswa_id'], $_POST['rfid_uid'])) {
    $_SESSION['error'] = 'Data tidak lengkap.';
    header('Location: ' . backUrl());
    exit;
}

$siswa_id = (int)$_POST['siswa_id'];
$rfid_uid = strtoupper(trim((string)$_POST['rfid_uid']));
$rfid_uid = preg_replace('/\s+/', '', $rfid_uid); // hapus spasi dalam UID

if ($siswa_id <= 0 || $rfid_uid === '') {
    $_SESSION['error'] = 'Data tidak valid.';
    header('Location: ' . backUrl());
    exit;
}

// Cek apakah siswa ada
$stmtSiswa = $conn->prepare("SELECT id FROM pendaftaran_siswa WHERE id = ?");
$stmtSiswa->bind_param('i', $siswa_id);
$stmtSiswa->execute();
$cekSiswa = $stmtSiswa->get_result();
$stmtSiswa->close();

if (!$cekSiswa || $cekSiswa->num_rows === 0) {
    $_SESSION['error'] = 'Data siswa tidak ditemukan.';
    header('Location: ' . backUrl());
    exit;
}

// Simpan melalui manajemen kartu terpusat SDS. UID juga diperiksa terhadap
// kartu pegawai agar satu kartu tidak dapat digunakan oleh dua pemilik.
try {
    sds_rfid_assign($conn, 'siswa', $siswa_id, $rfid_uid, 0, 'Diperbarui dari mKantin');
    sds_perpus_ensure_member($conn, 'siswa', $siswa_id, true);
    $_SESSION['success'] = 'Kode kartu berhasil disimpan dan tersinkron ke seluruh modul SDS.';
} catch (Throwable $e) {
    $message = $e->getMessage();
    $_SESSION['error'] = stripos($message, 'digunakan') !== false
        ? 'Kode kartu sudah digunakan oleh peserta didik atau pegawai lain.'
        : 'Gagal menyimpan kode kartu: ' . htmlspecialchars($message, ENT_QUOTES, 'UTF-8');
}

header('Location: ' . backUrl());
exit;
