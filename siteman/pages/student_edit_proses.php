<?php
function uploadFile($field, $allowed, $maxMB = 10, $subfolder = '', $existing = '')
{
    if (!isset($_FILES[$field]) || $_FILES[$field]['error'] == 4) {
        return $existing;
    }

    $f = $_FILES[$field];
    $ext = strtolower(pathinfo($f['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, $allowed)) {
        die("Format file $field tidak diizinkan");
    }

    if ($f['size'] / (1024 * 1024) > $maxMB) {
        die("Ukuran file $field > {$maxMB} MB");
    }

    $dir = '../uploads/' . $subfolder . '/';

    // Buat folder jika belum ada
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }

    // Buat nama file yang aman dan unik
    $cleanName = preg_replace('/\s+/', '_', basename($f['name']));
    $filename = uniqid() . "_" . $cleanName;

    // Path lengkap untuk menyimpan file
    $path = $dir . $filename;

    // Proses upload file
    if (!move_uploaded_file($f['tmp_name'], $path)) {
        die("Gagal unggah $field");
    }

    // Kembalikan path relatif dari folder publik (untuk disimpan di database)
    return  $subfolder . '/' . $filename;
}



function post($k, $def = null)
{
    return isset($_POST[$k]) ? $_POST[$k] : $def;
}

$id = intval(post('id'));
if ($id <= 0) {
    die("ID tidak valid.");
}

$tahunAjaran = post('tahun_ajaran');
$nisn = post('nisn');

// Ambil data lama
$stmt = $conn->prepare("SELECT kelas_id, file_kip, foto, file_kk, file_ijazah FROM pendaftaran_siswa WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$stmt->bind_result($old_kelas_id, $old_kip, $old_kk, $old_ijazah);
$stmt->fetch();
$stmt->close();

$new_kelas_id = intval(post('kelas'));
if ($new_kelas_id <= 0) {
    die("Kelas tidak valid.");
}

// Validasi kelas ada
$cek = $conn->prepare("SELECT COUNT(*) FROM kelas WHERE id = ?");
$cek->bind_param("i", $new_kelas_id);
$cek->execute();
$cek->bind_result($found);
$cek->fetch();
$cek->close();

if (!$found) {
    die("Pilihan kelas tidak ditemukan.");
}

// Upload file
$subfolder = "$tahunAjaran/$nisn";
$file_kip    = uploadFile('file_kip',   ['pdf', 'jpg', 'jpeg', 'png'], 5, $subfolder, $old_kip);
$foto    = uploadFile('foto',   ['pdf', 'jpg', 'jpeg', 'png'], 5, $subfolder, $old_foto);
$file_kk     = uploadFile('file_kk',    ['pdf', 'jpg', 'jpeg', 'png'], 5, $subfolder, $old_kk);
$file_ijazah = uploadFile('file_ijazah', ['pdf', 'jpg', 'jpeg', 'png'], 5, $subfolder, $old_ijazah);

// Ambil semua post lainnya
$fields = [
    'nama_lengkap',
    'email',
    'sekolah_asal',
    'nomor_ijazah',
    'jenis_kelamin',
    'tempat_lahir',
    'tanggal_lahir',
    'no_kk',
    'nik',
    'no_registrasi_akta',
    'kebutuhan_khusus',
    'agama',
    'alamat',
    'desa',
    'kecamatan',
    'kota',
    'provinsi',
    'latitude',
    'longitude',
    'tempat_tinggal',
    'moda_transportasi',
    'anak_ke',
    'jumlah_saudara_kandung',
    'tinggi_badan',
    'berat_badan',
    'hobi',
    'cita_cita',
    'nomor_kip',
    'nama_ayah',
    'nik_ayah',
    'tahun_lahir_ayah',
    'pendidikan_ayah',
    'pekerjaan_ayah',
    'penghasilan_ayah',
    'nama_ibu',
    'nik_ibu',
    'tahun_lahir_ibu',
    'pendidikan_ibu',
    'pekerjaan_ibu',
    'penghasilan_ibu',
    'nama_wali',
    'nik_wali',
    'tahun_lahir_wali',
    'pendidikan_wali',
    'pekerjaan_wali',
    'penghasilan_wali',
    'nohp_ortu',
    'nohp_siswa',
    'pernyataan_setuju'
];

$data = [];
foreach ($fields as $f) {
    $data[$f] = post($f);
}
$data['latitude'] = floatval($data['latitude']);
$data['longitude'] = floatval($data['longitude']);
$data['anak_ke'] = intval($data['anak_ke']);
$data['jumlah_saudara_kandung'] = intval($data['jumlah_saudara_kandung']);
$data['tinggi_badan'] = intval($data['tinggi_badan']);
$data['berat_badan'] = intval($data['berat_badan']);
$data['tahun_lahir_ayah'] = intval($data['tahun_lahir_ayah']);
if ($data['penghasilan_ayah'] === '-- Pilih Penghasilan --') $data['penghasilan_ayah'] = null;
$data['tahun_lahir_ibu'] = intval($data['tahun_lahir_ibu']);
if ($data['penghasilan_ibu'] === '-- Pilih Penghasilan --') $data['penghasilan_ibu'] = null;
$data['tahun_lahir_wali'] = $data['tahun_lahir_wali'] !== '' ? intval($data['tahun_lahir_wali']) : null;
if ($data['pendidikan_wali'] === '-- Pilih Pendidikan --') $data['pendidikan_wali'] = null;
if ($data['pekerjaan_wali'] === '-- Pilih Pekerjaan --') $data['pekerjaan_wali'] = null;
if ($data['penghasilan_wali'] === '-- Pilih Penghasilan --') $data['penghasilan_wali'] = null;
$data['pernyataan_setuju'] = isset($_POST['pernyataan_setuju']) ? 1 : 0;

// Tambahkan manual
$data['kelas_id'] = $new_kelas_id;
$data['file_kip'] = $file_kip;
$data['foto'] = $foto;
$data['file_kk'] = $file_kk;
$data['file_ijazah'] = $file_ijazah;
$data['tahun_ajaran'] = $tahunAjaran;

// Siapkan query update
$set = implode(", ", array_map(fn($k) => "$k = ?", array_keys($data)));
$sql = "UPDATE pendaftaran_siswa SET $set WHERE id = ?";
$stmt = $conn->prepare($sql);

$type = str_repeat("s", count($data)) . "i";
$values = array_values($data);
$values[] = $id;

$stmt->bind_param($type, ...$values);

if ($stmt->execute()) {
    // Jika kelas berubah, update kuota_terisi
    if ($old_kelas_id !== $new_kelas_id) {
        $conn->query("UPDATE kelas SET terisi = terisi - 1 WHERE id = $old_kelas_id");
        $conn->query("UPDATE kelas SET terisi = terisi + 1 WHERE id = $new_kelas_id");
    }

    // ✅ Catat log aktivitas edit
    if (isset($_SESSION['admin_id'])) {
        $namaSiswa = $data['nama_lengkap'] ?? '';
        $keterangan = "Mengedit data siswa: $namaSiswa (ID: $id)";
        catatLog($conn, $_SESSION['admin_id'], 'Edit Siswa', $keterangan);
    }

    $_SESSION['success'] = "Data <strong>$namaSiswa</strong> berhasil <strong>di ubah.</strong>";
    header("Location: student_view?id=$id");
    exit();
} else {
    echo "Gagal update data: " . $stmt->error;
}
