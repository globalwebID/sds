<?php
require_once '../config/runtime.php';
sds_session_start();
include '../config/db.php';

// Ambil dan sanitasi UID
$uid = trim($_POST['uid'] ?? '');

if (empty($uid)) {
    $_SESSION['error'] = "UID kosong.";
    redirectBack();
}

// Gunakan prepared statement untuk keamanan
$stmt = $conn->prepare("SELECT id, rfid_uid, blokir FROM pendaftaran_siswa WHERE rfid_uid = ?");
$stmt->bind_param("s", $uid);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $_SESSION['error'] = "MAAF!<br>KARTU ANDA TIDAK DIKENAL<br>GUNAKAN KARTU LAIN";
    redirectBack();
}

$siswa = $result->fetch_assoc();

// Cek apakah kartu diblokir
if ((int)$siswa['blokir'] === 1) {
    $_SESSION['error'] = "MAAF!<br>KARTU ANDA TELAH DIBLOKIR<br>SILAKAN HUBUNGI PETUGAS";
    redirectBack();
}

// Login berhasil
session_regenerate_id(true);
$_SESSION['id_siswa'] = $siswa['id'];
$_SESSION['rfid'] = $siswa['rfid_uid'];

header("Location: proses_transaksi.php");
exit;

// Fungsi redirect ulang ke halaman sebelumnya
function redirectBack()
{
    header("Location: scan_kartu.php"); // ganti ke scan_kartu.php agar tampil error
    exit;
}
