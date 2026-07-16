<?php
require dirname(__DIR__, 3) . '/db.php';

function e($value) { return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); }
function v($arr, $key, $default='-') {
    $val = trim((string)($arr[$key] ?? ''));
    return $val !== '' ? $val : $default;
}
function rawv($arr, $key) { return trim((string)($arr[$key] ?? '')); }
function validPrintToken($id, $nisn, $token) {
    $secret = (string)sds_config('security.print_secret', '');
    if ($secret === '') return false;
    $token = strtolower(trim((string)$token));
    if (!preg_match('/^[a-f0-9]{64}$/', $token)) return false;
    $expected = hash_hmac('sha256', 'print-v2|' . (int)$id, $secret);
    if (hash_equals($expected, $token)) return true;
    $legacy = hash_hmac('sha256', (int)$id . '|' . (string)$nisn, $secret);
    return hash_equals($legacy, $token);
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
function loadSavedTtd($conn, $siswaId) {
    ensureTtdTable($conn);
    $stmt = $conn->prepare("SELECT * FROM cetak_ttd_daftar_ulang WHERE siswa_id = ? LIMIT 1");
    $stmt->bind_param('i', $siswaId);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return $row ?: null;
}
function applySavedTtdToRequest($saved) {
    if (!$saved || isset($_GET['ttd'])) return;
    $_GET['ttd'] = $saved['ttd_mode'] ?? 'auto';
    $_GET['nama_ttd'] = $saved['nama_ttd'] ?? '';
    $_GET['hubungan_ttd'] = $saved['hubungan_ttd'] ?? '';
    $_GET['hp_ttd'] = $saved['hp_ttd'] ?? '';
    $_GET['alamat_sama_siswa'] = (string)($saved['alamat_sama_siswa'] ?? '1');
    $_GET['alamat_ttd'] = $saved['alamat_ttd'] ?? '';
}

function tanggalIndo($date = null, $withTime = false) {
    $bulan = [
        1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
        'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
    ];

    if ($date === null || trim((string)$date) === '') {
        $ts = time();
    } else {
        $date = trim((string)$date);
        $ts = strtotime($date);
        if ($ts === false || $date === '0000-00-00' || $date === '0000-00-00 00:00:00') {
            return $date;
        }
    }

    $hasil = date('d', $ts) . ' ' . $bulan[(int)date('n', $ts)] . ' ' . date('Y', $ts);
    if ($withTime) {
        $hasil .= ' ' . date('H:i:s', $ts);
    }
    return $hasil;
}

function row($label, $value) {
    // Khusus BIODATA SISWA: tidak memakai titik dua (:), sesuai format tabel biodata.
    echo '<tr><td class="label">'.e($label).'</td><td>'.e($value).'</td></tr>';
}
function sectionTitle($title) {
    echo '<tr><th colspan="2" class="section">'.e($title).'</th></tr>';
}
function isDeadJob($job) {
    $job = strtoupper(trim((string)$job));
    return $job !== '' && (strpos($job, 'MENINGGAL') !== false || strpos($job, 'WAFAT') !== false);
}
function pickParentName($s) {
    if (!isDeadJob($s['pekerjaan_ayah'] ?? '') && rawv($s, 'nama_ayah') !== '') return rawv($s, 'nama_ayah');
    if (!isDeadJob($s['pekerjaan_ibu'] ?? '') && rawv($s, 'nama_ibu') !== '') return rawv($s, 'nama_ibu');
    if (rawv($s, 'nama_wali') !== '') return rawv($s, 'nama_wali');
    if (rawv($s, 'nama_ayah') !== '') return rawv($s, 'nama_ayah');
    if (rawv($s, 'nama_ibu') !== '') return rawv($s, 'nama_ibu');
    return '-';
}

function cleanPrintParam($value, $maxLen = 80) {
    $value = trim((string)$value);
    $value = preg_replace('/\s+/', ' ', $value);
    if (function_exists('mb_substr')) {
        return mb_substr($value, 0, $maxLen, 'UTF-8');
    }
    return substr($value, 0, $maxLen);
}
function buildAlamatLengkap($s) {
    // Alamat lengkap untuk surat/pernyataan:
    // Alamat..., Desa/Kelurahan ..., Kecamatan ..., KOTA/KABUPATEN, PROVINSI
    // Untuk Kota/Kabupaten dan Provinsi tidak diberi label lagi, karena tampilan/nama
    // wilayah sudah mengikuti isi database.
    $parts = [];

    $alamat = rawv($s, 'alamat');
    $desa = rawv($s, 'desa');
    $kecamatan = rawv($s, 'kecamatan');
    $kota = rawv($s, 'kota');
    $provinsi = rawv($s, 'provinsi');

    if ($alamat !== '') $parts[] = $alamat;
    if ($desa !== '') $parts[] = 'Desa/Kelurahan ' . $desa;
    if ($kecamatan !== '') $parts[] = 'Kecamatan ' . $kecamatan;
    if ($kota !== '') $parts[] = $kota;
    if ($provinsi !== '') $parts[] = $provinsi;

    return $parts ? implode(', ', $parts) : '-';
}
function pickSignerData($s) {
    $mode = strtolower(trim((string)($_GET['ttd'] ?? 'auto')));
    $allowed = ['auto','ayah','ibu','wali','lainnya'];
    if (!in_array($mode, $allowed, true)) $mode = 'auto';

    $alamatSiswa = buildAlamatLengkap($s);

    $data = [
        'mode' => $mode,
        'nama' => pickParentName($s),
        'hp' => v($s, 'nohp_ortu'),
        'alamat' => $alamatSiswa,
        'label' => 'Orang Tua/Wali',
        'hubungan' => '',
    ];

    if ($mode === 'ayah') {
        $nama = rawv($s, 'nama_ayah');
        if ($nama !== '') $data['nama'] = $nama;
        $data['hubungan'] = 'Ayah';
    } elseif ($mode === 'ibu') {
        $nama = rawv($s, 'nama_ibu');
        if ($nama !== '') $data['nama'] = $nama;
        $data['hubungan'] = 'Ibu';
    } elseif ($mode === 'wali') {
        $nama = rawv($s, 'nama_wali');
        if ($nama !== '') $data['nama'] = $nama;
        $data['hubungan'] = 'Wali';
    } elseif ($mode === 'lainnya') {
        $namaManual = cleanPrintParam($_GET['nama_ttd'] ?? '', 90);
        $hubManual = cleanPrintParam($_GET['hubungan_ttd'] ?? '', 50);
        $hpManual = cleanPrintParam($_GET['hp_ttd'] ?? '', 30);
        $alamatManual = cleanPrintParam($_GET['alamat_ttd'] ?? '', 220);
        $alamatSamaSiswa = (string)($_GET['alamat_sama_siswa'] ?? '') === '1';

        if ($namaManual !== '') $data['nama'] = $namaManual;
        if ($hpManual !== '') $data['hp'] = $hpManual;
        $data['alamat'] = $alamatSamaSiswa || $alamatManual === '' ? $alamatSiswa : $alamatManual;
        $data['hubungan'] = $hubManual !== '' ? $hubManual : 'Pengantar';
        $data['label'] = 'Orang Tua/Wali';
    } else {
        $data['hubungan'] = 'Orang Tua/Wali';
    }

    if ($data['nama'] === '') $data['nama'] = '-';
    return $data;
}

function hasWaliData($s) {
    $fields = ['nama_wali','nik_wali','tahun_lahir_wali','pendidikan_wali','pekerjaan_wali','penghasilan_wali'];
    foreach ($fields as $field) {
        $value = rawv($s, $field);
        if ($value !== '' && $value !== '-- Pilih Pendidikan --' && $value !== '-- Pilih Pekerjaan --' && $value !== '-- Pilih Penghasilan --') {
            return true;
        }
    }
    return false;
}

function hasUploadedScan($s, $key) {
    $value = rawv($s, $key);
    return $value !== '' && $value !== '-' && strtolower($value) !== 'null';
}
function statusScan($s, $key) {
    return hasUploadedScan($s, $key) ? 'Ada' : 'Tidak Ada';
}
function hasFilePendukungData($s) {
    $fields = [
        'nomor_kip','nomor_kps','nomor_pkh','nomor_kks','nomor_kis',
        'file_kip','file_kps','file_pkh','file_kks','file_kis'
    ];
    foreach ($fields as $field) {
        if (rawv($s, $field) !== '') return true;
    }
    return false;
}
function rowFilePendukungIfNeeded($s, $nomorKey, $fileKey, $label) {
    // File pendukung ditampilkan bila nomor bantuan diisi, atau file scan-nya memang sudah diupload.
    if (rawv($s, $nomorKey) !== '' || hasUploadedScan($s, $fileKey)) {
        row($label, statusScan($s, $fileKey));
    }
}
function identRow($label, $value) {
    echo '<tr><td class="ident-label">'.e($label).'</td><td class="colon">:</td><td>'.e($value).'</td></tr>';
}
function kopSurat($pengaturan) {
    // Kop surat utama diambil dari Pengaturan Admin: pengaturan.kop_surat.
    // Contoh file: /sds/uploads/logo/kop_1748186070.png
    $kop = trim((string)($pengaturan['kop_surat'] ?? ''));
    $kopPath = $kop !== '' ? sds_root_path('uploads/logo/' . $kop) : '';
    $kopSrc = $kopPath !== '' ? 'uploads/logo/' . e($kop) : '';

    // Fallback jika kop_surat belum diisi: gunakan logo + teks statis.
    $logo = trim((string)($pengaturan['logo'] ?? ''));
    $logoSrc = $logo !== '' ? 'uploads/logo/' . e($logo) : '';
    ?>
    <?php if ($kopPath !== '' && file_exists($kopPath)): ?>
        <div class="kop-image-wrap">
            <img src="<?= $kopSrc ?>" alt="Kop Surat" class="kop-image">
        </div>
    <?php else: ?>
        <div class="kop-surat">
            <div class="kop-logo">
                <?php if ($logoSrc !== ''): ?>
                    <img src="<?= $logoSrc ?>" alt="Logo Sekolah">
                <?php endif; ?>
            </div>
            <div class="kop-text">
                <div class="kop-line-1">PEMERINTAH PROVINSI JAWA TIMUR</div>
                <div class="kop-line-2">DINAS&nbsp;&nbsp;&nbsp; PENDIDIKAN</div>
                <div class="kop-school">SMK NEGERI 1 PROBOLINGGO</div>
                <div class="kop-address">Jalan Mastrip No. 357 Telp. / Fax. (0335) 421121 Probolinggo (67239)</div>
                <div class="kop-address">Laman: smkn1probolinggo.sch.id Pos-el: smkn1_probolinggo@yahoo.co.id</div>
            </div>
        </div>
        <div class="kop-double-line"></div>
    <?php endif; ?>
    <?php
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$token = (string)($_GET['token'] ?? '');
$jenis = (string)($_GET['jenis'] ?? 'semua');
$allowed = ['biodata','pernyataan_siswa','pernyataan_ortu','komitmen','kendaraan','semua'];
if (!in_array($jenis, $allowed, true)) $jenis = 'semua';

$pengaturan = [];
$resPengaturan = $conn->query("SELECT * FROM pengaturan LIMIT 1");
if ($resPengaturan && $resPengaturan->num_rows > 0) {
    $pengaturan = $resPengaturan->fetch_assoc();
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
$s = $stmt->get_result()->fetch_assoc();
$stmt->close();
if (!$s || !validPrintToken($id, $s['nisn'] ?? '', $token)) { http_response_code(403); die('Akses cetak tidak valid.'); }

applySavedTtdToRequest(loadSavedTtd($conn, $id));
$ttdData = pickSignerData($s);
$namaOrtu = $ttdData['nama'];
$hpOrtu = $ttdData['hp'];
$alamatTtd = $ttdData['alamat'];
$labelTtd = $ttdData['label'];
$hubunganTtd = $ttdData['hubungan'];
$alamatLengkap = buildAlamatLengkap($s);
$tempatTanggal = v($s,'tempat_lahir') . ', ' . tanggalIndo(rawv($s,'tanggal_lahir'));
$tanggalCetak = 'Probolinggo, ' . tanggalIndo();

function doc_biodata($s, $alamatLengkap, $pengaturan) { 
    $waliTerisi = hasWaliData($s);
    ?>
<section class="page biodata">
    <?php kopSurat($pengaturan); ?>
    <h1>BIODATA SISWA</h1>
    <div class="pill"><?= e(v($s,'nama_jurusan')) ?></div>
    <table class="data-table">
        <?php sectionTitle('DATA PRIBADI');
        // Data Pribadi disesuaikan dengan input pada step Biodata Murid Baru.
        // NIPD sengaja tidak ditampilkan karena siswa masih proses daftar ulang.
        row('Nama Lengkap', v($s,'nama_lengkap'));
        row('Email', v($s,'email'));
        row('NISN', v($s,'nisn'));
        // Kelas sengaja tidak ditampilkan pada cetak biodata karena masih proses daftar ulang.
        row('Tahun Ajaran Masuk', v($s,'tahun_ajaran'));
        row('Konsentrasi Keahlian', v($s,'nama_jurusan'));
        row('Sekolah Asal', v($s,'sekolah_asal'));
        row('No. Ijazah SMP', v($s,'nomor_ijazah'));
        row('Jenis Kelamin', v($s,'jenis_kelamin'));
        row('Tempat Lahir', v($s,'tempat_lahir'));
        row('Tanggal Lahir', tanggalIndo(rawv($s,'tanggal_lahir')));
        row('No. KK', v($s,'no_kk'));
        row('No. NIK', v($s,'nik'));
        row('No. Registrasi Akta Lahir', v($s,'no_registrasi_akta'));
        row('Jenis Kebutuhan Khusus', v($s,'kebutuhan_khusus','Tidak'));
        row('Agama', v($s,'agama'));

        sectionTitle('DATA ALAMAT'); row('Alamat', v($s,'alamat')); row('Desa/Kelurahan', v($s,'desa')); row('Kecamatan', v($s,'kecamatan')); row('Kabupaten/Kota', v($s,'kota')); row('Provinsi', v($s,'provinsi')); row('Koordinat', v($s,'latitude') . ', ' . v($s,'longitude')); row('Tempat Tinggal', v($s,'tempat_tinggal')); row('Moda Transportasi', v($s,'moda_transportasi'));
        sectionTitle('DATA FISIK DAN MINAT'); row('Anak ke', v($s,'anak_ke')); row('Jumlah Saudara Kandung', v($s,'jumlah_saudara_kandung')); row('Tinggi Badan (cm)', v($s,'tinggi_badan')); row('Berat Badan (kg)', v($s,'berat_badan')); row('Hobi', v($s,'hobi')); row('Cita-cita', v($s,'cita_cita'));
        sectionTitle('DATA ORANG TUA - AYAH'); row('Nama Ayah', v($s,'nama_ayah')); row('NIK Ayah', v($s,'nik_ayah')); row('Tahun Lahir Ayah', v($s,'tahun_lahir_ayah')); row('Pendidikan Ayah', v($s,'pendidikan_ayah')); row('Pekerjaan Ayah', v($s,'pekerjaan_ayah')); row('Penghasilan Ayah', v($s,'penghasilan_ayah'));
        sectionTitle('DATA ORANG TUA - IBU'); row('Nama Ibu', v($s,'nama_ibu')); row('NIK Ibu', v($s,'nik_ibu')); row('Tahun Lahir Ibu', v($s,'tahun_lahir_ibu')); row('Pendidikan Ibu', v($s,'pendidikan_ibu')); row('Pekerjaan Ibu', v($s,'pekerjaan_ibu')); row('Penghasilan Ibu', v($s,'penghasilan_ibu'));
        if ($waliTerisi) {
            sectionTitle('DATA WALI'); row('Nama Wali', v($s,'nama_wali')); row('NIK Wali', v($s,'nik_wali')); row('Tahun Lahir Wali', v($s,'tahun_lahir_wali')); row('Pendidikan Wali', v($s,'pendidikan_wali')); row('Pekerjaan Wali', v($s,'pekerjaan_wali')); row('Penghasilan Wali', v($s,'penghasilan_wali'));
        }
        sectionTitle('PROGRAM BANTUAN'); row('Nomor KIP', v($s,'nomor_kip')); row('Nomor KPS', v($s,'nomor_kps')); row('Nomor PKH', v($s,'nomor_pkh')); row('Nomor KKS', v($s,'nomor_kks')); row('Nomor KIS', v($s,'nomor_kis'));

        sectionTitle('KETERANGAN FILE UTAMA');
        row('Foto Siswa', statusScan($s,'foto'));
        row('Kartu Keluarga', statusScan($s,'file_kk'));
        row('Ijazah SMP', statusScan($s,'file_ijazah'));
        row('Akta Kelahiran', statusScan($s,'file_akta'));

        if (hasFilePendukungData($s)) {
            sectionTitle('KETERANGAN FILE PENDUKUNG');
            rowFilePendukungIfNeeded($s, 'nomor_kip', 'file_kip', 'Scan KIP');
            rowFilePendukungIfNeeded($s, 'nomor_kps', 'file_kps', 'Scan KPS');
            rowFilePendukungIfNeeded($s, 'nomor_pkh', 'file_pkh', 'Scan PKH');
            rowFilePendukungIfNeeded($s, 'nomor_kks', 'file_kks', 'Scan KKS');
            rowFilePendukungIfNeeded($s, 'nomor_kis', 'file_kis', 'Scan KIS');
        } ?>
    </table>
    <div class="doc-date">Dokumen ini dicetak pada: <?= e(tanggalIndo(null, true)) ?></div>
</section>
<?php }

function doc_siswa($s, $namaOrtu, $tanggalCetak, $tempatTanggal, $pengaturan, $labelTtd) { ?>
<section class="page letter">
    <?php kopSurat($pengaturan); ?>
    <h1>SURAT PERNYATAAN MURID BARU</h1>
    <p>Yang bertanda tangan di bawah ini saya:</p>
    <table class="identity-table">
        <?php identRow('Nama Lengkap', v($s,'nama_lengkap')); identRow('Tempat/Tanggal Lahir', $tempatTanggal); identRow('Jenis Kelamin', v($s,'jenis_kelamin')); identRow('Asal Sekolah', v($s,'sekolah_asal')); identRow('No. HP/WA', v($s,'nohp_siswa')); identRow('Konsentrasi Keahlian', v($s,'nama_jurusan')); ?>
    </table>
    <p>Dengan sungguh-sungguh dan penuh kesadaran, menyatakan bahwa selama menjadi siswa SMK Negeri 1 Probolinggo, saya akan:</p>
    <ol><li>Belajar dengan rajin dan bersemangat.</li><li>Mengikuti semua program pembelajaran yang menjadi ketentuan sekolah.</li><li>Berperilaku baik serta mematuhi tata tertib sekolah.</li><li>Bersedia tidak menikah selama menjadi siswa SMK Negeri 1 Probolinggo.</li><li>Menjaga nama baik sekolah, integritas, sarana, dan prasarana sekolah.</li><li>Bersedia ditempatkan PKL sesuai ketentuan sekolah.</li><li>Bersedia menerima sanksi apabila tidak mematuhi aturan sekolah.</li></ol>
    <p>Demikian pernyataan ini saya buat dengan sebenarnya, untuk dapat saya pertanggungjawabkan kebenarannya.</p>
    <?= signatures($namaOrtu, v($s,'nama_lengkap'), $tanggalCetak, true, $labelTtd) ?>
</section>
<?php }
function doc_ortu($s, $namaOrtu, $alamatTtd, $hpOrtu, $tanggalCetak, $pengaturan, $labelTtd, $hubunganTtd) { ?>
<section class="page letter">
    <?php kopSurat($pengaturan); ?>
    <h1>SURAT PERNYATAAN ORANG TUA<br>MURID BARU</h1>
    <p>Yang bertanda tangan di bawah ini:</p>
    <table class="identity-table"><?php identRow('Nama', $namaOrtu); if ($hubunganTtd !== '') identRow('Hubungan dengan Siswa', $hubunganTtd); identRow('Alamat', $alamatTtd); identRow('No. HP/WA', $hpOrtu); ?></table>
    <p>Selaku Orang Tua/Wali dari:</p>
    <table class="identity-table"><?php identRow('NISN', v($s,'nisn')); identRow('Nama', v($s,'nama_lengkap')); identRow('No. HP/WA', v($s,'nohp_siswa')); ?></table>
    <p>Dengan ini menyatakan bahwa:</p><ol><li>Memberikan izin dan memotivasi kepada anak untuk mengikuti seluruh kegiatan pembelajaran di sekolah.</li><li>Bersedia mematuhi semua peraturan dan tata tertib sekolah.</li><li>Bersedia bertanggung jawab atas segala tindakan anak selama mengikuti kegiatan di sekolah.</li><li>Memberikan izin kepada guru untuk melihat HP apabila diperlukan dalam penyelesaian masalah kedisiplinan siswa.</li></ol>
    <p>Demikian surat pernyataan ini saya buat dengan sebenar-benarnya dalam keadaan sadar dan tidak ada paksaan dari pihak manapun.</p><?= singleSignature($namaOrtu, $tanggalCetak, false, $labelTtd) ?>
</section>
<?php }
function doc_komitmen($s, $namaOrtu, $alamatLengkap, $tanggalCetak, $pengaturan, $labelTtd) { ?>
<section class="page letter">
    <?php kopSurat($pengaturan); ?>
    <h1>PERNYATAAN TUJUAN DAN KOMITMEN BELAJAR</h1>
    <p>Yang bertanda tangan di bawah ini:</p>
    <table class="identity-table"><?php identRow('NISN', v($s,'nisn')); identRow('Nama', v($s,'nama_lengkap')); identRow('No. HP/WA', v($s,'nohp_siswa')); identRow('Alamat', $alamatLengkap); ?></table>
    <p>Untuk memaksimalkan potensi diri dan fokus dalam mengikuti proses pembelajaran di SMK Negeri 1 Probolinggo, saya menetapkan tujuan setelah lulus dari sekolah ini, yaitu:</p>
    <table class="identity-table komitmen-manual-table">
        <tr>
            <td class="ident-label">Saya memilih untuk</td>
            <td class="colon">:</td>
            <td>
                <span class="check-option"><span class="check-box"></span> Bekerja</span>
                <span class="check-option"><span class="check-box"></span> Melanjutkan</span>
                <span class="check-option"><span class="check-box"></span> Wirausaha</span>
            </td>
        </tr>
        <tr>
            <td class="ident-label">Alasan saya</td>
            <td class="colon">:</td>
            <td class="manual-lines">
                <div class="write-line"></div>
                <div class="write-line"></div>
            </td>
        </tr>
    </table>
    <p>Saya menyadari bahwa pilihan ini merupakan bentuk komitmen terhadap masa depan, dan saya akan berusaha sungguh-sungguh dalam proses belajar untuk mewujudkan tujuan tersebut.</p>
    <p>Demikian pernyataan ini saya buat dengan sebenar-benarnya, dalam keadaan sadar dan tanpa paksaan dari pihak manapun. Pernyataan ini telah diketahui dan disetujui oleh orang tua/wali saya.</p><?= signatures($namaOrtu, v($s,'nama_lengkap'), $tanggalCetak, false, $labelTtd) ?>
</section>
<?php }
function doc_kendaraan($s, $namaOrtu, $alamatTtd, $hpOrtu, $tanggalCetak, $pengaturan, $labelTtd, $hubunganTtd) { ?>
<section class="page letter">
    <?php kopSurat($pengaturan); ?>
    <h1>SURAT PERNYATAAN<br>MEMBAWA KENDARAAN KE SEKOLAH</h1>
    <p>Yang bertanda tangan di bawah ini:</p>
    <table class="identity-table"><?php identRow('Nama', $namaOrtu); if ($hubunganTtd !== '') identRow('Hubungan dengan Siswa', $hubunganTtd); identRow('Alamat', $alamatTtd); identRow('No. HP/WA', $hpOrtu); ?></table>
    <p>Selaku Orang Tua/Wali murid siswa SMK Negeri 1 Probolinggo:</p>
    <table class="identity-table"><?php identRow('NISN', v($s,'nisn')); identRow('Nama', v($s,'nama_lengkap')); identRow('No. HP/WA', v($s,'nohp_siswa')); ?></table>
    <p>Dengan ini menyatakan:</p><ol><li>Memberikan izin putra/putri saya untuk menggunakan kendaraan bermotor/sepeda motor ke sekolah.</li><li>Memastikan kendaraan yang dipakai sesuai standar dan layak digunakan.</li><li>Memastikan putra/putri saya menggunakan helm.</li><li>Memastikan putra/putri saya pulang tepat waktu.</li><li>Bersedia mematuhi semua peraturan sekolah.</li><li>Memastikan putra/putri saya tidak parkir di luar sekolah.</li><li>Bersedia menerima sanksi jika terbukti melanggar peraturan sekolah.</li><li>Sekolah tidak bertanggung jawab atas kerusakan, kehilangan, atau kecelakaan di luar kegiatan sekolah.</li></ol>
    <p>Demikian surat pernyataan ini saya buat dengan sebenar-benarnya dalam keadaan sadar dan tidak ada paksaan dari pihak manapun.</p><?= singleSignature($namaOrtu, $tanggalCetak, true, $labelTtd) ?>
</section>
<?php }
function signatures($ortu, $siswa, $tanggal, $materai=false, $label='Orang Tua/Wali') { ob_start(); ?><div class="sign-grid"><div><p>Mengetahui,<br><?= e($label) ?></p><div class="sign-space"></div><strong><?= e($ortu) ?></strong></div><div><p><?= e($tanggal) ?><br>Yang membuat pernyataan</p><div class="sign-space"><?= $materai ? '<span class="materai">materai<br>Rp. 10.000,-</span>' : '' ?></div><strong><?= e($siswa) ?></strong></div></div><?php return ob_get_clean(); }
function singleSignature($nama, $tanggal, $materai=false, $label='Yang membuat pernyataan') { ob_start(); ?><div class="single-sign"><p><?= e($tanggal) ?><br><?= e($label) ?></p><div class="sign-space"><?= $materai ? '<span class="materai">materai<br>Rp. 10.000,-</span>' : '' ?></div><strong><?= e($nama) ?></strong></div><?php return ob_get_clean(); }
?>
<!DOCTYPE html><html lang="id"><head><meta charset="UTF-8"><title>Cetak Dokumen - <?= e(v($s,'nama_lengkap')) ?></title><style>
@page { size: A4; margin: 12mm 16mm 14mm 16mm; }
*{box-sizing:border-box}
body{font-family:"Times New Roman", Times, serif;color:#111;margin:0;background:#eee;font-size:12pt}
.toolbar{position:sticky;top:0;background:#fff;border-bottom:1px solid #ddd;padding:10px;text-align:center;z-index:9;font-family:Arial,sans-serif}
.toolbar button{background:#1a73e8;color:#fff;border:0;border-radius:7px;padding:9px 14px;font-weight:bold;cursor:pointer}
/* Area cetak dibuat konsisten dengan garis kop: kanan-kiri 16mm.
   Semua tabel, paragraf, dan identitas dipaksa berada di dalam lebar area ini. */
.page{width:210mm;min-height:297mm;margin:12px auto;background:#fff;padding:12mm 16mm 14mm;page-break-after:always;overflow:hidden}
.page:last-child{page-break-after:auto}
.print-boundary{width:100%;max-width:100%;overflow:hidden}
.kop-image-wrap{width:100%;max-width:100%;margin:0 0 12px;text-align:center;overflow:hidden}.kop-image{width:100%;max-width:100%;max-height:38mm;object-fit:contain;display:block}.kop-surat{display:grid;grid-template-columns:30mm 1fr;gap:8mm;align-items:center;margin-bottom:4px;width:100%;max-width:100%}
.kop-logo{text-align:center}.kop-logo img{width:26mm;height:26mm;object-fit:contain}
.kop-text{text-align:center;line-height:1.1}.kop-line-1,.kop-line-2{font-size:17pt;font-weight:bold}.kop-school{font-size:22pt;font-weight:bold;letter-spacing:.2px}.kop-address{font-size:11.5pt}.kop-double-line{border-top:3px solid #111;border-bottom:1px solid #111;height:5px;margin:4px 0 12px}
h1{text-align:center;color:#111;font-size:15pt;margin:0 0 8px;font-weight:bold;text-transform:uppercase}h2{text-align:center;font-size:12pt;margin:0 0 10px;font-weight:bold}.pill{display:table;margin:0 auto 12px;background:#02a7b3;color:#fff;border-radius:999px;padding:4px 12px;font-size:10pt;font-weight:bold}.data-table{width:100%;max-width:100%;border-collapse:collapse;font-size:10.5pt;table-layout:fixed;word-break:break-word}.data-table td,.data-table th{border:1px solid #ddd;padding:4px 7px}.data-table .section{background:#b8bdc2;color:#fff;text-align:left;border:0;padding:6px}.label{width:34%;background:#f7f7f7}.data-colon{width:18px;text-align:center;background:#f7f7f7;padding-left:0!important;padding-right:0!important;white-space:nowrap}.doc-date{font-size:10pt;margin-top:10px}.letter{font-size:12pt;line-height:1.45}.letter p{margin:8px 0}.letter ol{margin-top:6px;padding-left:50px}.identity-table{border-collapse: collapse;margin: 10px 0 0px 30px;width: 100%;max-width: 93%;font-size: 12pt;
    table-layout: fixed;word-break: break-word;}.identity-table td{padding:2px 0;vertical-align:top}.identity-table .ident-label{width:165px;white-space:nowrap}.identity-table .colon{width:18px;text-align:center;white-space:nowrap}.wide-identity td:last-child{border-bottom:1px solid #111;height:22px}.komitmen-manual-table{margin-top:8px}.komitmen-manual-table td{padding-top:4px;padding-bottom:4px}.check-option{display:inline-flex;align-items:center;margin-right:20px;white-space:nowrap}.check-box{display:inline-block;width:11px;height:11px;border:1.4px solid #111;margin-right:5px;vertical-align:middle}.manual-lines{padding-top:0!important}.write-line{height:22px;border-bottom:1px solid #111;width:100%}.write-line + .write-line{margin-top:7px}.sign-grid{display:grid;grid-template-columns:1fr 1fr;gap:30px;margin-top:24px;text-align:center}.single-sign{width:270px;margin:26px 0 0 auto;text-align:center}.sign-space{height:78px;display:flex;align-items:center;justify-content:center}.materai{border:1px dashed #777;padding:8px 12px;font-size:10pt;color:#555;margin-left: -75px;}@media print{body{background:#fff}.toolbar{display:none}.page{margin:0;width:auto;min-height:auto;padding:0;box-shadow:none;overflow:visible}.kop-surat,.kop-image-wrap{break-inside:avoid}.data-table,.identity-table{page-break-inside:auto}.data-table tr,.identity-table tr{page-break-inside:avoid}}
</style></head><body><div class="toolbar"><button onclick="window.print()">Cetak / Simpan PDF</button></div>
<?php
if ($jenis === 'semua' || $jenis === 'biodata') doc_biodata($s, $alamatLengkap, $pengaturan);
if ($jenis === 'semua' || $jenis === 'pernyataan_siswa') doc_siswa($s, $namaOrtu, $tanggalCetak, $tempatTanggal, $pengaturan, $labelTtd);
if ($jenis === 'semua' || $jenis === 'pernyataan_ortu') doc_ortu($s, $namaOrtu, $alamatTtd, $hpOrtu, $tanggalCetak, $pengaturan, $labelTtd, $hubunganTtd);
if ($jenis === 'semua' || $jenis === 'komitmen') doc_komitmen($s, $namaOrtu, $alamatLengkap, $tanggalCetak, $pengaturan, $labelTtd);
if ($jenis === 'semua' || $jenis === 'kendaraan') doc_kendaraan($s, $namaOrtu, $alamatTtd, $hpOrtu, $tanggalCetak, $pengaturan, $labelTtd, $hubunganTtd);
?>
<script>window.addEventListener('load', function(){ setTimeout(function(){ window.print(); }, 450); });</script></body></html>
