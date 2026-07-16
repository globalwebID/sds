<?php
require '_config.php';
requireAuth();

$id = (int)($_SESSION['id_siswa'] ?? 0);
if ($id <= 0) response(false, 'Session tidak valid');

$q = mysqli_query($conn,"SELECT nama_lengkap, saldo, blokir, rfid_uid FROM pendaftaran_siswa WHERE id=$id LIMIT 1");
if(!$q) response(false,'Query saldo gagal',['db_error'=>mysqli_error($conn)]);

$d = mysqli_fetch_assoc($q);
if(!$d) response(false,'Data siswa tidak ditemukan');

response(true,'ok',[
  'nama' => $d['nama_lengkap'],
  'saldo' => (int)$d['saldo'],
  'blokir' => (int)$d['blokir'],
  'rfid_uid' => $d['rfid_uid']
]);
