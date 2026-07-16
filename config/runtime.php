<?php
declare(strict_types=1);

function sds_config(?string $key = null, mixed $default = null): mixed
{
    static $config;
    if ($config === null) {
        $file = __DIR__ . '/app.php';
        $config = is_file($file) ? require $file : [];
        if (!is_array($config)) $config = [];
    }
    if ($key === null) return $config;
    $value = $config;
    foreach (explode('.', $key) as $segment) {
        if (!is_array($value) || !array_key_exists($segment, $value)) return $default;
        $value = $value[$segment];
    }
    return $value;
}

function sds_root_path(string $suffix = ''): string
{
    $root=dirname(__DIR__);return $suffix===''?$root:$root.'/'.ltrim(str_replace('\\','/',$suffix),'/');
}

function sds_modules(): SdsModuleRegistry
{
    static $registry;if($registry instanceof SdsModuleRegistry)return $registry;
    require_once dirname(__DIR__).'/app/Core/Modules/ModuleRegistry.php';
    $file=__DIR__.'/modules.php';$enabled=is_file($file)?require $file:[];
    $registry=new SdsModuleRegistry(dirname(__DIR__),is_array($enabled)?$enabled:[]);return $registry;
}

function sds_is_installed(): bool
{
    return is_file(dirname(__DIR__) . '/storage/installed.lock') && is_file(__DIR__ . '/app.php');
}

function sds_require_installed(): void
{
    if (sds_is_installed()) return;
    if (PHP_SAPI === 'cli') throw new RuntimeException('Aplikasi belum diinstal.');
    $script = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '/');
    $root = preg_replace('#/(siteman|mkantin|emoney|sarpras|anjungan)(/.*)?$#', '', dirname($script));
    header('Location: ' . rtrim((string)$root, '/') . '/install/');
    exit;
}

function sds_database_config(string $name = 'main'): array
{
    $cfg = sds_config('databases.' . $name, []);
    // Sejak database digabung, konfigurasi attendance lama yang kosong
    // otomatis memakai database utama SDS.
    if ($name === 'attendance' && (!is_array($cfg) || empty($cfg['database']))) {
        return sds_database_config('main');
    }
    if (!is_array($cfg) || empty($cfg['database'])) {
        throw new RuntimeException("Konfigurasi database '{$name}' belum tersedia.");
    }
    return $cfg + ['host'=>'127.0.0.1','port'=>3306,'username'=>'','password'=>'','charset'=>'utf8mb4'];
}

function sds_mysqli(string $name = 'main'): mysqli
{
    $cfg = sds_database_config($name);
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
    $db = new mysqli((string)$cfg['host'], (string)$cfg['username'], (string)$cfg['password'], (string)$cfg['database'], (int)$cfg['port']);
    $db->set_charset((string)$cfg['charset']);
    return $db;
}

function sds_base_url(string $suffix = ''): string
{
    $base = rtrim((string)sds_config('app.base_url', ''), '/');
    return $base . ($suffix === '' ? '/' : '/' . ltrim($suffix, '/'));
}

function sds_rate_limit_key(string $scope, string $identity = ''): string
{
    return hash('sha256', $scope . '|' . ($_SERVER['REMOTE_ADDR'] ?? 'cli') . '|' . strtolower(trim($identity)));
}

function sds_rate_limit_file(string $key): string
{
    $dir = dirname(__DIR__) . '/storage/rate_limits';
    if (!is_dir($dir)) @mkdir($dir, 0750, true);
    return $dir . '/' . $key . '.json';
}

function sds_rate_limit_check(string $scope, string $identity = '', int $maxAttempts = 5, int $windowSeconds = 300): int
{
    $file = sds_rate_limit_file(sds_rate_limit_key($scope, $identity));
    $data = is_file($file) ? json_decode((string)@file_get_contents($file), true) : [];
    $started = (int)($data['started'] ?? 0);
    if ($started === 0 || time() - $started >= $windowSeconds) return 0;
    return (int)($data['attempts'] ?? 0) >= $maxAttempts ? max(1, $windowSeconds - (time() - $started)) : 0;
}

function sds_rate_limit_fail(string $scope, string $identity = '', int $windowSeconds = 300): void
{
    $file = sds_rate_limit_file(sds_rate_limit_key($scope, $identity));
    $now = time();
    $data = is_file($file) ? json_decode((string)@file_get_contents($file), true) : [];
    if ((int)($data['started'] ?? 0) === 0 || $now - (int)$data['started'] >= $windowSeconds) $data = ['started'=>$now,'attempts'=>0];
    $data['attempts'] = (int)$data['attempts'] + 1;
    @file_put_contents($file, json_encode($data), LOCK_EX);
}

function sds_rate_limit_clear(string $scope, string $identity = ''): void
{
    $file = sds_rate_limit_file(sds_rate_limit_key($scope, $identity));
    if (is_file($file)) @unlink($file);
}

function sds_session_start(int $idleTimeout = 1800): void
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        $https = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
        ini_set('session.use_strict_mode', '1');
        ini_set('session.use_only_cookies', '1');
        session_set_cookie_params(['lifetime'=>0,'path'=>'/','secure'=>$https,'httponly'=>true,'samesite'=>'Lax']);
        session_start();
    }
    $idleTimeout=max(600,(int)($_SESSION['_idle_timeout']??$idleTimeout));
    $now = time();
    if (!empty($_SESSION['_last_activity']) && $now - (int)$_SESSION['_last_activity'] > $idleTimeout) {
        $_SESSION = [];
        session_regenerate_id(true);
    }
    $_SESSION['_last_activity'] = $now;
}

function sds_session_destroy(): void
{
    if (session_status() !== PHP_SESSION_ACTIVE) sds_session_start();
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
    }
    session_destroy();
}

function sds_apply_central_controls(mysqli $conn, string $module = 'SDS'): array
{
    static $settingsCache = [];
    $key = spl_object_id($conn);
    if (!isset($settingsCache[$key])) {
        try {
            $result=$conn->query('SELECT * FROM pengaturan ORDER BY id LIMIT 1');
            $settingsCache[$key]=$result&&$result->num_rows?($result->fetch_assoc()?:[]):[];
        } catch (Throwable $e) {
            error_log('[SDS central controls] '.$e->getMessage());
            return [];
        }
    }
    $settings=$settingsCache[$key];
    $timezone=(string)($settings['system_timezone']??sds_config('app.timezone','Asia/Jakarta'));
    if(in_array($timezone,timezone_identifiers_list(),true)) date_default_timezone_set($timezone);
    if(PHP_SAPI==='cli') return $settings;

    $uri=strtolower((string)($_SERVER['REQUEST_URI']??''));
    $script=strtolower((string)($_SERVER['SCRIPT_NAME']??''));
    $isLogin=str_contains($uri,'login')||str_contains($script,'login')||str_contains($uri,'logout')||str_contains($script,'logout');
    $isSuperadmin=($_SESSION['admin_role']??'')==='superadmin'||($_SESSION['perpus_login_source']??'')==='sds_session';
    $hasAuthenticatedSession=!empty($_SESSION['admin_id'])||!empty($_SESSION['perpus_user_id'])||!empty($_SESSION['login']);
    if(!$isLogin&&$hasAuthenticatedSession){
        $timeout=max(600,min(86400,(int)($settings['admin_session_minutes']??30)*60));$now=time();
        if(!empty($_SESSION['_sds_central_activity'])&&$now-(int)$_SESSION['_sds_central_activity']>$timeout){$_SESSION=[];if(session_status()===PHP_SESSION_ACTIVE)session_regenerate_id(true);}
        $_SESSION['_sds_central_activity']=$now;$_SESSION['_idle_timeout']=$timeout;
    }
    if(empty($settings['maintenance_mode'])) return $settings;
    if($isLogin||$isSuperadmin) return $settings;

    http_response_code(503);
    header('Retry-After: 300');
    $accept=strtolower((string)($_SERVER['HTTP_ACCEPT']??''));
    $isJson=str_contains($accept,'application/json')||str_contains($script,'/api/')||strtolower((string)($_SERVER['HTTP_X_REQUESTED_WITH']??''))==='xmlhttprequest';
    $message=(string)($settings['maintenance_message']??'Sistem sedang dalam pemeliharaan.');
    if($isJson){header('Content-Type: application/json; charset=utf-8');echo json_encode(['success'=>false,'maintenance'=>true,'message'=>$message],JSON_UNESCAPED_UNICODE);exit;}
    $school=htmlspecialchars((string)($settings['nama_sekolah']??'SDS'),ENT_QUOTES,'UTF-8');
    $safeMessage=htmlspecialchars($message,ENT_QUOTES,'UTF-8');
    $safeModule=htmlspecialchars($module,ENT_QUOTES,'UTF-8');
    $loginUrl=htmlspecialchars(sds_base_url('siteman/login'),ENT_QUOTES,'UTF-8');
    echo '<!doctype html><html lang="id"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Pemeliharaan '.$safeModule.'</title><style>*{box-sizing:border-box}body{margin:0;background:#f4f6f9;font-family:Arial,sans-serif;color:#334151;display:grid;place-items:center;min-height:100vh;padding:16px}.box{width:min(560px,100%);background:#fff;border:1px solid #dee2e6;border-top:4px solid #0d6efd;padding:28px}.box small{color:#6c757d;text-transform:uppercase;font-weight:700}.box h1{font-size:1.35rem;margin:8px 0}.box p{color:#6c757d;line-height:1.6}.box a{display:inline-block;border:1px solid #0d6efd;color:#0d6efd;text-decoration:none;padding:8px 13px;margin-top:8px}</style></head><body><main class="box"><small>'.$school.' · '.$safeModule.'</small><h1>Sistem Sedang Dipelihara</h1><p>'.$safeMessage.'</p><a href="'.$loginUrl.'">Login Superadmin SDS</a></main></body></html>';
    exit;
}

function sds_csrf_token(): string
{
    if (session_status() !== PHP_SESSION_ACTIVE) sds_session_start();
    if (empty($_SESSION['_csrf'])) $_SESSION['_csrf'] = bin2hex(random_bytes(32));
    return (string)$_SESSION['_csrf'];
}

function sds_csrf_verify(?string $token): bool
{
    return !empty($_SESSION['_csrf']) && is_string($token) && hash_equals((string)$_SESSION['_csrf'], $token);
}

function sds_validate_upload(array $file, array $allowedExtensions, int $maxBytes): string
{
    if ((int)($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK || empty($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
        throw new RuntimeException('Upload file tidak valid.');
    }
    if ((int)($file['size'] ?? 0) <= 0 || (int)$file['size'] > $maxBytes) {
        throw new RuntimeException('Ukuran file melewati batas yang diizinkan.');
    }
    $extension = strtolower(pathinfo((string)($file['name'] ?? ''), PATHINFO_EXTENSION));
    $mimeMap = ['pdf'=>['application/pdf'],'jpg'=>['image/jpeg'],'jpeg'=>['image/jpeg'],'png'=>['image/png'],'webp'=>['image/webp']];
    if (!in_array($extension, $allowedExtensions, true) || !isset($mimeMap[$extension])) {
        throw new RuntimeException('Format file tidak didukung.');
    }
    $mime = (string)(new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
    if (!in_array($mime, $mimeMap[$extension], true)) throw new RuntimeException('Isi file tidak sesuai dengan ekstensinya.');
    return $extension;
}
