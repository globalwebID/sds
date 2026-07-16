<?php
date_default_timezone_set("Asia/Jakarta");

// --- Konstanta ---
define('WABLAS_API_URL', 'https://sby.wablas.com/api/v2/send-message');
define('WABLAS_API_KEY', 'QGgnMz6b3eWwybeFvRrq7VAhBAEicKGZRDIKj8MyLOXpfGxkXOfbREN.tvaqPo8i');

// --- Ambil input ---
$rfid = $_GET['rfid'] ?? null;
$ekskul_id = $_GET['ekskul_id'] ?? null;

if (!$rfid || !$ekskul_id) {
    http_response_code(400);
    exit("RFID dan Ekskul ID wajib diisi.");
}

// --- Cek siswa berdasarkan RFID ---
$stmt = $conn->prepare("
    SELECT ps.id, ps.nama_lengkap, ps.nohp_ortu 
    FROM pendaftaran_siswa ps
    JOIN ekstrakurikuler_siswa es ON es.siswa_id = ps.id
    WHERE ps.rfid = ? AND es.ekstrakurikuler_id = ?
");
$stmt->bind_param("si", $rfid, $ekskul_id);
$stmt->execute();
$siswa = $stmt->get_result()->fetch_assoc();

if (!$siswa) {
    http_response_code(404);
    exit("RFID tidak dikenal untuk ekskul ini.");
}

$siswa_id = $siswa['id'];
$tanggal = date("Y-m-d");

// --- Simpan absensi ---
$stmt2 = $conn->prepare("
    INSERT INTO ekskul_absensi (siswa_id, ekskul_id, status, tanggal)
    VALUES (?, ?, 'H', ?)
    ON DUPLICATE KEY UPDATE status = 'H'
");
$stmt2->bind_param("iis", $siswa_id, $ekskul_id, $tanggal);
$stmt2->execute();

// --- Kirim WhatsApp ---
function kirimWhatsapp($nohp, $pesan)
{
    $nohp = preg_replace('/[^0-9]/', '', $nohp);
    if (substr($nohp, 0, 1) === '0') {
        $nohp = '62' . substr($nohp, 1);
    }

    $data = [
        "data" => [[
            "phone" => $nohp,
            "message" => $pesan,
            "secret" => false,
            "priority" => true
        ]]
    ];

    $curl = curl_init();
    curl_setopt_array($curl, [
        CURLOPT_URL => WABLAS_API_URL,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CUSTOMREQUEST => "POST",
        CURLOPT_POSTFIELDS => json_encode($data),
        CURLOPT_HTTPHEADER => [
            "Authorization: " . WABLAS_API_KEY,
            "Content-Type: application/json"
        ],
    ]);
    curl_exec($curl);
    curl_close($curl);
}

// --- Kirim WA ke ortu ---
$pesan = "Hallo orang tua dari {$siswa['nama_lengkap']}, putra/putri Anda hari ini *Hadir* mengikuti kegiatan ekstrakurikuler.";
kirimWhatsapp($siswa['nohp_ortu'], $pesan);

// --- Respon sukses ke perangkat RFID ---
echo "Sukses: {$siswa['nama_lengkap']} absen.";
