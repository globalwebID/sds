<?php
declare(strict_types=1);

require_once dirname(__DIR__, 3) . '/config/runtime.php';

function perpus_session_start(): void
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_name('SDS_PERPUS_SESSION');
    }
    sds_session_start(3600);
}

function perpus_ensure_user_schema(mysqli $conn): void
{
    $conn->query("CREATE TABLE IF NOT EXISTS perpus_users (
        id INT UNSIGNED NOT NULL AUTO_INCREMENT,
        username VARCHAR(50) NOT NULL,
        email VARCHAR(120) DEFAULT NULL,
        password VARCHAR(255) NOT NULL,
        nama_lengkap VARCHAR(120) NOT NULL,
        role ENUM('admin','staf') NOT NULL DEFAULT 'staf',
        status ENUM('aktif','nonaktif') NOT NULL DEFAULT 'aktif',
        last_login_at DATETIME DEFAULT NULL,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id), UNIQUE KEY uq_perpus_users_username (username),
        UNIQUE KEY uq_perpus_users_email (email), KEY idx_perpus_users_status_role (status,role)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $column = $conn->query("SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='perpus_users' AND COLUMN_NAME='sds_admin_id' LIMIT 1");
    if (!$column || $column->num_rows === 0) {
        $conn->query('ALTER TABLE perpus_users ADD COLUMN sds_admin_id INT UNSIGNED DEFAULT NULL AFTER id, ADD UNIQUE KEY uq_perpus_users_sds_admin (sds_admin_id)');
    }
    $count = (int)($conn->query('SELECT COUNT(*) total FROM perpus_users')->fetch_assoc()['total'] ?? 0);
    if ($count === 0) {
        // Salin satu superadmin hanya sebagai akun awal. Setelah ini autentikasi berdiri sendiri.
        $conn->query("INSERT INTO perpus_users (sds_admin_id,username,email,password,nama_lengkap,role,status)
            SELECT id,username,NULLIF(email,''),password,COALESCE(NULLIF(full_name,''),username),'admin','aktif'
            FROM admins WHERE role='superadmin' ORDER BY id LIMIT 1");
    } else {
        $conn->query("UPDATE perpus_users p JOIN admins a ON a.username=p.username AND a.role='superadmin'
            SET p.sds_admin_id=a.id WHERE p.sds_admin_id IS NULL");
    }
}

function perpus_login_from_sds_session(mysqli $conn): bool
{
    if (session_status() !== PHP_SESSION_ACTIVE || perpus_user()) return false;
    $perpusSessionId = (string)($_COOKIE['SDS_PERPUS_SESSION'] ?? '');
    session_write_close();
    $sdsSessionId = (string)($_COOKIE['PHPSESSID'] ?? '');
    if ($sdsSessionId === '' || !preg_match('/^[a-zA-Z0-9,-]{16,128}$/', $sdsSessionId)) {
        session_name('SDS_PERPUS_SESSION'); session_id($perpusSessionId); sds_session_start(3600); return false;
    }

    session_name('PHPSESSID'); session_id($sdsSessionId); session_start(['read_and_close'=>true]);
    $sdsAdminId=(int)($_SESSION['admin_id']??0);$sdsRole=(string)($_SESSION['admin_role']??'');
    $_SESSION=[]; session_name('SDS_PERPUS_SESSION'); session_id($perpusSessionId); sds_session_start(3600);
    if($sdsAdminId<=0 || $sdsRole!=='superadmin') return false;

    $stmt=$conn->prepare("SELECT id,username,email,password,full_name FROM admins WHERE id=? AND role='superadmin' LIMIT 1");
    $stmt->bind_param('i',$sdsAdminId);$stmt->execute();$admin=$stmt->get_result()->fetch_assoc();$stmt->close();
    if(!$admin) return false;
    $username=(string)$admin['username'];$passwordHash=(string)$admin['password'];$email=trim((string)$admin['email']);$emailValue=$email===''?null:$email;$name=(string)($admin['full_name']?:$username);
    $stmt=$conn->prepare('SELECT id FROM perpus_users WHERE sds_admin_id=? OR username=? ORDER BY sds_admin_id IS NULL,id LIMIT 1');$stmt->bind_param('is',$sdsAdminId,$username);$stmt->execute();$local=$stmt->get_result()->fetch_assoc();$stmt->close();
    if($local){$localId=(int)$local['id'];$stmt=$conn->prepare("UPDATE perpus_users SET sds_admin_id=?,email=?,password=?,nama_lengkap=?,role='admin',status='aktif' WHERE id=?");$stmt->bind_param('isssi',$sdsAdminId,$emailValue,$passwordHash,$name,$localId);}
    else{$stmt=$conn->prepare("INSERT INTO perpus_users(sds_admin_id,username,email,password,nama_lengkap,role,status) VALUES(?,?,?,?,?,'admin','aktif')");$stmt->bind_param('issss',$sdsAdminId,$username,$emailValue,$passwordHash,$name);}
    $stmt->execute();if(!$local)$localId=(int)$conn->insert_id;$stmt->close();
    session_regenerate_id(true);$_SESSION['perpus_user_id']=$localId;$_SESSION['admin_id']=$localId;$_SESSION['perpus_user_name']=$name;$_SESSION['perpus_username']=$username;$_SESSION['perpus_user_role']='admin';$_SESSION['perpus_login_source']='sds_session';
    return true;
}

function perpus_user(): ?array
{
    if (empty($_SESSION['perpus_user_id'])) return null;
    return [
        'id' => (int)$_SESSION['perpus_user_id'],
        'name' => (string)($_SESSION['perpus_user_name'] ?? 'Staf'),
        'username' => (string)($_SESSION['perpus_username'] ?? ''),
        'role' => (string)($_SESSION['perpus_user_role'] ?? 'staf'),
    ];
}

function perpus_require_login(mysqli $conn, bool $adminOnly = false): array
{
    perpus_session_start();
    perpus_ensure_user_schema($conn);
    $user = perpus_user();
    if (!$user && perpus_login_from_sds_session($conn)) $user = perpus_user();
    if (!$user) {
        $intended = (string)($_SERVER['REQUEST_URI'] ?? '');
        $basePath = (string)(parse_url(sds_base_url('perpustakaan/'), PHP_URL_PATH) ?? '');
        if ($intended !== '' && $basePath !== '' && str_starts_with($intended, $basePath)) {
            $_SESSION['perpus_intended_url'] = $intended;
        }
        header('Location: ' . sds_base_url('perpustakaan/login'));
        exit;
    }
    $stmt = $conn->prepare("SELECT id,username,nama_lengkap,role,status FROM perpus_users WHERE id=? LIMIT 1");
    $stmt->bind_param('i', $user['id']); $stmt->execute();
    $fresh = $stmt->get_result()->fetch_assoc(); $stmt->close();
    if (!$fresh || $fresh['status'] !== 'aktif') {
        sds_session_destroy();
        perpus_session_start();
        $_SESSION['perpus_login_notice'] = 'Akun Anda sudah dinonaktifkan.';
        header('Location: ' . sds_base_url('perpustakaan/login')); exit;
    }
    if ($adminOnly && $fresh['role'] !== 'admin') { http_response_code(403); exit('Akses hanya untuk admin perpustakaan.'); }
    $_SESSION['perpus_user_name'] = $fresh['nama_lengkap'];
    $_SESSION['perpus_user_role'] = $fresh['role'];
    return ['id'=>(int)$fresh['id'],'name'=>(string)$fresh['nama_lengkap'],'username'=>(string)$fresh['username'],'role'=>(string)$fresh['role']];
}
