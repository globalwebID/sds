<?php
require_once '../config/runtime.php';
sds_session_start();
header('Content-Type: application/json');
include '../config/db.php';

$response = ['success' => false, 'message' => 'Terjadi kesalahan tidak diketahui.'];

if (!isset($_SESSION['id_siswa'])) {
    $response['message'] = 'Akses ditolak!';
    echo json_encode($response);
    exit;
}

if (empty($_SESSION['mkantin_csrf']) || !hash_equals((string)$_SESSION['mkantin_csrf'], (string)($_POST['csrf'] ?? ''))) {
    http_response_code(419);
    $response['message'] = 'Sesi formulir tidak valid. Muat ulang halaman.';
    echo json_encode($response);
    exit;
}

$id_pengirim = $_SESSION['id_siswa'];
$uid_teman   = isset($_POST['rfid_teman']) ? trim($_POST['rfid_teman']) : '';
$jumlah = isset($_POST['nominal']) ? intval($_POST['nominal']) : 0;

if (empty($uid_teman) || $jumlah <= 0) {
    $response['message'] = 'Data tidak valid!';
    echo json_encode($response);
    exit;
}

// Cari penerima
$stmt = $conn->prepare('SELECT id,nama_lengkap,nohp_siswa FROM pendaftaran_siswa WHERE rfid_uid=? LIMIT 1');
$stmt->bind_param('s', $uid_teman);
$stmt->execute();
$teman = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$teman) {
    $response['message'] = 'Kartu tidak dikenali!';
    echo json_encode($response);
    exit;
}

$id_penerima = $teman['id'];

if ($id_penerima == $id_pengirim) {
    $response['message'] = 'Tidak bisa kirim saldo ke diri sendiri!';
    echo json_encode($response);
    exit;
}

// Jalankan transaksi
$conn->begin_transaction();

try {
    $lock = $conn->prepare('SELECT id,nama_lengkap,nohp_siswa,saldo,blokir FROM pendaftaran_siswa WHERE id=? FOR UPDATE');
    $lock->bind_param('i', $id_pengirim);
    $lock->execute();
    $pengirim = $lock->get_result()->fetch_assoc();
    $lock->close();
    if (!$pengirim) throw new RuntimeException('Data pengirim tidak ditemukan!');
    if ((int)($pengirim['blokir'] ?? 0) === 1) throw new RuntimeException('Kartu pengirim sedang diblokir!');
    $pengirim_saldo = (int)$pengirim['saldo'];
    if ($pengirim_saldo < $jumlah) throw new RuntimeException('Saldo tidak cukup!');

    $debit = $conn->prepare('UPDATE pendaftaran_siswa SET saldo=saldo-? WHERE id=? AND saldo>=? AND blokir=0');
    $debit->bind_param('iii', $jumlah, $id_pengirim, $jumlah);
    $debit->execute();
    if ($debit->affected_rows !== 1) throw new RuntimeException('Saldo tidak cukup atau kartu diblokir!');
    $debit->close();

    $credit = $conn->prepare('UPDATE pendaftaran_siswa SET saldo=saldo+? WHERE id=?');
    $credit->bind_param('ii', $jumlah, $id_penerima);
    $credit->execute();
    if ($credit->affected_rows !== 1) throw new RuntimeException('Penerima tidak ditemukan!');
    $credit->close();

    $log = $conn->prepare('INSERT INTO log_transfer (id_pengirim,id_penerima,jumlah,tanggal) VALUES (?,?,?,NOW())');
    $log->bind_param('iii', $id_pengirim, $id_penerima, $jumlah);
    $log->execute();
    $log->close();

    $conn->commit();

    $response['success'] = true;
    $response['message'] = 'Saldo berhasil dikirim ke ' . htmlspecialchars($teman['nama_lengkap']) . '!';

    // === Kirim Notifikasi WA Setelah Commit ===
    $nohp_pengirim  = $pengirim['nohp_siswa'];
    $nohp_penerima  = $teman['nohp_siswa'];
    $nama_pengirim  = $pengirim['nama_lengkap'];
    $nama_penerima  = $teman['nama_lengkap'];
    $new_saldo_pengirim = $pengirim_saldo - $jumlah;

    $message_penerima = "📥 *Saldo Masuk*\n\n" .
        "Kamu menerima saldo sebesar *Rp " . number_format($jumlah, 0, ',', '.') . "* dari *$nama_pengirim*.\n\n" .
        "💰 Silakan cek saldo kamu di aplikasi.\n\n" .
        "_Pesan dikirim dari Aplikasi *M-Kantin* {$pengaturan['nama_sekolah']}_";

    $message_pengirim = "📤 *Saldo Terkirim*\n\n" .
        "Kamu telah mengirim saldo sebesar *Rp " . number_format($jumlah, 0, ',', '.') . "* ke *$nama_penerima*.\n\n" .
        "💰 Sisa saldo kamu: Rp " . number_format($new_saldo_pengirim, 0, ',', '.') . "\n\n" .
        "_Pesan dikirim dari Aplikasi *M-Kantin* {$pengaturan['nama_sekolah']}_";

    function kirim_wa($nomor, $message) {
        if (substr($nomor, 0, 1) === "0") {
            $nomor = "62" . substr($nomor, 1);
        }

        $apiUrl = (string)sds_config('services.whatsapp.url', '');
        $apiKey = (string)sds_config('services.whatsapp.api_key', '');
        $sender = (string)sds_config('services.whatsapp.sender', '');
        if ($apiUrl === '' || $apiKey === '' || $sender === '' || $nomor === '') return;
        $data = [
            'api_key' => $apiKey,
            'sender'  => $sender,
            'number'  => $nomor,
            'message' => $message
        ];

        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => $apiUrl,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => json_encode($data),
            CURLOPT_HTTPHEADER => array('Content-Type: application/json'),
        ));

        curl_exec($curl);
        curl_close($curl);
    }

    // Kirim WA
    if (!empty($nohp_penerima)) kirim_wa($nohp_penerima, $message_penerima);
    if (!empty($nohp_pengirim)) kirim_wa($nohp_pengirim, $message_pengirim);

} catch (Throwable $e) {
    $conn->rollback();
    error_log('[SDS transfer saldo] ' . $e->getMessage());
    $response['message'] = $e instanceof RuntimeException ? $e->getMessage() : 'Terjadi kesalahan saat memproses transfer.';
}

echo json_encode($response);
