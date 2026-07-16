<?php
$query = $conn->query("SELECT id FROM pendaftaran_siswa WHERE rfid IS NULL OR rfid = ''");
// if ($query->num_rows === 0) {
//     die("Tidak ada siswa yang perlu di-generate RFID.");
// }

$count = 0;
do {
    $rfid = 'RFID-' . strtoupper(bin2hex(random_bytes(3))) . '-' . str_pad($id, 4, '0', STR_PAD_LEFT);
    $cek = $conn->prepare("SELECT id FROM pendaftaran_siswa WHERE rfid = ?");
    $cek->bind_param("s", $rfid);
    $cek->execute();
    $cekResult = $cek->get_result();
} while ($cekResult->num_rows > 0);

while ($row = $query->fetch_assoc()) {
    $id = $row['id'];
    $rfid = 'RFID-' . strtoupper(bin2hex(random_bytes(3))) . '-' . str_pad($id, 4, '0', STR_PAD_LEFT);

    $stmt = $conn->prepare("UPDATE pendaftaran_siswa SET rfid = ? WHERE id = ?");
    $stmt->bind_param("si", $rfid, $id);
    $stmt->execute();
    $count++;
}

$_SESSION['success'] = "$count RFID Peserta Didik berhasil digenerate.";
header("Location: students_rfid");
exit;
