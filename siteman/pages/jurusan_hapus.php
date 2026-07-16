<?php
if (empty($_SESSION['admin_id']) || ($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST' || !sds_csrf_verify((string)($_POST['csrf'] ?? ''))) {
    http_response_code(419); exit('Permintaan penghapusan tidak valid.');
}
$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;

// Cek apakah ID valid
$jurusanResult = $conn->prepare("SELECT * FROM jurusan WHERE id = ?");
$jurusanResult->bind_param("i", $id);
$jurusanResult->execute();
$jurusan = $jurusanResult->get_result()->fetch_assoc();
$jurusanResult->close();

if (!$jurusan) {
    $_SESSION['error'] = "Data jurusan tidak ditemukan.";
    header("Location: index?page=jurusan");
    exit;
}

// Cek apakah masih digunakan di pendaftaran_siswa
$cekPendaftaran = $conn->prepare("SELECT COUNT(*) as jumlah FROM pendaftaran_siswa WHERE jurusan_id = ?");
$cekPendaftaran->bind_param("i", $id);
$cekPendaftaran->execute();
$jumlahPendaftaran = $cekPendaftaran->get_result()->fetch_assoc()['jumlah'];
$cekPendaftaran->close();

// Cek apakah masih digunakan di kelas
$cekKelas = $conn->prepare("SELECT COUNT(*) as jumlah FROM kelas WHERE jurusan_id = ?");
$cekKelas->bind_param("i", $id);
$cekKelas->execute();
$jumlahKelas = $cekKelas->get_result()->fetch_assoc()['jumlah'];
$cekKelas->close();

// Cek apakah masih digunakan di pengaturan_nipd
$cekNipd = $conn->prepare("SELECT COUNT(*) as jumlah FROM pengaturan_nipd WHERE jurusan_id = ?");
$cekNipd->bind_param("i", $id);
$cekNipd->execute();
$jumlahNipd = $cekNipd->get_result()->fetch_assoc()['jumlah'];
$cekNipd->close();

// Jika ada datanya, cek apakah kode_awal = 0
if ($jumlahNipd > 0) {
    $cekNilaiNipd = $conn->prepare("SELECT kode_depan FROM pengaturan_nipd WHERE jurusan_id = ?");
    $cekNilaiNipd->bind_param("i", $id);
    $cekNilaiNipd->execute();
    $result = $cekNilaiNipd->get_result();
    if ($row = $result->fetch_assoc()) {
        if ((int)$row['kode_awal'] === 0) {
            // Hapus jika kode_awal = 0
            $hapus = $conn->prepare("DELETE FROM pengaturan_nipd WHERE jurusan_id = ?");
            $hapus->bind_param("i", $id);
            $hapus->execute();
            $hapus->close();

            // Karena sudah dihapus, ubah jumlahNipd ke 0 agar bisa lanjut hapus jurusan
            $jumlahNipd = 0;
        }
    }
    $cekNilaiNipd->close();
}


// Jika masih digunakan, batalkan penghapusan
if ($jumlahPendaftaran > 0 || $jumlahKelas > 0 || $jumlahNipd > 0) {
    $_SESSION['error'] = "Tidak bisa menghapus jurusan '<strong>{$jurusan['nama_jurusan']}</strong>' karena masih digunakan di "
        . ($jumlahPendaftaran > 0 ? "<strong>$jumlahPendaftaran</strong> data siswa" : "")
        . ($jumlahPendaftaran > 0 && $jumlahKelas > 0 ? " dan " : "")
        . ($jumlahKelas > 0 ? "<strong>$jumlahKelas</strong> data kelas" : "")
        . (($jumlahPendaftaran > 0 || $jumlahKelas > 0) && $jumlahNipd > 0 ? " dan " : "")
        . ($jumlahNipd > 0 ? "<strong>$jumlahNipd</strong> pengaturan NIPD" : "") . ".";
    header("Location: jurusan");
    exit;
}

// Hapus jurusan karena aman
$hapus = $conn->prepare("DELETE FROM jurusan WHERE id = ?");
$hapus->bind_param("i", $id);
$hapus->execute();
$hapus->close();

// ✅ Catat log aktivitas
if (isset($_SESSION['admin_id'])) {
    $keterangan = "Menghapus jurusan '{$jurusan['nama_jurusan']}', Kode: '{$jurusan['kode_jurusan']}'";
    catatLog($conn, $_SESSION['admin_id'], 'Hapus Jurusan', $keterangan);
}

$_SESSION['success'] = "Jurusan berhasil <strong>dihapus.</strong>";
header("Location: jurusan");
exit;
