<?php
// Validasi ID siswa
$id = intval(post('id'));
if ($id <= 0) die("ID tidak valid.");

// Ambil data lama wali (opsional)
$stmt = $conn->prepare("SELECT nama_wali, nik_wali, tahun_lahir_wali, pendidikan_wali, pekerjaan_wali, penghasilan_wali FROM pendaftaran_siswa WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$stmt->bind_result($old_nama, $old_nik, $old_lahir, $old_pendidikan, $old_pekerjaan, $old_penghasilan);
$stmt->fetch();
$stmt->close();

// Ambil data dari POST
$data = [
    'nama_wali'         => post('nama_wali'),
    'nik_wali'          => post('nik_wali'),
    'tahun_lahir_wali'  => intval(post('tahun_lahir_wali')),
    'pendidikan_wali'   => post('pendidikan_wali'),
    'pekerjaan_wali'    => post('pekerjaan_wali'),
    'penghasilan_wali'  => post('penghasilan_wali'),
];

// Update ke DB
$set = implode(", ", array_map(fn($k) => "$k = ?", array_keys($data)));
$sql = "UPDATE pendaftaran_siswa SET $set WHERE id = ?";
$stmt = $conn->prepare($sql);

$type = str_repeat("s", count($data)) . "i";
$values = array_values($data);
$values[] = $id;

$stmt->bind_param($type, ...$values);

if ($stmt->execute()) {
    if (isset($_SESSION['admin_id'])) {
        $keterangan = "Mengedit data wali siswa ID: $id";
        catatLog($conn, $_SESSION['admin_id'], 'Edit Wali', $keterangan);
    }

    $_SESSION['success'] = "Data wali berhasil diubah.";
    header("Location: student_view?id=$id#dataWali");
    exit();
}
