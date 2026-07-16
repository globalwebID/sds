<?php
require '_config.php';
requireAuth();

@mysqli_set_charset($conn, 'utf8mb4');

$id_siswa = (int)($_SESSION['id_siswa'] ?? 0);
if ($id_siswa <= 0) response(false, 'Session id_siswa tidak valid');

$sql = "
SELECT
  id, nama_lengkap, email, nisn, nipd,
  sekolah_asal, nomor_ijazah, jenis_kelamin, tempat_lahir, tanggal_lahir,
  no_kk, nik, no_registrasi_akta, kebutuhan_khusus, agama,
  alamat, desa, kecamatan, kota, provinsi, latitude, longitude,
  tempat_tinggal, moda_transportasi, anak_ke, jumlah_saudara_kandung,
  tinggi_badan, berat_badan, hobi, cita_cita,
  nomor_kip, nomor_kps, nomor_pkh, nomor_kks, nomor_kis,
  nama_ayah, nik_ayah, tahun_lahir_ayah, pendidikan_ayah, pekerjaan_ayah, penghasilan_ayah,
  nama_ibu, nik_ibu, tahun_lahir_ibu, pendidikan_ibu, pekerjaan_ibu, penghasilan_ibu,
  nama_wali, nik_wali, tahun_lahir_wali, pendidikan_wali, pekerjaan_wali, penghasilan_wali,
  nohp_ortu, nohp_siswa, foto,
  tahun_ajaran, saldo, rfid_uid
FROM pendaftaran_siswa
WHERE id=?
LIMIT 1
";

$stmt = mysqli_prepare($conn, $sql);
if(!$stmt) response(false, 'Prepare profil gagal', ['db_error'=>mysqli_error($conn)]);

mysqli_stmt_bind_param($stmt, "i", $id_siswa);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);

if(!$res || mysqli_num_rows($res)===0) response(false, 'Profil tidak ditemukan');

$row = mysqli_fetch_assoc($res);

response(true, 'Profil siswa', $row);
