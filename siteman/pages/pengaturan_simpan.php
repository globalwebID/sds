<?php
declare(strict_types=1);

require_once __DIR__ . '/../../config/runtime.php';
sds_session_start();
require_once __DIR__ . '/../../config/db.php';

if (empty($_SESSION['admin_id'])) { header('Location: login'); exit; }
if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST' || !sds_csrf_verify((string)($_POST['csrf'] ?? ''))) {
    http_response_code(419);
    exit('Sesi formulir berakhir. Muat ulang halaman.');
}

$adminId = (int)$_SESSION['admin_id'];
$back = static function (string $anchor = ''): never {
    header('Location: pengaturan' . ($anchor !== '' ? '#' . $anchor : ''));
    exit;
};
$fail = static function (string $message, string $anchor = '') use ($back): never {
    $_SESSION['error'] = $message;
    $back($anchor);
};
$audit = static function (mysqli $conn, int $adminId, string $section, array $changes): void {
    if (!$changes) return;
    $json = json_encode($changes, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    $ip = substr((string)($_SERVER['REMOTE_ADDR'] ?? 'cli'), 0, 45);
    $stmt = $conn->prepare('INSERT INTO sds_pengaturan_audit (admin_id,bagian,perubahan,ip_address) VALUES (?,?,?,?)');
    $stmt->bind_param('isss', $adminId, $section, $json, $ip);
    $stmt->execute();
    $stmt->close();
};
$current = $conn->query('SELECT * FROM pengaturan ORDER BY id LIMIT 1')->fetch_assoc();
if (!$current) {
    $conn->query("INSERT INTO pengaturan (nama_sekolah) VALUES ('Sekolah')");
    $current = $conn->query('SELECT * FROM pengaturan ORDER BY id LIMIT 1')->fetch_assoc();
}
$settingsId = (int)$current['id'];

try {
    if (isset($_POST['submit_identitas'])) {
        $fields = [
            'nama_sekolah'=>150, 'npsn'=>20, 'kementerian'=>150, 'alamat'=>1000,
            'desa'=>100, 'kecamatan'=>100, 'kabupaten'=>100, 'provinsi'=>100,
            'telepon'=>30, 'email'=>150, 'website'=>255, 'kepala_sekolah'=>150,
            'nip_kepala_sekolah'=>40,
        ];
        $values = [];
        foreach ($fields as $field => $max) $values[$field] = mb_substr(trim((string)($_POST[$field] ?? '')), 0, $max);
        if ($values['nama_sekolah'] === '') $fail('Nama sekolah wajib diisi.', 'identitas');
        if ($values['email'] !== '' && !filter_var($values['email'], FILTER_VALIDATE_EMAIL)) $fail('Email sekolah tidak valid.', 'identitas');
        if ($values['website'] !== '' && !filter_var($values['website'], FILTER_VALIDATE_URL)) $fail('Website harus berupa URL lengkap, misalnya https://sekolah.sch.id.', 'identitas');
        $changes = [];
        foreach ($values as $key => $value) if ((string)($current[$key] ?? '') !== $value) $changes[$key] = ['lama'=>$current[$key] ?? null,'baru'=>$value];
        $stmt = $conn->prepare('UPDATE pengaturan SET nama_sekolah=?,npsn=?,kementerian=?,alamat=?,desa=?,kecamatan=?,kabupaten=?,provinsi=?,telepon=?,email=?,website=?,kepala_sekolah=?,nip_kepala_sekolah=? WHERE id=?');
        $stmt->bind_param('sssssssssssssi', $values['nama_sekolah'],$values['npsn'],$values['kementerian'],$values['alamat'],$values['desa'],$values['kecamatan'],$values['kabupaten'],$values['provinsi'],$values['telepon'],$values['email'],$values['website'],$values['kepala_sekolah'],$values['nip_kepala_sekolah'],$settingsId);
        $stmt->execute(); $stmt->close();
        $audit($conn,$adminId,'identitas_sekolah',$changes);
        $_SESSION['success']='Identitas sekolah berhasil disimpan.';
        $back('identitas');
    }

    if (isset($_POST['submit_branding'])) {
        $map = ['logo'=>'logo','favicon'=>'favicon','kop_surat'=>'kop','ttd_kepala_sekolah'=>'ttd','stempel'=>'stempel'];
        $changes = [];
        $updates = [];
        $uploadDir = dirname(__DIR__,2) . '/uploads/logo';
        if (!is_dir($uploadDir) && !mkdir($uploadDir,0750,true) && !is_dir($uploadDir)) throw new RuntimeException('Folder branding tidak dapat dibuat.');
        foreach ($map as $field => $prefix) {
            if (empty($_FILES[$field]['name'])) continue;
            $ext = sds_validate_upload($_FILES[$field], ['png','jpg','jpeg','webp'], 5*1024*1024);
            $name = $prefix . '_' . date('YmdHis') . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
            if (!move_uploaded_file((string)$_FILES[$field]['tmp_name'], $uploadDir . '/' . $name)) throw new RuntimeException('Gagal menyimpan ' . str_replace('_',' ',$field) . '.');
            $updates[$field] = $name;
            $changes[$field] = ['lama'=>$current[$field] ?? null,'baru'=>$name];
        }
        if (!$updates) $fail('Pilih minimal satu berkas branding.', 'branding');
        $assign = implode(',', array_map(static fn(string $field): string => "`{$field}`=?", array_keys($updates)));
        $params = [...array_values($updates),$settingsId];
        $types = str_repeat('s',count($updates)).'i';
        $stmt=$conn->prepare("UPDATE pengaturan SET {$assign} WHERE id=?");
        $stmt->bind_param($types,...$params); $stmt->execute(); $stmt->close();
        $audit($conn,$adminId,'branding_dokumen',$changes);
        $_SESSION['success']='Branding dan dokumen berhasil diperbarui.';
        $back('branding');
    }

    if (isset($_POST['submit_admin'])) {
        $name=mb_substr(trim((string)($_POST['admin_nama']??'')),0,150);
        $email=mb_substr(trim((string)($_POST['admin_email']??'')),0,150);
        if ($name==='' || !filter_var($email,FILTER_VALIDATE_EMAIL)) $fail('Nama dan email admin wajib valid.','admin');
        $stmt=$conn->prepare('SELECT id FROM admins WHERE email=? AND id<>? LIMIT 1');$stmt->bind_param('si',$email,$adminId);$stmt->execute();
        if ($stmt->get_result()->fetch_assoc()) $fail('Email sudah digunakan admin lain.','admin');
        $stmt->close();
        $admin=$conn->query('SELECT * FROM admins WHERE id='.$adminId.' LIMIT 1')->fetch_assoc();
        $newPassword=(string)($_POST['admin_password']??'');
        $confirm=(string)($_POST['admin_password_confirmation']??'');
        $currentPassword=(string)($_POST['current_password']??'');
        if ($newPassword!=='') {
            if (!password_verify($currentPassword,(string)$admin['password'])) $fail('Password saat ini tidak benar.','admin');
            if (strlen($newPassword)<10 || !preg_match('/[A-Za-z]/',$newPassword) || !preg_match('/\d/',$newPassword)) $fail('Password baru minimal 10 karakter serta mengandung huruf dan angka.','admin');
            if (!hash_equals($newPassword,$confirm)) $fail('Konfirmasi password baru tidak cocok.','admin');
            $hash=password_hash($newPassword,PASSWORD_DEFAULT);
            $stmt=$conn->prepare('UPDATE admins SET full_name=?,email=?,password=?,password_changed_at=NOW() WHERE id=?');$stmt->bind_param('sssi',$name,$email,$hash,$adminId);
        } else {
            $stmt=$conn->prepare('UPDATE admins SET full_name=?,email=? WHERE id=?');$stmt->bind_param('ssi',$name,$email,$adminId);
        }
        $stmt->execute();$stmt->close();
        $_SESSION['admin_name']=$name;
        if($newPassword!=='') $_SESSION['password_expired']=false;
        $audit($conn,$adminId,'profil_admin',['profil'=>['lama'=>['nama'=>$admin['full_name']??'','email'=>$admin['email']??''],'baru'=>['nama'=>$name,'email'=>$email]],'password_diubah'=>$newPassword!=='' ]);
        $_SESSION['success']='Profil admin berhasil diperbarui.';
        $back('admin');
    }

    if (isset($_POST['submit_kartu'])) {
        $orientation=in_array($_POST['kartu_orientasi']??'',['potrait','landscape'],true)?(string)$_POST['kartu_orientasi']:'potrait';
        $width=max(40,min(120,(float)($_POST['kartu_lebar_mm']??53.98)));
        $height=max(40,min(150,(float)($_POST['kartu_tinggi_mm']??85.60)));
        $changes=['kartu_orientasi'=>['lama'=>$current['kartu_orientasi']??null,'baru'=>$orientation],'kartu_lebar_mm'=>['lama'=>$current['kartu_lebar_mm']??null,'baru'=>$width],'kartu_tinggi_mm'=>['lama'=>$current['kartu_tinggi_mm']??null,'baru'=>$height]];
        if (!empty($_FILES['bg']['name'])) {
            sds_validate_upload($_FILES['bg'],['jpg','jpeg'],5*1024*1024);
            $bgDir=dirname(__DIR__,2).'/uploads/bg';if(!is_dir($bgDir))mkdir($bgDir,0750,true);
            if(!move_uploaded_file((string)$_FILES['bg']['tmp_name'],$bgDir.'/bg_'.$orientation.'.jpg'))throw new RuntimeException('Background kartu gagal disimpan.');
            $changes['background']=['baru'=>'bg_'.$orientation.'.jpg'];
        }
        $stmt=$conn->prepare('UPDATE pengaturan SET kartu_orientasi=?,kartu_lebar_mm=?,kartu_tinggi_mm=? WHERE id=?');$stmt->bind_param('sddi',$orientation,$width,$height,$settingsId);$stmt->execute();$stmt->close();
        $audit($conn,$adminId,'kartu_pelajar',$changes);
        $_SESSION['success']='Pengaturan kartu pelajar berhasil disimpan.';
        $back('kartu');
    }

    if (isset($_POST['submit_operasional'])) {
        $maintenance = isset($_POST['maintenance_mode']) ? 1 : 0;
        $message = mb_substr(trim((string)($_POST['maintenance_message'] ?? '')), 0, 500);
        if ($message === '') $message = 'Sistem sedang dalam pemeliharaan. Silakan coba kembali beberapa saat lagi.';
        $schedule = in_array($_POST['backup_schedule'] ?? '', ['disabled','daily','weekly'], true) ? (string)$_POST['backup_schedule'] : 'disabled';
        $retention = max(7, min(365, (int)($_POST['backup_retention_days'] ?? 30)));
        $maxAttempts = max(3, min(20, (int)($_POST['login_max_attempts'] ?? 5)));
        $window = max(1, min(60, (int)($_POST['login_window_minutes'] ?? 5)));
        $sessionMinutes = max(10, min(1440, (int)($_POST['admin_session_minutes'] ?? 30)));
        $expiry = max(0, min(365, (int)($_POST['password_expiry_days'] ?? 0)));
        $stmt=$conn->prepare('UPDATE pengaturan SET maintenance_mode=?,maintenance_message=?,backup_schedule=?,backup_retention_days=?,login_max_attempts=?,login_window_minutes=?,admin_session_minutes=?,password_expiry_days=? WHERE id=?');
        $stmt->bind_param('issiiiiii',$maintenance,$message,$schedule,$retention,$maxAttempts,$window,$sessionMinutes,$expiry,$settingsId);$stmt->execute();$stmt->close();
        $audit($conn,$adminId,'operasional_keamanan',['maintenance_mode'=>$maintenance,'backup_schedule'=>$schedule,'backup_retention_days'=>$retention,'login_max_attempts'=>$maxAttempts,'login_window_minutes'=>$window,'admin_session_minutes'=>$sessionMinutes,'password_expiry_days'=>$expiry]);
        $_SESSION['success']='Pengaturan operasional dan keamanan berhasil disimpan.';
        $back('operasional');
    }

    if (isset($_POST['submit_regional'])) {
        $timezone = (string)($_POST['system_timezone'] ?? 'Asia/Jakarta');
        if (!in_array($timezone, timezone_identifiers_list(), true)) $fail('Zona waktu tidak valid.','regional');
        $dateFormat = in_array($_POST['date_format'] ?? '', ['d/m/Y','d-m-Y','Y-m-d'], true) ? (string)$_POST['date_format'] : 'd/m/Y';
        $locale = in_array($_POST['number_locale'] ?? '', ['id_ID','en_US'], true) ? (string)$_POST['number_locale'] : 'id_ID';
        $stmt=$conn->prepare('UPDATE pengaturan SET system_timezone=?,date_format=?,number_locale=? WHERE id=?');$stmt->bind_param('sssi',$timezone,$dateFormat,$locale,$settingsId);$stmt->execute();$stmt->close();
        $audit($conn,$adminId,'regional',['system_timezone'=>$timezone,'date_format'=>$dateFormat,'number_locale'=>$locale]);
        $_SESSION['success']='Preferensi regional berhasil disimpan.';
        $back('regional');
    }

    if (isset($_POST['submit_test_integrasi'])) {
        $conn->query('SELECT 1');
        $wa=sds_config('services.whatsapp',[]);
        $checks=['Database pusat: tersambung'];
        $checks[]='Storage: '.(is_writable(dirname(__DIR__,2).'/storage')?'siap':'tidak dapat ditulis');
        $checks[]='WhatsApp: '.(!empty($wa['url'])&&!empty($wa['api_key'])?'konfigurasi tersedia':'belum lengkap');
        $checks[]='SMTP: '.(($currentLegacy=$conn->query('SELECT gmail_active FROM setting ORDER BY site_id LIMIT 1')->fetch_assoc())&&($currentLegacy['gmail_active']??'N')==='Y'?'aktif':'nonaktif');
        $audit($conn,$adminId,'uji_integrasi',['hasil'=>$checks]);
        $_SESSION['success']='Pemeriksaan integrasi selesai — '.implode('; ',$checks).'.';
        $back('integrasi');
    }
    $fail('Aksi pengaturan tidak dikenali.');
} catch (Throwable $e) {
    error_log('[SDS pengaturan] '.$e->getMessage());
    $fail('Pengaturan gagal disimpan: '.$e->getMessage());
}
