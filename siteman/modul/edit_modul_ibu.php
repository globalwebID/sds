<?php
// Validasi ID siswa
$id = intval(post('id'));
if ($id <= 0) die("ID tidak valid.");

// Ambil data lama ibu (opsional, bisa untuk audit)
$stmt = $conn->prepare("SELECT nama_ibu, nik_ibu, tahun_lahir_ibu, pendidikan_ibu, pekerjaan_ibu, penghasilan_ibu FROM pendaftaran_siswa WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$stmt->bind_result($old_nama, $old_nik, $old_lahir, $old_pendidikan, $old_pekerjaan, $old_penghasilan);
$stmt->fetch();
$stmt->close();

// Ambil data dari POST
$data = [
    'nama_ibu'         => post('nama_ibu'),
    'nik_ibu'          => post('nik_ibu'),
    'tahun_lahir_ibu'  => intval(post('tahun_lahir_ibu')),
    'pendidikan_ibu'   => post('pendidikan_ibu'),
    'pekerjaan_ibu'    => post('pekerjaan_ibu'),
    'penghasilan_ibu'  => post('penghasilan_ibu'),
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
        $keterangan = "Mengedit data ibu siswa ID: $id";
        catatLog($conn, $_SESSION['admin_id'], 'Edit Ibu', $keterangan);
    }

    $_SESSION['success'] = "Data ibu berhasil diubah.";
    header("Location: student_view?id=$id#dataIbu");
    exit();
} else {
    echo "Gagal update data ibu: " . $stmt->error;
}
