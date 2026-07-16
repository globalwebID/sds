<?php
if (session_status() !== PHP_SESSION_ACTIVE) session_start();

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    header('Location: login');
    exit;
}

if (empty($_SESSION['login_csrf']) || !hash_equals((string)$_SESSION['login_csrf'], (string)($_POST['csrf'] ?? ''))) {
    $_SESSION['error'] = 'Sesi login tidak valid. Muat ulang halaman dan coba lagi.';
    header('Location: login');
    exit;
}

$username = trim((string)($_POST['username'] ?? ''));
$password = (string)($_POST['password'] ?? '');
$securitySettings=$conn->query('SELECT login_max_attempts,login_window_minutes,admin_session_minutes,password_expiry_days FROM pengaturan ORDER BY id LIMIT 1')->fetch_assoc()?:[];
$maxAttempts=max(3,min(20,(int)($securitySettings['login_max_attempts']??5)));
$windowSeconds=max(60,min(3600,(int)($securitySettings['login_window_minutes']??5)*60));
$retryAfter = sds_rate_limit_check('siteman_login', $username, $maxAttempts, $windowSeconds);
if ($retryAfter > 0) {
    $_SESSION['error'] = 'Terlalu banyak percobaan login. Coba lagi dalam ' . $retryAfter . ' detik.';
    header('Retry-After: ' . $retryAfter);
    header('Location: login');
    exit;
}
$failureTarget = 'login';
if ($username === '' || $password === '') {
    $_SESSION['error'] = 'Username dan password harus diisi!';
    header('Location: ' . $failureTarget);
    exit;
}

try {
    // 1. Akun administrator SDS.
    $stmt = $conn->prepare('SELECT * FROM admins WHERE username = ? OR email = ? LIMIT 1');
    if (!$stmt) throw new RuntimeException('Query administrator tidak tersedia.');
    $stmt->bind_param('ss', $username, $username);
    $stmt->execute();
    $admin = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($admin) {
        $adminId = (int)$admin['id'];
        $stored = (string)$admin['password'];
        $legacyMd5 = preg_match('/^[a-f0-9]{32}$/i', $stored) === 1 && hash_equals(strtolower($stored), md5($password));
        $valid = password_verify($password, $stored) || $legacyMd5;
        if (!$valid) throw new RuntimeException('Password salah!');

        if ($legacyMd5 || password_needs_rehash($stored, PASSWORD_DEFAULT)) {
            $newHash = password_hash($password, PASSWORD_DEFAULT);
            $upgrade = $conn->prepare('UPDATE admins SET password=? WHERE id=?');
            $upgrade->bind_param('si', $newHash, $adminId);
            $upgrade->execute();
            $upgrade->close();
        }

        session_regenerate_id(true);
        unset($_SESSION['login_csrf']);
        sds_rate_limit_clear('siteman_login', $username);
        $_SESSION['admin_id'] = (int)$admin['id'];
        $_SESSION['admin_name'] = (string)$admin['full_name'];
        $_SESSION['admin_role'] = (string)$admin['role'];
        $_SESSION['admin_username'] = (string)$admin['username'];
        $_SESSION['auth_type'] = 'admin';
        $_SESSION['_idle_timeout'] = max(600,min(86400,(int)($securitySettings['admin_session_minutes']??30)*60));
        $expiryDays=(int)($securitySettings['password_expiry_days']??0);
        $_SESSION['password_expired']=$expiryDays>0 && strtotime((string)($admin['password_changed_at']??$admin['created_at']??'now')) < time()-($expiryDays*86400);
        unset($_SESSION['teacher_id']);
        $sessionHash=hash('sha256',session_id());$ip=substr((string)($_SERVER['REMOTE_ADDR']??''),0,45);$ua=mb_substr((string)($_SERVER['HTTP_USER_AGENT']??''),0,500);
        $sessionStmt=$conn->prepare('REPLACE INTO sds_admin_sessions (session_hash,admin_id,ip_address,user_agent,last_activity,created_at) VALUES (?,?,?,?,NOW(),NOW())');$sessionStmt->bind_param('siss',$sessionHash,$adminId,$ip,$ua);$sessionStmt->execute();$sessionStmt->close();
        catatLog($conn, (int)$admin['id'], 'Login', 'Admin berhasil login');
        header('Location: dashboard');
        exit;
    }

    throw new RuntimeException('Username atau email tidak ditemukan!');
} catch (Throwable $e) {
    sds_rate_limit_fail('siteman_login', $username, $windowSeconds ?? 300);
    $_SESSION['error'] = $e->getMessage();
    header('Location: ' . $failureTarget);
    exit;
}
