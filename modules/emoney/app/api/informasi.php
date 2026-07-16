<?php
require '_config.php';
requireAuth();

$id_siswa = (int)($_SESSION['id_siswa'] ?? 0);
if ($id_siswa <= 0) response(false, 'Session tidak valid');

$limit = (int)($_GET['limit'] ?? 10);
if ($limit <= 0 || $limit > 50) $limit = 10;

// Ambil pengumuman + status dibaca untuk user
$q = mysqli_query($conn, "
  SELECT i.id, i.judul, i.isi, i.tanggal,
         COALESCE(iu.dibaca, 0) AS dibaca
  FROM informasi i
  LEFT JOIN informasi_user iu
    ON iu.informasi_id = i.id AND iu.user_id = $id_siswa
  ORDER BY i.tanggal DESC
  LIMIT $limit
");

$data = [];
if ($q) {
  while($r = mysqli_fetch_assoc($q)){
    $data[] = $r;
  }
}

// Tandai semua yang tampil sebagai dibaca (opsional: bisa kamu matikan kalau mau)
mysqli_query($conn, "
  INSERT INTO informasi_user (user_id, informasi_id, dibaca)
  SELECT $id_siswa, i.id, 1
  FROM informasi i
  LEFT JOIN informasi_user iu
    ON iu.informasi_id=i.id AND iu.user_id=$id_siswa
  WHERE iu.informasi_id IS NULL
");
mysqli_query($conn, "UPDATE informasi_user SET dibaca=1 WHERE user_id=$id_siswa");

response(true, 'Pengumuman', $data);
