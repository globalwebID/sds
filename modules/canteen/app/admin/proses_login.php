<?php
session_start();
include '../../config/db.php';
include '../../config/config.php';

$username = $_POST['username'] ?? '';
$password = $_POST['password'] ?? '';

// Ambil user hanya berdasarkan username
$stmt = $conn->prepare("SELECT * FROM users WHERE username = ?");
$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

// Jika bukan akun operasional mKantin, coba identitas admin pusat SDS.
if (!$user || !password_verify($password, (string)$user['password'])) {
    $stmt = $conn->prepare("SELECT a.id,a.username,a.password,x.app_role FROM admins a JOIN app_admin_access x ON x.admin_id=a.id AND x.application='mkantin' AND x.active='Y' WHERE a.username=? OR a.email=? LIMIT 1");
    $stmt->bind_param('ss', $username, $username); $stmt->execute();
    $central = $stmt->get_result()->fetch_assoc();
    if ($central && password_verify($password, (string)$central['password'])) {
        $user = ['id'=>$central['id'], 'username'=>$central['username'], 'password'=>$central['password'], 'role'=>$central['app_role'], 'id_kantin'=>null];
        $_SESSION['central_admin'] = true;
    }
}

if ($user && password_verify($password, $user['password'])) {

    // Set session
    $_SESSION['user_id']   = $user['id'];
    $_SESSION['username'] = $user['username'];
    $_SESSION['role']     = $user['role'];
    $_SESSION['id_kantin'] = $user['id_kantin'];

    // Redirect berdasarkan role
    switch ($user['role']) {
        case 'admin':
        case 'superadmin':
            header('Location: ' . BASE_PATH . 'dashboard.php');
            break;

        case 'operator':
            header('Location: ' . BASE_PATH . 'topup.php');
            break;

        case 'kantin':
            header('Location: ' . BASE_PATH . 'kantin/dashboard.php');
            break;

        case 'siswa':
            header('Location: ' . BASE_PATH . 'siswa/dashboard.php');
            break;

        default:
            header('Location: ' . BASE_PATH . 'login.php?error=Role tidak dikenali.');
    }
    exit;

} else {
    header('Location: ' . BASE_PATH . 'login.php?error=Username atau password salah.');
    exit;
}
