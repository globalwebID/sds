<?php
require_once __DIR__ . '/runtime.php';
require_once __DIR__ . '/academic_year.php';
sds_require_installed();

$baseUrl = sds_base_url();
$uploadBaseUrl = sds_base_url('uploads/');

// config/db.php biasanya sudah menyediakan koneksi dan tahun ajaran aktif.
// Fallback ini menjaga file tetap dapat dipanggil mandiri tanpa kembali ke
// perhitungan otomatis berdasarkan tanggal server.
if (!isset($tahunAjaran) || trim((string)$tahunAjaran) === '') {
    try {
        $academicYearConnection = isset($conn) && $conn instanceof mysqli ? $conn : sds_mysqli('main');
        $tahunAjaranAktifData = sds_academic_year_get_active($academicYearConnection);
        $tahunAjaran = (string)$tahunAjaranAktifData['tahun_ajaran'];
        $semesterAktif = (string)($tahunAjaranAktifData['semester_aktif'] ?? 'ganjil');
        $tahunAjaranAktif = $tahunAjaran;
        if (!isset($conn) || $academicYearConnection !== $conn) {
            $academicYearConnection->close();
        }
    } catch (Throwable $e) {
        error_log('[SDS academic year config] ' . $e->getMessage());
        $tahunAjaran = sds_academic_year_default_label();
        $semesterAktif = ((int)date('n') <= 6) ? 'genap' : 'ganjil';
    }
}

$appPath = (string)(parse_url((string)sds_config('app.base_url', ''), PHP_URL_PATH) ?: '');
if (!defined('BASE_PATH')) define('BASE_PATH', rtrim($appPath, '/') . '/mkantin/admin/');
if (!defined('BASE_URL')) define('BASE_URL', sds_base_url('mkantin/admin/'));
