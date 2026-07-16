<?php
if (!isset($conn)) {
    die("Koneksi database belum diinisialisasi.");
}

$jurusan_id   = isset($_POST['jurusan_id']) ? (int)$_POST['jurusan_id'] : 0;
$tahun_ajaran = trim((string)($_POST['tahun_ajaran'] ?? ''));
$kode_depan   = isset($_POST['kode_depan']) ? (int)$_POST['kode_depan'] : 0;
$urutan_awal  = isset($_POST['urutan_awal']) ? (int)$_POST['urutan_awal'] : 0;
$reset_hasil_generate = isset($_POST['reset_hasil_generate']) && (string)$_POST['reset_hasil_generate'] === '1';

$redirect = 'jurusan' . ($tahun_ajaran !== '' ? '?tahun=' . urlencode($tahun_ajaran) : '');

if ($jurusan_id <= 0 || $tahun_ajaran === '' || $kode_depan <= 0 || $urutan_awal <= 0) {
    $_SESSION['error'] = 'Pengaturan NIS/NIPD gagal disimpan. Jurusan, tahun ajaran, nomor global awal, dan nomor tengah awal wajib diisi dengan benar.';
    header('Location: ' . $redirect);
    exit;
}

$cek = $conn->prepare("SELECT COUNT(*) FROM pengaturan_nipd WHERE jurusan_id = ? AND BINARY tahun_ajaran = BINARY ?");
if (!$cek) {
    $_SESSION['error'] = 'Query cek pengaturan NIS/NIPD gagal: ' . $conn->error;
    header('Location: ' . $redirect);
    exit;
}
$cek->bind_param("is", $jurusan_id, $tahun_ajaran);
$cek->execute();
$cek->bind_result($ada);
$cek->fetch();
$cek->close();

if ((int)$ada > 0) {
    if ($reset_hasil_generate) {
        $stmt = $conn->prepare("
            UPDATE pengaturan_nipd
            SET kode_depan = ?, urutan_awal = ?, kode_akhir = 0, urutan_akhir = 0
            WHERE jurusan_id = ? AND BINARY tahun_ajaran = BINARY ?
        ");
        if ($stmt) {
            $stmt->bind_param("iiis", $kode_depan, $urutan_awal, $jurusan_id, $tahun_ajaran);
        }
    } else {
        // Simpan hanya nomor awal. Nomor terakhir adalah riwayat hasil generate dan tidak boleh menimpa nomor awal.
        $stmt = $conn->prepare("
            UPDATE pengaturan_nipd
            SET kode_depan = ?, urutan_awal = ?
            WHERE jurusan_id = ? AND BINARY tahun_ajaran = BINARY ?
        ");
        if ($stmt) {
            $stmt->bind_param("iiis", $kode_depan, $urutan_awal, $jurusan_id, $tahun_ajaran);
        }
    }
} else {
    $kode_akhir = 0;
    $urutan_akhir = 0;
    $stmt = $conn->prepare("
        INSERT INTO pengaturan_nipd (jurusan_id, tahun_ajaran, kode_depan, urutan_awal, kode_akhir, urutan_akhir)
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    if ($stmt) {
        $stmt->bind_param("isiiii", $jurusan_id, $tahun_ajaran, $kode_depan, $urutan_awal, $kode_akhir, $urutan_akhir);
    }
}

if (!$stmt) {
    $_SESSION['error'] = 'Query simpan pengaturan NIS/NIPD gagal: ' . $conn->error;
    header('Location: ' . $redirect);
    exit;
}

if (!$stmt->execute()) {
    $_SESSION['error'] = 'Pengaturan NIS/NIPD gagal disimpan: ' . $stmt->error;
    $stmt->close();
    header('Location: ' . $redirect);
    exit;
}
$stmt->close();

$_SESSION['success'] = 'Pengaturan Nomor Tengah Awal Jurusan berhasil disimpan. Angka ini tidak akan berubah otomatis saat Generate NIS/NIPD.' . ($reset_hasil_generate ? ' Riwayat nomor terakhir hasil generate lama juga sudah dikosongkan.' : '');
header('Location: ' . $redirect);
exit;
