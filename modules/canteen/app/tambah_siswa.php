<?php
include 'inc/db.php';

if (isset($_POST['submit'])) {
    $nama = $_POST['nama'];
    $rfid = $_POST['rfid_uid'];
    $saldo = $_POST['saldo'];

    mysqli_query($conn, "INSERT INTO siswa (nama, rfid_uid, saldo) VALUES ('$nama', '$rfid', '$saldo')");
    echo "Siswa berhasil ditambahkan.";
}
?>

<form method="POST">
    <label>Nama:</label><input type="text" name="nama"><br>
    <label>UID Kartu:</label><input type="text" name="rfid_uid"><br>
    <label>Saldo Awal:</label><input type="number" name="saldo"><br>
    <button type="submit" name="submit">Simpan</button>
</form>
