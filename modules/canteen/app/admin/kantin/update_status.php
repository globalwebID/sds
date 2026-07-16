<?php
include 'inc/fungsi.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int) $_POST['id'];
    $query = mysqli_query($conn, "UPDATE transaksi_kantin SET status_dilayani = 1 WHERE id = $id");
    echo json_encode(['success' => $query]);
}
