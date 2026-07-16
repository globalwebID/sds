<?php
session_start();
include 'middleware/auth.php';
include 'middleware/role_check.php';
include '../../config/db.php';
require '../../vendor/autoload.php'; // pastikan PHPSpreadsheet sudah di-install

$username = $_SESSION['username'];
$role = $_SESSION['role'];

// ⬇️ Tambahkan baris ini
checkRole(['admin', 'superadmin', 'operator', 'super']);

$pengaturan = [];
$result = $conn->query("SELECT * FROM pengaturan LIMIT 1");

if ($result && $result->num_rows > 0) {
    $pengaturan = $result->fetch_assoc();
} else {
    // Default jika belum ada data
    $pengaturan = [
        'nama_sekolah' => '',
        'logo' => ''
    ];
}

// Ambil 5 transaksi terakhir dari transaksi_kantin
$log_result = mysqli_query($conn, "
    SELECT t.tanggal AS waktu, s.nama_lengkap AS nama_siswa, s.rfid_uid, k.nama AS nama_kantin, t.nominal, 'Pembelian' AS jenis
    FROM transaksi_kantin t
    JOIN pendaftaran_siswa s ON t.id_siswa = s.id
    JOIN kantin k ON t.id_kantin = k.id

    UNION ALL

    SELECT tp.tanggal AS waktu, s.nama_lengkap AS nama_siswa, s.rfid_uid, '-' AS nama_kantin, tp.nominal, 'Topup' AS jenis
    FROM topup tp
    JOIN pendaftaran_siswa s ON tp.id_siswa = s.id

    UNION ALL

    SELECT p.tanggal AS waktu, '-' AS nama_siswa, '-' AS rfid_uid, k.nama AS nama_kantin, p.jumlah AS nominal, 'Penarikan' AS jenis
    FROM penarikan p
    JOIN kantin k ON p.id_kantin = k.id

    ORDER BY waktu DESC
    LIMIT 5
");
if (!$log_result) {
    die("Query error: " . mysqli_error($conn));
}
$success = '';
$error = '';
$last_log = mysqli_fetch_assoc($log_result);
$last_waktu = $last_log ? date('d M Y H:i', strtotime($last_log['waktu'])) : 'Belum ada aktivitas';

// Fungsi untuk ubah nomor dan kirim WA
function kirim_wa($nomor, $message)
{
    if (substr($nomor, 0, 1) === "0") {
        $nomor = "62" . substr($nomor, 1);
    }

    $api_URL = (string)sds_config('services.whatsapp.url', '');
    $apiKey = (string)sds_config('services.whatsapp.api_key', '');
    $sender = (string)sds_config('services.whatsapp.sender', '');
    if ($api_URL === '' || $apiKey === '' || $sender === '' || $nomor === '') return;
    $data = [
        'api_key' => $apiKey,
        'sender'  => $sender,
        'number'  => $nomor,
        'message' => $message
    ];

    $curl = curl_init();
    curl_setopt_array($curl, array(
        CURLOPT_URL => $api_URL,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_POSTFIELDS => json_encode($data),
        CURLOPT_HTTPHEADER => array(
            'Content-Type: application/json'
        ),
    ));

    $response = curl_exec($curl);
    curl_close($curl);

    // (Opsional) Simpan log atau periksa status
    // file_put_contents('log_wa.txt', "WA to $nomor: $response\n", FILE_APPEND);
}
