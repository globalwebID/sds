<?php
require '_config.php';
requireAuth();

$id = (int)$_SESSION['id_siswa'];

$q = mysqli_query($conn, "
    SELECT id, nis, nama_lengkap, saldo 
    FROM pendaftaran_siswa 
    WHERE id=$id
");

response(true, 'OK', mysqli_fetch_assoc($q));
