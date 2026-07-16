<?php
declare(strict_types=1);
session_start();

$root = dirname(__DIR__);
require_once $root . '/config/runtime.php';
require_once $root . '/config/form-fields.php';

if (sds_is_installed()) {
    http_response_code(423);
    exit('<!doctype html><meta charset="utf-8"><title>Installer terkunci</title><style>body{font:16px system-ui;max-width:760px;margin:60px auto;padding:20px}a{color:#075985}</style><h1>Aplikasi sudah terpasang</h1><p>Installer telah dikunci. Untuk instalasi ulang, hapus <code>storage/installed.lock</code> dan <code>config/app.php</code> secara manual setelah membuat backup.</p><p><a href="../">Buka aplikasi</a></p>');
}

if (empty($_SESSION['installer_csrf'])) $_SESSION['installer_csrf'] = bin2hex(random_bytes(24));
$errors = [];
$success = false;
$installerProducts=[
    'attendance'=>['name'=>'Absensi','description'=>'Absensi siswa dan pegawai berbasis RFID.'],
    'emoney'=>['name'=>'E-Money','description'=>'Saldo kartu, top up, dan riwayat transaksi.'],
    'canteen'=>['name'=>'Kantin','description'=>'Transaksi kantin; membutuhkan E-Money.'],
    'library'=>['name'=>'Perpustakaan','description'=>'Katalog, anggota, sirkulasi, OPAC, dan laporan.'],
    'kiosk'=>['name'=>'Anjungan','description'=>'Informasi dan layanan mandiri sekolah.'],
];
$installerProducts=array_filter($installerProducts,static fn(array $product,string $id):bool=>is_file(dirname(__DIR__).'/modules/'.$id.'/database/schema.sql'),ARRAY_FILTER_USE_BOTH);

function installer_h(string $value): string { return htmlspecialchars($value, ENT_QUOTES, 'UTF-8'); }
function installer_post(string $key, string $default = ''): string { return trim((string)($_POST[$key] ?? $default)); }
function installer_base_url(): string {
    $https = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
    $scheme = $https ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $path = str_replace('\\', '/', dirname(dirname($_SERVER['SCRIPT_NAME'] ?? '/install/index.php')));
    // Pada layout distribusi ringkas, Apache meneruskan URL publik secara
    // internal ke folder /package. Segmen internal tidak boleh masuk Base URL.
    $path = (string)preg_replace('#/package$#', '', $path);
    return $scheme . '://' . $host . rtrim($path, '/');
}
function installer_import_sql(mysqli $db, string $file): void {
    $handle = fopen($file, 'rb');
    if (!$handle) throw new RuntimeException('File SQL kosong atau tidak dapat dibaca.');
    $delimiter = ';'; $statement = ''; $statementNumber = 0;
    try {
        while (($line = fgets($handle)) !== false) {
            if (preg_match('/^\s*DELIMITER\s+(\S+)\s*$/i', $line, $match)) {
                $delimiter = $match[1];
                continue;
            }
            $statement .= $line;
            $trimmed = rtrim($statement);
            if ($trimmed === '' || !str_ends_with($trimmed, $delimiter)) continue;
            $statement = substr($trimmed, 0, -strlen($delimiter));
            // Komentar executable MySQL/MariaDB (/*! ... */) harus dijalankan.
            // Dump memakai directive ini untuk FOREIGN_KEY_CHECKS dan charset.
            $meaningful = preg_replace('/^\s*(?:--[^\r\n]*(?:\r?\n|$)|#[^\r\n]*(?:\r?\n|$)|\/\*(?!\!)[\s\S]*?\*\/\s*)*/', '', $statement);
            if (trim((string)$meaningful) !== '') {
                $statementNumber++;
                try { $db->query($statement); }
                catch (Throwable $e) { throw new RuntimeException("Impor SQL gagal pada statement {$statementNumber}: " . $e->getMessage(), 0, $e); }
            }
            $statement = '';
        }
        $remainder = preg_replace('/^\s*(?:--[^\r\n]*(?:\r?\n|$)|#[^\r\n]*(?:\r?\n|$)|\/\*(?!\!)[\s\S]*?\*\/\s*)*/', '', $statement);
        if (trim((string)$remainder) !== '') throw new RuntimeException('File SQL berakhir dengan statement yang tidak lengkap.');
    } finally { fclose($handle); }
}
function installer_table_exists(mysqli $db, string $table): bool {
    $stmt = $db->prepare('SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = ?');
    $stmt->bind_param('s', $table); $stmt->execute();
    return (int)$stmt->get_result()->fetch_row()[0] > 0;
}
function installer_register_module_migrations(mysqli $db,string $root,array $modules): void {
    if(!installer_table_exists($db,'sds_module_migrations'))return;$definitions=require $root.'/packaging/packages.php';$statement=$db->prepare('INSERT IGNORE INTO sds_module_migrations (module_id,migration,checksum) VALUES (?,?,?)');
    foreach($modules as $module){$migrationFiles=glob($root.'/modules/'.$module.'/database/migrations/*.sql')?:[];foreach($migrationFiles as $path){$entry=str_replace('\\','/',substr($path,strlen($root)+1));$checksum=hash_file('sha256',$path);$statement->bind_param('sss',$module,$entry,$checksum);$statement->execute();}}
    $statement->close();
}
function installer_admin(mysqli $db, array $data): int {
    $columns = [];
    $result = $db->query('SHOW COLUMNS FROM admins');
    while ($row = $result->fetch_assoc()) $columns[] = $row['Field'];
    foreach (['username','password'] as $required) {
        if (!in_array($required, $columns, true)) throw new RuntimeException("Tabel admins tidak memiliki kolom {$required}.");
    }
    $values = ['username'=>$data['username'], 'password'=>password_hash($data['password'], PASSWORD_DEFAULT)];
    if (in_array('full_name',$columns,true)) $values['full_name']=$data['full_name'];
    if (in_array('email',$columns,true)) $values['email']=$data['email'];
    if (in_array('role',$columns,true)) $values['role']='superadmin';
    $check = $db->prepare('SELECT id FROM admins WHERE username = ? LIMIT 1');
    $check->bind_param('s', $data['username']); $check->execute();
    $existing = $check->get_result()->fetch_assoc();
    $names = array_keys($values);
    if ($existing) {
        $assignments = implode(',', array_map(fn($name) => "`{$name}` = ?", $names));
        $sql = "UPDATE admins SET {$assignments} WHERE id = ?";
        $stmt = $db->prepare($sql);
        $params = [...array_values($values), (int)$existing['id']];
        $types = str_repeat('s', count($values)) . 'i';
        $stmt->bind_param($types, ...$params); $stmt->execute();
        return (int)$existing['id'];
    }
    $sql = 'INSERT INTO admins (`' . implode('`,`',$names) . '`) VALUES (' . implode(',',array_fill(0,count($names),'?')) . ')';
    $stmt = $db->prepare($sql);
    $params = array_values($values); $types = str_repeat('s', count($params));
    $stmt->bind_param($types, ...$params); $stmt->execute();
    return (int)$db->insert_id;
}
function installer_grant_superadmin_apps(mysqli $db, int $adminId, array $selectedModules): void {
    if ($adminId <= 0 || !installer_table_exists($db, 'app_admin_access')) return;
    $moduleAccess = [
        'attendance' => ['absensi', 'superadmin'],
        'canteen' => ['mkantin', 'superadmin'],
        'library' => ['library', 'admin'],
    ];
    $stmt = $db->prepare("INSERT INTO app_admin_access (admin_id,application,app_role,active)
        VALUES (?,?,?,'Y')
        ON DUPLICATE KEY UPDATE app_role=VALUES(app_role),active='Y'");
    foreach ($moduleAccess as $module => [$application, $role]) {
        if (!in_array($module, $selectedModules, true)) continue;
        $stmt->bind_param('iss', $adminId, $application, $role);
        $stmt->execute();
    }
    $stmt->close();
}

$checks = [
    'PHP 8.1+' => version_compare(PHP_VERSION, '8.1.0', '>='),
    'mysqli' => extension_loaded('mysqli'),
    'PDO MySQL' => extension_loaded('pdo_mysql'),
    'mbstring' => extension_loaded('mbstring'),
    'GD' => extension_loaded('gd'),
    'FileInfo' => extension_loaded('fileinfo'),
    'ZIP' => extension_loaded('zip'),
    'SimpleXML' => extension_loaded('simplexml'),
    'config dapat ditulis' => is_writable($root . '/config'),
    'storage dapat ditulis' => is_writable($root . '/storage'),
];

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    if (!hash_equals($_SESSION['installer_csrf'], (string)($_POST['csrf'] ?? ''))) $errors[] = 'Token formulir tidak valid. Muat ulang installer.';
    foreach ($checks as $label=>$ok) if (!$ok) $errors[] = "Persyaratan belum terpenuhi: {$label}.";
    $dbName = installer_post('db_name');
    if (!preg_match('/^[A-Za-z0-9_\-]+$/', $dbName)) $errors[] = 'Nama database hanya boleh berisi huruf, angka, underscore, dan tanda minus.';
    $baseUrl = installer_post('base_url');
    if (!filter_var($baseUrl, FILTER_VALIDATE_URL) || !preg_match('#^https?://#i', $baseUrl)) $errors[] = 'Base URL harus berupa URL HTTP/HTTPS yang valid.';
    if (strlen(installer_post('admin_password')) < 8) $errors[] = 'Password admin minimal 8 karakter.';
    if (installer_post('admin_password') !== installer_post('admin_password_confirmation')) $errors[] = 'Konfirmasi password admin tidak cocok.';
    if (!filter_var(installer_post('admin_email'), FILTER_VALIDATE_EMAIL)) $errors[] = 'Email admin tidak valid.';
    $selectedModules=array_values(array_intersect(array_keys($installerProducts),(array)($_POST['modules']??[])));
    if(in_array('canteen',$selectedModules,true)&&!in_array('emoney',$selectedModules,true))$selectedModules[]='emoney';

    if (!$errors) {
        try {
            mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
            $host=installer_post('db_host','127.0.0.1'); $port=(int)installer_post('db_port','3306');
            $user=installer_post('db_user'); $pass=(string)($_POST['db_password'] ?? '');
            $server = new mysqli($host,$user,$pass,'',$port);
            $server->set_charset('utf8mb4');
            if (!empty($_POST['create_database'])) $server->query("CREATE DATABASE IF NOT EXISTS `{$dbName}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
            $server->select_db($dbName);

            $sqlFile = null;$uploadedRecovery=false;$freshInstall=false;
            $databaseHasTables = $server->query('SHOW TABLES')->num_rows > 0;
            if (!empty($_FILES['database_sql']['tmp_name']) && (int)$_FILES['database_sql']['error'] === UPLOAD_ERR_OK) {
                if (strtolower(pathinfo((string)$_FILES['database_sql']['name'], PATHINFO_EXTENSION)) !== 'sql') throw new RuntimeException('File database harus berekstensi .sql.');
                $sqlFile = $_FILES['database_sql']['tmp_name'];$uploadedRecovery=true;
            }
            if($uploadedRecovery){installer_import_sql($server,$sqlFile);}
            elseif(!$databaseHasTables){$freshInstall=true;$schemaFiles=[__DIR__.'/schema/core.sql'];foreach($selectedModules as $module)$schemaFiles[]=$root.'/modules/'.$module.'/database/schema.sql';foreach($schemaFiles as $schemaFile){if(!is_file($schemaFile))throw new RuntimeException('Schema produk tidak tersedia: '.$schemaFile);installer_import_sql($server,$schemaFile);}$freshMigrations=[__DIR__.'/migrations/018_siswa_kelas_terisi_triggers.sql'];if(in_array('attendance',$selectedModules,true))$freshMigrations[]=__DIR__.'/migrations/003_sds_master_projections.sql';foreach($freshMigrations as $migrationFile)installer_import_sql($server,$migrationFile);$seedFiles=[__DIR__.'/seeds/core.sql'];foreach($selectedModules as $module)if(is_file($root.'/modules/'.$module.'/database/seeds.sql'))$seedFiles[]=$root.'/modules/'.$module.'/database/seeds.sql';foreach($seedFiles as $seedFile)installer_import_sql($server,$seedFile);}

            $requiredTables=['admins','pendaftaran_siswa','pengaturan','formulir','jurusan','kelas','siswa_kelas','pegawai','setting','tahun_ajaran'];
            $moduleRequired=['attendance'=>['user','absen','absensi_kelas','jam_sekolah','level','modul','role'],'emoney'=>['users','topup','penarikan'],'canteen'=>['kantin','transaksi_kantin'],'library'=>['perpus_anggota','perpus_buku','perpus_buku_eksemplar','perpus_pengaturan','perpus_users','perpus_reservasi','perpus_notifikasi','perpus_saran','perpus_audit_log'],'kiosk'=>['anjungan'],'sarpras'=>['sp_inventaris','sp_ruangan']];foreach($selectedModules as $module)$requiredTables=array_merge($requiredTables,$moduleRequired[$module]??[]);
            if($freshInstall && in_array('attendance',$selectedModules,true)){
                $permissionCount=(int)($server->query("SELECT COUNT(*) total FROM role WHERE level_id=1 AND lihat='Y'")->fetch_assoc()['total']??0);
                if($permissionCount<25)throw new RuntimeException('Seed hak akses Superadmin Absensi tidak lengkap.');
            }
            $missing=array_values(array_filter($requiredTables, fn($table)=>!installer_table_exists($server,$table)));
            if ($missing) throw new RuntimeException('Database belum memiliki schema SDS lengkap. Tabel yang belum ada: ' . implode(', ', $missing) . '. Unggah database.sql/export database aplikasi.');
            if($freshInstall)installer_register_module_migrations($server,$root,$selectedModules);

            sds_seed_form_fields($server);
            $initialAdminId=installer_admin($server,[
                'username'=>installer_post('admin_username'), 'password'=>installer_post('admin_password'),
                'full_name'=>installer_post('admin_name'), 'email'=>installer_post('admin_email'),
            ]);
            installer_grant_superadmin_apps($server,$initialAdminId,$selectedModules);

            $config=[
                'app'=>['name'=>installer_post('app_name','SDS'),'base_url'=>rtrim(installer_post('base_url'),'/'),'timezone'=>'Asia/Jakarta'],
                'databases'=>[
                    'main'=>['host'=>$host,'port'=>$port,'database'=>$dbName,'username'=>$user,'password'=>$pass,'charset'=>'utf8mb4'],
                    // Absensi sengaja menunjuk koneksi yang sama: satu aplikasi, satu database.
                    'attendance'=>['host'=>$host,'port'=>$port,'database'=>$dbName,'username'=>$user,'password'=>$pass,'charset'=>'utf8mb4'],
                ],
                'security'=>['sync_token'=>bin2hex(random_bytes(24)),'print_secret'=>bin2hex(random_bytes(32)),'sso_secret'=>bin2hex(random_bytes(32))],
            ];
            $content="<?php\n// Dibuat otomatis oleh installer. Jangan bagikan file ini.\nreturn " . var_export($config,true) . ";\n";
            $temp=$root.'/config/app.php.tmp';
            if (file_put_contents($temp,$content,LOCK_EX)===false || !rename($temp,$root.'/config/app.php')) throw new RuntimeException('Gagal menulis config/app.php.');
            @chmod($root.'/config/app.php',0640);
            $moduleConfig=['core'=>true];foreach(array_keys($installerProducts) as $module)$moduleConfig[$module]=in_array($module,$selectedModules,true);$moduleContent="<?php\n// Dibuat otomatis oleh installer produk SDS.\nreturn ".var_export($moduleConfig,true).";\n";if(file_put_contents($root.'/config/modules.php',$moduleContent,LOCK_EX)===false)throw new RuntimeException('Gagal menulis config/modules.php.');
            foreach ([$root.'/uploads',$root.'/tmp_dompdf'] as $dir) if (!is_dir($dir) && !mkdir($dir,0755,true)) throw new RuntimeException('Gagal membuat folder: '.$dir);
            if (file_put_contents($root.'/storage/installed.lock',date(DATE_ATOM).PHP_EOL,LOCK_EX)===false) throw new RuntimeException('Gagal membuat install lock.');
            unset($_SESSION['installer_csrf']); $success=true;
        } catch (Throwable $e) { $errors[]=$e->getMessage(); }
    }
}

$baseDefault=installer_base_url();
$bundledSchema = is_file(__DIR__ . '/schema/core.sql');
?><!doctype html><html lang="id"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Installer SDS</title><style>
body{margin:0;background:#f1f5f9;font:15px system-ui;color:#172033}.wrap{max-width:900px;margin:30px auto;padding:20px}.card{background:white;border-radius:16px;padding:26px;box-shadow:0 8px 30px #0f172a18;margin-bottom:18px}h1{margin-top:0}h2{font-size:18px;border-bottom:1px solid #e2e8f0;padding-bottom:10px}.grid,.products{display:grid;grid-template-columns:repeat(2,1fr);gap:14px}.full{grid-column:1/-1}label{display:block;font-weight:600}input{box-sizing:border-box;width:100%;padding:11px;border:1px solid #cbd5e1;border-radius:8px;margin-top:5px}.check input,.product input{width:auto}.product{display:flex;gap:10px;border:1px solid #dbe3ed;border-radius:10px;padding:12px}.product strong,.product small{display:block}.product small{font-weight:400;color:#64748b;margin-top:3px}.ok{color:#15803d}.bad,.errors{color:#b91c1c}.errors{background:#fee2e2;padding:12px;border-radius:8px}.success{background:#dcfce7;padding:20px;border-radius:12px}button{background:#075985;color:white;border:0;border-radius:9px;padding:13px 22px;font-weight:700;cursor:pointer}@media(max-width:650px){.grid,.products{grid-template-columns:1fr}.full{grid-column:auto}}
</style></head><body><div class="wrap"><div class="card"><h1>Installer SDS</h1><p>Konfigurasi aplikasi, impor database, dan buat akun administrator pertama.</p>
<?php if($success): ?><div class="success"><h2>Instalasi selesai</h2><p>Konfigurasi tersimpan dan installer sudah dikunci.</p><p><a href="../siteman/login">Masuk ke Siteman</a> · <a href="../">Buka aplikasi</a></p></div>
<?php else: ?><?php if($errors): ?><div class="errors"><strong>Instalasi belum selesai:</strong><ul><?php foreach($errors as $error): ?><li><?=installer_h($error)?></li><?php endforeach?></ul></div><?php endif?>
<h2>Pemeriksaan server</h2><div class="grid"><?php foreach($checks as $label=>$ok): ?><div class="<?=$ok?'ok':'bad'?>"><?=$ok?'✓':'✗'?> <?=installer_h($label)?></div><?php endforeach?></div></div>
<form method="post" enctype="multipart/form-data"><input type="hidden" name="csrf" value="<?=installer_h($_SESSION['installer_csrf'])?>">
<div class="card"><h2>Aplikasi</h2><div class="grid"><label>Nama aplikasi<input name="app_name" value="<?=installer_h(installer_post('app_name','SDS'))?>" required></label><label>Base URL<input name="base_url" value="<?=installer_h(installer_post('base_url',$baseDefault))?>" required></label></div></div>
<div class="card"><h2>Database utama</h2><?php if($bundledSchema): ?><p class="ok">✓ Struktur database bersih bawaan tersedia dan akan dipasang otomatis.</p><?php else: ?><p class="bad">Struktur database bawaan tidak ditemukan; unggah file SQL.</p><?php endif?><div class="grid"><label>Host<input name="db_host" value="<?=installer_h(installer_post('db_host','127.0.0.1'))?>" required></label><label>Port<input name="db_port" value="<?=installer_h(installer_post('db_port','3306'))?>" required></label><label>Nama database<input name="db_name" value="<?=installer_h(installer_post('db_name','sds'))?>" required></label><label>Username<input name="db_user" value="<?=installer_h(installer_post('db_user','root'))?>" required></label><label>Password<input type="password" name="db_password"></label><label>SQL pemulihan (opsional)<input type="file" name="database_sql" accept=".sql"><small>Kosongkan untuk instalasi baru bersih. Unggah dump hanya untuk pemulihan/migrasi.</small></label><label class="check full"><input type="checkbox" name="create_database" value="1" <?=($_SERVER['REQUEST_METHOD']??'GET')==='GET'||!empty($_POST['create_database'])?'checked':''?>> Buat database jika belum ada (akun DB harus memiliki izin)</label></div></div>
<div class="card"><h2>Administrator</h2><div class="grid"><label>Nama lengkap<input name="admin_name" value="<?=installer_h(installer_post('admin_name'))?>" required></label><label>Email<input type="email" name="admin_email" value="<?=installer_h(installer_post('admin_email'))?>" required></label><label>Username<input name="admin_username" value="<?=installer_h(installer_post('admin_username','admin'))?>" required></label><label>Password<input type="password" name="admin_password" minlength="8" required></label><label>Ulangi password<input type="password" name="admin_password_confirmation" minlength="8" required></label></div></div>
<div class="card"><h2>Produk yang dipasang</h2><p>Core SDS selalu dipasang. Pilih modul sesuai paket sekolah; seluruh modul tetap memakai satu database.</p><div class="products"><?php $postedModules=(array)($_POST['modules']??array_keys($installerProducts));foreach($installerProducts as $id=>$product):?><label class="product"><input type="checkbox" name="modules[]" value="<?=installer_h($id)?>" <?=in_array($id,$postedModules,true)?'checked':''?>><span><strong><?=installer_h($product['name'])?></strong><small><?=installer_h($product['description'])?></small></span></label><?php endforeach?></div><p><small>Kantin otomatis mengaktifkan E-Money karena menggunakan saldo yang sama.</small></p></div>
<div class="card"><button type="submit">Pasang aplikasi</button></div></form><?php endif?></div></body></html>
