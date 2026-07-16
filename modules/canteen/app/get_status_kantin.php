<?php
include '../config/db.php';

$kantin_result = mysqli_query($conn, "SELECT * FROM kantin");
$kantin_data = mysqli_fetch_all($kantin_result, MYSQLI_ASSOC);

echo json_encode($kantin_data);
