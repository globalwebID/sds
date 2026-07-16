<?php
$pesan = strtolower(trim($_GET['pesan'] ?? ''));
$response = "Maaf, saya tidak mengerti.";

if (strpos($pesan, 'halo') !== false || strpos($pesan, 'hai') !== false) {
    $response = "Halo! Ada yang bisa saya bantu?";
} elseif (strpos($pesan, 'rpl') !== false) {
    $response = "Ruang RPL ada di sebelah timur lapangan, dekat ruang TU.";
} elseif (strpos($pesan, 'terima kasih') !== false) {
    $response = "Sama-sama. Semoga harimu menyenangkan!";
} elseif (strpos($pesan, 'siapa kamu') !== false) {
    $response = "Saya asisten virtual SMKN 1 Probolinggo.";
}

echo $response;
