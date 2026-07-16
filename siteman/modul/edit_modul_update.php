<?php
$set = implode(", ", array_map(fn($k) => "$k = ?", array_keys($data)));
$sql = "UPDATE pendaftaran_siswa SET $set WHERE id = ?";
$stmt = $conn->prepare($sql);

$type = str_repeat("s", count($data)) . "i";
$values = array_values($data);
$values[] = $id;

$stmt->bind_param($type, ...$values);

if ($stmt->execute()) {
    if ($old_kelas_id !== $new_kelas_id) {
        $conn->query("UPDATE kelas SET terisi = terisi - 1 WHERE id = $old_kelas_id");
        $conn->query("UPDATE kelas SET terisi = terisi + 1 WHERE id = $new_kelas_id");
    }

    if (isset($_SESSION['admin_id'])) {
        $namaSiswa = $data['nama_lengkap'] ?? '';
        $keterangan = "Mengedit data siswa: $namaSiswa (ID: $id)";
        catatLog($conn, $_SESSION['admin_id'], 'Edit Siswa', $keterangan);
    }

    $_SESSION['success'] = "Data <strong>{$data['nama_lengkap']}</strong> berhasil <strong>di ubah.</strong>";
    header("Location: student_view?id=$id");
    exit();
} else {
    echo "Gagal update data: " . $stmt->error;
}
