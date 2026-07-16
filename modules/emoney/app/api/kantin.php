<?php
require '_config.php';

$q = mysqli_query($conn, "
    SELECT id, nama, status_toko, gambar 
    FROM kantin 
    ORDER BY nama ASC
");

$data = [];
while ($r = mysqli_fetch_assoc($q)) {
    $data[] = $r;
}

response(true, 'Daftar kantin', $data);
