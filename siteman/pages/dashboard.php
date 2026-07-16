<?php
// Dashboard Admin SDS - versi ringkas, rapi, dan fokus monitoring tahun ajaran aktif.
// File ini dipanggil dari siteman/index.php sehingga $conn, $tahunAjaran, $pengaturan sudah tersedia.

if (!function_exists('dash_e')) {
    function dash_e($value) {
        return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('dash_int')) {
    function dash_int($value) {
        return number_format((int)$value, 0, ',', '.');
    }
}

if (!function_exists('dash_percent')) {
    function dash_percent($value, $target) {
        $target = (int)$target;
        if ($target <= 0) return 0;
        return min(100, round(((int)$value / $target) * 100));
    }
}

if (!function_exists('dash_scalar')) {
    function dash_scalar($conn, $sql, $types = '', $params = []) {
        $stmt = $conn->prepare($sql);
        if (!$stmt) return 0;
        if ($types !== '') $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $res = $stmt->get_result();
        $row = $res ? $res->fetch_row() : null;
        $stmt->close();
        return $row ? (int)$row[0] : 0;
    }
}

if (!function_exists('dash_rows')) {
    function dash_rows($conn, $sql, $types = '', $params = []) {
        $stmt = $conn->prepare($sql);
        if (!$stmt) return [];
        if ($types !== '') $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $res = $stmt->get_result();
        $rows = [];
        if ($res) {
            while ($row = $res->fetch_assoc()) $rows[] = $row;
        }
        $stmt->close();
        return $rows;
    }
}

// Status formulir
$formAktif = '0';
$resForm = $conn->query("SELECT nilai FROM formulir WHERE nama = 'form_aktif' LIMIT 1");
if ($resForm && $rowForm = $resForm->fetch_assoc()) {
    $formAktif = (string)$rowForm['nilai'];
}

// Token dan URL endpoint dibuat dari lokasi index.php yang sedang berjalan.
// Ini tetap benar ketika dashboard dibuka melalui /dashboard maupun /dashboard/.
if (empty($_SESSION['sds_toggle_formulir_csrf'])) {
    $_SESSION['sds_toggle_formulir_csrf'] = bin2hex(random_bytes(32));
}
$toggleFormCsrf = (string)$_SESSION['sds_toggle_formulir_csrf'];
$adminScriptDir = str_replace('\\', '/', dirname((string)($_SERVER['SCRIPT_NAME'] ?? '/siteman/index.php')));
$adminScriptDir = rtrim($adminScriptDir, '/');
$toggleFormEndpoint = ($adminScriptDir === '' ? '' : $adminScriptDir) . '/pages/ajax_toggle_formulir.php';

$today = date('Y-m-d');

// Ekspresi progress kelengkapan data wajib.
// Progress mengikuti aturan formulir: 45 field wajib per siswa.
// Data Wali, Kesejahteraan, File Pendukung, dan File Akta tidak masuk persentase karena opsional.
$dashNotEmpty = function ($field) {
    return "COALESCE($field,'') <> ''";
};
$dashNotPlaceholder = function ($field, $placeholder) {
    return "COALESCE($field,'') <> '' AND COALESCE($field,'') <> '$placeholder'";
};
$requiredProgressFields = [
    $dashNotEmpty('ps.nama_lengkap'),
    $dashNotEmpty('ps.email'),
    $dashNotEmpty('ps.nisn'),
    "k.id IS NOT NULL AND k.id > 0",
    $dashNotEmpty('ps.sekolah_asal'),
    $dashNotEmpty('ps.nomor_ijazah'),
    $dashNotPlaceholder('ps.jenis_kelamin', '-- Pilih Jenis Kelamin --'),
    $dashNotEmpty('ps.tempat_lahir'),
    "COALESCE(ps.tanggal_lahir,'') <> '' AND COALESCE(ps.tanggal_lahir,'') <> '0000-00-00'",
    $dashNotEmpty('ps.no_kk'),
    $dashNotEmpty('ps.nik'),
    $dashNotEmpty('ps.no_registrasi_akta'),
    $dashNotPlaceholder('ps.agama', '-- Pilih Agama --'),
    $dashNotEmpty('ps.provinsi'),
    $dashNotEmpty('ps.kota'),
    $dashNotEmpty('ps.kecamatan'),
    $dashNotEmpty('ps.desa'),
    $dashNotEmpty('ps.alamat'),
    "COALESCE(ps.latitude,0) <> 0 AND COALESCE(ps.longitude,0) <> 0",
    $dashNotPlaceholder('ps.tempat_tinggal', '-- Pilih Tempat Tinggal --'),
    $dashNotPlaceholder('ps.moda_transportasi', '-- Pilih Moda Transportasi --'),
    "COALESCE(ps.anak_ke,0) > 0",
    "ps.jumlah_saudara_kandung IS NOT NULL AND ps.jumlah_saudara_kandung >= 0",
    "COALESCE(ps.tinggi_badan,0) > 0",
    "COALESCE(ps.berat_badan,0) > 0",
    $dashNotEmpty('ps.hobi'),
    $dashNotEmpty('ps.cita_cita'),
    $dashNotEmpty('ps.foto'),
    $dashNotEmpty('ps.nama_ayah'),
    $dashNotEmpty('ps.nik_ayah'),
    "COALESCE(ps.tahun_lahir_ayah,0) > 0",
    $dashNotPlaceholder('ps.pendidikan_ayah', '-- Pilih Pendidikan --'),
    $dashNotPlaceholder('ps.pekerjaan_ayah', '-- Pilih Pekerjaan --'),
    $dashNotPlaceholder('ps.penghasilan_ayah', '-- Pilih Penghasilan --'),
    $dashNotEmpty('ps.nama_ibu'),
    $dashNotEmpty('ps.nik_ibu'),
    "COALESCE(ps.tahun_lahir_ibu,0) > 0",
    $dashNotPlaceholder('ps.pendidikan_ibu', '-- Pilih Pendidikan --'),
    $dashNotPlaceholder('ps.pekerjaan_ibu', '-- Pilih Pekerjaan --'),
    $dashNotPlaceholder('ps.penghasilan_ibu', '-- Pilih Penghasilan --'),
    $dashNotEmpty('ps.nohp_ortu'),
    $dashNotEmpty('ps.nohp_siswa'),
    $dashNotEmpty('ps.file_kk'),
    $dashNotEmpty('ps.file_ijazah'),
    "ps.pernyataan_setuju = 1"
];
$totalRequiredProgressFields = count($requiredProgressFields);
$exprProgressSkor = '(' . implode(' + ', array_map(fn($expr) => "CASE WHEN $expr THEN 1 ELSE 0 END", $requiredProgressFields)) . ')';
$exprProgressPersen = "ROUND(($exprProgressSkor / $totalRequiredProgressFields) * 100)";
$exprBiodata = implode(' AND ', array_slice($requiredProgressFields, 0, 28));
$exprOrtu = implode(' AND ', array_slice($requiredProgressFields, 28, 14));
$exprFile = "COALESCE(ps.foto,'') <> '' AND COALESCE(ps.file_kk,'') <> '' AND COALESCE(ps.file_ijazah,'') <> ''";
$exprLengkap = "$exprProgressSkor = $totalRequiredProgressFields";

// Statistik global tahun ajaran aktif.
$totalSiswaAktif = dash_scalar($conn, "SELECT COUNT(*) FROM pendaftaran_siswa WHERE status_aktif = 1");

$totalRombel = dash_scalar($conn, "SELECT COUNT(*) FROM kelas WHERE tahun_ajaran = ?", 's', [$tahunAjaran]);
$totalJurusan = dash_scalar($conn, "SELECT COUNT(*) FROM jurusan WHERE tahun_ajaran = ?", 's', [$tahunAjaran]);
$formMasukHariIni = dash_scalar($conn, "SELECT COUNT(*) FROM pendaftaran_siswa WHERE DATE(tanggal_input) = ?", 's', [$today]);
$totalNonAktif = dash_scalar($conn, "SELECT COUNT(*) FROM pendaftaran_siswa WHERE status_aktif = 0");
$totalAdmin = dash_scalar($conn, "SELECT COUNT(*) FROM admins");

// Tingkat awal (umumnya Kelas X) ditentukan dari master, bukan asumsi ID=1.
$tingkatAwalId = (int)dash_scalar($conn, "SELECT id FROM tingkat_kelas ORDER BY urutan_tingkat,id LIMIT 1");
$totalKuotaX = dash_scalar($conn, "SELECT COALESCE(SUM(kuota),0) FROM kelas WHERE tahun_ajaran = ? AND tingkat_id = ?", 'si', [$tahunAjaran,$tingkatAwalId]);
$totalDaftarX = dash_scalar($conn, "
    SELECT COUNT(DISTINCT ps.id)
    FROM siswa_kelas sk
    JOIN pendaftaran_siswa ps ON ps.id = sk.siswa_id
    JOIN kelas k ON k.id = sk.kelas_id
    WHERE sk.tahun_ajaran = ?
      AND k.tahun_ajaran = ?
      AND k.tingkat_id = ?
      AND ps.status_aktif = 1
", 'ssi', [$tahunAjaran, $tahunAjaran, $tingkatAwalId]);
$persenDaftarX = dash_percent($totalDaftarX, $totalKuotaX);
$sisaKuotaX = max(0, $totalKuotaX - $totalDaftarX);

// Kelengkapan data per tingkat.
$tingkatSummary = dash_rows($conn, "
    SELECT
        k.tingkat_id,
        COALESCE(tk.nama_tingkat, CONCAT('Tingkat ', k.tingkat_id)) AS nama_tingkat,
        COUNT(DISTINCT ps.id) AS total_siswa,
        SUM(CASE WHEN $exprLengkap THEN 1 ELSE 0 END) AS data_lengkap,
        SUM(CASE WHEN $exprFile THEN 1 ELSE 0 END) AS berkas_lengkap,
        ROUND(COALESCE(AVG($exprProgressPersen),0)) AS avg_progress,
        SUM(CASE WHEN ps.sudah_dapodik = 1 THEN 1 ELSE 0 END) AS sudah_dapodik
    FROM kelas k
    LEFT JOIN tingkat_kelas tk ON tk.id = k.tingkat_id
    LEFT JOIN siswa_kelas sk ON sk.kelas_id = k.id AND sk.tahun_ajaran = ?
    LEFT JOIN pendaftaran_siswa ps ON ps.id = sk.siswa_id AND ps.status_aktif = 1
    WHERE k.tahun_ajaran = ?
    GROUP BY k.tingkat_id, tk.nama_tingkat
    ORDER BY k.tingkat_id ASC
", 'ss', [$tahunAjaran, $tahunAjaran]);

// Progress daftar ulang kelas X per jurusan.
// Persentase Kelas X dihitung dari jumlah siswa yang sudah masuk rombel dibanding total kuota jurusan.
$progressJurusanX = dash_rows($conn, "
    SELECT
        j.id,
        j.nama_jurusan,
        COALESCE(kx.kuota,0) AS kuota,
        COALESCE(dx.sudah_daftar,0) AS sudah_daftar,
        COALESCE(dx.berkas_lengkap,0) AS berkas_lengkap,
        CASE
            WHEN COALESCE(kx.kuota,0) > 0
            THEN LEAST(100, ROUND((COALESCE(dx.sudah_daftar,0) / kx.kuota) * 100))
            ELSE 0
        END AS progress_daftar
    FROM jurusan j
    LEFT JOIN (
        SELECT jurusan_id, SUM(kuota) AS kuota
        FROM kelas
        WHERE tahun_ajaran = ? AND tingkat_id = ?
        GROUP BY jurusan_id
    ) kx ON kx.jurusan_id = j.id
    LEFT JOIN (
        SELECT
            k.jurusan_id,
            COUNT(DISTINCT ps.id) AS sudah_daftar,
            SUM(CASE WHEN $exprFile THEN 1 ELSE 0 END) AS berkas_lengkap
        FROM kelas k
        LEFT JOIN siswa_kelas sk ON sk.kelas_id = k.id AND sk.tahun_ajaran = ?
        LEFT JOIN pendaftaran_siswa ps ON ps.id = sk.siswa_id AND ps.status_aktif = 1
        WHERE k.tahun_ajaran = ? AND k.tingkat_id = ?
        GROUP BY k.jurusan_id
    ) dx ON dx.jurusan_id = j.id
    WHERE j.tahun_ajaran = ?
    ORDER BY j.nama_jurusan ASC
", 'sissis', [$tahunAjaran, $tingkatAwalId, $tahunAjaran, $tahunAjaran, $tingkatAwalId, $tahunAjaran]);

// Progress per rombel ringkas.
$progressKelas = dash_rows($conn, "
    SELECT
        k.id,
        k.nama_kelas,
        k.kuota,
        k.tingkat_id,
        COALESCE(j.nama_jurusan,'-') AS nama_jurusan,
        COUNT(DISTINCT ps.id) AS jumlah_siswa,
        SUM(CASE WHEN $exprLengkap THEN 1 ELSE 0 END) AS data_lengkap,
        SUM(CASE WHEN $exprFile THEN 1 ELSE 0 END) AS berkas_lengkap,
        CASE
            WHEN COALESCE(k.kuota,0) > 0
            THEN LEAST(100, ROUND((COUNT(DISTINCT ps.id) / k.kuota) * 100))
            ELSE 0
        END AS progress_daftar
    FROM kelas k
    LEFT JOIN jurusan j ON j.id = k.jurusan_id
    LEFT JOIN siswa_kelas sk ON sk.kelas_id = k.id AND sk.tahun_ajaran = ?
    LEFT JOIN pendaftaran_siswa ps ON ps.id = sk.siswa_id AND ps.status_aktif = 1
    WHERE k.tahun_ajaran = ?
      AND k.tingkat_id = ?
    GROUP BY k.id, k.nama_kelas, k.kuota, k.tingkat_id, j.nama_jurusan
    ORDER BY k.nama_kelas ASC
", 'ssi', [$tahunAjaran, $tahunAjaran, $tingkatAwalId]);

// Data terbaru.
$siswaTerbaru = dash_rows($conn, "
    SELECT
        ps.id,
        ps.nama_lengkap,
        ps.nisn,
        ps.tanggal_input,
        COALESCE(k.nama_kelas,'-') AS nama_kelas,
        COALESCE(j.nama_jurusan,'-') AS nama_jurusan,
        CASE WHEN $exprFile THEN 1 ELSE 0 END AS file_lengkap,
        CASE WHEN $exprLengkap THEN 1 ELSE 0 END AS data_lengkap,
        $exprProgressPersen AS progress_data_persen
    FROM pendaftaran_siswa ps
    LEFT JOIN siswa_kelas sk ON sk.siswa_id = ps.id AND sk.tahun_ajaran = ?
    LEFT JOIN kelas k ON k.id = sk.kelas_id
    LEFT JOIN jurusan j ON j.id = k.jurusan_id
    ORDER BY ps.tanggal_input DESC, ps.id DESC
    LIMIT 6
", 's', [$tahunAjaran]);

// Map siswa yang punya koordinat.
$siswaMap = dash_rows($conn, "
    SELECT
        ps.id,
        ps.nama_lengkap AS nama,
        ps.nisn,
        ps.nipd,
        ps.latitude AS lat,
        ps.longitude AS lng,
        ps.foto,
        COALESCE(tk.nama_tingkat, '-') AS nama_tingkat,
        COALESCE(k.nama_kelas, '-') AS nama_kelas
    FROM siswa_kelas sk
    JOIN pendaftaran_siswa ps ON ps.id = sk.siswa_id
    JOIN kelas k ON k.id = sk.kelas_id
    LEFT JOIN tingkat_kelas tk ON tk.id = k.tingkat_id
    WHERE ps.latitude IS NOT NULL
      AND ps.longitude IS NOT NULL
      AND ps.latitude <> 0
      AND ps.longitude <> 0
      AND sk.tahun_ajaran = ?
      AND k.tahun_ajaran = ?
      AND ps.status_aktif = 1
    LIMIT 1200
", 'ss', [$tahunAjaran, $tahunAjaran]);

$kelasMapList = [];
$tingkatMapList = [];
foreach ($siswaMap as $s) {
    if (!empty($s['nama_kelas'])) $kelasMapList[$s['nama_kelas']] = true;
    if (!empty($s['nama_tingkat'])) $tingkatMapList[$s['nama_tingkat']] = true;
}
$kelasMapList = array_keys($kelasMapList);
$tingkatMapList = array_keys($tingkatMapList);
sort($kelasMapList);
sort($tingkatMapList);

// Asal sekolah top 12 tahun aktif.
$asalSekolah = dash_rows($conn, "
    SELECT COALESCE(NULLIF(ps.sekolah_asal,''),'Tidak Diisi') AS sekolah_asal, COUNT(DISTINCT ps.id) AS total
    FROM siswa_kelas sk
    JOIN pendaftaran_siswa ps ON ps.id = sk.siswa_id
    JOIN kelas k ON k.id = sk.kelas_id
    WHERE sk.tahun_ajaran = ?
      AND k.tahun_ajaran = ?
      AND ps.status_aktif = 1
    GROUP BY COALESCE(NULLIF(ps.sekolah_asal,''),'Tidak Diisi')
    ORDER BY total DESC
    LIMIT 12
", 'ss', [$tahunAjaran, $tahunAjaran]);
$asalLabels = array_column($asalSekolah, 'sekolah_asal');
$asalCounts = array_map('intval', array_column($asalSekolah, 'total'));

// Survey layanan.
$surveyRows = dash_rows($conn, "SELECT penilaian, COUNT(*) AS total FROM survey_kepuasan GROUP BY penilaian");
$surveyMap = [1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 0];
foreach ($surveyRows as $sr) {
    $surveyMap[(int)$sr['penilaian']] = (int)$sr['total'];
}
$surveyLabels = ['Tidak Puas', 'Kurang Puas', 'Cukup', 'Puas', 'Sangat Puas'];
$surveyCounts = array_values($surveyMap);
?>

<style>
    /* Dashboard menyesuaikan gaya halaman admin lain: Bootstrap/AdminKit, ringan dan tidak terlalu berbeda. */
    .sds-dashboard{padding:0}
    .sds-hero{background: #fff;
    border: 1px solid #dee2e6;
    border-radius: 0;
    padding: 1rem;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 0;
    margin-bottom: 0;
    box-shadow: unset;}
    .sds-hero h2{margin:0 0 .25rem;font-size:1.25rem;font-weight:600;color:#334151}
    .sds-hero p{margin:0;color:#6c757d;font-size:.875rem}
    .sds-hero-actions{display:flex;align-items:center;gap:.65rem;flex-wrap:wrap}
    .sds-card,.sds-stat-card{background:#fff;border:1px solid #dee2e6;border-radius:0rem;box-shadow:unset}
    .sds-stats{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:0rem;margin-bottom:0rem}
    .sds-stat-card{position:relative;padding:1rem;min-height:116px}
    .sds-stat-card:before{display:none}
    .sds-stat-card .icon{width:38px;height:38px;border-radius:.25rem;background:#e9f2ff;color:#0d6efd;display:flex;align-items:center;justify-content:center;margin-bottom:.75rem;display:none;}
    .sds-stat-card small{display:block;color:#6c757d;font-weight:700;text-transform:uppercase;letter-spacing:.04em;font-size:.72rem}
    .sds-stat-card strong{display:block;font-size:1.65rem;line-height:1.1;margin-top:.25rem;color:#212529;font-weight:700}
    .sds-stat-card span{display:block;color:#6c757d;font-size:.78rem;margin-top:.25rem}
    .sds-grid-main{display:grid;grid-template-columns:1.55fr .95fr;gap:0rem;margin-bottom:0rem}
    .sds-grid-2{display:grid;grid-template-columns:50% 50%;gap:0rem;margin-bottom:0rem}
    .sds-card-header{display:flex;align-items:center;justify-content:space-between;gap:.75rem;padding:.9rem 1rem;border-bottom:1px solid #dee2e6;background:#f8f9fa}
    .sds-card-header h5{margin:0;font-weight:600;color:#334151;font-size:1rem}
    .sds-card-body{padding:1rem}
    .sds-progress-title{display:flex;align-items:center;justify-content:space-between;margin-bottom:.45rem;font-size:.82rem;font-weight:600;color:#495057}
    .sds-progress{height:1.05rem;background:#e9ecef;border-radius:999px;overflow:hidden;position:relative}
    .sds-progress-fill{height:100%;background:#0d6efd;border-radius:999px;min-width:4px;color:#fff;font-size:.68rem;font-weight:800;display:flex;align-items:center;justify-content:flex-end;padding-right:6px;line-height:1}
    .sds-table-wrap{overflow:auto}
    .sds-table{width:100%;border-collapse:collapse;background:#fff}
    .sds-table th{font-size:.72rem;text-transform:uppercase;letter-spacing:.04em;color:#6c757d;background:#f8f9fa;padding:.48rem .55rem;border-bottom:1px solid #dee2e6;white-space:nowrap}
    .sds-table td{padding:5px .55rem;border-bottom:1px solid #edf1f5;vertical-align:middle;color:#334151}
    .sds-table tr:last-child td{border-bottom:0}
    .sds-badge{display:inline-flex;align-items:center;gap:5px;border-radius:.25rem;padding:7px;font-size:.75rem;font-weight:600;white-space:nowrap}
    .sds-badge.ok{background:#d1e7dd;color:#0f5132}
    .sds-badge.warn{background:#fff3cd;color:#664d03}
    .sds-badge.info{background:unset;color:#055160}
    .sds-badge.danger{background:#f8d7da;color:#842029}
    .sds-quick{display:grid;grid-template-columns:repeat(4,1fr);gap:0rem;margin-bottom:0rem}
    .sds-quick a{text-decoration:none;background:#fff;border:1px solid #dee2e6;border-radius:0rem;padding:.75rem;color:#334151;font-weight:600;display:flex;align-items:center;gap:.55rem;box-shadow:0 .125rem .25rem rgba(0,0,0,.035)}
    .sds-quick a:hover{background:#f8f9fa;color:#0d6efd}
    .sds-toggle-card{display:flex;align-items:center;gap:.75rem;background:#f8f9fa;color:#334151;border:1px solid #dee2e6;border-radius:.25rem;padding:.5rem .75rem}
    .toggle-checkbox{width:2.8rem;height:1.45rem;appearance:none;background:#adb5bd;border-radius:999px;position:relative;cursor:pointer;transition:.25s;vertical-align:middle}
    .toggle-checkbox:checked{background:#198754}
    .toggle-checkbox:before{content:'';position:absolute;width:1.1rem;height:1.1rem;border-radius:999px;background:#fff;top:.18rem;left:.18rem;transition:.25s;box-shadow:0 1px 3px rgba(0,0,0,.25)}
    .toggle-checkbox:checked:before{transform:translateX(1.35rem)}
    #mapContainer:fullscreen{background:#fff;padding:1rem}
    #dashMap{height:360px;border-radius:.25rem;overflow:hidden;border:1px solid #dee2e6}
    #mapContainer:fullscreen #dashMap{height:calc(100vh - 110px)}
    .dash-filter{display:grid;grid-template-columns:1fr 1fr 1fr;gap:.65rem;margin-bottom:.75rem}
    .dash-filter input,.dash-filter select{border:1px solid #ced4da;border-radius:.25rem;padding:.5rem .65rem;background:#fff;color:#495057}
    .sds-mini{font-size:.78rem;color:#6c757d}
    .sds-student{display:flex;gap:.65rem;align-items:center;border-bottom:1px solid #edf1f5;padding-bottom:.75rem}
    .sds-student:last-child{border-bottom:0;padding-bottom:0}
    .sds-avatar{width:34px;height:34px;border-radius:50%;background:#e9f2ff;color:#0d6efd;display:flex;align-items:center;justify-content:center;font-weight:700;flex:0 0 auto}
    .sds-section-title{font-size:.78rem;text-transform:uppercase;letter-spacing:.06em;color:#6c757d;font-weight:700;margin:1rem 0 .6rem}
    @media(max-width:1200px){.sds-stats{grid-template-columns:repeat(2,1fr)}.sds-grid-main,.sds-grid-2{grid-template-columns:1fr}.sds-quick{grid-template-columns:repeat(2,1fr)}}
    @media(max-width:700px){.sds-dashboard{padding:0 6px}.sds-hero{display:block}.sds-hero-actions{margin-top:.75rem}.sds-stats,.sds-quick{grid-template-columns:1fr}.dash-filter{grid-template-columns:1fr}.sds-table{min-width:760px}}
</style>

<div class="sds-dashboard">
    <div class="sds-hero">
        <div>
            <h2>Dashboard Sistem Data Siswa</h2>
            <p>Ringkasan monitoring tahun ajaran aktif, daftar ulang, kelengkapan data, dan sebaran siswa.</p>
        </div>
        <div class="sds-hero-actions">
            <div class="sds-toggle-card">
                <div>
                    <div style="font-weight:800;font-size:13px">Formulir</div>
                    <div class="sds-mini" id="statusMsg" aria-live="polite"><?= $formAktif == '1' ? 'Dibuka' : 'Ditutup' ?></div>
                </div>
                <input type="checkbox" id="toggleFormulir" <?= $formAktif == '1' ? 'checked' : '' ?> class="toggle-checkbox" title="Buka/Tutup formulir" aria-label="Buka atau tutup formulir">
            </div>
        </div>
    </div>

    <div class="sds-stats">
        <div class="sds-stat-card"><div class="icon"><i data-feather="users"></i></div><small>Siswa Aktif</small><strong><?= dash_int($totalSiswaAktif) ?></strong><span>Tahun ajaran <?= dash_e($tahunAjaran) ?></span></div>
        <div class="sds-stat-card"><div class="icon"><i data-feather="layers"></i></div><small>Rombel</small><strong><?= dash_int($totalRombel) ?></strong><span><?= dash_int($totalJurusan) ?> kompetensi keahlian</span></div>
        <div class="sds-stat-card"><div class="icon"><i data-feather="file-text"></i></div><small>Form Masuk Hari Ini</small><strong><?= dash_int($formMasukHariIni) ?></strong><span><?= date('d/m/Y') ?></span></div>
        <div class="sds-stat-card"><div class="icon"><i data-feather="user-x"></i></div><small>Siswa Non Aktif</small><strong><?= dash_int($totalNonAktif) ?></strong><span><a href="students?status=nonaktif">Lihat daftar</a></span></div>
    </div>

    <div class="sds-quick">
        <a href="students"><i data-feather="users"></i> Peserta Didik</a>
        <a href="jurusan"><i data-feather="list"></i> Kompetensi Keahlian</a>
        <a href="kuota_kelas"><i data-feather="grid"></i> Data Rombel</a>
        <a href="admin_fields"><i data-feather="settings"></i> Pengaturan Formulir</a>
    </div>

    <div class="sds-grid-main">
        <div class="sds-card">
            <div class="sds-card-header">
                <h5>Progress Daftar Ulang Kelas X</h5>
                <span class="sds-badge info" style="padding:0"><?= dash_int($totalDaftarX) ?> / <?= dash_int($totalKuotaX) ?></span>
            </div>
            <div class="sds-card-body">
                <div class="sds-mini mb-2">Progress daftar ulang dihitung dari jumlah siswa yang sudah mengisi formulir dibanding kuota Kelas X.</div>
                <div class="sds-progress-title"><span>Progress keseluruhan</span><span><?= $persenDaftarX ?>%</span></div>
                <div class="sds-progress"><div class="sds-progress-fill" style="width:<?= $persenDaftarX ?>%"><?= $persenDaftarX ?>%</div></div>
                <div class="row mt-1 g-3">
                    <div class="col-md-4"><span class="sds-badge ok">Sudah daftar <?= dash_int($totalDaftarX) ?></span></div>
                    <div class="col-md-4"><span class="sds-badge warn">Sisa kuota <?= dash_int($sisaKuotaX) ?></span></div>
                    <div class="col-md-4"><span class="sds-badge info">Kuota <?= dash_int($totalKuotaX) ?></span></div>
                </div>
                <div class="sds-section-title">Progress Daftar Ulang Per Jurusan Kelas X</div>
                <div class="sds-table-wrap">
                    <table class="sds-table">
                        <thead><tr><th>Jurusan</th><th>Kuota</th><th>Daftar</th><th>Berkas</th><th>Progress</th></tr></thead>
                        <tbody>
                        <?php foreach ($progressJurusanX as $j): $p = (int)($j['progress_daftar'] ?? 0); ?>
                            <tr>
                                <td><b><?= dash_e($j['nama_jurusan']) ?></b></td>
                                <td><?= dash_int($j['kuota']) ?></td>
                                <td><?= dash_int($j['sudah_daftar']) ?></td>
                                <td><?= dash_int($j['berkas_lengkap']) ?></td>
                                <td style="min-width:150px"><div class="sds-progress"><div class="sds-progress-fill" style="width:<?= $p ?>%"><?= $p ?>%</div></div></td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($progressJurusanX)): ?><tr><td colspan="5" class="text-center text-muted">Belum ada data jurusan tahun ajaran ini.</td></tr><?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="sds-card">
            <div class="sds-card-header"><h5>Kelengkapan Data per Tingkat</h5></div>
            <div class="sds-card-body">
                <?php foreach ($tingkatSummary as $t):
                    $p = (int)($t['avg_progress'] ?? 0);
                ?>
                    <div class="mb-4">
                        <div class="sds-progress-title"><span>Kelas <?= dash_e($t['nama_tingkat']) ?></span><span><?= dash_int($t['data_lengkap']) ?> / <?= dash_int($t['total_siswa']) ?></span></div>
                        <div class="sds-progress"><div class="sds-progress-fill" style="width:<?= $p ?>%"><?= $p ?>%</div></div>
                        <div class="sds-mini mt-1">Berkas lengkap: <?= dash_int($t['berkas_lengkap']) ?> · Dapodik: <?= dash_int($t['sudah_dapodik']) ?></div>
                    </div>
                <?php endforeach; ?>
                <?php if (empty($tingkatSummary)): ?><div class="text-muted">Belum ada data kelas tahun ajaran ini.</div><?php endif; ?>
                <div class="sds-section-title">Akun Admin</div>
                <div class="d-flex align-items-center justify-content-between p-3" style="border:1px solid #e2e8f0;border-radius:14px;background:#f8fafc">
                    <span>Total pengguna admin</span><b><?= dash_int($totalAdmin) ?></b>
                </div>
            </div>
        </div>
    </div>

    <div class="sds-grid-main">
        <div class="sds-card">
            <div id="mapContainer">
                <div class="sds-card-header">
                    <h5>Lokasi Sebaran Siswa</h5>
                    <button id="fullscreenButton" type="button" class="btn btn-sm btn-outline-primary" style="display:none">Fullscreen</button>
                </div>
                <div class="sds-card-body">
                    <div class="dash-filter">
                        <input type="text" id="searchInput" placeholder="Cari nama / NISN...">
                        <select id="filterTingkat"><option value="">Semua Tingkat</option><?php foreach ($tingkatMapList as $tingkat): ?><option value="<?= dash_e($tingkat) ?>"><?= dash_e($tingkat) ?></option><?php endforeach; ?></select>
                        <select id="filterKelas"><option value="">Semua Kelas</option><?php foreach ($kelasMapList as $kelas): ?><option value="<?= dash_e($kelas) ?>"><?= dash_e($kelas) ?></option><?php endforeach; ?></select>
                    </div>
                    <div id="dashMap"></div>
                    <div class="sds-mini mt-2">Menampilkan maksimal 1.200 titik siswa yang memiliki koordinat.</div>
                </div>
            </div>
        </div>

        <div class="sds-card">
            <div class="sds-card-header"><h5>Data Terbaru Masuk</h5></div>
            <div class="sds-card-body">
                <?php foreach ($siswaTerbaru as $s): ?>
                    <div class="sds-student mb-3">
                        <div class="sds-avatar"><?= dash_e(mb_substr($s['nama_lengkap'] ?: '?', 0, 1)) ?></div>
                        <div class="flex-grow-1">
                            <div><b><?= dash_e($s['nama_lengkap']) ?></b></div>
                            <div class="sds-mini"><?= dash_e($s['nama_kelas']) ?> · <?= dash_e($s['nama_jurusan']) ?></div>
                            <div class="sds-mini"><?= dash_e($s['tanggal_input'] ?: '-') ?></div>
                        </div>
                        <?php if ((int)$s['data_lengkap'] === 1): ?>
                            <a class="sds-badge ok text-decoration-none" href="student_view&id=<?= (int)$s['id'] ?>" title="Lihat profil peserta didik">Lengkap</a>
                        <?php else: ?>
                            <a class="sds-badge warn text-decoration-none" href="student_view&id=<?= (int)$s['id'] ?>" title="Cek data peserta didik"><?= (int)($s['progress_data_persen'] ?? 0) ?>%</a>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
                <?php if (empty($siswaTerbaru)): ?><div class="text-muted">Belum ada data terbaru.</div><?php endif; ?>
            </div>
        </div>
    </div>

    <div class="sds-grid-2">
        <div class="sds-card">
            <div class="sds-card-header"><h5>Asal Sekolah Siswa</h5></div>
            <div class="sds-card-body"><canvas id="asalSekolahChart" height="260"></canvas></div>
        </div>
        <div class="sds-card">
            <div class="sds-card-header"><h5>Survey Layanan</h5></div>
            <div class="sds-card-body"><canvas id="surveyChart" height="260"></canvas></div>
        </div>
    </div>

    <div class="sds-card">
        <div class="sds-card-header"><h5>Progress Rombel Kelas X Tahun Ajaran <?= dash_e($tahunAjaran) ?></h5><a class="btn btn-sm btn-primary" href="kuota_kelas">Kelola Rombel</a></div>
        <div class="">
            <div class="sds-table-wrap">
                <table class="sds-table">
                    <thead><tr><th>Kelas</th><th>Jurusan</th><th>Daftar / Kuota</th><th>Berkas</th><th>Data Lengkap</th><th>Progress Daftar</th><th></th></tr></thead>
                    <tbody>
                    <?php foreach ($progressKelas as $k):
                        $p = (int)($k['progress_daftar'] ?? 0);
                    ?>
                        <tr>
                            <td><b><?= dash_e($k['nama_kelas']) ?></b></td>
                            <td><?= dash_e($k['nama_jurusan']) ?></td>
                            <td><?= dash_int($k['jumlah_siswa']) ?><?= (int)$k['tingkat_id'] === 1 ? ' / ' . dash_int($k['kuota']) : '' ?></td>
                            <td><?= dash_int($k['berkas_lengkap']) ?></td>
                            <td><?= dash_int($k['data_lengkap']) ?></td>
                            <td style="min-width:150px"><div class="sds-progress"><div class="sds-progress-fill" style="width:<?= $p ?>%"><?= $p ?>%</div></div></td>
                            <td><a href="kuota_kelas_siswa?kelas_id=<?= (int)$k['id'] ?>" class="btn btn-sm btn-outline-primary" style="width: 100%;">Lihat</a></td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (empty($progressKelas)): ?><tr><td colspan="7" class="text-center text-muted">Belum ada data rombel.</td></tr><?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
(function(){
    const toggle = document.getElementById('toggleFormulir');
    const statusMsg = document.getElementById('statusMsg');
    const endpoint = <?= json_encode($toggleFormEndpoint, JSON_UNESCAPED_SLASHES) ?>;
    const csrfToken = <?= json_encode($toggleFormCsrf) ?>;

    if (!toggle || !statusMsg) return;

    let savedStatus = toggle.checked;
    let requestInProgress = false;

    function setStatusLabel(isOpen, temporaryText) {
        statusMsg.textContent = temporaryText || (isOpen ? 'Dibuka' : 'Ditutup');
        statusMsg.style.color = temporaryText ? '#6c757d' : '';
    }

    function notify(message, type) {
        if (typeof window.sdsNotify === 'function') {
            window.sdsNotify(message, type, { duration: type === 'danger' ? 7500 : 4500 });
        } else {
            console[type === 'danger' ? 'error' : 'log'](message);
        }
    }

    toggle.addEventListener('change', async function() {
        if (requestInProgress) {
            toggle.checked = savedStatus;
            return;
        }

        const requestedStatus = toggle.checked;
        requestInProgress = true;
        toggle.disabled = true;
        toggle.setAttribute('aria-busy', 'true');
        setStatusLabel(requestedStatus, 'Menyimpan...');

        try {
            const body = new URLSearchParams();
            body.set('status', requestedStatus ? '1' : '0');
            body.set('csrf_token', csrfToken);

            const response = await fetch(endpoint, {
                method: 'POST',
                credentials: 'same-origin',
                cache: 'no-store',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: body.toString()
            });

            const responseText = await response.text();
            let payload;
            try {
                payload = JSON.parse(responseText);
            } catch (parseError) {
                throw new Error('Respons server tidak valid. Muat ulang halaman lalu coba lagi.');
            }

            if (!response.ok || !payload || payload.success !== true) {
                throw new Error((payload && payload.message) || 'Gagal menyimpan status formulir.');
            }

            savedStatus = Number(payload.status) === 1;
            toggle.checked = savedStatus;
            setStatusLabel(savedStatus);
            notify(payload.message || (savedStatus ? 'Formulir berhasil diaktifkan.' : 'Formulir berhasil dinonaktifkan.'), 'success');
        } catch (error) {
            toggle.checked = savedStatus;
            setStatusLabel(savedStatus);
            notify(error && error.message ? error.message : 'Gagal mengubah status formulir.', 'danger');
        } finally {
            requestInProgress = false;
            toggle.disabled = false;
            toggle.removeAttribute('aria-busy');
        }
    });
})();
</script>

<script>
(function(){
    const asalLabels = <?= json_encode($asalLabels, JSON_UNESCAPED_UNICODE) ?>;
    const asalCounts = <?= json_encode($asalCounts) ?>;
    const surveyLabels = <?= json_encode($surveyLabels, JSON_UNESCAPED_UNICODE) ?>;
    const surveyCounts = <?= json_encode($surveyCounts) ?>;

    const asalCtx = document.getElementById('asalSekolahChart');
    if (asalCtx) {
        new Chart(asalCtx.getContext('2d'), {
            type: 'bar',
            data: { labels: asalLabels, datasets: [{ label: 'Jumlah Siswa', data: asalCounts, borderRadius: 7 }] },
            options: { indexAxis: 'y', plugins: { legend: { display: false } }, scales: { x: { beginAtZero: true, ticks: { precision: 0 } }, y: { ticks: { autoSkip: false } } } }
        });
    }

    const surveyCtx = document.getElementById('surveyChart');
    if (surveyCtx) {
        new Chart(surveyCtx.getContext('2d'), {
            type: 'bar',
            data: { labels: surveyLabels, datasets: [{ label: 'Jumlah Responden', data: surveyCounts, borderRadius: 7 }] },
            options: { plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true, ticks: { precision: 0 } } } }
        });
    }
})();
</script>

<script>
(function(){
    const mapEl = document.getElementById('dashMap');
    if (!mapEl || typeof L === 'undefined') return;

    const dataSiswa = <?= json_encode($siswaMap, JSON_UNESCAPED_UNICODE) ?>;
    const map = L.map('dashMap', { center: [-7.771264, 113.213682], zoom: 12, fullscreenControl: true }).setView([-7.771264, 113.213682], 12);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { attribution: '&copy; OpenStreetMap' }).addTo(map);

    const userIcon = L.icon({
        iconUrl: 'https://static.vecteezy.com/system/resources/previews/050/757/191/large_2x/a-person-with-a-red-and-blue-figure-free-png.png',
        iconSize: [34, 34], iconAnchor: [17, 34], popupAnchor: [0, -34]
    });
    let markers = [];

    function clearMarkers(){ markers.forEach(m => map.removeLayer(m)); markers = []; }
    function renderMarkers(rows){
        clearMarkers();
        const bounds = [];
        rows.forEach(s => {
            const lat = parseFloat(s.lat), lng = parseFloat(s.lng);
            if (!lat || !lng) return;
            const fotoUrl = s.foto ? `../uploads/${s.foto}` : '../uploads/foto/default.png';
            const popup = `<img src="${fotoUrl}" style="width:100%;height:130px;border-radius:10px;object-fit:cover;margin-bottom:8px"><b>${s.nama || '-'}</b><br>NISN: ${s.nisn || '-'}<br>NIPD: ${s.nipd || '-'}<br>Tingkat: ${s.nama_tingkat || '-'}<br>Kelas: ${s.nama_kelas || '-'}<br><a href="student_view&id=${s.id}" target="_blank" class="btn btn-sm btn-primary mt-2 w-100" style="color:#fff">Lihat Profil</a><a href="https://www.google.com/maps?q=${lat},${lng}" target="_blank" class="btn btn-sm btn-success mt-1 w-100" style="color:#fff">Google Maps</a>`;
            const marker = L.marker([lat,lng], {icon:userIcon}).addTo(map).bindPopup(popup);
            markers.push(marker); bounds.push([lat,lng]);
        });
        if (bounds.length > 1) map.fitBounds(bounds, { padding: [25,25] });
    }
    function applyFilters(){
        const keyword = (document.getElementById('searchInput')?.value || '').toLowerCase();
        const kelas = document.getElementById('filterKelas')?.value || '';
        const tingkat = document.getElementById('filterTingkat')?.value || '';
        const filtered = dataSiswa.filter(s => {
            const text = `${s.nama || ''} ${s.nisn || ''}`.toLowerCase();
            return text.includes(keyword) && (kelas === '' || s.nama_kelas === kelas) && (tingkat === '' || s.nama_tingkat === tingkat);
        });
        renderMarkers(filtered);
    }
    document.getElementById('searchInput')?.addEventListener('input', applyFilters);
    document.getElementById('filterKelas')?.addEventListener('change', applyFilters);
    document.getElementById('filterTingkat')?.addEventListener('change', applyFilters);
    renderMarkers(dataSiswa);

    const fullscreenBtn = document.getElementById('fullscreenButton');
    const mapContainer = document.getElementById('mapContainer');
    fullscreenBtn?.addEventListener('click', () => {
        if (!document.fullscreenElement) {
            mapContainer.requestFullscreen?.();
            fullscreenBtn.textContent = 'Exit Fullscreen';
        } else {
            document.exitFullscreen?.();
            fullscreenBtn.textContent = 'Fullscreen';
        }
        setTimeout(() => map.invalidateSize(), 300);
    });
    document.addEventListener('fullscreenchange', () => setTimeout(() => map.invalidateSize(), 300));
})();
</script>
