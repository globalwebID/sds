<?php
require_once __DIR__ . '/runtime.php';
require_once __DIR__ . '/academic_year.php';
sds_require_installed();

try {
    $conn = sds_mysqli('main');
} catch (Throwable $e) {
    error_log('[SDS database] ' . $e->getMessage());
    http_response_code(500);
    exit('Koneksi database tidak tersedia. Periksa konfigurasi aplikasi.');
}

try {
    $academicYearMigrationLock = dirname(__DIR__) . '/storage/academic_year_v1_4.lock';
    if (!is_file($academicYearMigrationLock)) {
        sds_academic_year_ensure_schema($conn);
        @file_put_contents($academicYearMigrationLock, date('c'));
    }
    $tahunAjaranAktifData = sds_academic_year_get_active($conn);
} catch (Throwable $e) {
    error_log('[SDS academic year] ' . $e->getMessage());
    $tahunAjaranAktifData = sds_academic_year_get_active($conn);
}
$tahunAjaran = (string)($tahunAjaranAktifData['tahun_ajaran'] ?? sds_academic_year_default_label());
$semesterAktif = (string)($tahunAjaranAktifData['semester_aktif'] ?? 'ganjil');
$tahunAjaranAktif = $tahunAjaran;

$pengaturan = [];
$result = $conn->query('SELECT * FROM pengaturan LIMIT 1');
if ($result && $result->num_rows > 0) {
    $pengaturan = $result->fetch_assoc();
} else {
    $pengaturan = ['nama_sekolah'=>'','logo'=>''];
}
sds_apply_central_controls($conn, 'SDS');
