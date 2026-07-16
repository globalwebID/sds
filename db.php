<?php
require_once __DIR__ . '/config/runtime.php';
require_once __DIR__ . '/config/academic_year.php';
sds_require_installed();

try {
    $conn = sds_mysqli('main');
} catch (Throwable $e) {
    error_log('[SDS database] ' . $e->getMessage());
    http_response_code(500);
    exit('Koneksi database tidak tersedia. Periksa konfigurasi aplikasi.');
}

$baseUrl = sds_base_url();
$uploadBaseUrl = sds_base_url('uploads/');

// Tahun ajaran tidak lagi dihitung dari bulan server. Seluruh modul memakai
// satu baris aktif yang dikelola dari menu Tahun Ajaran SDS.
try {
    $academicYearMigrationLock = __DIR__ . '/storage/academic_year_v1_4.lock';
    if (!is_file($academicYearMigrationLock)) {
        sds_academic_year_ensure_schema($conn);
        @file_put_contents($academicYearMigrationLock, date('c'));
    }
    $tahunAjaranAktifData = sds_academic_year_get_active($conn);
} catch (Throwable $e) {
    // Aplikasi lama tetap berjalan apabila akun database belum memiliki izin
    // ALTER. Menu Tahun Ajaran akan menampilkan pesan migrasi yang lebih jelas.
    error_log('[SDS academic year] ' . $e->getMessage());
    $tahunAjaranAktifData = sds_academic_year_get_active($conn);
}

$tahunAjaran = (string)($tahunAjaranAktifData['tahun_ajaran'] ?? sds_academic_year_default_label());
$semesterAktif = (string)($tahunAjaranAktifData['semester_aktif'] ?? 'ganjil');
$tahunAjaranAktif = $tahunAjaran;

$pengaturan=[];$centralSettingsResult=$conn->query('SELECT * FROM pengaturan ORDER BY id LIMIT 1');
if($centralSettingsResult&&$centralSettingsResult->num_rows)$pengaturan=$centralSettingsResult->fetch_assoc()?:[];
sds_apply_central_controls($conn, 'Aplikasi SDS');
