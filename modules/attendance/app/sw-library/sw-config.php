<?php  @session_start();
ini_set('display_errors', 0);
error_reporting(0);
header("X-XSS-Protection: 1; mode=block");

// Absensi dan SDS memakai satu konfigurasi serta satu database utama.
$sdsRoot = dirname(__DIR__, 2);
$sdsProjectRoot = dirname(__DIR__, 4);
require_once $sdsRoot . '/config/runtime.php';
if (!sds_is_installed()) {
    header('Location: ../install/');
    exit;
}

$dbConfig = sds_database_config('main');
$DB_HOST = (string)$dbConfig['host'];
$DB_USER = (string)$dbConfig['username'];
$DB_NAME = (string)$dbConfig['database'];
$DB_PASSWD = (string)$dbConfig['password'];
$DB_PORT = (int)$dbConfig['port'];

@define("DB_HOST", $DB_HOST);
@define("DB_NAME", $DB_NAME);
@define("DB_USER", $DB_USER);
@define("DB_PASSWD" , $DB_PASSWD);

if (empty($DB_HOST) || empty($DB_USER) || empty($DB_NAME)) {
    die("Konfigurasi database tidak lengkap.");
}

$connection = new mysqli($DB_HOST, $DB_USER, $DB_PASSWD, $DB_NAME, $DB_PORT);
if ($connection->connect_error) {
    echo "
        <style>
            body {
                background: #000000;
                color: #ffffff;
                font-family: Arial, sans-serif;
                display: flex;
                justify-content: center;
                align-items: center;
                height: 100vh;
                text-align: center;
            }
        </style>
        <div>
            <h3>Koneksi database gagal</h3>
            <p>Silakan cek kembali konfigurasi Database Anda.</p>
            <small>Error: " . $connection->connect_error . "</small>
        </div>";
    exit();
}
$connection->set_charset((string)$dbConfig['charset']);
$centralControlSettings=sds_apply_central_controls($connection, 'Absensi');

// Absensi mengikuti tahun ajaran aktif yang ditetapkan dari SDS.
require_once $sdsRoot . '/config/academic_year.php';
try {
    $tahunAjaranAktifData = sds_academic_year_get_active($connection);
    $tahunAjaran = (string)($tahunAjaranAktifData['tahun_ajaran'] ?? '');
    $semesterAktif = (string)($tahunAjaranAktifData['semester_aktif'] ?? 'ganjil');
    $tahunAjaranAktif = $tahunAjaran;
} catch (Throwable $e) {
    error_log('[Absensi academic year] ' . $e->getMessage());
    $tahunAjaranAktifData = sds_academic_year_get_active($connection);
    $tahunAjaran = (string)($tahunAjaranAktifData['tahun_ajaran'] ?? sds_academic_year_default_label());
    $semesterAktif = (string)($tahunAjaranAktifData['semester_aktif'] ?? 'ganjil');
    $tahunAjaranAktif = $tahunAjaran;
}

$query_site  = "SELECT * FROM setting LIMIT 1";
$result_site = $connection->query($query_site);
if ($result_site && $row_site = $result_site->fetch_assoc()) {
	extract($row_site);
	$centralTimezone=(string)($centralControlSettings['system_timezone']??'Asia/Jakarta');
	date_default_timezone_set(in_array($centralTimezone,timezone_identifiers_list(),true)?$centralTimezone:'Asia/Jakarta');
	$whatsapp_sender = htmlspecialchars($row_site['whatsapp_phone']??'-');
	$whatsapp_token = htmlspecialchars($row_site['whatsapp_token']??'-');
	$secret_key = htmlspecialchars($row_site['secret_key']??'-');
	$whatsapp_domain = htmlspecialchars($row_site['whatsapp_domain']??'-');
	$whatsapp_tipe = htmlspecialchars($row_site['whatsapp_tipe']??'-');
}

// Branding Absensi mengikuti sumber utama SDS. Nilai di tabel `setting` tetap
// dipakai sebagai fallback agar modul tidak rusak ketika logo SDS belum diatur.
// Path diawali dari absensi/sw-content sehingga ../../uploads mengarah tepat ke
// folder uploads milik proyek SDS tanpa menyalin atau menggandakan berkas.
try {
	$sdsBrandResult = $connection->query('SELECT * FROM pengaturan LIMIT 1');
	$sdsBrand = $sdsBrandResult ? $sdsBrandResult->fetch_assoc() : null;
	if (is_array($sdsBrand)) {
		$sdsLogoFile = basename(trim((string)($sdsBrand['logo'] ?? '')));
		$sdsKopFile = basename(trim((string)($sdsBrand['kop_surat'] ?? '')));
		$sdsLogoDisk = $sdsProjectRoot . '/uploads/logo/' . $sdsLogoFile;
		$sdsKopDisk = $sdsProjectRoot . '/uploads/logo/' . $sdsKopFile;

		if ($sdsLogoFile !== '' && is_file($sdsLogoDisk)) {
			$site_logo = '../../uploads/logo/' . $sdsLogoFile;
			$site_favicon = $site_logo;
			$sds_site_logo_url = sds_base_url('uploads/logo/' . rawurlencode($sdsLogoFile));
			$sds_site_favicon_url = $sds_site_logo_url;
			$row_site['site_logo'] = $site_logo;
			$row_site['site_favicon'] = $site_favicon;
		}
		if ($sdsKopFile !== '' && is_file($sdsKopDisk)) {
			$site_kop = '../../uploads/logo/' . $sdsKopFile;
			$row_site['site_kop'] = $site_kop;
		}
		foreach (['favicon'=>'site_favicon','ttd_kepala_sekolah'=>'ttd_kepsek','stempel'=>'stempel'] as $source => $target) {
			$file = basename(trim((string)($sdsBrand[$source] ?? '')));
			if ($file !== '' && is_file($sdsProjectRoot . '/uploads/logo/' . $file)) {
				$row_site[$target] = '../../uploads/logo/' . $file;
				${$target} = $row_site[$target];
			}
		}
		if (trim((string)($sdsBrand['nama_sekolah'] ?? '')) !== '') {
			$row_site['nama_sekolah'] = trim((string)$sdsBrand['nama_sekolah']);
		}
		$identityMap = [
			'npsn'=>'npsn','kementerian'=>'kementrian','alamat'=>'site_address','desa'=>'desa',
			'kecamatan'=>'kecamatan','kabupaten'=>'kabupaten','provinsi'=>'propinsi',
			'telepon'=>'site_phone','email'=>'site_email','website'=>'site_url',
			'kepala_sekolah'=>'kepala_sekolah','nip_kepala_sekolah'=>'nip_kepala_sekolah'
		];
		foreach ($identityMap as $source => $target) {
			$value = trim((string)($sdsBrand[$source] ?? ''));
			if ($value !== '') $row_site[$target] = $value;
		}
	}
} catch (Throwable $e) {
	error_log('[Absensi branding SDS] ' . $e->getMessage());
}

if (empty($_SESSION['csrf_token'])) {
	$_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

if (!function_exists('base_url')) {
	function base_url($atRoot = false, $atCore = false, $parse = false) {
		$base_url = $atRoot ? sds_base_url() : sds_base_url('absensi');
		$base_url = rtrim($base_url, '/') . '/';
		if ($parse) {
			$base_url = parse_url($base_url);
			if (isset($base_url['path']) && $base_url['path'] === '/') {
				$base_url['path'] = '';
			}
		}
		return $base_url;
	}
}
$base_url = base_url();
$site_url = rtrim($base_url, '/');
if (isset($row_site) && is_array($row_site)) $row_site['site_url'] = $site_url;
?>
