<?php
// Pastikan koneksi dan fungsi post() sudah ada

$id = intval(post('id'));
if ($id <= 0) die("ID tidak valid.");

// Ambil data lama untuk referensi (optional)
$stmt = $conn->prepare("SELECT nama_ayah, nik_ayah, tahun_lahir_ayah, pendidikan_ayah, pekerjaan_ayah, penghasilan_ayah FROM pendaftaran_siswa WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$stmt->bind_result($old_nama_ayah, $old_nik_ayah, $old_thn_lahir_ayah, $old_pendidikan_ayah, $old_pekerjaan_ayah, $old_penghasilan_ayah);
$stmt->fetch();
$stmt->close();

// Ambil data ayah dari POST
$data = [
    'nama_ayah' => post('nama_ayah'),
    'nik_ayah' => post('nik_ayah'),
    'tahun_lahir_ayah' => intval(post('tahun_lahir_ayah')),
    'pendidikan_ayah' => post('pendidikan_ayah'),
    'pekerjaan_ayah' => post('pekerjaan_ayah'),
    'penghasilan_ayah' => post('penghasilan_ayah'),
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
        $namaSiswa = post('nama_lengkap', ''); // bisa diambil dari POST atau query db
        $keterangan = "Mengedit data ayah siswa dengan ID: $id";
        catatLog($conn, $_SESSION['admin_id'], 'Edit Ayah', $keterangan);
    }

    $_SESSION['success'] = "Data ayah berhasil diubah.";
    header("Location: student_view?id=$id#dataAyah");
    exit();
} else {
    echo "Gagal update data ayah: " . $stmt->error;
}
