<?php
// edit_proses.php
// Handler untuk POST dari form edit (action="edit_proses")

include 'modul/fungsi_upload.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function post($k, $def = null) {
    return isset($_POST[$k]) ? $_POST[$k] : $def;
}

function redirect_back_with_error($msg, $id = null) {
    $_SESSION['error'] = $msg;
    if ($id) {
        header("Location: student_view?id=" . (int)$id);
    } else {
        header("Location: students");
    }
    exit;
}

function recalc_terisi(mysqli $conn, int $kelasId, string $tahunAjaran): void {
    // Hitung terisi berdasarkan siswa_kelas + status_aktif (lebih aman daripada +/- yang bisa drift)
    $sql = "
        SELECT COUNT(*) AS jml
        FROM siswa_kelas sk
        JOIN pendaftaran_siswa ps ON ps.id = sk.siswa_id
        WHERE sk.kelas_id = ?
          AND sk.tahun_ajaran = ?
          AND ps.status_aktif = '1'
    ";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("is", $kelasId, $tahunAjaran);
    $stmt->execute();
    $res = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    $jml = (int)($res['jml'] ?? 0);

    $up = $conn->prepare("UPDATE kelas SET terisi = ? WHERE id = ?");
    $up->bind_param("ii", $jml, $kelasId);
    $up->execute();
    $up->close();
}

$mode = post('mode', 'siswa');
$id   = (int) post('id', 0);

if ($id <= 0) {
    die("ID tidak valid.");
}

if ($mode === 'siswa') {

    // 1) Ambil input kelas
    $new_kelas_id = (int) post('kelas', 0);
    if ($new_kelas_id <= 0) {
        // Debug cepat kalau perlu:
        // die("Kelas tidak valid. POST[kelas]=" . var_export($_POST['kelas'] ?? null, true));
        die("Kelas tidak valid.");
    }

    // 2) Validasi kelas ada di DB
    $cek = $conn->prepare("SELECT id, tahun_ajaran FROM kelas WHERE id = ? LIMIT 1");
    $cek->bind_param("i", $new_kelas_id);
    $cek->execute();
    $kelasRow = $cek->get_result()->fetch_assoc();
    $cek->close();

    if (!$kelasRow) {
        die("Pilihan kelas tidak ditemukan.");
    }

    // 3) Ambil data lama (kelas + file)
    $stmt = $conn->prepare("
        SELECT kelas_id, nisn, tahun_ajaran, nama_lengkap, file_kip, file_kk, file_ijazah, provinsi, kota, kecamatan, desa, latitude, longitude
        FROM pendaftaran_siswa
        WHERE id = ?
        LIMIT 1
    ");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $old = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$old) {
        redirect_back_with_error("Data siswa tidak ditemukan.", $id);
    }

    $old_kelas_id = (int)($old['kelas_id'] ?? 0);

    // Tahun ajaran yang dipakai untuk sinkron rombel:
    // - utamakan dari POST (karena form kamu kirim tahun_ajaran)
    // - fallback ke data siswa di DB
    $tahunAjaran = (string) post('tahun_ajaran', $old['tahun_ajaran'] ?? '');
    $tahunAjaran = trim($tahunAjaran);

    // 4) Upload file (tetap)
    $nisn = (string) post('nisn', $old['nisn'] ?? '');
    $nisn = trim($nisn);
    $subfolder = $nisn ?: (string)$id;

    $old_kip    = $old['file_kip'] ?? null;
    $old_kk     = $old['file_kk'] ?? null;
    $old_ijazah = $old['file_ijazah'] ?? null;

    $file_kip    = uploadFile('file_kip',    ['pdf', 'jpg', 'jpeg', 'png'], 5, $subfolder, $old_kip);
    $file_kk     = uploadFile('file_kk',     ['pdf', 'jpg', 'jpeg', 'png'], 5, $subfolder, $old_kk);
    $file_ijazah = uploadFile('file_ijazah', ['pdf', 'jpg', 'jpeg', 'png'], 5, $subfolder, $old_ijazah);

    // 5) Modul existing kamu (tetap dipanggil)
    //    Pastikan modul ini mengisi array $data (field-field yang ingin diupdate)
    $data = [];
    include 'modul/edit_modul_siswa.php';
    include 'modul/edit_modul_validasi.php';


    // 6a) Pengaman alamat/koordinat:
    // Jika hidden wilayah kosong karena API wilayah gagal memuat/match, jangan kosongkan data lama.
    foreach (['provinsi', 'kota', 'kecamatan', 'desa'] as $lokasiField) {
        if (!isset($data[$lokasiField]) || trim((string)$data[$lokasiField]) === '') {
            $data[$lokasiField] = $old[$lokasiField] ?? '';
        }
    }

    // Jika koordinat tidak valid/0, pertahankan koordinat lama yang valid.
    $latPost = isset($data['latitude']) ? (float)$data['latitude'] : 0.0;
    $lngPost = isset($data['longitude']) ? (float)$data['longitude'] : 0.0;
    $latOld = isset($old['latitude']) ? (float)$old['latitude'] : 0.0;
    $lngOld = isset($old['longitude']) ? (float)$old['longitude'] : 0.0;
    if (($latPost == 0.0 || $lngPost == 0.0) && ($latOld != 0.0 && $lngOld != 0.0)) {
        $data['latitude'] = $latOld;
        $data['longitude'] = $lngOld;
    }

    // 6) Tambahan field yang pasti kita set
    $data['kelas_id']     = $new_kelas_id;
    $data['file_kip']     = $file_kip;
    $data['file_kk']      = $file_kk;
    $data['file_ijazah']  = $file_ijazah;
    if ($tahunAjaran !== '') {
        $data['tahun_ajaran'] = $tahunAjaran;
    }

    if (empty($data)) {
        redirect_back_with_error("Tidak ada data yang diubah.", $id);
    }

    // 7) Build UPDATE aman
    $fields = array_keys($data);
    $setParts = [];
    foreach ($fields as $f) {
        $setParts[] = "{$f} = ?";
    }
    $sql = "UPDATE pendaftaran_siswa SET " . implode(", ", $setParts) . " WHERE id = ?";

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        die("Prepare gagal: " . $conn->error);
    }

    // Tentukan type bind param (i untuk integer, s untuk string)
    $types = '';
    $vals  = [];
    foreach ($data as $k => $v) {
        if (in_array($k, ['kelas_id'], true)) {
            $types .= 'i';
            $vals[] = (int)$v;
        } else {
            $types .= 's';
            $vals[] = (string)$v;
        }
    }
    $types .= 'i';
    $vals[] = $id;

    $stmt->bind_param($types, ...$vals);

    if (!$stmt->execute()) {
        $err = $stmt->error;
        $stmt->close();
        die("Gagal update data: " . $err);
    }
    $stmt->close();

    // 8) Sinkron rombel: update/insert siswa_kelas tahun ajaran aktif
    if ($tahunAjaran !== '') {
        // cek ada row siswa_kelas untuk tahun ajaran tsb?
        $cekSk = $conn->prepare("SELECT id FROM siswa_kelas WHERE siswa_id = ? AND tahun_ajaran = ? LIMIT 1");
        $cekSk->bind_param("is", $id, $tahunAjaran);
        $cekSk->execute();
        $skRow = $cekSk->get_result()->fetch_assoc();
        $cekSk->close();

        if ($skRow) {
            $upSk = $conn->prepare("UPDATE siswa_kelas SET kelas_id = ? WHERE siswa_id = ? AND tahun_ajaran = ?");
            $upSk->bind_param("iis", $new_kelas_id, $id, $tahunAjaran);
            $upSk->execute();
            $upSk->close();
        } else {
            // default naik_kelas=1 (biar tampil di rombel)
            $insSk = $conn->prepare("INSERT INTO siswa_kelas (siswa_id, kelas_id, tahun_ajaran, naik_kelas) VALUES (?, ?, ?, 1)");
            $insSk->bind_param("iis", $id, $new_kelas_id, $tahunAjaran);
            $insSk->execute();
            $insSk->close();
        }
    }

    // 9) Recalc terisi agar kuota akurat (kalau kamu pakai terisi)
    if ($tahunAjaran !== '') {
        if ($old_kelas_id > 0 && $old_kelas_id !== $new_kelas_id) {
            recalc_terisi($conn, $old_kelas_id, $tahunAjaran);
        }
        recalc_terisi($conn, $new_kelas_id, $tahunAjaran);
    }

    // 10) Log aktivitas (tetap)
    $namaSiswa = $data['nama_lengkap'] ?? ($old['nama_lengkap'] ?? '');
    if (isset($_SESSION['admin_id']) && function_exists('catatLog')) {
        $keterangan = "Mengedit data siswa: {$namaSiswa} (ID: {$id})";
        catatLog($conn, (int)$_SESSION['admin_id'], 'Edit Siswa', $keterangan);
    }

    $_SESSION['success'] = "Data <strong>" . htmlspecialchars($namaSiswa) . "</strong> berhasil <strong>di ubah.</strong>";
    header("Location: student_view?id={$id}");
    exit;

} elseif ($mode === 'kesejahteraan') {
    $data = [
        'nomor_kip' => trim((string) post('nomor_kip', '')),
        'nomor_kps' => trim((string) post('nomor_kps', '')),
        'nomor_pkh' => trim((string) post('nomor_pkh', '')),
        'nomor_kis' => trim((string) post('nomor_kis', '')),
        'nomor_kks' => trim((string) post('nomor_kks', '')),
    ];

    $stmt = $conn->prepare("
        UPDATE pendaftaran_siswa
        SET nomor_kip = ?, nomor_kps = ?, nomor_pkh = ?, nomor_kis = ?, nomor_kks = ?
        WHERE id = ?
    ");
    if (!$stmt) {
        die("Prepare gagal: " . $conn->error);
    }
    $stmt->bind_param(
        'sssssi',
        $data['nomor_kip'],
        $data['nomor_kps'],
        $data['nomor_pkh'],
        $data['nomor_kis'],
        $data['nomor_kks'],
        $id
    );

    if (!$stmt->execute()) {
        $err = $stmt->error;
        $stmt->close();
        die("Gagal update data kesejahteraan: " . $err);
    }
    $stmt->close();

    if (isset($_SESSION['admin_id']) && function_exists('catatLog')) {
        catatLog($conn, (int)$_SESSION['admin_id'], 'Edit Kesejahteraan Siswa', "Mengedit data kesejahteraan siswa ID: {$id}");
    }

    $_SESSION['success'] = "Data kesejahteraan berhasil diubah.";
    header("Location: student_view?id={$id}#kesejahteraan");
    exit;

} elseif ($mode === 'ayah') {
    include 'modul/edit_modul_ayah.php';

} elseif ($mode === 'ibu') {
    include 'modul/edit_modul_ibu.php';

} elseif ($mode === 'wali') {
    include 'modul/edit_modul_wali.php';

} elseif ($mode === 'update') {
    // ini memperbaiki bug kamu: sebelumnya elseif 'wali' dobel
    include 'modul/edit_modul_update.php';

} else {
    die("Mode tidak valid.");
}
