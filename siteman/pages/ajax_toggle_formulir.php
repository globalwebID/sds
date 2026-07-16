<?php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

ob_start();

function sds_toggle_formulir_response(bool $success, string $message, int $statusCode = 200, ?int $status = null): never
{
    while (ob_get_level() > 0) {
        ob_end_clean();
    }

    http_response_code($statusCode);
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    header('Pragma: no-cache');

    echo json_encode([
        'success' => $success,
        'message' => $message,
        'status' => $status,
        'label' => $status === 1 ? 'Dibuka' : ($status === 0 ? 'Ditutup' : null),
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sds_toggle_formulir_response(false, 'Metode permintaan tidak diizinkan.', 405);
}

if (empty($_SESSION['admin_id'])) {
    sds_toggle_formulir_response(false, 'Sesi login telah berakhir. Silakan login kembali.', 401);
}

$csrfToken = (string)($_POST['csrf_token'] ?? '');
$sessionToken = (string)($_SESSION['sds_toggle_formulir_csrf'] ?? '');
if ($csrfToken === '' || $sessionToken === '' || !hash_equals($sessionToken, $csrfToken)) {
    sds_toggle_formulir_response(false, 'Token keamanan tidak valid. Muat ulang halaman lalu coba lagi.', 419);
}

$rawStatus = (string)($_POST['status'] ?? '');
if (!in_array($rawStatus, ['0', '1'], true)) {
    sds_toggle_formulir_response(false, 'Status formulir tidak valid.', 422);
}
$status = (int)$rawStatus;

try {
    require_once dirname(__DIR__, 2) . '/db.php';

    $conn->begin_transaction();

    // Jangan hanya UPDATE: pada database lama baris form_aktif bisa belum tersedia.
    $select = $conn->prepare("SELECT id FROM formulir WHERE nama = 'form_aktif' LIMIT 1 FOR UPDATE");
    $select->execute();
    $select->bind_result($formId);
    $exists = $select->fetch();
    $select->close();

    if ($exists) {
        $update = $conn->prepare('UPDATE formulir SET nilai = ? WHERE id = ?');
        $statusString = (string)$status;
        $formId = (int)$formId;
        $update->bind_param('si', $statusString, $formId);
        $update->execute();
        $update->close();
    } else {
        $insert = $conn->prepare("INSERT INTO formulir (nama, nilai, kirim_pesan) VALUES ('form_aktif', ?, 0)");
        $statusString = (string)$status;
        $insert->bind_param('s', $statusString);
        $insert->execute();
        $insert->close();
    }

    // Verifikasi nilai yang benar-benar tersimpan sebelum mengirim respons sukses.
    $verify = $conn->prepare("SELECT nilai FROM formulir WHERE nama = 'form_aktif' LIMIT 1");
    $verify->execute();
    $verify->bind_result($storedStatus);
    $verified = $verify->fetch();
    $verify->close();

    if (!$verified || (string)$storedStatus !== (string)$status) {
        throw new RuntimeException('Status formulir tidak berhasil diverifikasi setelah penyimpanan.');
    }

    $conn->commit();

    sds_toggle_formulir_response(
        true,
        $status === 1 ? 'Formulir berhasil diaktifkan.' : 'Formulir berhasil dinonaktifkan.',
        200,
        $status
    );
} catch (Throwable $e) {
    if (isset($conn) && $conn instanceof mysqli) {
        try {
            $conn->rollback();
        } catch (Throwable $rollbackError) {
            // Abaikan kegagalan rollback, error utama tetap dicatat di bawah.
        }
    }

    error_log('[SDS toggle formulir] ' . $e->getMessage());
    sds_toggle_formulir_response(false, 'Gagal menyimpan status formulir. Periksa koneksi dan struktur database.', 500);
}
