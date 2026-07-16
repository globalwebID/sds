<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }

// Status buka/tutup formulir harus selalu dibaca langsung dari database.
// Jangan biarkan browser/proxy menampilkan salinan halaman sebelum status berubah.
if (!headers_sent()) {
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    header('Pragma: no-cache');
    header('Expires: 0');
}

require_once dirname(__DIR__, 3) . '/db.php';
include sds_root_path('siteman/fungsi.php');

// Tahun ajaran aktif berasal dari master Tahun Ajaran SDS melalui db.php.
$tahunAjaran = (string)($tahunAjaran ?? '');

// Ambil data kelas
$sqlKelas = "SELECT id, nama_kelas, tahun_ajaran FROM kelas ORDER BY nama_kelas, tahun_ajaran ASC";
$resultKelas = $conn->query($sqlKelas);
$daftarKelas = [];

if ($resultKelas && $resultKelas->num_rows > 0) {
    while ($row = $resultKelas->fetch_assoc()) {
        $daftarKelas[] = $row;
    }
}
//Cek status Formulir
// $cek = $conn->query("SELECT nilai FROM formulir WHERE nama = 'form_aktif'")->fetch_assoc()['nilai'] ?? '0';

// if ($cek != '1') {
//     echo "<p class='text-center text-red-500 font-bold'>Formulir sedang tidak aktif. Silakan kembali lagi nanti.</p>";
//     exit;
// }
$formAktif = '0'; // Nilai default
$result = $conn->query("SELECT nilai FROM formulir WHERE nama = 'form_aktif'");
if ($result && $row = $result->fetch_assoc()) {
    $formAktif = $row['nilai'];
}

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

$activeTab = $_GET['tab'] ?? 'instruksi';
$allowedTabs = ['instruksi', 'formulir', 'progress'];
if (!in_array($activeTab, $allowedTabs, true)) {
    $activeTab = 'instruksi';
}

function sds_e($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function sds_mask_private($value, $visible = 6, $maskLength = 10)
{
    $digits = preg_replace('/\D+/', '', (string) $value);
    if ($digits === '') return '-';
    $prefix = substr($digits, 0, $visible);
    $remaining = max(0, strlen($digits) - strlen($prefix));
    return $prefix . str_repeat('*', min($maskLength, $remaining));
}

function sds_filled($value)
{
    $value = trim((string) $value);
    return $value !== '' && $value !== '0' && $value !== '0000-00-00' && $value !== '0.000000';
}

function sds_persen($jumlah, $target)
{
    $target = (int) $target;
    if ($target <= 0) return 0;
    return min(100, round(((int) $jumlah / $target) * 100));
}

function sds_badge($ok, $okText = 'Ada', $badText = 'Belum')
{
    return $ok ? '<span class="sds-badge ok">' . sds_e($okText) . '</span>' : '<span class="sds-badge danger">' . sds_e($badText) . '</span>';
}

function sds_file_link($path, $label = 'Lihat')
{
    if (!sds_filled($path)) return '<span class="sds-badge danger">Belum</span>';
    return '<a class="sds-badge ok" href="uploads/' . sds_e($path) . '" target="_blank">' . sds_e($label) . '</a>';
}

function sds_current_url()
{
    $uri = $_SERVER['REQUEST_URI'] ?? 'formulir?tab=progress';
    return $uri !== '' ? $uri : 'formulir?tab=progress';
}

function sds_print_token($id, $nisn)
{
    $secret = (string)sds_config('security.print_secret', '');
    if ($secret === '') throw new RuntimeException('Secret cetak belum dikonfigurasi.');
    // Token v2 memakai ID primer yang stabil. NISN dapat dikoreksi oleh admin,
    // sehingga tidak boleh membuat tautan cetak yang baru saja dibuat kedaluwarsa.
    return hash_hmac('sha256', 'print-v2|' . (int)$id, $secret);
}

function sds_print_url($id, $nisn)
{
    return 'cetak_daftar_ulang.php?id=' . (int)$id . '&token=' . urlencode(sds_print_token($id, $nisn));
}

$progressCsrf = (string)($_SESSION['progress_csrf'] ?? '');
if ($progressCsrf === '') {
    $progressCsrf = bin2hex(random_bytes(32));
    $_SESSION['progress_csrf'] = $progressCsrf;
}
$detailAccessTimeout = 30 * 60;
$now = time();
if (!empty($_SESSION['progress_detail_access'])) {
    $lastActivity = (int) ($_SESSION['progress_detail_last_activity'] ?? 0);
    if ($lastActivity <= 0 || ($now - $lastActivity) > $detailAccessTimeout) {
        unset($_SESSION['progress_detail_access'], $_SESSION['progress_detail_last_activity']);
        $_SESSION['progress_detail_error'] = 'Sesi akses detail sudah habis karena tidak ada aktivitas selama 30 menit. Silakan masukkan password kembali.';
    } else {
        $_SESSION['progress_detail_last_activity'] = $now;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['progress_detail_password'])) {
    $inputPassword = (string) ($_POST['progress_detail_password'] ?? '');
    $submittedCsrf = (string)($_POST['progress_csrf'] ?? '');
    $retryAfter = sds_rate_limit_check('progress-detail', '', 5, 300);
    $passwordValid = false;
    if ($retryAfter <= 0 && hash_equals($progressCsrf, $submittedCsrf)) {
        $adminResult = $conn->query("SELECT password FROM admins WHERE role='superadmin' ORDER BY id LIMIT 1");
        $adminRow = $adminResult ? $adminResult->fetch_assoc() : null;
        $passwordValid = is_array($adminRow) && password_verify($inputPassword, (string)($adminRow['password'] ?? ''));
    }
    if ($passwordValid) {
        sds_rate_limit_clear('progress-detail');
        $_SESSION['progress_detail_access'] = true;
        $_SESSION['progress_detail_last_activity'] = time();
    } else {
        if ($retryAfter <= 0) sds_rate_limit_fail('progress-detail', '', 300);
        $_SESSION['progress_detail_error'] = $retryAfter > 0
            ? 'Terlalu banyak percobaan. Coba lagi dalam ' . $retryAfter . ' detik.'
            : 'Password superadmin atau token akses tidak valid.';
    }
    $redirect = (string) ($_POST['redirect_to'] ?? 'formulir?tab=progress');
    if ($redirect === '' || preg_match('/^https?:\/\//i', $redirect)) $redirect = 'formulir?tab=progress';
    header('Location: ' . $redirect);
    exit;
}


function sds_progress_clean_post($key, $default = '')
{
    return trim((string)($_POST[$key] ?? $default));
}

function sds_progress_upload_file($fieldName, $oldValue = '')
{
    if (empty($_FILES[$fieldName]) || empty($_FILES[$fieldName]['name']) || (int)($_FILES[$fieldName]['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return $oldValue;
    }
    if ((int)$_FILES[$fieldName]['error'] !== UPLOAD_ERR_OK) {
        return $oldValue;
    }
    $maxSize = 10 * 1024 * 1024;
    if ((int)($_FILES[$fieldName]['size'] ?? 0) > $maxSize) {
        return $oldValue;
    }
    $ext = strtolower(pathinfo((string)$_FILES[$fieldName]['name'], PATHINFO_EXTENSION));
    $allowed = ['jpg','jpeg','png','pdf'];
    if (!in_array($ext, $allowed, true)) {
        return $oldValue;
    }
    $uploadDir = sds_root_path('uploads');
    if (!is_dir($uploadDir)) @mkdir($uploadDir, 0755, true);
    $filename = $fieldName . '_progress_' . date('YmdHis') . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
    $target = $uploadDir . '/' . $filename;
    if (move_uploaded_file($_FILES[$fieldName]['tmp_name'], $target)) {
        return $filename;
    }
    return $oldValue;
}


function sds_progress_sync_terisi_kelas($conn, $kelasId)
{
    $kelasId = (int)$kelasId;
    if ($kelasId <= 0) return;

    $stmt = $conn->prepare("
        UPDATE kelas k
        SET k.terisi = (
            SELECT COUNT(DISTINCT ps.id)
            FROM siswa_kelas sk
            JOIN pendaftaran_siswa ps ON ps.id = sk.siswa_id
            WHERE sk.kelas_id = k.id
              AND BINARY sk.tahun_ajaran = BINARY k.tahun_ajaran
              AND ps.status_aktif = 1
        )
        WHERE k.id = ?
        LIMIT 1
    ");
    if ($stmt) {
        $stmt->bind_param('i', $kelasId);
        $stmt->execute();
        $stmt->close();
    }
}

function sds_progress_get_target_kelas($conn, $kelasId, $jurusanId, $tahunAjaran, $tingkatId, $excludeSiswaId)
{
    $stmt = $conn->prepare("
        SELECT
            k.id,
            k.nama_kelas,
            k.jurusan_id,
            k.kuota,
            k.tingkat_id,
            k.tahun_ajaran,
            COUNT(DISTINCT CASE WHEN ps.id <> ? THEN ps.id END) AS terisi_tanpa_siswa_ini
        FROM kelas k
        LEFT JOIN siswa_kelas sk
            ON sk.kelas_id = k.id
           AND BINARY sk.tahun_ajaran = BINARY k.tahun_ajaran
        LEFT JOIN pendaftaran_siswa ps
            ON ps.id = sk.siswa_id
           AND ps.status_aktif = 1
        WHERE k.id = ?
          AND k.jurusan_id = ?
          AND BINARY k.tahun_ajaran = BINARY ?
          AND k.tingkat_id = ?
        GROUP BY k.id, k.nama_kelas, k.jurusan_id, k.kuota, k.tingkat_id, k.tahun_ajaran
        LIMIT 1
    ");
    if (!$stmt) return null;
    $stmt->bind_param('iiisi', $excludeSiswaId, $kelasId, $jurusanId, $tahunAjaran, $tingkatId);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return $row ?: null;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['progress_edit_student'])) {
    $redirect = (string)($_POST['redirect_to'] ?? 'formulir?tab=progress');
    if ($redirect === '' || preg_match('/^https?:\/\//i', $redirect)) $redirect = 'formulir?tab=progress';

    if (!hash_equals($progressCsrf, (string)($_POST['progress_csrf'] ?? ''))) {
        $_SESSION['progress_update_error'] = 'Token formulir tidak valid. Muat ulang halaman.';
        header('Location: ' . $redirect);
        exit;
    }
    if (empty($_SESSION['progress_detail_access'])) {
        $_SESSION['progress_detail_error'] = 'Silakan buka akses detail terlebih dahulu sebelum mengubah data.';
        header('Location: ' . $redirect);
        exit;
    }

    $editId = (int)($_POST['siswa_id'] ?? 0);
    if ($editId <= 0) {
        $_SESSION['progress_update_error'] = 'ID siswa tidak valid.';
        header('Location: ' . $redirect);
        exit;
    }

    $oldStmt = $conn->prepare("
        SELECT
            ps.foto, ps.file_kk, ps.file_ijazah, ps.file_akta, ps.file_kip, ps.file_kps, ps.file_pkh, ps.file_kks, ps.file_kis,
            ps.kelas_id AS old_kelas_id,
            ps.jurusan_id AS old_jurusan_id,
            ps.tahun_ajaran AS old_tahun_ajaran,
            COALESCE(k.tingkat_id, 1) AS old_tingkat_id
        FROM pendaftaran_siswa ps
        LEFT JOIN kelas k ON k.id = ps.kelas_id
        WHERE ps.id = ?
        LIMIT 1
    " );
    if (!$oldStmt) {
        $_SESSION['progress_update_error'] = 'Gagal menyiapkan data lama siswa: ' . $conn->error;
        header('Location: ' . $redirect);
        exit;
    }
    $oldStmt->bind_param('i', $editId);
    $oldStmt->execute();
    $oldFiles = $oldStmt->get_result()->fetch_assoc() ?: [];
    $oldStmt->close();

    if (empty($oldFiles)) {
        $_SESSION['progress_update_error'] = 'Data siswa tidak ditemukan.';
        header('Location: ' . $redirect);
        exit;
    }

    $oldKelasId = (int)($oldFiles['old_kelas_id'] ?? 0);
    $oldJurusanId = (int)($oldFiles['old_jurusan_id'] ?? 0);
    $studentTahunAjaran = (string)($oldFiles['old_tahun_ajaran'] ?? $tahunAjaran);
    $studentTingkatId = (int)($oldFiles['old_tingkat_id'] ?? 1);

    $targetJurusanId = (int)($_POST['edit_jurusan_id'] ?? $oldJurusanId);
    $targetKelasId = (int)($_POST['edit_kelas_id'] ?? $oldKelasId);
    if ($targetJurusanId <= 0) $targetJurusanId = $oldJurusanId;
    if ($targetKelasId <= 0) $targetKelasId = $oldKelasId;

    $stmtJurusanValid = $conn->prepare("SELECT id FROM jurusan WHERE id = ? AND BINARY tahun_ajaran = BINARY ? LIMIT 1");
    if (!$stmtJurusanValid) {
        $_SESSION['progress_update_error'] = 'Gagal validasi jurusan: ' . $conn->error;
        header('Location: ' . $redirect);
        exit;
    }
    $stmtJurusanValid->bind_param('is', $targetJurusanId, $studentTahunAjaran);
    $stmtJurusanValid->execute();
    $jurusanValid = $stmtJurusanValid->get_result()->fetch_assoc();
    $stmtJurusanValid->close();
    if (!$jurusanValid) {
        $_SESSION['progress_update_error'] = 'Jurusan yang dipilih tidak valid untuk tahun ajaran siswa.';
        header('Location: ' . $redirect);
        exit;
    }

    $targetKelas = sds_progress_get_target_kelas($conn, $targetKelasId, $targetJurusanId, $studentTahunAjaran, $studentTingkatId, $editId);
    if (!$targetKelas) {
        $_SESSION['progress_update_error'] = 'Kelas yang dipilih tidak sesuai jurusan/tahun ajaran/tingkat siswa.';
        header('Location: ' . $redirect);
        exit;
    }
    $targetKuota = (int)($targetKelas['kuota'] ?? 0);
    $targetTerisiTanpaSiswaIni = (int)($targetKelas['terisi_tanpa_siswa_ini'] ?? 0);
    if ($targetKuota <= 0 || $targetTerisiTanpaSiswaIni >= $targetKuota) {
        $_SESSION['progress_update_error'] = 'Kelas tujuan sudah penuh atau kuotanya belum diatur.';
        header('Location: ' . $redirect);
        exit;
    }

    $fileFoto   = sds_progress_upload_file('foto', $oldFiles['foto'] ?? '');
    $fileKk     = sds_progress_upload_file('file_kk', $oldFiles['file_kk'] ?? '');
    $fileIjazah = sds_progress_upload_file('file_ijazah', $oldFiles['file_ijazah'] ?? '');
    $fileAkta   = sds_progress_upload_file('file_akta', $oldFiles['file_akta'] ?? '');
    $fileKip    = sds_progress_upload_file('file_kip', $oldFiles['file_kip'] ?? '');
    $fileKps    = sds_progress_upload_file('file_kps', $oldFiles['file_kps'] ?? '');
    $filePkh    = sds_progress_upload_file('file_pkh', $oldFiles['file_pkh'] ?? '');
    $fileKks    = sds_progress_upload_file('file_kks', $oldFiles['file_kks'] ?? '');
    $fileKis    = sds_progress_upload_file('file_kis', $oldFiles['file_kis'] ?? '');

    $fields = [
        'nama_lengkap','email','nisn','nik','no_kk','no_registrasi_akta','jenis_kelamin','tempat_lahir','tanggal_lahir','agama',
        'sekolah_asal','nomor_ijazah','kebutuhan_khusus','alamat','desa','kecamatan','kota','provinsi','latitude','longitude',
        'tempat_tinggal','moda_transportasi','anak_ke','jumlah_saudara_kandung','tinggi_badan','berat_badan','hobi','cita_cita',
        'nama_ayah','nik_ayah','tahun_lahir_ayah','pendidikan_ayah','pekerjaan_ayah','penghasilan_ayah',
        'nama_ibu','nik_ibu','tahun_lahir_ibu','pendidikan_ibu','pekerjaan_ibu','penghasilan_ibu',
        'nama_wali','nik_wali','tahun_lahir_wali','pendidikan_wali','pekerjaan_wali','penghasilan_wali',
        'nohp_siswa','nohp_ortu','nomor_kip','nomor_kps','nomor_pkh','nomor_kks','nomor_kis'
    ];
    $data = [];
    foreach ($fields as $f) $data[$f] = sds_progress_clean_post($f);
    $data['pernyataan_setuju'] = isset($_POST['pernyataan_setuju']) ? 1 : 0;
    $data['sudah_dapodik'] = isset($_POST['sudah_dapodik']) ? 1 : 0;

    $sqlUpdate = "UPDATE pendaftaran_siswa SET
        nama_lengkap=?, email=?, nisn=?, nik=?, no_kk=?, no_registrasi_akta=?, jenis_kelamin=?, tempat_lahir=?, tanggal_lahir=?, agama=?,
        sekolah_asal=?, nomor_ijazah=?, kebutuhan_khusus=?, alamat=?, desa=?, kecamatan=?, kota=?, provinsi=?, latitude=?, longitude=?,
        tempat_tinggal=?, moda_transportasi=?, anak_ke=?, jumlah_saudara_kandung=?, tinggi_badan=?, berat_badan=?, hobi=?, cita_cita=?,
        nama_ayah=?, nik_ayah=?, tahun_lahir_ayah=?, pendidikan_ayah=?, pekerjaan_ayah=?, penghasilan_ayah=?,
        nama_ibu=?, nik_ibu=?, tahun_lahir_ibu=?, pendidikan_ibu=?, pekerjaan_ibu=?, penghasilan_ibu=?,
        nama_wali=?, nik_wali=?, tahun_lahir_wali=?, pendidikan_wali=?, pekerjaan_wali=?, penghasilan_wali=?,
        nohp_siswa=?, nohp_ortu=?, nomor_kip=?, nomor_kps=?, nomor_pkh=?, nomor_kks=?, nomor_kis=?,
        pernyataan_setuju=?, sudah_dapodik=?, foto=?, file_kk=?, file_ijazah=?, file_akta=?, file_kip=?, file_kps=?, file_pkh=?, file_kks=?, file_kis=?,
        jurusan_id=?, kelas_id=?
        WHERE id=? LIMIT 1";

    $stmtUp = $conn->prepare($sqlUpdate);
    if (!$stmtUp) {
        $_SESSION['progress_update_error'] = 'Gagal menyiapkan update data: ' . $conn->error;
        header('Location: ' . $redirect);
        exit;
    }
    $values = [];
    foreach ($fields as $f) $values[] = $data[$f];
    $values[] = $data['pernyataan_setuju'];
    $values[] = $data['sudah_dapodik'];
    array_push($values, $fileFoto, $fileKk, $fileIjazah, $fileAkta, $fileKip, $fileKps, $filePkh, $fileKks, $fileKis, $targetJurusanId, $targetKelasId, $editId);
    $types = str_repeat('s', count($fields)) . 'iisssssssssiii';
    $stmtUp->bind_param($types, ...$values);

    $conn->begin_transaction();
    try {
        if (!$stmtUp->execute()) {
            throw new Exception('Gagal menyimpan data: ' . $stmtUp->error);
        }
        $stmtUp->close();

        $stmtSk = $conn->prepare("UPDATE siswa_kelas SET kelas_id = ? WHERE siswa_id = ? AND BINARY tahun_ajaran = BINARY ?");
        if (!$stmtSk) {
            throw new Exception('Gagal menyiapkan update kelas siswa: ' . $conn->error);
        }
        $stmtSk->bind_param('iis', $targetKelasId, $editId, $studentTahunAjaran);
        if (!$stmtSk->execute()) {
            throw new Exception('Gagal update kelas siswa: ' . $stmtSk->error);
        }
        $affectedSk = $stmtSk->affected_rows;
        $stmtSk->close();

        if ($affectedSk === 0) {
            $stmtSkCheck = $conn->prepare("SELECT id FROM siswa_kelas WHERE siswa_id = ? AND BINARY tahun_ajaran = BINARY ? LIMIT 1");
            if (!$stmtSkCheck) {
                throw new Exception('Gagal cek relasi kelas siswa: ' . $conn->error);
            }
            $stmtSkCheck->bind_param('is', $editId, $studentTahunAjaran);
            $stmtSkCheck->execute();
            $existingSk = $stmtSkCheck->get_result()->fetch_assoc();
            $stmtSkCheck->close();

            if (!$existingSk) {
                $stmtSkIns = $conn->prepare("INSERT INTO siswa_kelas (siswa_id, kelas_id, tahun_ajaran, naik_kelas) VALUES (?, ?, ?, 1)");
                if (!$stmtSkIns) {
                    throw new Exception('Gagal menyiapkan relasi kelas siswa: ' . $conn->error);
                }
                $stmtSkIns->bind_param('iis', $editId, $targetKelasId, $studentTahunAjaran);
                if (!$stmtSkIns->execute()) {
                    throw new Exception('Gagal menambahkan relasi kelas siswa: ' . $stmtSkIns->error);
                }
                $stmtSkIns->close();
            }
        }

        $conn->commit();
        foreach (array_unique(array_filter([$oldKelasId, $targetKelasId])) as $syncKelasId) {
            sds_progress_sync_terisi_kelas($conn, (int)$syncKelasId);
        }
        $_SESSION['progress_update_success'] = 'Data siswa berhasil diperbarui.';
    } catch (Exception $e) {
        $conn->rollback();
        $_SESSION['progress_update_error'] = $e->getMessage();
    }
    header('Location: ' . $redirect . '#detail-' . $editId);
    exit;
}
$detailAccessUnlocked = !empty($_SESSION['progress_detail_access']);
$detailAccessError = !empty($_SESSION['progress_detail_error']) ? $_SESSION['progress_detail_error'] : '';
$progressUpdateSuccess = !empty($_SESSION['progress_update_success']) ? $_SESSION['progress_update_success'] : '';
$progressUpdateError = !empty($_SESSION['progress_update_error']) ? $_SESSION['progress_update_error'] : '';
unset($_SESSION['progress_detail_error'], $_SESSION['progress_update_success'], $_SESSION['progress_update_error']);

$progressData = null;
if ($activeTab === 'progress') {
    $tingkatList = [];
    $stmt = $conn->prepare("SELECT DISTINCT tk.id,tk.nama_tingkat,tk.urutan_tingkat
        FROM tingkat_kelas tk
        JOIN kelas k ON k.tingkat_id=tk.id AND BINARY k.tahun_ajaran=BINARY ?
        ORDER BY tk.urutan_tingkat,tk.id");
    if ($stmt) {
        $stmt->bind_param('s', $tahunAjaran);
        $stmt->execute();
        $res = $stmt->get_result();
        while ($row = $res->fetch_assoc()) $tingkatList[] = $row;
        $stmt->close();
    }
    $tingkatIds = array_map(static fn($row) => (int)$row['id'], $tingkatList);
    $tingkatId = isset($_GET['tingkat_id']) ? (int)$_GET['tingkat_id'] : (int)($tingkatIds[0] ?? 0);
    if (!in_array($tingkatId, $tingkatIds, true)) $tingkatId = (int)($tingkatIds[0] ?? 0);
    $jurusanId = isset($_GET['jurusan_id']) ? (int) $_GET['jurusan_id'] : 0;
    $kelasId = isset($_GET['kelas_id']) ? (int) $_GET['kelas_id'] : 0;
    $q = trim((string) ($_GET['q'] ?? ''));
    $progressSubtab = (string)($_GET['progress_subtab'] ?? 'rekap');
    if (!in_array($progressSubtab, ['rekap','detail'], true)) $progressSubtab = 'rekap';
    $selectedTingkat = null;
    foreach ($tingkatList as $tingkatRow) {
        if ((int)$tingkatRow['id'] === $tingkatId) { $selectedTingkat = $tingkatRow; break; }
    }
    $namaTingkat = (string)($selectedTingkat['nama_tingkat'] ?? '-');
    $isKelasX = $selectedTingkat !== null
        && (int)$selectedTingkat['urutan_tingkat'] === (int)($tingkatList[0]['urutan_tingkat'] ?? -1);

    // Progress kelengkapan data dihitung dari field wajib saja.
    // Wali, kesejahteraan, file pendukung, dan file akta tidak masuk persentase karena bersifat opsional.
    $notEmpty = function ($field) {
        return "COALESCE($field,'') <> ''";
    };
    $notPlaceholder = function ($field, $placeholder) {
        return "COALESCE($field,'') <> '' AND COALESCE($field,'') <> '$placeholder'";
    };

    $requiredProgressFields = [
        $notEmpty('ps.nama_lengkap'),
        $notEmpty('ps.email'),
        $notEmpty('ps.nisn'),
        "k.id IS NOT NULL AND k.id > 0",
        $notEmpty('ps.sekolah_asal'),
        $notEmpty('ps.nomor_ijazah'),
        $notPlaceholder('ps.jenis_kelamin', '-- Pilih Jenis Kelamin --'),
        $notEmpty('ps.tempat_lahir'),
        "COALESCE(ps.tanggal_lahir,'') <> '' AND COALESCE(ps.tanggal_lahir,'') <> '0000-00-00'",
        $notEmpty('ps.no_kk'),
        $notEmpty('ps.nik'),
        $notEmpty('ps.no_registrasi_akta'),
        $notPlaceholder('ps.agama', '-- Pilih Agama --'),
        $notEmpty('ps.provinsi'),
        $notEmpty('ps.kota'),
        $notEmpty('ps.kecamatan'),
        $notEmpty('ps.desa'),
        $notEmpty('ps.alamat'),
        "COALESCE(ps.latitude,0) <> 0 AND COALESCE(ps.longitude,0) <> 0",
        $notPlaceholder('ps.tempat_tinggal', '-- Pilih Tempat Tinggal --'),
        $notPlaceholder('ps.moda_transportasi', '-- Pilih Moda Transportasi --'),
        "COALESCE(ps.anak_ke,0) > 0",
        // Jumlah saudara kandung boleh 0, karena anak tunggal tetap data valid.
        "ps.jumlah_saudara_kandung IS NOT NULL AND ps.jumlah_saudara_kandung >= 0",
        "COALESCE(ps.tinggi_badan,0) > 0",
        "COALESCE(ps.berat_badan,0) > 0",
        $notEmpty('ps.hobi'),
        $notEmpty('ps.cita_cita'),
        $notEmpty('ps.foto'),
        $notEmpty('ps.nama_ayah'),
        $notEmpty('ps.nik_ayah'),
        "COALESCE(ps.tahun_lahir_ayah,0) > 0",
        $notPlaceholder('ps.pendidikan_ayah', '-- Pilih Pendidikan --'),
        $notPlaceholder('ps.pekerjaan_ayah', '-- Pilih Pekerjaan --'),
        $notPlaceholder('ps.penghasilan_ayah', '-- Pilih Penghasilan --'),
        $notEmpty('ps.nama_ibu'),
        $notEmpty('ps.nik_ibu'),
        "COALESCE(ps.tahun_lahir_ibu,0) > 0",
        $notPlaceholder('ps.pendidikan_ibu', '-- Pilih Pendidikan --'),
        $notPlaceholder('ps.pekerjaan_ibu', '-- Pilih Pekerjaan --'),
        $notPlaceholder('ps.penghasilan_ibu', '-- Pilih Penghasilan --'),
        $notEmpty('ps.nohp_ortu'),
        $notEmpty('ps.nohp_siswa'),
        $notEmpty('ps.file_kk'),
        $notEmpty('ps.file_ijazah'),
        "ps.pernyataan_setuju = 1"
    ];
    $totalRequiredProgressFields = count($requiredProgressFields);
    $exprProgressSkorParts = array_map(function($expr) { return "CASE WHEN $expr THEN 1 ELSE 0 END"; }, $requiredProgressFields);
    $exprProgressSkor = '(' . implode(' + ', $exprProgressSkorParts) . ')';
    $exprProgressPersen = "ROUND(($exprProgressSkor / $totalRequiredProgressFields) * 100)";

    $exprBiodata = implode(' AND ', array_slice($requiredProgressFields, 0, 28));
    $exprOrtu = implode(' AND ', array_slice($requiredProgressFields, 28, 14));
    $exprFile = "COALESCE(ps.foto,'') <> '' AND COALESCE(ps.file_kk,'') <> '' AND COALESCE(ps.file_ijazah,'') <> ''";
    $exprLengkap = "$exprProgressSkor = $totalRequiredProgressFields";

    $jurusanList = [];
    $stmt = $conn->prepare("SELECT DISTINCT j.id, j.nama_jurusan FROM kelas k JOIN jurusan j ON j.id = k.jurusan_id WHERE BINARY k.tahun_ajaran = BINARY ? AND k.tingkat_id = ? ORDER BY j.nama_jurusan ASC");
    if ($stmt) {
        $stmt->bind_param('si', $tahunAjaran, $tingkatId);
        $stmt->execute();
        $res = $stmt->get_result();
        while ($row = $res->fetch_assoc()) $jurusanList[] = $row;
        $stmt->close();
    }

    $kelasList = [];
    $kelasSql = "SELECT id, nama_kelas FROM kelas WHERE BINARY tahun_ajaran = BINARY ? AND tingkat_id = ?";
    $kelasTypes = 'si';
    $kelasParams = [$tahunAjaran, $tingkatId];
    if ($jurusanId > 0) { $kelasSql .= " AND jurusan_id = ?"; $kelasTypes .= 'i'; $kelasParams[] = $jurusanId; }
    $kelasSql .= " ORDER BY nama_kelas ASC";
    $stmt = $conn->prepare($kelasSql);
    if ($stmt) {
        $stmt->bind_param($kelasTypes, ...$kelasParams);
        $stmt->execute();
        $res = $stmt->get_result();
        while ($row = $res->fetch_assoc()) $kelasList[] = $row;
        $stmt->close();
    }

    $allKelasList = [];
    $stmt = $conn->prepare("SELECT id, nama_kelas, jurusan_id, kuota FROM kelas WHERE BINARY tahun_ajaran = BINARY ? AND tingkat_id = ? ORDER BY nama_kelas ASC");
    if ($stmt) {
        $stmt->bind_param('si', $tahunAjaran, $tingkatId);
        $stmt->execute();
        $res = $stmt->get_result();
        while ($row = $res->fetch_assoc()) $allKelasList[] = $row;
        $stmt->close();
    }

    $baseFrom = "FROM siswa_kelas sk JOIN pendaftaran_siswa ps ON ps.id = sk.siswa_id JOIN kelas k ON k.id = sk.kelas_id LEFT JOIN jurusan j ON j.id = k.jurusan_id";
    $baseWhere = "BINARY sk.tahun_ajaran = BINARY ? AND BINARY k.tahun_ajaran = BINARY ? AND k.tingkat_id = ? AND ps.status_aktif = 1";
    $baseTypes = 'ssi';
    $baseParams = [$tahunAjaran, $tahunAjaran, $tingkatId];
    if ($kelasId > 0) { $baseWhere .= " AND k.id = ?"; $baseTypes .= 'i'; $baseParams[] = $kelasId; }
    if ($jurusanId > 0) { $baseWhere .= " AND k.jurusan_id = ?"; $baseTypes .= 'i'; $baseParams[] = $jurusanId; }

    $summary = ['total'=>0,'biodata'=>0,'ortu'=>0,'file'=>0,'setuju'=>0,'lengkap'=>0,'avg_progress'=>0,'dapodik'=>0];
    $sql = "SELECT COUNT(DISTINCT ps.id) total, SUM(CASE WHEN $exprBiodata THEN 1 ELSE 0 END) biodata, SUM(CASE WHEN $exprOrtu THEN 1 ELSE 0 END) ortu, SUM(CASE WHEN $exprFile THEN 1 ELSE 0 END) file, SUM(CASE WHEN ps.pernyataan_setuju=1 THEN 1 ELSE 0 END) setuju, SUM(CASE WHEN $exprLengkap THEN 1 ELSE 0 END) lengkap, ROUND(COALESCE(AVG($exprProgressPersen),0)) avg_progress, SUM(CASE WHEN ps.sudah_dapodik=1 THEN 1 ELSE 0 END) dapodik $baseFrom WHERE $baseWhere";
    $stmt = $conn->prepare($sql);
    if ($stmt) {
        $stmt->bind_param($baseTypes, ...$baseParams);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        foreach ($summary as $k => $_) $summary[$k] = (int)($row[$k] ?? 0);
        $stmt->close();
    }

    $kuotaWhere = "BINARY tahun_ajaran = BINARY ? AND tingkat_id = ?";
    $kuotaTypes = 'si'; $kuotaParams = [$tahunAjaran, $tingkatId];
    if ($kelasId > 0) { $kuotaWhere .= " AND id = ?"; $kuotaTypes .= 'i'; $kuotaParams[] = $kelasId; }
    if ($jurusanId > 0) { $kuotaWhere .= " AND jurusan_id = ?"; $kuotaTypes .= 'i'; $kuotaParams[] = $jurusanId; }
    $totalKuota = 0;
    $stmt = $conn->prepare("SELECT COALESCE(SUM(kuota),0) total_kuota FROM kelas WHERE $kuotaWhere");
    if ($stmt) { $stmt->bind_param($kuotaTypes, ...$kuotaParams); $stmt->execute(); $row = $stmt->get_result()->fetch_assoc(); $totalKuota = (int)($row['total_kuota'] ?? 0); $stmt->close(); }

    $progressKelas = [];
    $kelasWhere = "BINARY k.tahun_ajaran = BINARY ? AND k.tingkat_id = ?";
    $kelasKTypes = 'ssi';
    $kelasKParams = [$tahunAjaran, $tahunAjaran, $tingkatId];
    if ($kelasId > 0) { $kelasWhere .= " AND k.id = ?"; $kelasKTypes .= 'i'; $kelasKParams[] = $kelasId; }
    if ($jurusanId > 0) { $kelasWhere .= " AND k.jurusan_id = ?"; $kelasKTypes .= 'i'; $kelasKParams[] = $jurusanId; }
    $sqlKelasProgress = "SELECT k.id,k.nama_kelas,k.kuota,j.nama_jurusan,COUNT(DISTINCT ps.id) jumlah_siswa, SUM(CASE WHEN $exprFile THEN 1 ELSE 0 END) file_lengkap, SUM(CASE WHEN ps.pernyataan_setuju=1 THEN 1 ELSE 0 END) setuju, SUM(CASE WHEN $exprLengkap THEN 1 ELSE 0 END) lengkap, ROUND(COALESCE(AVG($exprProgressPersen),0)) avg_progress FROM kelas k LEFT JOIN jurusan j ON j.id=k.jurusan_id LEFT JOIN siswa_kelas sk ON sk.kelas_id=k.id AND BINARY sk.tahun_ajaran=BINARY ? LEFT JOIN pendaftaran_siswa ps ON ps.id=sk.siswa_id AND ps.status_aktif=1 WHERE $kelasWhere GROUP BY k.id,k.nama_kelas,k.kuota,j.nama_jurusan ORDER BY k.nama_kelas ASC";
    $stmt = $conn->prepare($sqlKelasProgress);
    if ($stmt) { $stmt->bind_param($kelasKTypes, ...$kelasKParams); $stmt->execute(); $res=$stmt->get_result(); while($row=$res->fetch_assoc()) $progressKelas[]=$row; $stmt->close(); }

    // Detail siswa dimuat hanya saat tab Detail Siswa dibuka.
    // Ini membuat tab Rekap Per Kelas lebih ringan.
    $detailRows = [];
    $loadDetailRows = ($progressSubtab === 'detail');
    if ($loadDetailRows) {
        $detailWhere = $baseWhere; $detailTypes=$baseTypes; $detailParams=$baseParams;
        if ($q !== '') { $detailWhere .= " AND (ps.nama_lengkap LIKE ? OR ps.nisn LIKE ? OR ps.nik LIKE ? OR ps.sekolah_asal LIKE ? OR k.nama_kelas LIKE ? OR j.nama_jurusan LIKE ?)"; $detailTypes.='ssssss'; $like='%'.$q.'%'; array_push($detailParams,$like,$like,$like,$like,$like,$like); }
        $sqlDetail = "SELECT DISTINCT ps.*, k.nama_kelas, j.nama_jurusan, $exprProgressPersen AS progress_data_persen, CASE WHEN $exprLengkap THEN 1 ELSE 0 END is_lengkap $baseFrom WHERE $detailWhere ORDER BY COALESCE(ps.tanggal_input,'1970-01-01 00:00:00') DESC, ps.id DESC LIMIT 200";
        $stmt = $conn->prepare($sqlDetail);
        if ($stmt) { $stmt->bind_param($detailTypes, ...$detailParams); $stmt->execute(); $res=$stmt->get_result(); while($row=$res->fetch_assoc()) $detailRows[]=$row; $stmt->close(); }
    }

    // Untuk Kelas X, progress utama adalah jumlah siswa yang sudah mengisi dibanding kuota kelas.
    // Contoh: kuota 36, yang mengisi 1 siswa = 3%, bukan 100%.
    // Untuk Kelas XI/XII, progress tetap memakai rata-rata kelengkapan field wajib per siswa.
    if ($isKelasX) {
        $target = max(1, (int)$totalKuota);
        $nilai = (int)($summary['total'] ?? 0);
    } else {
        $target = 100;
        $nilai = (int)($summary['avg_progress'] ?? 0);
    }
    $progressData = compact('tingkatList','tingkatId','jurusanId','kelasId','q','progressSubtab','loadDetailRows','namaTingkat','isKelasX','jurusanList','kelasList','allKelasList','summary','totalKuota','progressKelas','detailRows','target','nilai');
}
?>

<?php if (false): ?>
    <script src="https://cdn.tailwindcss.com"></script>
    <div class="flex items-center justify-center min-h-[200px] px-4">
        <div class="w-full max-w-md bg-yellow-50 border border-yellow-200 rounded-xl p-6 shadow-md animate-fadeIn">
            <div class="text-center">
                <div class="flex justify-center mb-4">
                    <div class="bg-yellow-100 rounded-full p-3 shadow-sm hover:scale-110 transition-transform duration-300">
                        <svg class="w-8 h-8 text-yellow-500" fill="none" stroke="currentColor" stroke-width="2"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M12 9v2m0 4h.01m-6.938 4h13.856C18.07 18.043 19 16.11 19 14c0-3.866-3.582-7-8-7s-8 3.134-8 7c0 2.11.93 4.043 2.938 5z" />
                        </svg>
                    </div>
                </div>
                <h2 class="text-xl md:text-2xl font-bold text-yellow-800 mb-2 transition-opacity duration-300">FORMULIR TELAH DITUTUP</h2>
                <p class="text-yellow-700 text-sm md:text-base">Silakan kembali lagi nanti saat pendataan dibuka.</p>
            </div>
        </div>
    </div>

    <style>
        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(10px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .animate-fadeIn {
            animation: fadeIn 0.6s ease-out;
        }
    </style>

<?php else: ?>
    <!DOCTYPE html>
    <html lang="id">

    <head>
        <meta charset="UTF-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Form Pendataan Siswa - <?= !empty($pengaturan['nama_sekolah']) ? $pengaturan['nama_sekolah'] : 'Sekolah' ?></title>
        <!-- Tambahkan di <head> -->
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

        <!-- Bootstrap JS -->

        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

        <!-- Leaflet CSS & JS -->
        <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
        <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
        <style>
            /* Reset dan dasar */
            * {
                box-sizing: border-box;
            }

            .modal-backdrop.show {
                opacity: 0;
                display: none;
            }

            body {
                font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
                background: #eef3fb;
                margin: 0;
                padding: 0;
                color: #333;
            }

            .app-shell {
                min-height: 100vh;
                display: flex;
                align-items: stretch;
                background: #eef3fb;
            }

            .app-sidebar {
                width: 270px;
                flex: 0 0 270px;
                background: linear-gradient(180deg, #0f4fb3 0%, #063b8c 100%);
                color: #fff;
                padding: 24px 18px;
                position: fixed;
                left: 0;
                top: 0;
                bottom: 0;
                height: 100vh;
                overflow-y: auto;
                box-shadow: 10px 0 28px rgba(15, 79, 179, .16);
                z-index: 40;
                transition: width .22s ease, padding .22s ease;
            }

            .sidebar-toggle {
                position: absolute;
                top: 18px;
                right: 35px;
                width: 0;
                height: 0;
                margin: 0;
                border: 0px solid rgba(255, 255, 255, .28);
                border-radius: 0;
                background: unset;
                color: #fff;
                font-weight: 900;
                display: inline-flex;
                align-items: center;
                justify-content: center;
                cursor: pointer;
                transition: all .2s ease;
                box-shadow: none;
            }

            .sidebar-toggle:hover {
                /*background: rgba(255,255,255,.24);*/
                transform: translateY(-1px);
            }

            .sidebar-toggle .toggle-icon {
                width: 18px;
                height: 14px;
                position: relative;
                display: inline-block;
            }

            .sidebar-toggle .toggle-icon::before,
            .sidebar-toggle .toggle-icon::after,
            .sidebar-toggle .toggle-icon span {
                content: '';
                position: absolute;
                left: 0;
                width: 18px;
                height: 2px;
                border-radius: 99px;
                background: currentColor;
            }

            .sidebar-toggle .toggle-icon::before { top: 0; }
            .sidebar-toggle .toggle-icon span { top: 6px; }
            .sidebar-toggle .toggle-icon::after { bottom: 0; }

            .app-shell.sidebar-collapsed .app-sidebar {
                width: 86px;
                flex-basis: 86px;
                padding: 18px 12px;
                overflow-x: hidden;
            }

            .app-shell.sidebar-collapsed .sidebar-brand {
                padding: 35px 0 14px;
            }

            .app-shell.sidebar-collapsed .sidebar-brand img {
                width: 54px;
                height: 54px;
                border-radius: 14px;
                margin-bottom: 0;
            }

            .app-shell.sidebar-collapsed .sidebar-brand .school-name,
            .app-shell.sidebar-collapsed .sidebar-brand .school-subtitle,
            .app-shell.sidebar-collapsed .sidebar-menu a span:not(.menu-icon) {
                display: none;
            }

            .app-shell.sidebar-collapsed .sidebar-menu a {
                justify-content: center;
                padding: 13px 8px;
            }

            .app-shell.sidebar-collapsed .sidebar-menu a:hover,
            .app-shell.sidebar-collapsed .sidebar-menu a.active {
                transform: none;
            }

            .app-shell.sidebar-collapsed .sidebar-menu .menu-icon {
                width: 34px;
                height: 34px;
                font-size: 17px;
            }

            .app-shell.sidebar-collapsed .app-main {
                margin-left: 86px;
            }

            .app-shell.sidebar-collapsed .footer {
                left: 86px;
            }

            .sidebar-brand {
                text-align: center;
                padding: 0px 8px 22px;
                border-bottom: 1px solid rgba(255,255,255,.18);
                margin-bottom: 18px;
            }

            .sidebar-brand img {
                width: 92px;
                height: 92px;
                object-fit: contain;
                background: #fff;
                border-radius: 18px;
                padding: 8px;
                margin-bottom: 12px;
                box-shadow: 0 12px 25px rgba(0,0,0,.18);
            }

            .sidebar-brand .school-name {
                font-size: 14px;
                font-weight: 800;
                line-height: 1.35;
            }

            .sidebar-brand .school-subtitle {
                font-size: 12px;
                opacity: .86;
                margin-top: 5px;
            }

            .sidebar-menu {
                display: grid;
                gap: 10px;
            }

            .sidebar-menu a {
                display: flex;
                align-items: center;
                gap: 10px;
                color: #eaf2ff;
                text-decoration: none;
                font-weight: 600;
                padding: 13px 14px;
                border-radius: 12px;
                background: rgba(255,255,255,.08);
                transition: all .2s ease;
            }

            .sidebar-menu a:hover,
            .sidebar-menu a.active {
                color: #063b8c;
                background: #fff;
                transform: translateX(2px);
                box-shadow: 0 10px 24px rgba(0,0,0,.14);
            }

            .sidebar-menu .menu-icon {
                width: 26px;
                height: 26px;
                display: inline-flex;
                align-items: center;
                justify-content: center;
                border-radius: 8px;
                background: rgba(255,255,255,.16);
            }

            .sidebar-menu a.active .menu-icon,
            .sidebar-menu a:hover .menu-icon {
                background: #eaf2ff;
            }

            .sidebar-menu a:focus {
                outline: 3px solid rgba(255,255,255,.45);
                outline-offset: 2px;
            }

            .sidebar-menu a.active {
                font-weight: 800;
            }

            .app-main {
                flex: 1;
                min-width: 0;
                padding: 0px 0px 86px;
                margin-left: 270px;
            }

            .container {
                width: 100%;
                max-width: 1560px;
                margin: 0 auto;
                background: white;
                padding: 24px 28px 28px;
                border-radius: 0px;
                box-shadow: 0 10px 35px rgba(15, 79, 179, 0.11);
            }

            .page-title {
                display: flex;
                align-items: center;
                justify-content: space-between;
                gap: 16px;
                margin-bottom: 12px;
                padding-bottom: 16px;
                border-bottom: 1px solid #e5edf7;
            }

            .page-title h1 {
                font-weight: 600;
                font-size: 24px;
                color: #1a73e8;
                margin: 0;
            }

            .page-title .tahun-badge {
                background: #eff6ff;
                color: #0f4fb3;
                border: 1px solid #d7e8ff;
                border-radius: 999px;
                padding: 9px 14px;
                font-size: 13px;
                font-weight: 700;
                white-space: nowrap;
            }

            header {
                display: none;
            }

            form label {
                display: block;
                margin-top: 15px;
                font-weight: 500;
                color: #555;
                font-size:15px;
            }

            input[type="text"],
            input[type="number"],
            input[type="date"],
            input[type="file"],
            select,
            textarea {
                width: 100%;
                padding: 9px 12px;
                margin-top: 5px;
                border: 1.5px solid #ccc;
                border-radius: 6px;
                font-size: 15px;
                transition: border-color 0.3s ease;
                resize: vertical;
            }

            input[type="text"]:focus,
            input[type="number"]:focus,
            input[type="date"]:focus,
            input[type="file"]:focus,
            select:focus,
            textarea:focus {
                border-color: #1a73e8;
                outline: none;
            }

            textarea {
                min-height: 70px;
            }

            button {
                margin-top: 30px;
                background-color: #1a73e8;
                color: white;
                font-weight: 700;
                padding: 14px 20px;
                border: none;
                border-radius: 7px;
                cursor: pointer;
                font-size: 16px;
                transition: background-color 0.3s ease;
            }

            button:hover {
                background-color: #155bb5;
            }

            h3 {
                margin-top: 40px;
                border-bottom: 2px solid #1a73e8;
                padding-bottom: 6px;
                color: #1a73e8;
            }

            input[type="checkbox"] {
                width: auto;
                margin-right: 8px;
                vertical-align: middle;
            }

            label.checkbox-label {
                display: flex;
                align-items: center;
                margin-top: 20px;
                font-weight: 600;
            }


            .required-star {
                color: #dc2626;
                font-weight: 800;
            }

            /* Style modal background */
            .modal {
                display: none;
                /* awalnya disembunyikan */
                position: fixed;
                z-index: 1000;
                left: 0;
                top: 0;
                width: 100%;
                height: 100%;
                overflow: auto;
                background-color: rgba(0, 0, 0, 0.5);
            }

            /* Style konten modal */
            .modal-content {
                background-color: #fefefe;
                margin: 15% auto;
                padding: 20px;
                border-radius: 8px;
                width: 500px;
                text-align: center;
            }

            .modal-buttons {
                margin-top: 20px;
                display: flex;
                justify-content: space-around;
            }

            button {
                padding: 8px 16px;
                cursor: pointer;
                border: none;
                border-radius: 4px;
                font-size: 14px;
            }

            .btn-confirm {
                background-color: #4CAF50;
                color: white;
            }

            .btn-cancel {
                background-color: #f44336;
                color: white;
            }

            .footer {
                position: fixed;
                left: 270px;
                right: 0;
                bottom: 0;
                z-index: 999;
                text-align: center;
                padding: 13px 18px;
                color: #64748b;
                font-size: 13px;
                font-weight: 500;
                background: rgba(255,255,255,.94);
                border-top: 1px solid #e5edf7;
                backdrop-filter: blur(12px);
                box-shadow: 0 -10px 25px rgba(15,23,42,.06);
            }



            /* Wizard Form */
            .wizard-card {
                margin-top: 20px;
            }

            .wizard-progress {
                display: grid;
                grid-template-columns: repeat(6, 1fr);
                gap: 10px;
                margin: 25px 0 30px;
            }

            .wizard-step-indicator {
                position: relative;
                text-align: center;
                color: #7b8794;
                font-size: 12px;
                font-weight: 700;
                cursor: pointer;
                user-select: none;
            }

            .wizard-step-indicator:hover .step-number {
                border-color: #1a73e8;
                color: #1a73e8;
            }

            .wizard-step-indicator.active:hover .step-number,
            .wizard-step-indicator.done:hover .step-number {
                color: #fff;
            }

            .wizard-step-indicator .step-number {
                width: 34px;
                height: 34px;
                border-radius: 999px;
                display: flex;
                align-items: center;
                justify-content: center;
                margin: 0 auto 8px;
                background: #e8eef7;
                color: #52616f;
                border: 2px solid #d8e0ea;
                transition: all .25s ease;
            }

            .wizard-step-indicator.active .step-number,
            .wizard-step-indicator.done .step-number {
                background: #1a73e8;
                border-color: #1a73e8;
                color: #fff;
                box-shadow: 0 8px 18px rgba(26, 115, 232, .22);
            }

            .wizard-step-indicator.active {
                color: #1a73e8;
            }

            .wizard-step {
                display: none;
                animation: fadeIn .25s ease-out;
            }

            .wizard-step.active {
                display: block;
            }

            .wizard-step h3 {
                margin-top: 0;
            }

            .wizard-grid {
                display: grid;
                grid-template-columns: repeat(3, minmax(0, 1fr));
                gap: 14px 18px;
                align-items: start;
            }

            .file-main-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .file-support-empty {
                grid-column: 1 / -1;
                display: none;
                background: #fff8e7;
                border: 1px dashed #fbbf24;
                color: #7c4a03;
                border-radius: 12px;
                padding: 13px 14px;
                font-size: 14px;
                line-height: 1.55;
            }

            .file-support-hint {
                display: block;
                margin-top: 6px;
                color: #64748b;
                font-size: 12px;
                line-height: 1.4;
            }

            .wizard-field,
            .form-group,
            .mb-3 {
                margin: 0;
            }

            .wizard-field label,
            .form-group label,
            .mb-3 label {
                margin-top: 0;
            }

            .wizard-field.full,
            .wizard-full,
            #field_alamat,
            #field_koordinat,
            .map-field {
                grid-column: 1 / -1;
            }

            #map {
                width: 100%;
                border-radius: 10px;
                overflow: hidden;
                border: 1px solid #d8e0ea;
            }

            .koordinat-input-row {
                display: grid;
                grid-template-columns: minmax(0, 1fr) auto;
                gap: 10px;
                align-items: end;
            }

            .koordinat-input-row input[type="text"] {
                margin-top: 5px;
            }

            .btn-google-maps {
                height: 41px;
                margin: 5px 0 0;
                padding: 0 14px;
                border-radius: 8px;
                border: 1px solid #dbeafe;
                background: linear-gradient(135deg, #16a34a, #15803d);
                color: #fff;
                font-weight: 800;
                font-size: 13px;
                display: inline-flex;
                align-items: center;
                justify-content: center;
                gap: 6px;
                white-space: nowrap;
                box-shadow: 0 8px 18px rgba(22, 163, 74, .18);
            }

            .btn-google-maps:hover:not(:disabled) {
                background: linear-gradient(135deg, #15803d, #166534);
                transform: translateY(-1px);
            }

            .btn-google-maps:disabled {
                cursor: not-allowed;
                opacity: .55;
                background: #94a3b8;
                box-shadow: none;
            }

            .btn-google-maps .maps-icon {
                font-size: 15px;
                line-height: 1;
            }


            .wizard-actions {
                display: flex;
                align-items: center;
                justify-content: space-between;
                gap: 12px;
                margin-top: 30px;
                padding-top: 20px;
                border-top: 1px solid #e8eef7;
            }

            .wizard-actions .btn-secondary-wizard {
                background: #eef3fb;
                color: #1f2937;
            }

            .wizard-actions .btn-secondary-wizard:hover {
                background: #dce8f8;
            }

            .wizard-note {
                background: #f8fbff;
                border: 1px solid #d8e8ff;
                color: #35506b;
                border-radius: 10px;
                padding: 12px 14px;
                margin-bottom: 18px;
                font-size: 14px;
            }

            .declaration-box {
                background: #f8fbff;
                border: 1px solid #d8e8ff;
                border-radius: 12px;
                padding: 18px;
                grid-column: 1 / -1;
            }

            .declaration-box label {
                display: flex;
                align-items: flex-start;
                gap: 10px;
                margin: 0;
                line-height: 1.5;
            }

            .declaration-box input[type="checkbox"] {
                margin-top: 4px;
                flex: 0 0 auto;
            }

            .wizard-note {
                margin-bottom: 14px;
            }

            .wizard-card {
                margin-top: 12px;
            }

            .wizard-progress {
                margin: 18px 0 22px;
            }

            h3 {
                font-size: 20px;
            }
            .small, small {
                font-size: 12px;
            }

            .top-tabs {
                display: flex;
                gap: 10px;
                flex-wrap: wrap;
                margin: 18px 0 18px;
                border-bottom: 1px solid #e5edf7;
                padding-bottom: 12px;
            }

            .top-tabs a {
                display: inline-flex;
                align-items: center;
                gap: 8px;
                padding: 11px 16px;
                border-radius: 999px;
                text-decoration: none;
                font-weight: 800;
                color: #35506b;
                background: #f5f8fc;
                border: 1px solid #e5edf7;
            }

            .top-tabs a.active {
                background: #1a73e8;
                color: #fff;
                border-color: #1a73e8;
                box-shadow: 0 10px 22px rgba(26, 115, 232, .18);
            }

            .info-grid { display:grid; grid-template-columns: repeat(2,minmax(0,1fr)); gap:16px; }
            .info-card { background:#fff; border:1px solid #e5edf7; border-radius:16px; padding:18px; box-shadow:0 8px 22px rgba(15,79,179,.08); }
            .info-card h2 { margin:0 0 12px; color:#0f4fb3; font-size:18px; }
            .info-card ol, .info-card ul { margin:0; padding-left:20px; line-height:1.8; }
            .info-card.full { grid-column:1/-1; background:#f8fbff; }
            .start-actions { display:flex; gap:10px; flex-wrap:wrap; margin-top:16px; }
            .start-actions a { display:inline-flex; padding:11px 16px; border-radius:12px; background:#1a73e8; color:#fff; text-decoration:none; font-weight:600; }
            .start-actions a.secondary { background:#eef3fb; color:#1f2937; }

            .progress-filter { background:#f8fbff; border:1px solid #d8e8ff; border-radius:16px; padding:14px; margin-bottom:16px; }
            .progress-filter-grid { display:grid; grid-template-columns:1.3fr 1fr 1fr 1.5fr auto; gap:10px; align-items:end; }
            .progress-filter label { margin:0 0 6px; font-size:12px; color:#64748b; text-transform:uppercase; letter-spacing:.04em; font-weight:800; }
            .progress-filter input, .progress-filter select { margin-top:0; }
            .progress-filter .filter-hint { grid-column:1/-1; color:#64748b; font-size:12px; margin-top:-2px; }
            .progress-tab-nav { display:flex; gap:10px; flex-wrap:wrap; margin:14px 0 16px; border-bottom:1px solid #e5edf7; padding-bottom:12px; }
            .progress-tab-btn { display:inline-flex; align-items:center; gap:8px; padding:11px 16px; border-radius:999px; text-decoration:none; font-weight:900; color:#35506b; background:#f5f8fc; border:1px solid #e5edf7; }
            .progress-tab-btn.active { background:#1a73e8; color:#fff; border-color:#1a73e8; box-shadow:0 10px 22px rgba(26,115,232,.18); }
            .progress-tab-panel { display:none; }
            .progress-tab-panel.active { display:block; }
            .progress-tab-title { margin-top:0; }
            .progress-cards { display:grid; grid-template-columns: repeat(5,minmax(0,1fr)); gap:12px; margin-bottom:16px; }
            .progress-card { background:#fff; border:1px solid #e5edf7; border-radius:16px; padding:16px; box-shadow:0 8px 22px rgba(15,79,179,.06);}
            .progress-card small { display:block; color:#64748b; font-weight:500; text-transform:uppercase; letter-spacing:.04em; margin-bottom:8px; }
            .progress-card strong { display:block; font-size:26px; color:#152033; }
            .progress-bar-wrap { height:16px; background:#eaf0f8; border-radius:999px; overflow:hidden; min-width:150px; }
            .progress-bar-fill { height:100%; background:linear-gradient(90deg,#1a73e8,#22c55e); color:#fff; font-size:11px; display:flex; align-items:center; justify-content:flex-end; padding-right:6px; font-weight:800; min-width:30px; }
            .table-card { border:1px solid #e5edf7; border-radius:16px; overflow:auto; margin-bottom:18px; }
            .sds-table { width:100%; border-collapse:collapse; min-width:900px; background:#fff; }
            .sds-table th { background:#f8fafc; color:#475569; font-size:12px; text-align:left; text-transform:uppercase; letter-spacing:.04em; }
            .sds-table th, .sds-table td { padding:11px 12px; border-bottom:1px solid #e5edf7; vertical-align:top; }
            .sds-badge { display:inline-flex; padding:5px 8px; border-radius:999px; font-size:12px; font-weight:800; text-decoration:none; }
            .sds-badge.ok { background:#dcfce7; color:#166534; }
            .sds-badge.danger { background:#fee2e2; color:#991b1b; }
            .sds-badge.info { background:#dbeafe; color:#1e40af; }
            .detail-btn { margin:0; padding:7px 10px; border-radius:10px; font-size:12px; text-decoration:none; display:inline-flex; align-items:center; justify-content:center; gap:6px; }
            .action-cell { display:flex; flex-wrap:wrap; gap:6px; align-items:center; }
            .print-btn { background:#16a34a; color:#fff; }
            .print-btn:hover { background:#15803d; color:#fff; }
            .detail-modal { display:none; position:fixed; inset:0; background:rgba(15,23,42,.64); z-index:9999; padding:22px; overflow:auto; }
            .detail-modal.active { display:block; }
            .detail-box { max-width:980px; margin:0 auto; background:#fff; border-radius:18px; overflow:hidden; }
            .detail-head { display:flex; justify-content:space-between; gap:12px; align-items:center; background:#f8fafc; padding:16px 18px; border-bottom:1px solid #e5edf7; }
            .detail-head h3 { margin:0; border:0; padding:0; color:#0f4fb3; }
            .detail-close { margin:0; background:#eef3fb; color:#1f2937; border-radius:10px; }
            .detail-body { padding:18px; }
            .detail-grid { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:10px; }
            .detail-item { border:1px solid #e5edf7; border-radius:12px; padding:10px; }
            .detail-item.full { grid-column:1/-1; }
            .detail-edit-toggle { margin-top:0px;border:0; background:#f59e0b; color:#fff; border-radius:999px; padding:9px 14px; font-weight:800; cursor:pointer; }
            .detail-edit-form { display:block; margin:0; }
            .detail-edit-form .detail-edit { display:none; }
            .detail-edit-form.active .detail-view { display:none; }
            .detail-edit-form.active .detail-edit { display:block; }
            .detail-item.editing { background:#fffef7; border-color:#facc15; }
            .detail-item input, .detail-item select, .detail-item textarea { width:100%; border:1px solid #d7e1ee; border-radius:10px; padding:8px 10px; font-size:13px; background:#fff; margin-top:4px; }
            .detail-item textarea { resize:vertical; min-height:58px; }
            .detail-edit.two { display:none; grid-template-columns:1fr 1fr; gap:8px; }
            .detail-edit-form.active .detail-edit.two { display:grid; }
            .detail-edit.three { display:none; grid-template-columns:repeat(3,minmax(0,1fr)); gap:8px; }
            .detail-edit-form.active .detail-edit.three { display:grid; }
            .detail-edit.file-edit { display:none; gap:8px; align-items:center; margin-top:8px; }
            .detail-edit-form.active .detail-edit.file-edit { display:block; }
            .edit-checks { display:flex; gap:14px; align-items:center; flex-wrap:wrap; }
            .edit-checks label { display:flex; gap:6px; align-items:center; margin:0; text-transform:none; letter-spacing:0; color:#334155; font-size:13px; }
            .edit-actions { margin-top:14px; display:none; gap:10px; justify-content:flex-end; position:sticky; bottom:0; background:#fff; border-top:1px solid #e5edf7; padding-top:12px; }
            .detail-edit-form.active .edit-actions { display:flex; }
            .edit-actions button { border:0; border-radius:999px; padding:10px 16px; font-weight:900; cursor:pointer; }
            .edit-actions .save { background:#16a34a; color:#fff; }
            .edit-actions .cancel { background:#e2e8f0; color:#334155; }
            .progress-alert { border-radius:14px; padding:12px 14px; margin:0 0 14px; font-weight:800; }
            .progress-alert.success { background:#dcfce7; color:#166534; border:1px solid #86efac; }
            .progress-alert.error { background:#fee2e2; color:#991b1b; border:1px solid #fecaca; }
            .detail-label { display:block; color:#64748b; font-size:11px; font-weight:800; text-transform:uppercase; margin-bottom:3px; }
            .password-card {
                position: relative;
                background: linear-gradient(135deg, #fff7ed 0%, #fff 58%, #eff6ff 100%);
                border: 1px solid #fed7aa;
                border-radius: 20px;
                padding: 20px;
                margin: 12px 0;
                box-shadow: 0 14px 32px rgba(124, 74, 3, .08);
                overflow: hidden;
            }
            .password-card::before {
                content: "";
                position: absolute;
                width: 110px;
                height: 110px;
                right: -38px;
                top: -42px;
                border-radius: 999px;
                background: rgba(26,115,232,.10);
            }
            .password-card .password-title {
                display: flex;
                align-items: center;
                gap: 10px;
                margin: 0 0 8px;
                font-size: 18px;
                font-weight: 900;
                color: #1e293b;
            }
            .password-card .password-icon {
                width: 38px;
                height: 38px;
                border-radius: 12px;
                display: inline-flex;
                align-items: center;
                justify-content: center;
                background: #1a73e8;
                color: #fff;
                box-shadow: 0 10px 20px rgba(26,115,232,.22);
            }
            .password-card .password-desc {
                margin: 0 0 16px;
                color: #7c4a03;
                line-height: 1.55;
                max-width: 760px;
            }
            .password-card form,
            .password-access-form {
                display: grid;
                grid-template-columns: minmax(260px, 420px) auto;
                gap: 12px;
                align-items: end;
                position: relative;
                z-index: 1;
            }
            .password-card label {
                margin: 0 0 7px;
                color: #475569;
                font-size: 12px;
                font-weight: 900;
                text-transform: uppercase;
                letter-spacing: .04em;
            }
            .password-card input[type="password"] {
                width: 100%;
                height: 46px;
                margin: 0;
                border: 1px solid #cbd5e1;
                border-radius: 13px;
                background: #fff;
                padding: 0 14px;
                box-shadow: inset 0 1px 2px rgba(15,23,42,.04);
            }
            .password-card input[type="password"]:focus {
                border-color: #1a73e8;
                box-shadow: 0 0 0 4px rgba(26,115,232,.12);
            }
            .password-card button[type="submit"] {
                height: 46px;
                margin: 0;
                border-radius: 13px;
                padding: 0 18px;
                background: linear-gradient(135deg,#1a73e8,#0f5ed7);
                box-shadow: 0 12px 24px rgba(26,115,232,.20);
            }

            @media (max-width: 1180px) {
                .wizard-grid {
                    grid-template-columns: repeat(2, minmax(0, 1fr));
                }
            }

            /* Responsive */
            @media (max-width: 860px) {
                .app-shell {
                    display: block;
                }

                .app-sidebar,
                .app-shell.sidebar-collapsed .app-sidebar {
                    position: relative;
                    width: 100%;
                    min-height: auto;
                    height: auto;
                    padding: 16px;
                }

                .sidebar-toggle {
                    display: none;
                }

                .sidebar-brand {
                    display: flex;
                    align-items: center;
                    gap: 12px;
                    text-align: left;
                    padding: 0 0 14px;
                }

                .sidebar-brand img,
                .app-shell.sidebar-collapsed .sidebar-brand img {
                    width: 62px;
                    height: 62px;
                    margin: 0;
                }

                .app-shell.sidebar-collapsed .sidebar-brand .school-name,
                .app-shell.sidebar-collapsed .sidebar-brand .school-subtitle,
                .app-shell.sidebar-collapsed .sidebar-menu a span:not(.menu-icon) {
                    display: initial;
                }

                .sidebar-menu {
                    grid-template-columns: repeat(3, 1fr);
                    gap: 8px;
                }

                .sidebar-menu a {
                    justify-content: center;
                    padding: 10px 8px;
                    font-size: 13px;
                }

                .sidebar-menu .menu-icon {
                    display: none;
                }

                .app-main,
                .app-shell.sidebar-collapsed .app-main {
                    margin-left: 0;
                    padding: 14px 14px 76px;
                }

                .footer,
                .app-shell.sidebar-collapsed .footer {
                    left: 0;
                }

                .password-card form,
                .password-access-form {
                    grid-template-columns: 1fr;
                }

                .container {
                    padding: 18px;
                    border-radius: 14px;
                }

                .page-title {
                    display: block;
                }

                .page-title .tahun-badge {
                    display: inline-block;
                    margin-top: 10px;
                }

                .wizard-progress {
                    grid-template-columns: repeat(2, 1fr);
                }

                .wizard-grid {
                    grid-template-columns: 1fr;
                }

                .koordinat-input-row {
                    grid-template-columns: 1fr;
                }

                .btn-google-maps {
                    width: 100%;
                }
                .info-grid, .progress-filter-grid, .progress-cards, .detail-grid { grid-template-columns: 1fr; }
            }
        </style>
    </head>

    <body>

        <div class="app-shell">
            <aside class="app-sidebar">
                <div class="sidebar-brand">
                    <?php if (!empty($pengaturan['logo'])): ?>
                        <img src="uploads/logo/<?= htmlspecialchars($pengaturan['logo']) ?>" alt="Logo Sekolah">
                    <?php endif; ?>
                    <div>
                        <div class="school-name"><?= !empty($pengaturan['nama_sekolah']) ? htmlspecialchars($pengaturan['nama_sekolah']) : 'Sekolah' ?></div>
                        <div class="school-subtitle">Sistem Pendataan Siswa</div>
                    </div>
                </div>
                <button type="button" class="sidebar-toggle" id="sidebarToggle" aria-label="Minimize sidebar" aria-expanded="true" title="Minimize / Maximize sidebar">
                    <span class="toggle-icon"><span></span></span>
                </button>
                <div class="sidebar-menu" aria-label="Tab Formulir">
                    <a href="formulir?tab=instruksi" class="<?= $activeTab === 'instruksi' ? 'active' : '' ?>"><span class="menu-icon">ℹ️</span><span>Instruksi</span></a>
                    <a href="formulir?tab=formulir" class="<?= $activeTab === 'formulir' ? 'active' : '' ?>"><span class="menu-icon">✎</span><span>Formulir</span></a>
                    <a href="formulir?tab=progress" class="<?= $activeTab === 'progress' ? 'active' : '' ?>"><span class="menu-icon">%</span><span>Progress</span></a>
                </div>
            </aside>

            <main class="app-main">
                <div class="container">
                    <div class="page-title">
                        <h1><?= $activeTab === 'progress' ? 'Progress Daftar Ulang & Kelengkapan Data' : ($activeTab === 'formulir' ? 'Formulir Daftar Ulang Siswa Baru' : 'Instruksi Daftar Ulang Siswa Baru') ?></h1>
                        <div class="tahun-badge">Tahun Ajaran: <?= htmlspecialchars($tahunAjaran) ?></div>
                    </div>


                    <?php if ($activeTab === 'instruksi'): ?>
                        <section class="info-grid">
                            <div class="info-card">
                                <h2>Persiapan Sebelum Mengisi</h2>
                                <ul>
                                    <li>Siapkan data NISN, NIK, No. KK, dan Akta Kelahiran.</li>
                                    <li>Siapkan data lengkap ayah, ibu, atau wali.</li>
                                    <li>Siapkan file Foto Diri, KK, Ijazah SMP, dan Akta Kelahiran.</li>
                                    <li>Pastikan nomor HP siswa/orang tua aktif.</li>
                                </ul>
                            </div>
                            <div class="info-card">
                                <h2>Langkah Pengisian</h2>
                                <ol>
                                    <li>Buka tab <b>Formulir</b>.</li>
                                    <li>Isi wizard dari Biodata sampai Pernyataan.</li>
                                    <li>Pilih wilayah sampai Desa agar koordinat terisi otomatis.</li>
                                    <li>Periksa ulang data, lalu klik <b>Kirim Data</b>.</li>
                                </ol>
                            </div>
                            <div class="info-card full">
                                <h2>Catatan Penting</h2>
                                <p>Data yang dikirim akan dipakai untuk proses daftar ulang dan kelengkapan Dapodik. Pastikan semua data benar. Untuk titik rumah, koordinat dari pilihan desa hanya titik bantu; klik peta untuk menyesuaikan lokasi rumah yang lebih tepat.</p>
                                <div class="start-actions">
                                    <a href="formulir?tab=formulir">Mulai Isi Formulir</a>
                                    <a href="formulir?tab=progress" class="secondary">Lihat Progress</a>
                                </div>
                            </div>
                        </section>

                    <?php elseif ($activeTab === 'progress'): ?>
                        <?php $pd = $progressData; ?>
                        <?php if ($progressUpdateSuccess): ?><div class="progress-alert success"><?= sds_e($progressUpdateSuccess) ?></div><?php endif; ?>
                        <?php if ($progressUpdateError): ?><div class="progress-alert error"><?= sds_e($progressUpdateError) ?></div><?php endif; ?>
                        <?php if (!$pd): ?>
                            <div class="info-card full">Progress belum bisa dimuat.</div>
                        <?php else: ?>
                            <?php if ($detailAccessError): ?><div class="password-card" style="color:#991b1b;font-weight:800"><?= sds_e($detailAccessError) ?></div><?php endif; ?>

                            <div class="progress-cards">
                                <div class="progress-card"><small><?= $pd['isKelasX'] ? 'Total Kuota' : 'Total Siswa' ?></small><strong><?= number_format($pd['isKelasX'] ? $pd['totalKuota'] : $pd['summary']['total'],0,',','.') ?></strong></div>
                                <div class="progress-card"><small><?= $pd['isKelasX'] ? 'Sudah Daftar' : 'Data Lengkap' ?></small><strong><?= number_format($pd['isKelasX'] ? $pd['summary']['total'] : $pd['summary']['lengkap'],0,',','.') ?></strong></div>
                                <div class="progress-card"><small><?= $pd['isKelasX'] ? 'Progress Daftar Ulang' : 'Progress Data' ?></small><strong><?= sds_persen($pd['nilai'],$pd['target']) ?>%</strong></div>
                                <div class="progress-card"><small>Berkas Lengkap</small><strong><?= number_format($pd['summary']['file'],0,',','.') ?></strong></div>
                                <div class="progress-card"><small>Setuju</small><strong><?= number_format($pd['summary']['setuju'],0,',','.') ?></strong></div>
                            </div>

                            <?php if ($pd['isKelasX']): ?>
                                <div class="wizard-note" style="display:none">Progress daftar ulang Kelas X dihitung dari jumlah siswa yang sudah mengisi formulir dibanding kuota kelas. Contoh: kuota 36 dan sudah mengisi 1 siswa = 3%.</div>
                            <?php else: ?>
                                <div class="wizard-note">Progress dihitung dari 45 field wajib per siswa. Data wali, kesejahteraan, file pendukung, dan file akta tidak masuk persentase karena bersifat opsional.</div>
                            <?php endif; ?>

                            <?php
                                $progressBaseParams = [
                                    'tab' => 'progress',
                                    'tingkat_id' => $pd['tingkatId'],
                                    'jurusan_id' => $pd['jurusanId'],
                                    'kelas_id' => $pd['kelasId'],
                                    'q' => $pd['q'],
                                ];
                                $activeProgressSubtab = $pd['progressSubtab'] ?? 'rekap';
                                $rekapUrl = '?' . http_build_query(array_merge($progressBaseParams, ['progress_subtab' => 'rekap']));
                                $detailUrl = '?' . http_build_query(array_merge($progressBaseParams, ['progress_subtab' => 'detail']));
                            ?>
                            <div class="progress-tab-nav" role="tablist" aria-label="Tab Progress">
                                <a href="<?= sds_e($rekapUrl) ?>" class="progress-tab-btn <?= $activeProgressSubtab === 'rekap' ? 'active' : '' ?>" role="tab" aria-selected="<?= $activeProgressSubtab === 'rekap' ? 'true' : 'false' ?>">Rekap Per Kelas</a>
                                <a href="<?= sds_e($detailUrl) ?>" class="progress-tab-btn <?= $activeProgressSubtab === 'detail' ? 'active' : '' ?>" role="tab" aria-selected="<?= $activeProgressSubtab === 'detail' ? 'true' : 'false' ?>">Detail Siswa</a>
                            </div>

                            <div class="progress-tab-panel <?= $activeProgressSubtab === 'rekap' ? 'active' : '' ?>" id="progress-tab-rekap-kelas" role="tabpanel">
                                <h3 class="progress-tab-title">Rekap Per Kelas</h3>
                                <div class="table-card"><table class="sds-table"><thead><tr><th>Kelas</th><th>Jurusan</th><th><?= $pd['isKelasX'] ? 'Kuota' : 'Total Siswa' ?></th><th><?= $pd['isKelasX'] ? 'Sudah Daftar' : 'Data Lengkap' ?></th><th>Setuju</th><th><?= $pd['isKelasX'] ? 'Progress Daftar Ulang' : 'Progress Data' ?></th></tr></thead><tbody><?php foreach($pd['progressKelas'] as $row): $p = $pd['isKelasX'] ? sds_persen((int)($row['jumlah_siswa'] ?? 0), (int)($row['kuota'] ?? 0)) : (int)($row['avg_progress'] ?? 0); ?><tr><td><b><?= sds_e($row['nama_kelas']) ?></b></td><td><?= sds_e($row['nama_jurusan'] ?? '-') ?></td><td><?= (int)($pd['isKelasX']?$row['kuota']:$row['jumlah_siswa']) ?></td><td><?= (int)($pd['isKelasX']?$row['jumlah_siswa']:$row['lengkap']) ?></td><td><?= (int)$row['setuju'] ?></td><td><div class="progress-bar-wrap"><div class="progress-bar-fill" style="width:<?= $p ?>%"><?= $p ?>%</div></div></td></tr><?php endforeach; ?></tbody></table></div>
                            </div>

                            <div class="progress-tab-panel <?= $activeProgressSubtab === 'detail' ? 'active' : '' ?>" id="progress-tab-detail-siswa" role="tabpanel">
                                <h3 class="progress-tab-title">Detail Siswa</h3>
                                <form method="get" class="progress-filter" id="progressDetailFilterForm">
                                    <input type="hidden" name="tab" value="progress">
                                    <input type="hidden" name="progress_subtab" value="detail">
                                    <div class="progress-filter-grid">
                                        <div><label>Jenis Progress</label><select name="tingkat_id" data-autofilter="1"><?php foreach($pd['tingkatList'] as $index=>$tingkat): $entryLevel=$index===0; ?><option value="<?=(int)$tingkat['id']?>" <?= $pd['tingkatId']===(int)$tingkat['id']?'selected':'' ?>>Kelas <?=sds_e($tingkat['nama_tingkat'])?> - <?=$entryLevel?'Daftar Ulang':'Kelengkapan Data'?></option><?php endforeach; ?></select></div>
                                        <div><label>Jurusan</label><select name="jurusan_id" data-autofilter="1"><option value="0">Semua Jurusan</option><?php foreach($pd['jurusanList'] as $j): ?><option value="<?= (int)$j['id'] ?>" <?= $pd['jurusanId']===(int)$j['id']?'selected':'' ?>><?= sds_e($j['nama_jurusan']) ?></option><?php endforeach; ?></select></div>
                                        <div><label>Kelas</label><select name="kelas_id" data-autofilter="1"><option value="0">Semua Kelas</option><?php foreach($pd['kelasList'] as $k): ?><option value="<?= (int)$k['id'] ?>" <?= $pd['kelasId']===(int)$k['id']?'selected':'' ?>><?= sds_e($k['nama_kelas']) ?></option><?php endforeach; ?></select></div>
                                        <div><label>Cari Siswa</label><input type="text" name="q" value="<?= sds_e($pd['q']) ?>" placeholder="Nama / NISN / NIK / sekolah asal" data-autofilter-search="1"></div>
                                        <div class="filter-hint" style="display:none">Filter otomatis berjalan saat pilihan diubah atau pencarian diketik. Tidak perlu klik Terapkan.</div>
                                    </div>
                                </form>
                                <?php if (empty($pd['loadDetailRows'])): ?>
                                    <div class="wizard-note">Klik tab <b>Detail Siswa</b> untuk memuat daftar siswa. Ini dibuat terpisah agar tab Rekap Per Kelas tetap cepat.</div>
                                <?php endif; ?>
                                <div class="table-card">
                                <table class="sds-table">
                                    <thead>
                                        <tr>
                                            <th>Nama</th>
                                            <th>NISN</th>
                                            <th style="display:none">NIK</th>
                                            <th>Kelas</th>
                                            <th style="display:none">No HP</th>
                                            <th>Berkas</th>
                                            <th>Status</th>
                                            <th>Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                    <?php foreach($pd['detailRows'] as $row): ?>
                                        <tr>
                                            <td><b><?= sds_e($row['nama_lengkap']) ?></b><br><small><?= sds_e($row['sekolah_asal'] ?? '-') ?></small></td>
                                            <td><?= sds_e($row['nisn']) ?></td>
                                            <td style="display:none"><?= sds_e(sds_mask_private($row['nik'],6,10)) ?></td>
                                            <td><?= sds_e($row['nama_kelas'] ?? '-') ?><br><small><?= sds_e($row['nama_jurusan'] ?? '-') ?></small></td>
                                            <td style="display:none">Siswa: <?= sds_e(sds_mask_private($row['nohp_siswa'] ?? '',5,8)) ?><br>Ortu: <?= sds_e(sds_mask_private($row['nohp_ortu'] ?? '',5,8)) ?></td>
                                            <td><?= sds_badge(sds_filled($row['foto'] ?? ''),'Foto') ?> <?= sds_badge(sds_filled($row['file_kk'] ?? ''),'KK') ?> <?= sds_badge(sds_filled($row['file_ijazah'] ?? ''),'Ijazah') ?> <?= sds_badge(sds_filled($row['file_akta'] ?? ''),'Akta') ?></td>
                                            <td><?= !empty($row['is_lengkap']) ? '<span class="sds-badge ok">Lengkap</span>' : '<span class="sds-badge danger">' . (int)($row['progress_data_persen'] ?? 0) . '%</span>' ?></td>
                                            <td class="action-cell">
                                                <button type="button" class="detail-btn" data-target="detail-<?= (int)$row['id'] ?>">Lihat Data</button>
                                                <a class="detail-btn print-btn" href="<?= sds_e(sds_print_url((int)$row['id'], $row['nisn'] ?? '')) ?>" target="_blank" rel="noopener">Cetak Berkas</a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                    <?php if (!empty($pd['loadDetailRows']) && empty($pd['detailRows'])): ?>
                                        <tr><td colspan="8" style="text-align:center;color:#64748b;padding:18px">Tidak ada data siswa sesuai filter.</td></tr>
                                    <?php endif; ?>
                                    </tbody>
                                </table>
                                </div>
                            </div>

                            <?php foreach($pd['detailRows'] as $row): ?>
                                <div class="detail-modal" id="detail-<?= (int)$row['id'] ?>">
                                    <div class="detail-box">
                                        <div class="detail-head">
                                            <div>
                                                <h3><?= sds_e($row['nama_lengkap']) ?></h3>
                                                <small>NISN <?= sds_e($row['nisn'] ?: '-') ?> · <?= sds_e($row['nama_kelas'] ?: '-') ?> · <?= sds_e($row['nama_jurusan'] ?: '-') ?></small>
                                            </div>
                                            <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap">
                                                <?php if ($detailAccessUnlocked): ?><button type="button" class="detail-edit-toggle" data-target="edit-<?= (int)$row['id'] ?>">Edit Data</button><?php endif; ?>
                                                <button type="button" class="detail-close">Tutup</button>
                                            </div>
                                        </div>
                                        <div class="detail-body">
                                            <?php if (!$detailAccessUnlocked): ?>
                                                <div class="password-card" style="margin:0">
                                                    <?php if ($detailAccessError): ?>
                                                        <div style="background:#fee2e2;color:#991b1b;border-radius:12px;padding:10px 12px;margin-bottom:12px;font-weight:800"><?= sds_e($detailAccessError) ?></div>
                                                    <?php endif; ?>
                                                    <div class="password-title"><span class="password-icon">🔐</span><span>Akses Detail Data</span></div>
                                                    <p class="password-desc">Masukkan password akses untuk membuka data lengkap siswa. Data NIK dan No. HP pada tabel tetap disamarkan.</p>
                                                    <form method="post" class="password-access-form">
                                                        <input type="hidden" name="progress_csrf" value="<?= sds_e($progressCsrf) ?>">
                                                        <input type="hidden" name="redirect_to" value="<?= sds_e(sds_current_url()) ?>">
                                                        <div>
                                                            <label>Password Akses</label>
                                                            <input type="password" name="progress_detail_password" required placeholder="Masukkan password akses" autofocus>
                                                        </div>
                                                        <button type="submit">Buka Data</button>
                                                    </form>
                                                </div>
                                            <?php else: ?>
                                                <form method="post" enctype="multipart/form-data" class="detail-edit-form" id="edit-<?= (int)$row['id'] ?>">
                                                    <input type="hidden" name="progress_csrf" value="<?= sds_e($progressCsrf) ?>">
                                                    <input type="hidden" name="progress_edit_student" value="1">
                                                    <input type="hidden" name="siswa_id" value="<?= (int)$row['id'] ?>">
                                                    <input type="hidden" name="redirect_to" value="<?= sds_e(sds_current_url()) ?>">
                                                    <div class="detail-grid">
                                                        <div class="detail-item"><span class="detail-label">Nama Lengkap</span><span class="detail-view"><?= sds_e($row['nama_lengkap'] ?: '-') ?></span><span class="detail-edit"><input name="nama_lengkap" value="<?= sds_e($row['nama_lengkap'] ?? '') ?>" required></span></div>
                                                        <div class="detail-item"><span class="detail-label">Email</span><span class="detail-view"><?= sds_e($row['email'] ?: '-') ?></span><span class="detail-edit"><input name="email" type="email" value="<?= sds_e($row['email'] ?? '') ?>"></span></div>
                                                        <div class="detail-item"><span class="detail-label">NISN</span><span class="detail-view"><?= sds_e($row['nisn'] ?: '-') ?></span><span class="detail-edit"><input name="nisn" value="<?= sds_e($row['nisn'] ?? '') ?>"></span></div>
                                                        <div class="detail-item"><span class="detail-label">NIPD</span><span class="detail-view"><?= sds_e($row['nipd'] ?: '-') ?></span><span class="detail-edit"><input name="nipd" value="<?= sds_e($row['nipd'] ?? '') ?>" disabled title="NIPD hanya ditampilkan, tidak diubah dari progress."></span></div>
                                                        <div class="detail-item"><span class="detail-label">NIK</span><span class="detail-view"><?= sds_e($row['nik'] ?: '-') ?></span><span class="detail-edit"><input name="nik" value="<?= sds_e($row['nik'] ?? '') ?>"></span></div>
                                                        <div class="detail-item"><span class="detail-label">No KK</span><span class="detail-view"><?= sds_e($row['no_kk'] ?: '-') ?></span><span class="detail-edit"><input name="no_kk" value="<?= sds_e($row['no_kk'] ?? '') ?>"></span></div>
                                                        <div class="detail-item"><span class="detail-label">No Registrasi Akta</span><span class="detail-view"><?= sds_e($row['no_registrasi_akta'] ?: '-') ?></span><span class="detail-edit"><input name="no_registrasi_akta" value="<?= sds_e($row['no_registrasi_akta'] ?? '') ?>"></span></div>
                                                        <div class="detail-item"><span class="detail-label">Jenis Kelamin</span><span class="detail-view"><?= sds_e($row['jenis_kelamin'] ?: '-') ?></span><span class="detail-edit"><select name="jenis_kelamin"><option value="">Pilih</option><?php foreach(['Laki-laki','Perempuan'] as $opt): ?><option value="<?= $opt ?>" <?= (($row['jenis_kelamin'] ?? '')===$opt?'selected':'') ?>><?= $opt ?></option><?php endforeach; ?></select></span></div>
                                                        <div class="detail-item"><span class="detail-label">Tempat/Tanggal Lahir</span><span class="detail-view"><?= sds_e(($row['tempat_lahir'] ?: '-') . ', ' . ($row['tanggal_lahir'] ?: '-')) ?></span><span class="detail-edit two"><input name="tempat_lahir" value="<?= sds_e($row['tempat_lahir'] ?? '') ?>" placeholder="Tempat lahir"><input type="date" name="tanggal_lahir" value="<?= sds_e($row['tanggal_lahir'] ?? '') ?>"></span></div>
                                                        <div class="detail-item"><span class="detail-label">Agama</span><span class="detail-view"><?= sds_e($row['agama'] ?: '-') ?></span><span class="detail-edit"><input name="agama" value="<?= sds_e($row['agama'] ?? '') ?>"></span></div>
                                                        <div class="detail-item"><span class="detail-label">Jurusan</span><span class="detail-view"><?= sds_e($row['nama_jurusan'] ?: '-') ?></span><span class="detail-edit"><select name="edit_jurusan_id" class="detail-jurusan-select" required><option value="">Pilih Jurusan</option><?php foreach(($pd['jurusanList'] ?? []) as $j): ?><option value="<?= (int)$j['id'] ?>" <?= ((int)($row['jurusan_id'] ?? 0)===(int)$j['id']?'selected':'') ?>><?= sds_e($j['nama_jurusan']) ?></option><?php endforeach; ?></select></span></div>
                                                        <div class="detail-item"><span class="detail-label">Kelas Saat Ini</span><span class="detail-view"><?= sds_e($row['nama_kelas'] ?: '-') ?></span><span class="detail-edit"><select name="edit_kelas_id" class="detail-kelas-select" required><option value="">Pilih Kelas</option><?php foreach(($pd['allKelasList'] ?? []) as $k): ?><option value="<?= (int)$k['id'] ?>" data-jurusan-id="<?= (int)$k['jurusan_id'] ?>" <?= ((int)($row['kelas_id'] ?? 0)===(int)$k['id']?'selected':'') ?>><?= sds_e($k['nama_kelas']) ?><?= isset($k['kuota']) ? ' · Kuota ' . (int)$k['kuota'] : '' ?></option><?php endforeach; ?></select><small class="file-support-hint">Kelas otomatis dibatasi sesuai jurusan yang dipilih.</small></span></div>
                                                        <div class="detail-item"><span class="detail-label">Sekolah Asal</span><span class="detail-view"><?= sds_e($row['sekolah_asal'] ?: '-') ?></span><span class="detail-edit"><input name="sekolah_asal" value="<?= sds_e($row['sekolah_asal'] ?? '') ?>"></span></div>
                                                        <div class="detail-item"><span class="detail-label">No Ijazah SMP</span><span class="detail-view"><?= sds_e($row['nomor_ijazah'] ?: '-') ?></span><span class="detail-edit"><input name="nomor_ijazah" value="<?= sds_e($row['nomor_ijazah'] ?? '') ?>"></span></div>
                                                        <div class="detail-item"><span class="detail-label">Kebutuhan Khusus</span><span class="detail-view"><?= sds_e($row['kebutuhan_khusus'] ?: '-') ?></span><span class="detail-edit"><input name="kebutuhan_khusus" value="<?= sds_e($row['kebutuhan_khusus'] ?? '') ?>"></span></div>
                                                        <div class="detail-item"><span class="detail-label">Tempat Tinggal</span><span class="detail-view"><?= sds_e($row['tempat_tinggal'] ?: '-') ?></span><span class="detail-edit"><input name="tempat_tinggal" value="<?= sds_e($row['tempat_tinggal'] ?? '') ?>"></span></div>
                                                        <div class="detail-item"><span class="detail-label">Moda Transportasi</span><span class="detail-view"><?= sds_e($row['moda_transportasi'] ?: '-') ?></span><span class="detail-edit"><input name="moda_transportasi" value="<?= sds_e($row['moda_transportasi'] ?? '') ?>"></span></div>
                                                        <div class="detail-item"><span class="detail-label">Anak Ke / Saudara</span><span class="detail-view"><?= sds_e(($row['anak_ke'] ?: '-') . ' / ' . ($row['jumlah_saudara_kandung'] ?: '-')) ?></span><span class="detail-edit two"><input type="number" name="anak_ke" value="<?= sds_e($row['anak_ke'] ?? '') ?>" placeholder="Anak ke"><input type="number" name="jumlah_saudara_kandung" value="<?= sds_e($row['jumlah_saudara_kandung'] ?? '') ?>" placeholder="Jumlah saudara"></span></div>
                                                        <div class="detail-item"><span class="detail-label">Tinggi / Berat</span><span class="detail-view"><?= sds_e(($row['tinggi_badan'] ?: '-') . ' cm / ' . ($row['berat_badan'] ?: '-') . ' kg') ?></span><span class="detail-edit two"><input type="number" name="tinggi_badan" value="<?= sds_e($row['tinggi_badan'] ?? '') ?>" placeholder="Tinggi badan"><input type="number" name="berat_badan" value="<?= sds_e($row['berat_badan'] ?? '') ?>" placeholder="Berat badan"></span></div>
                                                        <div class="detail-item"><span class="detail-label">Hobi</span><span class="detail-view"><?= sds_e($row['hobi'] ?: '-') ?></span><span class="detail-edit"><input name="hobi" value="<?= sds_e($row['hobi'] ?? '') ?>"></span></div>
                                                        <div class="detail-item"><span class="detail-label">Cita-cita</span><span class="detail-view"><?= sds_e($row['cita_cita'] ?: '-') ?></span><span class="detail-edit"><input name="cita_cita" value="<?= sds_e($row['cita_cita'] ?? '') ?>"></span></div>
                                                        <div class="detail-item full"><span class="detail-label">Alamat</span><span class="detail-view"><?= sds_e(trim(($row['alamat'] ?: '-') . ' ' . ($row['desa'] ?: '') . ' ' . ($row['kecamatan'] ?: '') . ' ' . ($row['kota'] ?: '') . ' ' . ($row['provinsi'] ?: ''))) ?></span><span class="detail-edit"><textarea name="alamat" rows="2" placeholder="Alamat jalan/dusun"><?= sds_e($row['alamat'] ?? '') ?></textarea><div class="detail-edit three"><input name="desa" value="<?= sds_e($row['desa'] ?? '') ?>" placeholder="Desa/Kelurahan"><input name="kecamatan" value="<?= sds_e($row['kecamatan'] ?? '') ?>" placeholder="Kecamatan"><input name="kota" value="<?= sds_e($row['kota'] ?? '') ?>" placeholder="Kabupaten/Kota"></div><div class="detail-edit two"><input name="provinsi" value="<?= sds_e($row['provinsi'] ?? '') ?>" placeholder="Provinsi"><span></span></div></span></div>
                                                        <div class="detail-item full"><span class="detail-label">Koordinat</span><span class="detail-view"><?= sds_e(($row['latitude'] ?: '-') . ', ' . ($row['longitude'] ?: '-')) ?></span><span class="detail-edit two"><input name="latitude" value="<?= sds_e($row['latitude'] ?? '') ?>" placeholder="Latitude"><input name="longitude" value="<?= sds_e($row['longitude'] ?? '') ?>" placeholder="Longitude"></span></div>
                                                        <div class="detail-item"><span class="detail-label">Nama Ayah / NIK</span><span class="detail-view"><?= sds_e(($row['nama_ayah'] ?: '-') . ' / ' . ($row['nik_ayah'] ?: '-')) ?></span><span class="detail-edit two"><input name="nama_ayah" value="<?= sds_e($row['nama_ayah'] ?? '') ?>" placeholder="Nama Ayah"><input name="nik_ayah" value="<?= sds_e($row['nik_ayah'] ?? '') ?>" placeholder="NIK Ayah"></span></div>
                                                        <div class="detail-item"><span class="detail-label">Data Ayah</span><span class="detail-view"><?= sds_e(($row['tahun_lahir_ayah'] ?: '-') . ' · ' . ($row['pendidikan_ayah'] ?: '-') . ' · ' . ($row['pekerjaan_ayah'] ?: '-') . ' · ' . ($row['penghasilan_ayah'] ?: '-')) ?></span><span class="detail-edit two"><input type="number" name="tahun_lahir_ayah" value="<?= sds_e($row['tahun_lahir_ayah'] ?? '') ?>" placeholder="Tahun lahir"><input name="pendidikan_ayah" value="<?= sds_e($row['pendidikan_ayah'] ?? '') ?>" placeholder="Pendidikan"><input name="pekerjaan_ayah" value="<?= sds_e($row['pekerjaan_ayah'] ?? '') ?>" placeholder="Pekerjaan"><input name="penghasilan_ayah" value="<?= sds_e($row['penghasilan_ayah'] ?? '') ?>" placeholder="Penghasilan"></span></div>
                                                        <div class="detail-item"><span class="detail-label">Nama Ibu / NIK</span><span class="detail-view"><?= sds_e(($row['nama_ibu'] ?: '-') . ' / ' . ($row['nik_ibu'] ?: '-')) ?></span><span class="detail-edit two"><input name="nama_ibu" value="<?= sds_e($row['nama_ibu'] ?? '') ?>" placeholder="Nama Ibu"><input name="nik_ibu" value="<?= sds_e($row['nik_ibu'] ?? '') ?>" placeholder="NIK Ibu"></span></div>
                                                        <div class="detail-item"><span class="detail-label">Data Ibu</span><span class="detail-view"><?= sds_e(($row['tahun_lahir_ibu'] ?: '-') . ' · ' . ($row['pendidikan_ibu'] ?: '-') . ' · ' . ($row['pekerjaan_ibu'] ?: '-') . ' · ' . ($row['penghasilan_ibu'] ?: '-')) ?></span><span class="detail-edit two"><input type="number" name="tahun_lahir_ibu" value="<?= sds_e($row['tahun_lahir_ibu'] ?? '') ?>" placeholder="Tahun lahir"><input name="pendidikan_ibu" value="<?= sds_e($row['pendidikan_ibu'] ?? '') ?>" placeholder="Pendidikan"><input name="pekerjaan_ibu" value="<?= sds_e($row['pekerjaan_ibu'] ?? '') ?>" placeholder="Pekerjaan"><input name="penghasilan_ibu" value="<?= sds_e($row['penghasilan_ibu'] ?? '') ?>" placeholder="Penghasilan"></span></div>
                                                        <div class="detail-item"><span class="detail-label">Nama Wali / NIK</span><span class="detail-view"><?= sds_e(($row['nama_wali'] ?: '-') . ' / ' . ($row['nik_wali'] ?: '-')) ?></span><span class="detail-edit two"><input name="nama_wali" value="<?= sds_e($row['nama_wali'] ?? '') ?>" placeholder="Nama Wali"><input name="nik_wali" value="<?= sds_e($row['nik_wali'] ?? '') ?>" placeholder="NIK Wali"></span></div>
                                                        <div class="detail-item"><span class="detail-label">Data Wali</span><span class="detail-view"><?= sds_e(($row['tahun_lahir_wali'] ?: '-') . ' · ' . ($row['pendidikan_wali'] ?: '-') . ' · ' . ($row['pekerjaan_wali'] ?: '-') . ' · ' . ($row['penghasilan_wali'] ?: '-')) ?></span><span class="detail-edit two"><input type="number" name="tahun_lahir_wali" value="<?= sds_e($row['tahun_lahir_wali'] ?? '') ?>" placeholder="Tahun lahir"><input name="pendidikan_wali" value="<?= sds_e($row['pendidikan_wali'] ?? '') ?>" placeholder="Pendidikan"><input name="pekerjaan_wali" value="<?= sds_e($row['pekerjaan_wali'] ?? '') ?>" placeholder="Pekerjaan"><input name="penghasilan_wali" value="<?= sds_e($row['penghasilan_wali'] ?? '') ?>" placeholder="Penghasilan"></span></div>
                                                        <div class="detail-item"><span class="detail-label">No HP Siswa</span><span class="detail-view"><?= sds_e($row['nohp_siswa'] ?: '-') ?></span><span class="detail-edit"><input name="nohp_siswa" value="<?= sds_e($row['nohp_siswa'] ?? '') ?>"></span></div>
                                                        <div class="detail-item"><span class="detail-label">No HP Ortu/Wali</span><span class="detail-view"><?= sds_e($row['nohp_ortu'] ?: '-') ?></span><span class="detail-edit"><input name="nohp_ortu" value="<?= sds_e($row['nohp_ortu'] ?? '') ?>"></span></div>
                                                        <div class="detail-item"><span class="detail-label">KIP / KPS</span><span class="detail-view"><?= sds_e(($row['nomor_kip'] ?: '-') . ' / ' . ($row['nomor_kps'] ?: '-')) ?></span><span class="detail-edit two"><input name="nomor_kip" value="<?= sds_e($row['nomor_kip'] ?? '') ?>" placeholder="Nomor KIP"><input name="nomor_kps" value="<?= sds_e($row['nomor_kps'] ?? '') ?>" placeholder="Nomor KPS"></span></div>
                                                        <div class="detail-item"><span class="detail-label">PKH / KKS / KIS</span><span class="detail-view"><?= sds_e(($row['nomor_pkh'] ?: '-') . ' / ' . ($row['nomor_kks'] ?: '-') . ' / ' . ($row['nomor_kis'] ?: '-')) ?></span><span class="detail-edit three"><input name="nomor_pkh" value="<?= sds_e($row['nomor_pkh'] ?? '') ?>" placeholder="Nomor PKH"><input name="nomor_kks" value="<?= sds_e($row['nomor_kks'] ?? '') ?>" placeholder="Nomor KKS"><input name="nomor_kis" value="<?= sds_e($row['nomor_kis'] ?? '') ?>" placeholder="Nomor KIS"></span></div>
                                                        <div class="detail-item full"><span class="detail-label">Berkas Utama</span><span class="detail-view"><?= sds_file_link($row['foto'],'Foto') ?> <?= sds_file_link($row['file_kk'],'KK') ?> <?= sds_file_link($row['file_ijazah'],'Ijazah') ?> <?= sds_file_link($row['file_akta'],'Akta') ?></span><span class="detail-edit file-edit"><small>File lama tetap dipakai jika tidak memilih file baru.</small><input type="file" name="foto" accept=".jpg,.jpeg,.png,.pdf"><input type="file" name="file_kk" accept=".jpg,.jpeg,.png,.pdf"><input type="file" name="file_ijazah" accept=".jpg,.jpeg,.png,.pdf"><input type="file" name="file_akta" accept=".jpg,.jpeg,.png,.pdf"></span></div>
                                                        <div class="detail-item full"><span class="detail-label">Berkas Pendukung</span><span class="detail-view"><?= sds_file_link($row['file_kip'],'KIP') ?> <?= sds_file_link($row['file_kps'],'KPS') ?> <?= sds_file_link($row['file_pkh'],'PKH') ?> <?= sds_file_link($row['file_kks'],'KKS') ?> <?= sds_file_link($row['file_kis'],'KIS') ?></span><span class="detail-edit file-edit"><small>File lama tetap dipakai jika tidak memilih file baru.</small><input type="file" name="file_kip" accept=".jpg,.jpeg,.png,.pdf"><input type="file" name="file_kps" accept=".jpg,.jpeg,.png,.pdf"><input type="file" name="file_pkh" accept=".jpg,.jpeg,.png,.pdf"><input type="file" name="file_kks" accept=".jpg,.jpeg,.png,.pdf"><input type="file" name="file_kis" accept=".jpg,.jpeg,.png,.pdf"></span></div>
                                                        <div class="detail-item"><span class="detail-label">Pernyataan</span><span class="detail-view"><?= ((int)($row['pernyataan_setuju'] ?? 0) === 1) ? '<span class="sds-badge ok">Setuju</span>' : '<span class="sds-badge danger">Belum Setuju</span>' ?></span><span class="detail-edit edit-checks"><label><input type="checkbox" name="pernyataan_setuju" value="1" <?= ((int)($row['pernyataan_setuju'] ?? 0) === 1 ? 'checked' : '') ?>> Pernyataan Setuju</label></span></div>
                                                        <div class="detail-item"><span class="detail-label">Dapodik</span><span class="detail-view"><?= ((int)($row['sudah_dapodik'] ?? 0) === 1) ? '<span class="sds-badge ok">Sudah</span>' : '<span class="sds-badge info">Belum</span>' ?></span><span class="detail-edit edit-checks"><label><input type="checkbox" name="sudah_dapodik" value="1" <?= ((int)($row['sudah_dapodik'] ?? 0) === 1 ? 'checked' : '') ?>> Sudah Dapodik</label></span></div>
                                                        <div class="detail-item"><span class="detail-label">Tahun Formulir</span><span class="detail-view"><?= sds_e($row['tahun_ajaran'] ?: '-') ?></span><span class="detail-edit"><input value="<?= sds_e($row['tahun_ajaran'] ?? '') ?>" disabled></span></div>
                                                        <div class="detail-item"><span class="detail-label">Tanggal Input</span><span class="detail-view"><?= sds_e($row['tanggal_input'] ?: '-') ?></span><span class="detail-edit"><input value="<?= sds_e($row['tanggal_input'] ?? '') ?>" disabled></span></div>
                                                    </div>
                                                    <div class="edit-actions"><button type="button" class="cancel detail-edit-cancel">Batal Edit</button><button type="submit" class="save">Simpan Perubahan</button></div>
                                                </form>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>

                    <?php elseif ($formAktif !== '1'): ?>
                        <div class="info-card full"><h2>Formulir Telah Ditutup</h2><p>Silakan kembali lagi nanti saat pendataan dibuka.</p></div>

                    <?php else: ?>

            <form id="formSiswa" action="upload.php" method="POST" enctype="multipart/form-data" autocomplete="off">

                <!-- Tahun Ajaran -->
                <input type="hidden" name="tahun_ajaran" value="<?= htmlspecialchars($tahunAjaran) ?>">
                <!-- <p><center><strong>Tahun Ajaran: <?= htmlspecialchars($tahunAjaran) ?></strong></center></p> -->

<div class="wizard-card">
                    <div class="wizard-progress" aria-label="Tahapan Formulir">
                        <div class="wizard-step-indicator active" data-step-indicator="0" role="button" tabindex="0"><span class="step-number">1</span><span>Biodata</span></div>
                        <div class="wizard-step-indicator" data-step-indicator="1" role="button" tabindex="0"><span class="step-number">2</span><span>Orang Tua / Wali</span></div>
                        <div class="wizard-step-indicator" data-step-indicator="2" role="button" tabindex="0"><span class="step-number">3</span><span>Kesejahteraan</span></div>
                        <div class="wizard-step-indicator" data-step-indicator="3" role="button" tabindex="0"><span class="step-number">4</span><span>File Utama</span></div>
                        <div class="wizard-step-indicator" data-step-indicator="4" role="button" tabindex="0"><span class="step-number">5</span><span>File Pendukung</span></div>
                        <div class="wizard-step-indicator" data-step-indicator="5" role="button" tabindex="0"><span class="step-number">6</span><span>Pernyataan</span></div>
                    </div>

                    <div class="wizard-step active" data-step="0">
                        <h3>Biodata Peserta Didik</h3>
                        <p class="wizard-note">Isi data utama peserta didik dengan lengkap. Bagian alamat dan koordinat dibuat penuh agar mudah dipilih.</p>
                        <div class="wizard-grid">
                            <div id="field_nama_lengkap" class="form-group">
                                <label for="nama_lengkap">Nama Lengkap *</label>
                                <input type="text" id="nama_lengkap" name="nama_lengkap" />
                            </div>

                            <div id="field_email" class="form-group">
                                <label for="email">Email *</label>
                                <input type="text" id="email" name="email" />
                            </div>

                            <div id="field_nisn" class="form-group">
                                <label for="nisn">NISN *</label>
                                <input type="text" id="nisn" name="nisn" />
                            </div>

                            <div id="field_jurusan_id" class="form-group">
                                <label for="jurusan_id">Jurusan</label>
                                <select name="jurusan_id" id="jurusan_id">
                                    <option value="">-- Pilih Jurusan --</option>
                                    <?php
                                    // Ambil data jurusan hanya untuk tahun ajaran aktif.
                                    // Jangan memakai MAX(tahun_ajaran), karena jika tahun aktif belum diisi,
                                    // form akan mengambil jurusan dari tahun ajaran lama.
                                    $stmtJurusan = $conn->prepare("SELECT id, nama_jurusan FROM jurusan WHERE tahun_ajaran = ? ORDER BY nama_jurusan ASC");
                                    $stmtJurusan->bind_param("s", $tahunAjaran);
                                    $stmtJurusan->execute();
                                    $result = $stmtJurusan->get_result();

                                    if ($result && $result->num_rows > 0) {
                                        while ($row = $result->fetch_assoc()) {
                                            echo '<option value="' . $row['id'] . '">' . htmlspecialchars($row['nama_jurusan']) . '</option>';
                                        }
                                    } else {
                                        echo '<option value="" disabled>Belum ada jurusan untuk tahun ajaran ' . htmlspecialchars($tahunAjaran) . '</option>';
                                    }
                                    $stmtJurusan->close();
                                    ?>
                                </select>
                            </div>

                            <div id="field_sekolah_asal" class="form-group">
                                <label for="sekolah_asal">Sekolah Asal (SMP) *</label>
                                <input type="text" id="sekolah_asal" name="sekolah_asal" />
                            </div>

                            <div id="field_nomor_ijazah" class="form-group">
                                <label for="nomor_ijazah">No. Ijazah (SMP) *</label>
                                <input type="text" id="nomor_ijazah" name="nomor_ijazah" />
                            </div>

                            <div id="field_jenis_kelamin" class="form-group">
                                <label for="jenis_kelamin">Jenis Kelamin *</label>
                                <select id="jenis_kelamin" name="jenis_kelamin">
                                    <option value="">-- Pilih Jenis Kelamin --</option>
                                    <option value="Laki-laki">Laki-laki</option>
                                    <option value="Perempuan">Perempuan</option>
                                </select>
                            </div>

                            <div id="field_tempat_lahir" class="form-group">
                                <label for="tempat_lahir">Tempat Lahir *</label>
                                <input type="text" id="tempat_lahir" name="tempat_lahir" />
                            </div>

                            <div id="field_tanggal_lahir" class="form-group">
                                <label for="tanggal_lahir">Tanggal Lahir *</label>
                                <input type="date" id="tanggal_lahir" name="tanggal_lahir" />
                            </div>

                            <div id="field_no_kk" class="form-group">
                                <label for="no_kk">No KK *</label>
                                <input type="text" id="no_kk" name="no_kk" />
                            </div>

                            <div id="field_nik" class="form-group">
                                <label for="nik">No NIK *</label>
                                <input type="text" id="nik" name="nik" />
                            </div>

                            <div id="field_no_registrasi_akta" class="form-group">
                                <label for="no_registrasi_akta">No Registrasi Akta Lahir *</label>
                                <input type="text" id="no_registrasi_akta" name="no_registrasi_akta" />
                            </div>

                            <div id="field_kebutuhan_khusus" class="form-group">
                                <label for="kebutuhan_khusus">Kebutuhan Khusus (Kosongi jika tidak ada)</label>
                                <input type="text" id="kebutuhan_khusus" name="kebutuhan_khusus" />
                            </div>

                            <div id="field_agama" class="form-group">
                                <label for="agama">Agama *</label>
                                <select id="agama" name="agama">
                                    <?= getOptionsAgama(); ?>
                                </select>
                            </div>

                            <div id="field_provinsi" class="form-group">
                                <label for="provinsi">Provinsi *</label>
                                <select id="provinsi">
                                    <option value="">-- Pilih Provinsi --</option>
                                </select>
                                <input type="hidden" name="provinsi" id="provinsi_nama">
                            </div>

                            <div id="field_kota" class="form-group">
                                <label for="kota">Kabupaten/Kota *</label>
                                <select id="kota">
                                    <option value="">-- Pilih Kabupaten/Kota --</option>
                                </select>
                                <input type="hidden" name="kota" id="kota_nama">
                            </div>

                            <div id="field_kecamatan" class="form-group">
                                <label for="kecamatan">Kecamatan *</label>
                                <select id="kecamatan">
                                    <option value="">-- Pilih Kecamatan --</option>
                                </select>
                                <input type="hidden" name="kecamatan" id="kecamatan_nama">
                            </div>

                            <div id="field_desa" class="form-group">
                                <label for="desa">Desa *</label>
                                <select id="desa">
                                    <option value="">-- Pilih Desa --</option>
                                </select>
                                <input type="hidden" name="desa" id="desa_nama">
                            </div>

                            <div id="field_alamat" class="form-group wizard-full">
                                <label for="alamat">Alamat Rumah (Jalan/Dusun/RT/RW) *</label>
                                <textarea id="alamat" name="alamat"></textarea>
                            </div>

                            <div id="field_koordinat" class="form-group wizard-full map-field">
                                <label for="koordinat">Koordinat Rumah *</label>
                                <div class="koordinat-input-row">
                                    <input type="text" name="koordinat" id="koordinat" placeholder="Pilih lokasi pada peta dibawah untuk mendapat koordinat otomatis">
                                    <button type="button" id="btnLihatGoogleMaps" class="btn-google-maps" disabled>
                                        <span class="maps-icon">📍</span> Lihat Google Maps
                                    </button>
                                </div>
                                <input type="hidden" name="latitude" id="latitude">
                                <input type="hidden" name="longitude" id="longitude">
                                <div id="map" style="height:300px;margin-top:10px;"></div>
                            </div>

                            <div id="field_tempat_tinggal" class="form-group">
                                <label for="tempat_tinggal">Tempat Tinggal *</label>
                                <select id="tempat_tinggal" name="tempat_tinggal">
                                    <?= getOptionsTempatTinggal(); ?>
                                </select>
                            </div>

                            <div id="field_moda_transportasi" class="form-group">
                                <label for="moda_transportasi">Moda Transportasi *</label>
                                <select id="moda_transportasi" name="moda_transportasi">
                                    <?= getOptionsModaTransportasi(); ?>
                                </select>
                            </div>

                            <div id="field_anak_ke" class="form-group">
                                <label for="anak_ke">Anak ke-berapa *</label>
                                <input type="number" id="anak_ke" name="anak_ke" min="1" />
                            </div>

                            <div id="field_jumlah_saudara_kandung" class="form-group">
                                <label for="jumlah_saudara_kandung">Jumlah Saudara Kandung *</label>
                                <input type="number" id="jumlah_saudara_kandung" name="jumlah_saudara_kandung" min="0" />
                            </div>

                            <div id="field_tinggi_badan" class="form-group">
                                <label for="tinggi_badan">Tinggi Badan (cm) *</label>
                                <input type="number" id="tinggi_badan" name="tinggi_badan" min="0" />
                            </div>

                            <div id="field_berat_badan" class="form-group">
                                <label for="berat_badan">Berat Badan (kg) *</label>
                                <input type="number" id="berat_badan" name="berat_badan" min="0" />
                            </div>

                            <div id="field_hobi" class="form-group">
                                <label for="hobi">Hobi *</label>
                                <input type="text" id="hobi" name="hobi" />
                            </div>

                            <div id="field_cita_cita" class="form-group">
                                <label for="cita_cita">Cita-cita *</label>
                                <input type="text" id="cita_cita" name="cita_cita" />
                            </div>
                        </div>
                    </div>

                    <div class="wizard-step" data-step="1">
                        <h3>Data Orang Tua / Wali</h3>
                        <p class="wizard-note">Lengkapi data ayah, ibu, wali jika ada, serta nomor HP yang bisa dihubungi.</p>
                        <div class="wizard-grid">
                            <div id="field_nama_ayah" class="form-group">
                                <label for="nama_ayah">Nama Ayah Kandung *</label>
                                <input type="text" id="nama_ayah" name="nama_ayah" />
                            </div>

                            <div id="field_nik_ayah" class="form-group">
                                <label for="nik_ayah">NIK Ayah *</label>
                                <input type="text" id="nik_ayah" name="nik_ayah" />
                            </div>

                            <div id="field_tahun_lahir_ayah" class="form-group">
                                <label for="tahun_lahir_ayah">Tahun Lahir Ayah *</label>
                                <input type="number" id="tahun_lahir_ayah" name="tahun_lahir_ayah" min="1900" max="2100" />
                            </div>

                            <div id="field_pendidikan_ayah" class="form-group">
                                <label for="pendidikan_ayah">Pendidikan Ayah *</label>
                                <select id="pendidikan_ayah" name="pendidikan_ayah">
                                    <?= getOptionsPendidikan(); ?>
                                </select>
                            </div>

                            <div id="field_pekerjaan_ayah" class="form-group">
                                <label for="pekerjaan_ayah">Pekerjaan Ayah *</label>
                                <select id="pekerjaan_ayah" name="pekerjaan_ayah">
                                    <?= getOptionsPekerjaan(); ?>
                                </select>
                            </div>

                            <div id="field_penghasilan_ayah" class="form-group">
                                <label for="penghasilan_ayah">Penghasilan Ayah *</label>
                                <select id="penghasilan_ayah" name="penghasilan_ayah">
                                    <?= getOptionsPenghasilan(); ?>
                                </select>
                            </div>

                            <div id="field_nama_ibu" class="form-group">
                                <label for="nama_ibu">Nama Ibu Kandung *</label>
                                <input type="text" id="nama_ibu" name="nama_ibu" />
                            </div>

                            <div id="field_nik_ibu" class="form-group">
                                <label for="nik_ibu">NIK Ibu *</label>
                                <input type="text" id="nik_ibu" name="nik_ibu" />
                            </div>

                            <div id="field_tahun_lahir_ibu" class="form-group">
                                <label for="tahun_lahir_ibu">Tahun Lahir Ibu *</label>
                                <input type="number" id="tahun_lahir_ibu" name="tahun_lahir_ibu" min="1900" max="2100" />
                            </div>

                            <div id="field_pendidikan_ibu" class="form-group">
                                <label for="pendidikan_ibu">Pendidikan Ibu *</label>
                                <select id="pendidikan_ibu" name="pendidikan_ibu">
                                    <?= getOptionsPendidikan(); ?>
                                </select>
                            </div>

                            <div id="field_pekerjaan_ibu" class="form-group">
                                <label for="pekerjaan_ibu">Pekerjaan Ibu *</label>
                                <select id="pekerjaan_ibu" name="pekerjaan_ibu">
                                    <?= getOptionsPekerjaan(); ?>
                                </select>
                            </div>

                            <div id="field_penghasilan_ibu" class="form-group">
                                <label for="penghasilan_ibu">Penghasilan Ibu *</label>
                                <select id="penghasilan_ibu" name="penghasilan_ibu">
                                    <?= getOptionsPenghasilan(); ?>
                                </select>
                            </div>

                            <div id="field_nama_wali" class="form-group">
                                <label for="nama_wali">Nama Wali (jika ada)</label>
                                <input type="text" id="nama_wali" name="nama_wali" />
                            </div>

                            <div id="field_nik_wali" class="form-group">
                                <label for="nik_wali">NIK Wali (jika ada)</label>
                                <input type="text" id="nik_wali" name="nik_wali" />
                            </div>

                            <div id="field_tahun_lahir_wali" class="form-group">
                                <label for="tahun_lahir_wali">Tahun Lahir Wali (jika ada)</label>
                                <input type="number" id="tahun_lahir_wali" name="tahun_lahir_wali" min="1900" max="2100" />
                            </div>

                            <div id="field_pendidikan_wali" class="form-group">
                                <label for="pendidikan_wali">Pendidikan Wali (jika ada)</label>
                                <select id="pendidikan_wali" name="pendidikan_wali">
                                    <?= getOptionsPendidikan(); ?>
                                </select>
                            </div>

                            <div id="field_pekerjaan_wali" class="form-group">
                                <label for="pekerjaan_wali">Pekerjaan Wali (jika ada)</label>
                                <select id="pekerjaan_wali" name="pekerjaan_wali">
                                    <?= getOptionsPekerjaan(); ?>
                                </select>
                            </div>

                            <div id="field_penghasilan_wali" class="form-group">
                                <label for="penghasilan_wali">Penghasilan Wali (jika ada)</label>
                                <select id="penghasilan_wali" name="penghasilan_wali">
                                    <?= getOptionsPenghasilan(); ?>
                                </select>
                            </div>

                            <div id="field_nohp_ortu" class="form-group">
                                <label for="nohp_ortu">Nomor HP Orang Tua / Wali *</label>
                                <input type="text" class="form-control" id="nohp_ortu" name="nohp_ortu">
                            </div>

                            <div id="field_nohp_siswa" class="form-group">
                                <label for="nohp_siswa">Nomor HP Siswa *</label>
                                <input type="text" class="form-control" id="nohp_siswa" name="nohp_siswa">
                            </div>
                        </div>
                    </div>

                    <div class="wizard-step" data-step="2">
                        <h3>Kesejahteraan</h3>
                        <p class="wizard-note">Isi nomor bantuan sosial jika peserta didik memilikinya. Kosongi jika tidak ada.</p>
                        <div class="wizard-grid">
                            <div id="field_nomor_kip" class="form-group">
                                <label for="nomor_kip">Nomor KIP (jika ada)</label>
                                <input type="text" id="nomor_kip" name="nomor_kip" />
                            </div>

                            <div id="field_nomor_kps" class="form-group">
                                <label for="nomor_kps">Nomor KPS (jika ada)</label>
                                <input type="text" id="nomor_kps" name="nomor_kps" />
                            </div>

                            <div id="field_nomor_pkh" class="form-group">
                                <label for="nomor_pkh">Nomor PKH (jika ada)</label>
                                <input type="text" id="nomor_pkh" name="nomor_pkh" />
                            </div>

                            <div id="field_nomor_kks" class="form-group">
                                <label for="nomor_kks">Nomor KKS (jika ada)</label>
                                <input type="text" id="nomor_kks" name="nomor_kks" />
                            </div>

                            <div id="field_nomor_kis" class="form-group">
                                <label for="nomor_kis">Nomor KIS (jika ada)</label>
                                <input type="text" id="nomor_kis" name="nomor_kis" />
                            </div>
                        </div>
                    </div>

                    <div class="wizard-step" data-step="3">
                        <h3>File Utama</h3>
                        <p class="wizard-note">Upload file utama yang wajib dilampirkan. Format JPG/PNG/PDF maksimal 10MB.</p>
                        <div class="wizard-grid file-main-grid">
                            <div id="field_foto" class="form-group">
                                <label for="foto"><b>Foto Diri</b> (JPG/PDF max 10MB) *</label>
                                <input type="file" id="foto" name="foto" accept=".jpg,.jpeg,.png,.pdf" />
                            </div>

                            <div id="field_file_kk" class="form-group">
                                <label for="file_kk">Foto/Scan <b>Kartu Keluarga</b> (JPG/PDF max 10MB) *</label>
                                <input type="file" id="file_kk" name="file_kk" accept=".jpg,.jpeg,.png,.pdf" />
                            </div>

                            <div id="field_file_ijazah" class="form-group">
                                <label for="file_ijazah">Foto/Scan <b>Ijazah SMP</b> (JPG/PDF max 10MB) *</label>
                                <input type="file" id="file_ijazah" name="file_ijazah" accept=".jpg,.jpeg,.png,.pdf" />
                            </div>

                            <div id="field_file_akta" class="form-group">
                                <label for="file_akta">Foto/Scan <b>Akta Kelahiran</b> (JPG/PDF max 10MB) *</label>
                                <input type="file" id="file_akta" name="file_akta" accept=".jpg,.jpeg,.png,.pdf" />
                            </div>
                        </div>
                    </div>

                    <div class="wizard-step" data-step="4">
                        <h3>File Pendukung (Jika Ada)</h3>
                        <p class="wizard-note">Upload dokumen pendukung akan tampil otomatis sesuai nomor bantuan yang diisi pada step Kesejahteraan.</p>
                        <div class="wizard-grid">
                            <div id="file_pendukung_empty" class="file-support-empty">Isi nomor KIP/KPS/PKH/KKS/KIS pada step <b>Kesejahteraan</b> agar upload file pendukung yang sesuai muncul di sini.</div>

                            <div id="field_file_kip" class="form-group support-file-field" data-related-number="nomor_kip">
                                <label for="file_kip">Foto/Scan <b>KIP</b> (JPG/PDF max 10MB)</label>
                                <input type="file" id="file_kip" name="file_kip" accept=".jpg,.jpeg,.png,.pdf" />
                                <small class="file-support-hint">Muncul jika Nomor KIP diisi pada step Kesejahteraan.</small>
                            </div>

                            <div id="field_file_kps" class="form-group support-file-field" data-related-number="nomor_kps">
                                <label for="file_kps">Foto/Scan <b>KPS</b> (JPG/PDF max 10MB)</label>
                                <input type="file" id="file_kps" name="file_kps" accept=".jpg,.jpeg,.png,.pdf" />
                                <small class="file-support-hint">Muncul jika Nomor KPS diisi pada step Kesejahteraan.</small>
                            </div>

                            <div id="field_file_pkh" class="form-group support-file-field" data-related-number="nomor_pkh">
                                <label for="file_pkh">Foto/Scan <b>PKH</b> (JPG/PDF max 10MB)</label>
                                <input type="file" id="file_pkh" name="file_pkh" accept=".jpg,.jpeg,.png,.pdf" />
                                <small class="file-support-hint">Muncul jika Nomor PKH diisi pada step Kesejahteraan.</small>
                            </div>

                            <div id="field_file_kks" class="form-group support-file-field" data-related-number="nomor_kks">
                                <label for="file_kks">Foto/Scan <b>KKS</b> (JPG/PDF max 10MB)</label>
                                <input type="file" id="file_kks" name="file_kks" accept=".jpg,.jpeg,.png,.pdf" />
                                <small class="file-support-hint">Muncul jika Nomor KKS diisi pada step Kesejahteraan.</small>
                            </div>

                            <div id="field_file_kis" class="form-group support-file-field" data-related-number="nomor_kis">
                                <label for="file_kis">Foto/Scan <b>KIS</b> (JPG/PDF max 10MB)</label>
                                <input type="file" id="file_kis" name="file_kis" accept=".jpg,.jpeg,.png,.pdf" />
                                <small class="file-support-hint">Muncul jika Nomor KIS diisi pada step Kesejahteraan.</small>
                            </div>
                        </div>
                    </div>

                    <div class="wizard-step" data-step="5">
                        <h3>Pernyataan & Kirim Data</h3>
                        <div class="wizard-grid">
                            <div id="field_pernyataan_setuju" class="declaration-box">
                                <label for="pernyataan_setuju">
                                    <input type="checkbox" id="pernyataan_setuju" name="persetujuan" />
                                    <span>Saya menyatakan data yang saya isi adalah benar dan bersedia mengikuti aturan sekolah.</span>
                                </label>
                            </div>
                        </div>
                    </div>

                    <div class="wizard-actions">
                        <button type="button" class="btn-secondary-wizard" id="prevStepBtn">Sebelumnya</button>
                        <button type="button" id="nextStepBtn">Selanjutnya</button>
                        <!-- <button type="submit">Kirim</button> -->
                        <button type="button" id="openModalBtn" style="display:none;">Kirim Data</button>
                    </div>
                </div>
            </form>
        </div>

        <!-- Modal NISN Ganda -->
        <div class="modal fade" id="modalNISN" tabindex="-1" aria-labelledby="modalNISNLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content border-danger">
                    <div class="modal-header bg-danger text-white">
                        <h5 class="modal-title" id="modalNISNLabel">Pendaftaran Gagal</h5>
                        <!--<button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>-->
                    </div>
                    <div class="modal-body">
                        NISN yang kamu masukkan <strong>Sudah Terdaftar</strong> Silakan gunakan NISN lain.
                    </div>
                    <div class="modal-footer">
                        <!--<button type="button" class="btn btn-danger" data-bs-dismiss="modal">Tutup</button>-->
                        <button onclick="window.location.href='formulir'" class="btn btn-danger">Tutup</button>
                    </div>
                </div>
            </div>
        </div>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const urlParams = new URLSearchParams(window.location.search);
                if (urlParams.get('error') === 'nisn_terdaftar') {
                    var myModal = new bootstrap.Modal(document.getElementById('modalNISN'));
                    myModal.show();
                }
            });
        </script>

        <!-- Modal Kelas Penuuh -->
        <div class="modal fade" id="modalKelas" tabindex="-1" aria-labelledby="modalKelasLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content border-danger">
                    <div class="modal-header bg-danger text-white">
                        <h5 class="modal-title" id="modalKelasLabel">Pendaftaran Gagal</h5>
                        <!--<button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>-->
                    </div>
                    <div class="modal-body">
                        Kelas pada jurusan ini yang kamu pilih sudah penuh. Silakan pilih jurusan lain atau tunggu pembukaan kelas baru.
                    </div>
                    <div class="modal-footer">
                        <!--<button type="button" class="btn btn-danger" data-bs-dismiss="modal">Tutup</button>-->
                        <button onclick="window.location.href='formulir'" class="btn btn-danger">Tutup</button>
                    </div>
                </div>
            </div>
        </div>

        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const urlParams = new URLSearchParams(window.location.search);
                if (urlParams.get('error') === 'kelas_penuh') {
                    var myModal = new bootstrap.Modal(document.getElementById('modalKelas'));
                    myModal.show();
                }
            });
        </script>


        <div id="confirmModal" class="modal">
            <div class="modal-content">
                <p>Apakah kamu yakin data yang kamu masukkan sudah benar?</p>
                <div class="modal-buttons">
                    <button class="btn-confirm" id="btnSubmit">Kirim Data</button>
                    <button class="btn-cancel" id="btnCancel">Batal</button>
                </div>
            </div>
        </div>


        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const form = document.getElementById('formSiswa');
                const steps = Array.from(document.querySelectorAll('.wizard-step'));
                const indicators = Array.from(document.querySelectorAll('.wizard-step-indicator'));
                const prevBtn = document.getElementById('prevStepBtn');
                const nextBtn = document.getElementById('nextStepBtn');
                const submitBtn = document.getElementById('openModalBtn');
                let currentStep = 0;

                function wrapLooseFields() {
                    document.querySelectorAll('.wizard-grid').forEach(grid => {
                        const children = Array.from(grid.childNodes);
                        let i = 0;

                        while (i < children.length) {
                            const node = children[i];

                            if (node.nodeType !== 1 || node.classList.contains('wizard-field') || node.classList.contains('form-group') || node.classList.contains('mb-3') || node.classList.contains('declaration-box')) {
                                i++;
                                continue;
                            }

                            if (node.tagName === 'LABEL') {
                                const field = document.createElement('div');
                                field.className = 'wizard-field';
                                if ((node.textContent || '').toLowerCase().includes('alamat') || (node.textContent || '').toLowerCase().includes('koordinat')) {
                                    field.classList.add('full');
                                }

                                grid.insertBefore(field, node);
                                field.appendChild(node);

                                let next = field.nextSibling;
                                while (next) {
                                    const nextOriginal = next;
                                    const isElement = next.nodeType === 1;
                                    const tag = isElement ? next.tagName : '';
                                    const isNextLabel = isElement && tag === 'LABEL';
                                    const isNextHeading = isElement && /^H[1-6]$/.test(tag);
                                    const isNextWizardBlock = isElement && (next.classList.contains('wizard-field') || next.classList.contains('form-group') || next.classList.contains('mb-3') || next.classList.contains('declaration-box'));

                                    if (isNextLabel || isNextHeading || isNextWizardBlock) break;

                                    next = next.nextSibling;
                                    field.appendChild(nextOriginal);

                                    if (isElement && nextOriginal.id === 'map') {
                                        field.classList.add('full', 'map-field');
                                        break;
                                    }
                                }
                            }
                            i++;
                        }
                    });
                }

                function refreshMap() {
                    if (typeof map !== 'undefined' && map) {
                        setTimeout(() => map.invalidateSize(), 250);
                    }
                }

                function setStep(index) {
                    currentStep = index;
                    steps.forEach((step, i) => step.classList.toggle('active', i === currentStep));
                    indicators.forEach((indicator, i) => {
                        indicator.classList.toggle('active', i === currentStep);
                        indicator.classList.toggle('done', i < currentStep);
                        indicator.setAttribute('aria-current', i === currentStep ? 'step' : 'false');
                    });

                    prevBtn.style.visibility = currentStep === 0 ? 'hidden' : 'visible';
                    nextBtn.style.display = currentStep === steps.length - 1 ? 'none' : 'inline-block';
                    submitBtn.style.display = currentStep === steps.length - 1 ? 'inline-block' : 'none';
                    refreshMap();
                    window.scrollTo({ top: 0, behavior: 'smooth' });
                }

                function isFieldHiddenByAdmin(field) {
                    const wrapper = field.closest('[id^="field_"]');
                    return wrapper && window.getComputedStyle(wrapper).display === 'none';
                }

                function validateStep(stepIndex, focusInvalid = true) {
                    const fields = Array.from(steps[stepIndex].querySelectorAll('input, select, textarea'));
                    for (const field of fields) {
                        if (field.disabled || isFieldHiddenByAdmin(field)) continue;
                        if (!field.checkValidity()) {
                            if (focusInvalid) {
                                setStep(stepIndex);
                                setTimeout(() => {
                                    field.reportValidity();
                                    field.focus();
                                }, 120);
                            }
                            return false;
                        }
                    }
                    return true;
                }

                function validateStepsUntil(targetStep) {
                    const start = Math.min(currentStep, targetStep);
                    const end = Math.max(currentStep, targetStep);
                    for (let i = start; i <= end; i++) {
                        if (!validateStep(i, true)) return false;
                    }
                    return true;
                }

                function validateAllSteps() {
                    for (let i = 0; i < steps.length; i++) {
                        if (!validateStep(i, true)) return false;
                    }
                    return true;
                }

                window.formWizardValidateAllSteps = validateAllSteps;
                wrapLooseFields();
                setStep(0);

                nextBtn.addEventListener('click', function() {
                    if (!validateStep(currentStep, true)) return;
                    if (currentStep < steps.length - 1) setStep(currentStep + 1);
                });

                prevBtn.addEventListener('click', function() {
                    if (currentStep > 0) setStep(currentStep - 1);
                });

                indicators.forEach((indicator, targetStep) => {
                    function goToClickedStep() {
                        if (targetStep === currentStep) return;
                        if (targetStep < currentStep) {
                            setStep(targetStep);
                            return;
                        }
                        if (validateStepsUntil(targetStep - 1)) {
                            setStep(targetStep);
                        }
                    }

                    indicator.addEventListener('click', goToClickedStep);
                    indicator.addEventListener('keydown', function(e) {
                        if (e.key === 'Enter' || e.key === ' ') {
                            e.preventDefault();
                            goToClickedStep();
                        }
                    });
                });
            });
        </script>
        <script>
            const modal = document.getElementById('confirmModal');
            const openBtn = document.getElementById('openModalBtn');
            const btnCancel = document.getElementById('btnCancel');
            const btnSubmit = document.getElementById('btnSubmit');
            const form = document.getElementById('formSiswa');

            // klik "Kirim Data" pada step terakhir
            openBtn.addEventListener('click', () => {
                if (typeof window.formWizardValidateAllSteps === 'function' && !window.formWizardValidateAllSteps()) {
                    return;
                }

                modal.style.display = 'block';
            });

            // batal
            btnCancel.addEventListener('click', () => modal.style.display = 'none');

            // konfirmasi → submit form
            btnSubmit.addEventListener('click', () => {
                modal.style.display = 'none';
                form.submit();
            });

            // klik di luar konten modal
            window.addEventListener('click', e => {
                if (e.target === modal) modal.style.display = 'none';
            });
        </script>
        <script>
            // Menangani pengisian form
            document.getElementById('formSiswa').addEventListener('submit', function(event) {
                event.preventDefault(); // Mencegah pengiriman form default
                // Validasi dan kirim data ke server
                this.submit();
            });
        </script>


        <!-- Modal Notifikasi -->
        <div id="successModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5);">
            <div style="background:#fff; padding:20px; width:300px; margin:100px auto; border-radius:8px; text-align:center;">
                <h2>Berhasil!</h2>
                <p>Data berhasil disimpan.</p>
                <button onclick="window.location.href='index'">Tutup</button>
            </div>
        </div>
        <?php
        if (isset($_GET['status']) && $_GET['status'] === 'success') {
            echo "<script>window.onload = function() { showSuccessModal(); };</script>";
        }
        ?>

        <script>
            function showSuccessModal() {
                document.getElementById('successModal').style.display = 'block';
            }
        </script>

        <script>
            // Leaflet map
            var defaultLatLng = [-7.781571, 113.212075];
            var map = L.map('map').setView(defaultLatLng, 15);
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '&copy; OpenStreetMap Affan Contributor'
            }).addTo(map);
            var marker;
            var geocodeTimer = null;

            function setKoordinatMarker(lat, lng, zoom) {
                lat = parseFloat(lat);
                lng = parseFloat(lng);
                if (Number.isNaN(lat) || Number.isNaN(lng)) return;

                var latLng = [lat, lng];
                map.setView(latLng, zoom || 15);

                if (marker) {
                    marker.setLatLng(latLng);
                } else {
                    marker = L.marker(latLng).addTo(map);
                }

                document.getElementById('koordinat').value = lat.toFixed(6) + ', ' + lng.toFixed(6);
                document.getElementById('latitude').value = lat.toFixed(6);
                document.getElementById('longitude').value = lng.toFixed(6);
                updateGoogleMapsButton();
            }

            function getKoordinatValue() {
                var koordinatInput = document.getElementById('koordinat');
                var latInput = document.getElementById('latitude');
                var lngInput = document.getElementById('longitude');
                var lat = latInput ? (latInput.value || '').trim() : '';
                var lng = lngInput ? (lngInput.value || '').trim() : '';

                if ((!lat || !lng) && koordinatInput && koordinatInput.value) {
                    var match = koordinatInput.value.match(/(-?\d+(?:\.\d+)?)\s*,\s*(-?\d+(?:\.\d+)?)/);
                    if (match) {
                        lat = match[1];
                        lng = match[2];
                    }
                }

                lat = parseFloat(lat);
                lng = parseFloat(lng);

                if (Number.isNaN(lat) || Number.isNaN(lng)) return null;
                return { lat: lat.toFixed(6), lng: lng.toFixed(6) };
            }

            function updateGoogleMapsButton() {
                var btn = document.getElementById('btnLihatGoogleMaps');
                if (!btn) return;

                var koordinat = getKoordinatValue();
                btn.disabled = !koordinat;
                btn.title = koordinat ? 'Buka titik koordinat di Google Maps' : 'Isi atau pilih Koordinat Rumah terlebih dahulu';
            }

            function getSelectedText(selectId) {
                var select = document.getElementById(selectId);
                if (!select || !select.value || select.selectedIndex < 0) return '';

                var text = select.options[select.selectedIndex].text || '';
                if (text.indexOf('--') === 0) return '';
                return text.trim();
            }

            function normalisasiNamaWilayah(text) {
                text = (text || '').trim();
                text = text.replace(/^PROVINSI\s+/i, '');
                text = text.replace(/^KABUPATEN\s+/i, '');
                text = text.replace(/^KAB\.\s*/i, '');
                text = text.replace(/^KOTA\s+/i, '');
                text = text.replace(/^KECAMATAN\s+/i, '');
                text = text.replace(/^KEC\.\s*/i, '');
                text = text.replace(/^DESA\s+/i, '');
                text = text.replace(/^KELURAHAN\s+/i, '');
                return text.trim();
            }

            function fetchNominatim(query) {
                var url = 'https://nominatim.openstreetmap.org/search?format=json&addressdetails=1&limit=1&countrycodes=id&q=' + encodeURIComponent(query);
                return fetch(url, {
                    headers: {
                        'Accept': 'application/json'
                    }
                })
                    .then(function(res) { return res.json(); })
                    .then(function(data) {
                        if (Array.isArray(data) && data.length > 0) return data[0];
                        return null;
                    });
            }

            function geocodeAlamatTerpilih(level) {
                clearTimeout(geocodeTimer);

                geocodeTimer = setTimeout(function() {
                    var desa = normalisasiNamaWilayah(getSelectedText('desa'));
                    var kecamatan = normalisasiNamaWilayah(getSelectedText('kecamatan'));
                    var kota = normalisasiNamaWilayah(getSelectedText('kota'));
                    var provinsi = normalisasiNamaWilayah(getSelectedText('provinsi'));

                    if (!provinsi) return;

                    var queries = [];

                    // Query dibuat beberapa versi karena data Nominatim kadang tidak menemukan nama desa jika terlalu lengkap.
                    if (desa && kecamatan && kota && provinsi) queries.push([desa, kecamatan, kota, provinsi, 'Indonesia'].join(', '));
                    if (desa && kota && provinsi) queries.push([desa, kota, provinsi, 'Indonesia'].join(', '));
                    if (desa && kecamatan && provinsi) queries.push([desa, kecamatan, provinsi, 'Indonesia'].join(', '));
                    if (kecamatan && kota && provinsi) queries.push([kecamatan, kota, provinsi, 'Indonesia'].join(', '));
                    if (kota && provinsi) queries.push([kota, provinsi, 'Indonesia'].join(', '));
                    queries.push([provinsi, 'Indonesia'].join(', '));

                    // Hilangkan query dobel.
                    queries = queries.filter(function(item, index) {
                        return item && queries.indexOf(item) === index;
                    });

                    var zoom = 11;
                    if (level === 'provinsi') zoom = 8;
                    if (level === 'kota') zoom = 11;
                    if (level === 'kecamatan') zoom = 13;
                    if (level === 'desa') zoom = 16;

                    function cobaQuery(index) {
                        if (index >= queries.length) {
                            console.warn('Koordinat wilayah tidak ditemukan untuk pilihan:', { desa, kecamatan, kota, provinsi });
                            return;
                        }

                        fetchNominatim(queries[index])
                            .then(function(result) {
                                if (result && result.lat && result.lon) {
                                    setKoordinatMarker(result.lat, result.lon, zoom);
                                    return;
                                }
                                cobaQuery(index + 1);
                            })
                            .catch(function(error) {
                                console.warn('Gagal mencari koordinat alamat:', error);
                                cobaQuery(index + 1);
                            });
                    }

                    cobaQuery(0);
                }, 350);
            }

            map.on('click', function(e) {
                var lat = e.latlng.lat.toFixed(6),
                    lng = e.latlng.lng.toFixed(6);
                setKoordinatMarker(lat, lng, 16);
            });

            // Deteksi lokasi saat ini
            if (navigator.geolocation) {
                navigator.geolocation.getCurrentPosition(function(position) {
                    var lat = position.coords.latitude.toFixed(6);
                    var lng = position.coords.longitude.toFixed(6);
                    var currentLatLng = [lat, lng];
                    setKoordinatMarker(lat, lng, 16);
                }, function(error) {
                    console.warn("Gagal mendapatkan lokasi: ", error.message);
                });
            }

            var koordinatInput = document.getElementById('koordinat');
            if (koordinatInput) {
                koordinatInput.addEventListener('input', updateGoogleMapsButton);
                koordinatInput.addEventListener('change', updateGoogleMapsButton);
            }

            var btnLihatGoogleMaps = document.getElementById('btnLihatGoogleMaps');
            if (btnLihatGoogleMaps) {
                btnLihatGoogleMaps.addEventListener('click', function() {
                    var koordinat = getKoordinatValue();
                    if (!koordinat) {
                        alert('Koordinat Rumah belum tersedia. Silakan pilih lokasi pada peta atau pilih wilayah sampai Desa terlebih dahulu.');
                        return;
                    }

                    var url = 'https://www.google.com/maps?q=' + encodeURIComponent(koordinat.lat + ',' + koordinat.lng);
                    window.open(url, '_blank', 'noopener');
                });
            }

            updateGoogleMapsButton();
        </script>
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                // pageshow akan dipicu setiap kali halaman ditampilkan,
                // termasuk ketika datang dari tombol Back / Forward.
                window.addEventListener('pageshow', (e) => {
                    if (e.persisted) { // true ⇒ berasal dari bfcache
                        const form = document.getElementById('formSiswa');
                        if (form) form.reset(); // kosongkan seluruh field
                    }
                });
            });
        </script>
        <script>
            fetch('https://www.emsifa.com/api-wilayah-indonesia/api/provinces.json')
                .then(res => res.json())
                .then(data => {
                    let select = document.getElementById('provinsi');
                    data.forEach(item => {
                        let option = document.createElement('option');
                        option.value = item.id;
                        option.text = item.name;
                        select.add(option);
                    });
                });

            document.getElementById('provinsi').addEventListener('change', function() {
                let provId = this.value;
                let provName = this.options[this.selectedIndex].text;
                document.getElementById('provinsi_nama').value = provName;
                geocodeAlamatTerpilih('provinsi');

                fetch(`https://www.emsifa.com/api-wilayah-indonesia/api/regencies/${provId}.json`)
                    .then(res => res.json())
                    .then(data => {
                        let kabupaten = document.getElementById('kota');
                        kabupaten.innerHTML = '<option value="">-- Pilih Kabupaten --</option>';
                        data.forEach(item => {
                            let option = document.createElement('option');
                            option.value = item.id;
                            option.text = item.name;
                            kabupaten.add(option);
                        });

                        document.getElementById('kota_nama').value = '';
                        document.getElementById('kecamatan').innerHTML = '<option value="">-- Pilih Kecamatan --</option>';
                        document.getElementById('desa').innerHTML = '<option value="">-- Pilih Desa --</option>';
                        document.getElementById('kecamatan_nama').value = '';
                        document.getElementById('desa_nama').value = '';
                    });
            });

            document.getElementById('kota').addEventListener('change', function() {
                let kabId = this.value;
                let kabName = this.options[this.selectedIndex].text;
                document.getElementById('kota_nama').value = kabName;
                geocodeAlamatTerpilih('kota');

                fetch(`https://www.emsifa.com/api-wilayah-indonesia/api/districts/${kabId}.json`)
                    .then(res => res.json())
                    .then(data => {
                        let kecamatan = document.getElementById('kecamatan');
                        kecamatan.innerHTML = '<option value="">-- Pilih Kecamatan --</option>';
                        data.forEach(item => {
                            let option = document.createElement('option');
                            option.value = item.id;
                            option.text = item.name;
                            kecamatan.add(option);
                        });

                        document.getElementById('kecamatan_nama').value = '';
                        document.getElementById('desa').innerHTML = '<option value="">-- Pilih Desa --</option>';
                        document.getElementById('desa_nama').value = '';
                    });
            });

            document.getElementById('kecamatan').addEventListener('change', function() {
                let kecId = this.value;
                let kecName = this.options[this.selectedIndex].text;
                document.getElementById('kecamatan_nama').value = kecName;
                geocodeAlamatTerpilih('kecamatan');

                fetch(`https://www.emsifa.com/api-wilayah-indonesia/api/villages/${kecId}.json`)
                    .then(res => res.json())
                    .then(data => {
                        let desa = document.getElementById('desa');
                        desa.innerHTML = '<option value="">-- Pilih Desa --</option>';
                        data.forEach(item => {
                            let option = document.createElement('option');
                            option.value = item.id;
                            option.text = item.name;
                            desa.add(option);
                        });

                        document.getElementById('desa_nama').value = '';
                    });
            });

            document.getElementById('desa').addEventListener('change', function() {
                let desaName = this.options[this.selectedIndex].text;
                document.getElementById('desa_nama').value = desaName;
                geocodeAlamatTerpilih('desa');
            });
        </script>

        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const supportMap = [
                    { number: 'nomor_kip', fileWrapper: 'field_file_kip' },
                    { number: 'nomor_kps', fileWrapper: 'field_file_kps' },
                    { number: 'nomor_pkh', fileWrapper: 'field_file_pkh' },
                    { number: 'nomor_kks', fileWrapper: 'field_file_kks' },
                    { number: 'nomor_kis', fileWrapper: 'field_file_kis' }
                ];

                function hasValue(input) {
                    return input && input.value && input.value.trim() !== '';
                }

                function applySupportRequired(wrapper, show) {
                    const fields = Array.from(wrapper.querySelectorAll('input, select, textarea'))
                        .filter(field => field.type !== 'hidden');
                    const adminStatus = wrapper.dataset.adminStatus || 'optional';
                    const requiredByAdmin = adminStatus === 'required';

                    fields.forEach(field => {
                        field.required = show && requiredByAdmin;
                        field.dataset.fieldRequired = show && requiredByAdmin ? '1' : '0';
                        if (!show && field.type === 'file') {
                            field.value = '';
                        }
                    });
                }

                function updateFilePendukungByKesejahteraan() {
                    let visibleCount = 0;

                    supportMap.forEach(item => {
                        const numberInput = document.getElementById(item.number);
                        const wrapper = document.getElementById(item.fileWrapper);
                        if (!wrapper) return;

                        const adminHidden = wrapper.dataset.adminStatus === 'hidden';
                        const show = !adminHidden && hasValue(numberInput);

                        wrapper.style.display = show ? '' : 'none';
                        applySupportRequired(wrapper, show);

                        if (show) visibleCount++;
                    });

                    const emptyBox = document.getElementById('file_pendukung_empty');
                    if (emptyBox) emptyBox.style.display = visibleCount === 0 ? 'block' : 'none';
                }

                window.updateFilePendukungByKesejahteraan = updateFilePendukungByKesejahteraan;

                supportMap.forEach(item => {
                    const numberInput = document.getElementById(item.number);
                    if (numberInput) {
                        numberInput.addEventListener('input', updateFilePendukungByKesejahteraan);
                        numberInput.addEventListener('change', updateFilePendukungByKesejahteraan);
                    }
                });

                updateFilePendukungByKesejahteraan();
            });
        </script>

        <script>
            document.addEventListener("DOMContentLoaded", function() {
                fetch('ambil_field_aktif.php')
                    .then(response => response.json())
                    .then(data => {
                        function setRequiredMark(wrapper, isRequired) {
                            const labels = Array.from(wrapper.querySelectorAll('label'));

                            labels.forEach(label => {
                                // Hapus tanda bintang lama, baik yang sudah berbentuk span maupun teks biasa.
                                label.querySelectorAll('.required-star').forEach(star => star.remove());

                                Array.from(label.childNodes).forEach(node => {
                                    if (node.nodeType === Node.TEXT_NODE) {
                                        node.textContent = node.textContent.replace(/\s*\*\s*$/g, '').trimEnd();
                                    }
                                });

                                if (isRequired) {
                                    const star = document.createElement('span');
                                    star.className = 'required-star';
                                    star.textContent = ' *';
                                    label.appendChild(star);
                                }
                            });
                        }

                        Object.entries(data).forEach(([name, status]) => {
                            const wrapper = document.getElementById(`field_${name}`);
                            if (!wrapper) return;

                            const fields = Array.from(wrapper.querySelectorAll('input, select, textarea'))
                                .filter(field => field.type !== 'hidden');

                            const isRequired = status === 'required';
                            wrapper.dataset.adminStatus = status;

                            if (status === 'hidden') {
                                wrapper.style.display = 'none';
                                fields.forEach(field => {
                                    field.required = false;
                                    field.dataset.fieldRequired = '0';
                                });
                                setRequiredMark(wrapper, false);
                            } else {
                                wrapper.style.display = '';
                                fields.forEach(field => {
                                    field.required = isRequired;
                                    field.dataset.fieldRequired = isRequired ? '1' : '0';
                                });
                                setRequiredMark(wrapper, isRequired);
                            }
                        });

                        if (typeof window.updateFilePendukungByKesejahteraan === 'function') {
                            window.updateFilePendukungByKesejahteraan();
                        }

                        if (typeof map !== 'undefined' && map) {
                            setTimeout(() => map.invalidateSize(), 250);
                        }
                    })
                    .catch(error => {
                        console.error("❌ Gagal mengambil field dinamis:", error);
                    });
            });
        </script>



                    <?php endif; ?>


        <script>
            (function() {
                const shell = document.querySelector('.app-shell');
                const toggle = document.getElementById('sidebarToggle');
                if (!shell || !toggle) return;

                function applySidebarState(collapsed) {
                    shell.classList.toggle('sidebar-collapsed', collapsed);
                    toggle.setAttribute('aria-expanded', collapsed ? 'false' : 'true');
                    toggle.setAttribute('aria-label', collapsed ? 'Maximize sidebar' : 'Minimize sidebar');
                }

                const saved = localStorage.getItem('sds_sidebar_collapsed') === '1';
                applySidebarState(saved);

                toggle.addEventListener('click', function() {
                    const collapsed = !shell.classList.contains('sidebar-collapsed');
                    localStorage.setItem('sds_sidebar_collapsed', collapsed ? '1' : '0');
                    applySidebarState(collapsed);
                });
            })();
        </script>

        <script>
            document.addEventListener('click', function(e) {
                const editBtn = e.target.closest('.detail-edit-toggle');
                if (editBtn) {
                    e.preventDefault();
                    const form = document.getElementById(editBtn.dataset.target);
                    if (form) {
                        form.classList.toggle('active');
                        form.querySelectorAll('.detail-item').forEach(item => item.classList.toggle('editing', form.classList.contains('active')));
                        editBtn.textContent = form.classList.contains('active') ? 'Mode Edit Aktif' : 'Edit Data';
                    }
                    return;
                }

                const cancelEdit = e.target.closest('.detail-edit-cancel');
                if (cancelEdit) {
                    e.preventDefault();
                    const form = cancelEdit.closest('.detail-edit-form');
                    if (form) {
                        form.classList.remove('active');
                        form.querySelectorAll('.detail-item').forEach(item => item.classList.remove('editing'));
                        const modal = form.closest('.detail-modal');
                        const btn = modal ? modal.querySelector('.detail-edit-toggle') : null;
                        if (btn) btn.textContent = 'Edit Data';
                    }
                    return;
                }

                const btn = e.target.closest('[data-target]:not(.detail-edit-toggle)');
                if (btn) {
                    const modal = document.getElementById(btn.dataset.target);
                    if (modal) modal.classList.add('active');
                }
                if (e.target.classList.contains('detail-close') || e.target.classList.contains('detail-modal')) {
                    const modal = e.target.closest('.detail-modal');
                    if (modal) modal.classList.remove('active');
                }
            });

            function refreshDetailKelasOptions(form, clearSelected) {
                if (!form) return;
                const jurusanSelect = form.querySelector('.detail-jurusan-select');
                const kelasSelect = form.querySelector('.detail-kelas-select');
                if (!jurusanSelect || !kelasSelect) return;
                const selectedJurusan = jurusanSelect.value;
                let currentSelectedStillVisible = false;
                let firstVisibleValue = '';

                Array.from(kelasSelect.options).forEach(function(option) {
                    if (!option.value) {
                        option.hidden = false;
                        return;
                    }
                    const isVisible = selectedJurusan !== '' && option.dataset.jurusanId === selectedJurusan;
                    option.hidden = !isVisible;
                    if (isVisible && !firstVisibleValue) firstVisibleValue = option.value;
                    if (isVisible && option.selected) currentSelectedStillVisible = true;
                });

                if (clearSelected || !currentSelectedStillVisible) {
                    kelasSelect.value = firstVisibleValue || '';
                }
            }

            document.querySelectorAll('.detail-edit-form').forEach(function(form) {
                refreshDetailKelasOptions(form, false);
                const jurusanSelect = form.querySelector('.detail-jurusan-select');
                if (jurusanSelect) {
                    jurusanSelect.addEventListener('change', function() {
                        refreshDetailKelasOptions(form, true);
                    });
                }
            });
        </script>

        <div class="footer">© <?= date('Y') ?> <?= !empty($pengaturan['nama_sekolah']) ? htmlspecialchars($pengaturan['nama_sekolah']) : 'Sekolah' ?></div>
            </main>
        </div>


<script>
document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('progressDetailFilterForm');
    if (!form) return;

    let timer = null;
    let lastSerialized = '';

    function serializeForm() {
        const fd = new FormData(form);
        const params = new URLSearchParams();
        fd.forEach(function (value, key) {
            params.set(key, value);
        });
        return params.toString();
    }

    function submitFilter(delay) {
        clearTimeout(timer);
        timer = setTimeout(function () {
            const serialized = serializeForm();
            if (serialized === lastSerialized) return;
            lastSerialized = serialized;
            const action = form.getAttribute('action') || window.location.pathname;
            window.location.href = action + '?' + serialized;
        }, delay || 0);
    }

    lastSerialized = serializeForm();

    form.querySelectorAll('select[data-autofilter="1"]').forEach(function (select) {
        select.addEventListener('change', function () {
            if (select.name === 'tingkat_id') {
                const jurusan = form.querySelector('[name="jurusan_id"]');
                const kelas = form.querySelector('[name="kelas_id"]');
                if (jurusan) jurusan.value = '0';
                if (kelas) kelas.value = '0';
            }
            if (select.name === 'jurusan_id') {
                const kelas = form.querySelector('[name="kelas_id"]');
                if (kelas) kelas.value = '0';
            }
            submitFilter(120);
        });
    });

    const search = form.querySelector('[data-autofilter-search="1"]');
    if (search) {
        search.addEventListener('input', function () {
            submitFilter(650);
        });
        search.addEventListener('change', function () {
            submitFilter(0);
        });
        search.addEventListener('keydown', function (e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                submitFilter(0);
            }
        });
    }
});
</script>
    </body>

    </html>
<?php endif; ?>
