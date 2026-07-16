<?php
// 1. Pastikan admin sudah login
if (!isset($_SESSION['admin_id'])) {
    header('Location: login');
    exit;
}
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST' || !sds_csrf_verify((string)($_POST['csrf'] ?? ''))) {
    http_response_code(419);
    exit('Permintaan penghapusan tidak valid.');
}

// 2. Ambil & validasi parameter id
$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
if ($id <= 0) {
    $_SESSION['error'] = 'ID siswa tidak valid!';
    header('students');
    exit;
}

// 3. Ambil data siswa termasuk kelas_id sebelum dihapus
$stmt_select = $conn->prepare("SELECT nama_lengkap, nisn, kelas_id FROM pendaftaran_siswa WHERE id = ?");
$stmt_select->bind_param("i", $id);
$stmt_select->execute();
$stmt_select->bind_result($nama_lengkap, $nisn, $kelas_id);
$data_ada = $stmt_select->fetch();
$stmt_select->close();

if (!$data_ada) {
    $_SESSION['error'] = 'Siswa tidak ditemukan.';
    header('Location: index?page=students');
    exit;
}

try {
    $conn->begin_transaction();

    // Relasi tanpa ON DELETE CASCADE dibersihkan dalam transaksi yang sama.
    $stmt_del_kelas = $conn->prepare("DELETE FROM siswa_kelas WHERE siswa_id = ?");
    $stmt_del_kelas->bind_param("i", $id);
    $stmt_del_kelas->execute();
    $stmt_del_kelas->close();

    $stmt = $conn->prepare("DELETE FROM pendaftaran_siswa WHERE id = ?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    if ($stmt->affected_rows !== 1) throw new RuntimeException('Data siswa tidak berhasil dihapus.');
    $stmt->close();

    if ((int)$kelas_id > 0) {
        $kelas = $conn->prepare('UPDATE kelas SET terisi=GREATEST(terisi-1,0) WHERE id=?');
        $kelas->bind_param('i', $kelas_id);
        $kelas->execute();
        $kelas->close();
    }

    $keterangan = "Menghapus siswa: $nama_lengkap (NISN: $nisn)";
    catatLog($conn, (int)$_SESSION['admin_id'], 'Hapus Siswa', $keterangan);
    $conn->commit();
    $_SESSION['success'] = 'Data siswa berhasil dihapus.';
} catch (Throwable $e) {
    $conn->rollback();
    error_log('[SDS hapus siswa] ' . $e->getMessage());
    $_SESSION['error'] = 'Data siswa masih digunakan modul lain atau gagal dihapus. Tidak ada perubahan yang disimpan.';
}
$conn->close();

// 7. Redirect kembali ke daftar siswa
header('Location: students');
exit;
