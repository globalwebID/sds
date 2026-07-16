<?php
require dirname(__DIR__, 3) . '/db.php';

function e($value) { return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); }
function validPrintToken($id, $nisn, $token) {
    $secret = (string)sds_config('security.print_secret', '');
    if ($secret === '') return false;
    $token = strtolower(trim((string)$token));
    if (!preg_match('/^[a-f0-9]{64}$/', $token)) return false;
    $expected = hash_hmac('sha256', 'print-v2|' . (int)$id, $secret);
    if (hash_equals($expected, $token)) return true;
    // Kompatibilitas tautan v1 selama NISN belum berubah.
    $legacy = hash_hmac('sha256', (int)$id . '|' . (string)$nisn, $secret);
    return hash_equals($legacy, $token);
}
function rawv($arr, $key) { return trim((string)($arr[$key] ?? '')); }
function isDeadJob($job) {
    $job = strtoupper(trim((string)$job));
    return $job !== '' && (strpos($job, 'MENINGGAL') !== false || strpos($job, 'WAFAT') !== false);
}

function ensureTtdTable($conn) {
    $conn->query("CREATE TABLE IF NOT EXISTS cetak_ttd_daftar_ulang (
        id INT AUTO_INCREMENT PRIMARY KEY,
        siswa_id INT NOT NULL,
        ttd_mode VARCHAR(20) NOT NULL DEFAULT 'auto',
        nama_ttd VARCHAR(150) DEFAULT NULL,
        hubungan_ttd VARCHAR(80) DEFAULT NULL,
        hp_ttd VARCHAR(50) DEFAULT NULL,
        alamat_sama_siswa TINYINT(1) NOT NULL DEFAULT 1,
        alamat_ttd TEXT DEFAULT NULL,
        updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY uniq_siswa_id (siswa_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
}
function cleanTtdParam($value, $maxLen = 120) {
    $value = trim((string)$value);
    $value = preg_replace('/\s+/', ' ', $value);
    if (function_exists('mb_substr')) return mb_substr($value, 0, $maxLen, 'UTF-8');
    return substr($value, 0, $maxLen);
}
function loadSavedTtd($conn, $siswaId) {
    ensureTtdTable($conn);
    $stmt = $conn->prepare("SELECT * FROM cetak_ttd_daftar_ulang WHERE siswa_id = ? LIMIT 1");
    $stmt->bind_param('i', $siswaId);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return $row ?: null;
}
function saveTtdChoice($conn, $siswaId, $post) {
    ensureTtdTable($conn);
    $mode = strtolower(cleanTtdParam($post['ttd'] ?? 'auto', 20));
    $allowed = ['auto','ayah','ibu','wali','lainnya'];
    if (!in_array($mode, $allowed, true)) $mode = 'auto';

    $nama = cleanTtdParam($post['nama_ttd'] ?? '', 150);
    $hubungan = cleanTtdParam($post['hubungan_ttd'] ?? '', 80);
    $hp = cleanTtdParam($post['hp_ttd'] ?? '', 50);
    $alamatSama = ((string)($post['alamat_sama_siswa'] ?? '1') === '1') ? 1 : 0;
    $alamat = trim((string)($post['alamat_ttd'] ?? ''));
    if (function_exists('mb_substr')) $alamat = mb_substr($alamat, 0, 500, 'UTF-8');
    else $alamat = substr($alamat, 0, 500);

    if ($mode !== 'lainnya') {
        $nama = '';
        $hubungan = '';
        $hp = '';
        $alamatSama = 1;
        $alamat = '';
    }

    $stmt = $conn->prepare("
        INSERT INTO cetak_ttd_daftar_ulang
            (siswa_id, ttd_mode, nama_ttd, hubungan_ttd, hp_ttd, alamat_sama_siswa, alamat_ttd)
        VALUES (?, ?, ?, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE
            ttd_mode = VALUES(ttd_mode),
            nama_ttd = VALUES(nama_ttd),
            hubungan_ttd = VALUES(hubungan_ttd),
            hp_ttd = VALUES(hp_ttd),
            alamat_sama_siswa = VALUES(alamat_sama_siswa),
            alamat_ttd = VALUES(alamat_ttd),
            updated_at = CURRENT_TIMESTAMP
    ");
    $stmt->bind_param('issssis', $siswaId, $mode, $nama, $hubungan, $hp, $alamatSama, $alamat);
    $ok = $stmt->execute();
    $stmt->close();
    return $ok;
}

function pickDefaultSigner($s) {
    if (!isDeadJob($s['pekerjaan_ayah'] ?? '') && rawv($s, 'nama_ayah') !== '') return ['mode' => 'ayah', 'nama' => rawv($s, 'nama_ayah')];
    if (!isDeadJob($s['pekerjaan_ibu'] ?? '') && rawv($s, 'nama_ibu') !== '') return ['mode' => 'ibu', 'nama' => rawv($s, 'nama_ibu')];
    if (rawv($s, 'nama_wali') !== '') return ['mode' => 'wali', 'nama' => rawv($s, 'nama_wali')];
    return ['mode' => 'auto', 'nama' => rawv($s, 'nama_ayah') ?: (rawv($s, 'nama_ibu') ?: '-')];
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$token = (string)($_GET['token'] ?? '');
if ($id <= 0 || $token === '') {
    http_response_code(403);
    die('Akses cetak tidak valid.');
}

$tahunAjaranAktif = (string)$tahunAjaran;

$sql = "
    SELECT ps.*, k.nama_kelas, j.nama_jurusan
    FROM pendaftaran_siswa ps
    LEFT JOIN siswa_kelas sk ON sk.siswa_id = ps.id AND BINARY sk.tahun_ajaran = BINARY ?
    LEFT JOIN kelas k ON k.id = COALESCE(sk.kelas_id, ps.kelas_id)
    LEFT JOIN jurusan j ON j.id = COALESCE(k.jurusan_id, ps.jurusan_id)
    WHERE ps.id = ?
    LIMIT 1
";
$stmt = $conn->prepare($sql);
$stmt->bind_param('si', $tahunAjaranAktif, $id);
$stmt->execute();
$student = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$student) {
    http_response_code(404);
    die('Data siswa tidak ditemukan. Muat ulang halaman progres sebelum mencetak.');
}
if (!validPrintToken($id, $student['nisn'] ?? '', $token)) {
    http_response_code(403);
    die('Token cetak tidak valid. Tutup halaman ini, muat ulang halaman progres, lalu klik Cetak Berkas kembali.');
}

if (isset($_GET['action']) && $_GET['action'] === 'save_ttd') {
    header('Content-Type: application/json; charset=utf-8');
    $ok = saveTtdChoice($conn, $id, $_POST);
    echo json_encode(['success' => (bool)$ok]);
    exit;
}

$defaultSigner = pickDefaultSigner($student);
$savedSigner = loadSavedTtd($conn, $id);
$currentTtdMode = $savedSigner['ttd_mode'] ?? $defaultSigner['mode'];
if (!in_array($currentTtdMode, ['auto','ayah','ibu','wali','lainnya'], true)) {
    $currentTtdMode = $defaultSigner['mode'];
}
$savedNamaTtd = $savedSigner['nama_ttd'] ?? '';
$savedHubunganTtd = $savedSigner['hubungan_ttd'] ?? '';
$savedHpTtd = $savedSigner['hp_ttd'] ?? '';
$savedAlamatSamaSiswa = isset($savedSigner['alamat_sama_siswa']) ? (int)$savedSigner['alamat_sama_siswa'] : 1;
$savedAlamatTtd = $savedSigner['alamat_ttd'] ?? '';
$basePrint = 'cetak_dokumen_daftar_ulang.php';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cetak Berkas Daftar Ulang - <?= e($student['nama_lengkap'] ?? '') ?></title>
    <style>
        * { box-sizing: border-box; }
        body { margin:0; font-family: Arial, sans-serif; background:#eef3fb; color:#1f2937; }
        .wrap { max-width: 1040px; margin: 0 auto; padding: 28px 16px 60px; }
        .card { background:#fff; border:1px solid #e5edf7; border-radius:18px; box-shadow:0 12px 32px rgba(15,79,179,.08); overflow:hidden; }
        .head { background:linear-gradient(135deg,#0f5ed7,#1a73e8); color:#fff; padding:24px 28px; }
        .head h1 { margin:0 0 8px; font-size:26px; }
        .head p { margin:0; opacity:.9; }
        .body { padding:24px 28px; }
        .student { display:grid; grid-template-columns: repeat(3,1fr); gap:12px; margin-bottom:18px; }
        .info { background:#f8fbff; border:1px solid #e5edf7; border-radius:14px; padding:12px; }
        .info small { display:block; color:#64748b; font-weight:bold; margin-bottom:4px; text-transform:uppercase; font-size:11px; }
        .info strong { display:block; font-size:15px; }
        .sign-box { background:#f8fbff; border:1px solid #dbe7f6; border-radius:16px; padding:16px; margin:0 0 20px; }
        .sign-box h2 { margin:0 0 6px; font-size:17px; color:#0f4fb3; }
        .sign-box p { margin:0 0 12px; color:#64748b; font-size:13px; line-height:1.45; }
        .sign-options { display:grid; grid-template-columns: repeat(4,1fr); gap:10px; }
        .radio-card { border:1px solid #dbe7f6; border-radius:12px; background:#fff; padding:11px; cursor:pointer; min-height:68px; }
        .radio-card input { margin-right:6px; }
        .radio-card b { display:block; margin-bottom:4px; color:#1e293b; }
        .radio-card span { display:block; font-size:12px; color:#64748b; word-break:break-word; }
        .other-row { display:none; grid-template-columns:1fr 1fr; gap:10px; margin-top:12px; }
        .other-row.show { display:grid; }
        .other-row .wide { grid-column:1 / -1; }
        .check-row { grid-column:1 / -1; display:flex; align-items:center; gap:8px; padding:9px 10px; background:#fff; border:1px dashed #cbd5e1; border-radius:10px; color:#334155; font-size:14px; }
        .check-row input { width:16px; height:16px; }
        .hidden-field { display:none !important; }
        .form-control { width:100%; border:1px solid #cbd5e1; border-radius:10px; padding:10px 12px; font-size:14px; background:#fff; }
        .docs { display:grid; grid-template-columns: repeat(2,1fr); gap:14px; }
        .doc { border:1px solid #dbe7f6; border-radius:16px; padding:16px; background:#fff; }
        .doc h3 { margin:0 0 8px; font-size:17px; color:#0f4fb3; }
        .doc p { margin:0 0 12px; color:#64748b; font-size:13px; line-height:1.45; }
        .btn { display:inline-flex; align-items:center; justify-content:center; gap:7px; min-height:38px; padding:9px 14px; background:#1a73e8; color:#fff; border-radius:10px; text-decoration:none; font-weight:bold; font-size:13px; border:0; cursor:pointer; }
        .btn.secondary { background:#eef3fb; color:#1e293b; }
        .actions { display:flex; gap:10px; flex-wrap:wrap; margin-top:22px; padding-top:18px; border-top:1px solid #e5edf7; }
        .note { margin-top:18px; padding:13px 14px; border-radius:13px; background:#fff8e7; border:1px solid #fde68a; color:#7c4a03; font-size:13px; line-height:1.45; }
        @media (max-width: 860px) { .student,.docs,.sign-options { grid-template-columns:1fr; } .other-row { grid-template-columns:1fr; } .head h1 { font-size:22px; } }
    </style>
</head>
<body>
<div class="wrap">
    <div class="card">
        <div class="head">
            <h1>Berkas Cetak Daftar Ulang</h1>
            <p>Pilih penanda tangan, lalu cetak dokumen yang dibutuhkan tanpa login admin.</p>
        </div>
        <div class="body">
            <div class="student">
                <div class="info"><small>Nama Siswa</small><strong><?= e($student['nama_lengkap'] ?? '-') ?></strong></div>
                <div class="info"><small>NISN</small><strong><?= e($student['nisn'] ?? '-') ?></strong></div>
                <div class="info"><small>Konsentrasi Keahlian</small><strong><?= e($student['nama_jurusan'] ?? '-') ?></strong></div>
            </div>

            <div class="sign-box">
                <h2>Orang Tua/Wali Yang bertanda tangan</h2>
                <p>Pilih siapa yang menandatangani berkas. Default mengikuti data formulir: Ayah → Ibu → Wali. Opsi Pengantar digunakan jika siswa diantar oleh saudara/keluarga lain.</p>
                <div class="sign-options">
                    <label class="radio-card">
                        <input type="radio" name="ttd" value="ayah" <?= $currentTtdMode === 'ayah' ? 'checked' : '' ?>>
                        <b>Ayah</b>
                        <span><?= e($student['nama_ayah'] ?: 'Nama ayah belum diisi') ?></span>
                    </label>
                    <label class="radio-card">
                        <input type="radio" name="ttd" value="ibu" <?= $currentTtdMode === 'ibu' ? 'checked' : '' ?>>
                        <b>Ibu</b>
                        <span><?= e($student['nama_ibu'] ?: 'Nama ibu belum diisi') ?></span>
                    </label>
                    <label class="radio-card">
                        <input type="radio" name="ttd" value="wali" <?= $currentTtdMode === 'wali' ? 'checked' : '' ?>>
                        <b>Wali</b>
                        <span><?= e($student['nama_wali'] ?: 'Nama wali belum diisi') ?></span>
                    </label>
                    <label class="radio-card">
                        <input type="radio" name="ttd" value="lainnya" <?= $currentTtdMode === 'lainnya' ? 'checked' : '' ?>>
                        <b>Pengantar Lainnya</b>
                        <span>Kakak, paman, bibi, saudara, atau pengantar lain</span>
                    </label>
                </div>
                <div class="other-row" id="otherRow">
                    <input class="form-control" type="text" id="namaTtd" placeholder="Nama Pengantar" value="<?= e($savedNamaTtd) ?>">
                    <input class="form-control" type="text" id="hubunganTtd" placeholder="Hubungan dengan siswa, contoh: Paman" value="<?= e($savedHubunganTtd) ?>">
                    <input class="form-control" type="text" id="hpTtd" placeholder="No. HP/WA Pengantar" value="<?= e($savedHpTtd) ?>">
                    <label class="check-row">
                        <input type="checkbox" id="alamatSamaSiswa" <?= $savedAlamatSamaSiswa ? 'checked' : '' ?>>
                        <span>Alamat pengantar sama dengan alamat siswa</span>
                    </label>
                    <textarea class="form-control wide hidden-field" id="alamatTtd" rows="3" placeholder="Alamat Pengantar"><?= e($savedAlamatTtd) ?></textarea>
                </div>
            </div>

            <div class="docs">
                <div class="doc"><h3>Biodata Siswa</h3><p>Ringkasan data peserta didik, alamat, orang tua, dan data pendukung.</p><a class="btn print-link" target="_blank" data-jenis="biodata" href="#">Cetak Biodata</a></div>
                <div class="doc"><h3>Surat Pernyataan Peserta Didik</h3><p>Pernyataan siswa untuk mematuhi tata tertib dan ketentuan sekolah.</p><a class="btn print-link" target="_blank" data-jenis="pernyataan_siswa" href="#">Cetak Pernyataan Siswa</a></div>
                <div class="doc"><h3>Surat Pernyataan Orang Tua</h3><p>Pernyataan orang tua/wali/pengantar terkait izin dan tanggung jawab siswa.</p><a class="btn print-link" target="_blank" data-jenis="pernyataan_ortu" href="#">Cetak Pernyataan Ortu</a></div>
                <div class="doc"><h3>Tujuan & Komitmen Belajar</h3><p>Dokumen komitmen tujuan setelah lulus; beberapa bagian bisa diisi manual.</p><a class="btn print-link" target="_blank" data-jenis="komitmen" href="#">Cetak Komitmen</a></div>
                <div class="doc"><h3>Pernyataan Kendaraan</h3><p>Opsional untuk siswa yang membawa kendaraan ke sekolah.</p><a class="btn print-link" target="_blank" data-jenis="kendaraan" href="#">Cetak Kendaraan</a></div>
                <div class="doc"><h3>Cetak Semua</h3><p>Menggabungkan semua dokumen dalam satu tampilan cetak dengan pemisah halaman.</p><a class="btn print-link" target="_blank" data-jenis="semua" href="#">Cetak Semua</a></div>
            </div>

            <div class="actions">
                <a class="btn secondary" href="formulir?tab=formulir">Input Siswa Baru Lagi</a>
                <a class="btn secondary" href="formulir?tab=progress">Lihat Progress</a>
            </div>
            <div class="note"><b>Catatan:</b> Pilihan penanda tangan hanya untuk kebutuhan cetak dan tidak mengubah data siswa di database.</div>
        </div>
    </div>
</div>
<script>
(function(){
    const id = <?= (int)$id ?>;
    const token = <?= json_encode($token) ?>;
    const basePrint = <?= json_encode($basePrint) ?>;
    const saveUrl = 'cetak_daftar_ulang.php?action=save_ttd&id=' + encodeURIComponent(id) + '&token=' + encodeURIComponent(token);
    const radios = document.querySelectorAll('input[name="ttd"]');
    const otherRow = document.getElementById('otherRow');
    const namaTtd = document.getElementById('namaTtd');
    const hubunganTtd = document.getElementById('hubunganTtd');
    const hpTtd = document.getElementById('hpTtd');
    const alamatSamaSiswa = document.getElementById('alamatSamaSiswa');
    const alamatTtd = document.getElementById('alamatTtd');
    const links = document.querySelectorAll('.print-link');

    function selectedTtd(){
        const checked = document.querySelector('input[name="ttd"]:checked');
        return checked ? checked.value : 'auto';
    }
    function updateOther(){
        const isOther = selectedTtd() === 'lainnya';
        otherRow.classList.toggle('show', isOther);
        alamatTtd.classList.toggle('hidden-field', alamatSamaSiswa.checked);
    }
    function buildUrl(jenis){
        const params = new URLSearchParams();
        params.set('id', id);
        params.set('token', token);
        params.set('jenis', jenis);
        params.set('ttd', selectedTtd());
        if (selectedTtd() === 'lainnya') {
            params.set('nama_ttd', namaTtd.value.trim());
            params.set('hubungan_ttd', hubunganTtd.value.trim());
            params.set('hp_ttd', hpTtd.value.trim());
            params.set('alamat_sama_siswa', alamatSamaSiswa.checked ? '1' : '0');
            if (!alamatSamaSiswa.checked) {
                params.set('alamat_ttd', alamatTtd.value.trim());
            }
        }
        return basePrint + '?' + params.toString();
    }
    function refreshLinks(){
        links.forEach(link => link.href = buildUrl(link.dataset.jenis || 'semua'));
    }
    function buildSaveData(){
        const data = new FormData();
        data.set('ttd', selectedTtd());
        data.set('nama_ttd', namaTtd.value.trim());
        data.set('hubungan_ttd', hubunganTtd.value.trim());
        data.set('hp_ttd', hpTtd.value.trim());
        data.set('alamat_sama_siswa', alamatSamaSiswa.checked ? '1' : '0');
        data.set('alamat_ttd', alamatTtd.value.trim());
        return data;
    }
    async function saveChoice(){
        try {
            const res = await fetch(saveUrl, { method: 'POST', body: buildSaveData(), credentials: 'same-origin' });
            return res.ok;
        } catch (err) {
            return false;
        }
    }

    radios.forEach(radio => radio.addEventListener('change', function(){ updateOther(); refreshLinks(); }));
    [namaTtd, hubunganTtd, hpTtd, alamatTtd].forEach(input => input.addEventListener('input', refreshLinks));
    alamatSamaSiswa.addEventListener('change', function(){ updateOther(); refreshLinks(); });
    links.forEach(link => link.addEventListener('click', async function(e){
        e.preventDefault();
        if (selectedTtd() === 'lainnya' && namaTtd.value.trim() === '') {
            alert('Isi Nama Pengantar terlebih dahulu.');
            namaTtd.focus();
            return false;
        }
        if (selectedTtd() === 'lainnya' && !alamatSamaSiswa.checked && alamatTtd.value.trim() === '') {
            alert('Isi Alamat Pengantar atau centang alamat sama dengan siswa.');
            alamatTtd.focus();
            return false;
        }
        refreshLinks();
        await saveChoice();
        window.open(buildUrl(link.dataset.jenis || 'semua'), '_blank');
        return false;
    }));
    updateOther();
    refreshLinks();
})();
</script>
</body>
</html>
