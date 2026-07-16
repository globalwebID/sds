<?php
require_once __DIR__ . '/../../config/perpus.php';
sds_perpus_ensure_schema($conn);
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['error'] = 'Permintaan tidak valid.';
    header('Location: students_rfid');
    exit;
}

function rfidRedirectBack(): void
{
    $allowed = ['search', 'tahun', 'kelas', 'kartu', 'status', 'per_page'];
    $query = [];
    $raw = (string)($_POST['return_query'] ?? '');
    if ($raw !== '') {
        $parsed = [];
        parse_str($raw, $parsed);
        foreach ($allowed as $key) {
            if (isset($parsed[$key]) && !is_array($parsed[$key]) && trim((string)$parsed[$key]) !== '') {
                $query[$key] = trim((string)$parsed[$key]);
            }
        }
    }
    header('Location: students_rfid' . ($query ? '?' . http_build_query($query) : ''));
    exit;
}

$sessionToken = (string)($_SESSION['csrf_rfid'] ?? '');
$requestToken = (string)($_POST['csrf_token'] ?? '');
if ($sessionToken === '' || $requestToken === '' || !hash_equals($sessionToken, $requestToken)) {
    $_SESSION['error'] = 'Sesi formulir sudah berubah. Muat ulang halaman lalu coba kembali.';
    rfidRedirectBack();
}

$siswaId = (int)($_POST['siswa_id'] ?? 0);
$action = (string)($_POST['action'] ?? 'save');
$action = in_array($action, ['save', 'remove'], true) ? $action : 'save';

if ($siswaId <= 0) {
    $_SESSION['error'] = 'Peserta didik tidak valid.';
    rfidRedirectBack();
}

$stmtStudent = $conn->prepare('SELECT id, nama_lengkap, nisn, rfid_uid FROM pendaftaran_siswa WHERE id=? LIMIT 1');
$stmtStudent->bind_param('i', $siswaId);
$stmtStudent->execute();
$student = $stmtStudent->get_result()->fetch_assoc();
$stmtStudent->close();

if (!$student) {
    $_SESSION['error'] = 'Data peserta didik tidak ditemukan.';
    rfidRedirectBack();
}

try {
    if ($action === 'remove') {
        sds_rfid_remove(
            $conn,
            'siswa',
            $siswaId,
            (int)($_SESSION['admin_id'] ?? 0),
            'dilepas',
            'Dilepaskan melalui menu RFID Peserta Didik'
        );

        if (function_exists('catatLog')) {
            catatLog(
                $conn,
                (int)($_SESSION['admin_id'] ?? 0),
                'Lepas Kartu Peserta Didik',
                'Melepaskan kode kartu dari ' . $student['nama_lengkap'] . ' (NISN: ' . ($student['nisn'] ?: '-') . ')'
            );
        }
        $_SESSION['success'] = 'Kartu berhasil dilepaskan dari ' . $student['nama_lengkap'] . '.';
        rfidRedirectBack();
    }

    $rfidUid = trim((string)($_POST['rfid_uid'] ?? ''));
    $rfidUid = preg_replace('/[\x00-\x1F\x7F]/u', '', $rfidUid) ?? '';

    if ($rfidUid === '') {
        throw new RuntimeException('Kode kartu wajib diisi atau dipindai.');
    }
    $rfidLength = function_exists('mb_strlen') ? mb_strlen($rfidUid) : strlen($rfidUid);
    if ($rfidLength > 50) {
        throw new RuntimeException('Kode kartu maksimal 50 karakter.');
    }

    sds_rfid_assign(
        $conn,
        'siswa',
        $siswaId,
        $rfidUid,
        (int)($_SESSION['admin_id'] ?? 0),
        'Dipasang melalui menu RFID Peserta Didik'
    );

    if (function_exists('catatLog')) {
        catatLog(
            $conn,
            (int)($_SESSION['admin_id'] ?? 0),
            'Simpan Kartu Peserta Didik',
            'Memasang kode kartu untuk ' . $student['nama_lengkap'] . ' (NISN: ' . ($student['nisn'] ?: '-') . ')'
        );
    }

    $_SESSION['success'] = 'Kode kartu ' . $student['nama_lengkap'] . ' berhasil disimpan.';
} catch (Throwable $error) {
    $_SESSION['error'] = $error->getMessage();
}

rfidRedirectBack();
