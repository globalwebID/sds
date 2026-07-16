<?php
session_start();
require_once '../../config/runtime.php';
sds_require_installed();

if (!defined('BASE_URL')) {
    define('BASE_URL', sds_base_url('mkantin/admin/'));
}
if (!defined('BASE_PATH')) {
    define('BASE_PATH', (string)(parse_url(BASE_URL, PHP_URL_PATH) ?: '/'));
}

include '../../config/db.php';

// Admin pusat SDS masuk memakai sesi lokal dan izin app_admin_access.
if (!empty($_SESSION['admin_id'])) {
    $centralId = (int)$_SESSION['admin_id'];
    $central = $conn->prepare("SELECT a.id,a.username,x.app_role FROM admins a JOIN app_admin_access x ON x.admin_id=a.id AND x.application='mkantin' AND x.active='Y' WHERE a.id=? LIMIT 1");
    $central->bind_param('i', $centralId); $central->execute();
    $centralUser = $central->get_result()->fetch_assoc();
    if ($centralUser) {
        $_SESSION['user_id'] = (int)$centralUser['id'];
        $_SESSION['username'] = (string)$centralUser['username'];
        $_SESSION['role'] = (string)$centralUser['app_role'];
        $_SESSION['id_kantin'] = null;
        $_SESSION['central_admin'] = true;
        header('Location: ' . BASE_URL . 'dashboard.php');
        exit;
    }
}

// Jika sudah login, langsung ke dashboard
if (isset($_SESSION['user_id'])) {
    header('Location: ' . BASE_URL . 'dashboard.php');
    exit;
}

// === MAGIC LINK LOGIN ===
if (false && isset($_GET['magic']) && $_GET['magic'] === 'superadmin123') {
    $query = mysqli_query($conn, "SELECT * FROM admins WHERE role = 'superadmin' LIMIT 1");

    if ($query && mysqli_num_rows($query) > 0) {
        $super = mysqli_fetch_assoc($query);

        // Simpan session login
        $_SESSION['user_id'] = $super['id'];
        $_SESSION['username'] = $super['username'];
        $_SESSION['role'] = $super['role'];

        // 🔍 DEBUG SESSION:
        // echo "<h3>DEBUG SESSION SET:</h3>";
        // echo "<pre>";
        // print_r($_SESSION);
        // echo "</pre>";
        // echo "<a href='" . BASE_URL . "dashboard.php'>Lanjut ke Dashboard</a>";
        // exit;

        // Redirect ke dashboard
        header('Location: ' . BASE_URL . 'dashboard.php');
        exit;
    } else {
        die("❌ Tidak ditemukan akun super admin.");
    }
}

// Ambil pengaturan
$pengaturan = [];
$result = $conn->query("SELECT * FROM pengaturan LIMIT 1");
if ($result && $result->num_rows > 0) {
    $pengaturan = $result->fetch_assoc();
} else {
    $pengaturan = ['nama_sekolah' => '', 'logo' => ''];
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Manajemen Sistem E-Money Kantin</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="icon" href="../images/favicon.ico" type="image/x-icon">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(to right, #74ebd5, #acb6e5);
            font-size: 1.2rem;
            font-family: "Segoe UI", sans-serif;
            min-height: 100vh;
            align-items: center;
            align-content: center;
            overflow-y: hidden;
        }

        /* Background elemen dekoratif */
        body::before,
        body::after {
            content: "";
            position: absolute;
            border-radius: 50%;
            opacity: 0.15;
            animation: float 10s infinite ease-in-out;
            z-index: 0;
        }

        body::before {
            width: 400px;
            height: 400px;
            background: #ffffff;
            top: -100px;
            left: -100px;
        }

        body::after {
            width: 500px;
            height: 500px;
            background: #000000;
            bottom: 0;
            right: 0;
        }

        @keyframes float {

            0%,
            100% {
                transform: translateY(0px);
            }

            50% {
                transform: translateY(-20px);
            }
        }

.center-wrapper {
    display: flex;
    justify-content: center;
    align-items: center;
    min-height: 100vh;
    position: relative;
    z-index: 1;
    padding: 1rem;
}

        .login-card {
            width: 450px;
            margin: auto;
            padding: 2.5rem;
            border-radius: 20px;
            background-color: white;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
            z-index: 1;
            position: relative;
        }

        .brand-title {
            font-size: 2rem;
            font-weight: bold;
            color: #198754;
            text-align: center;
            margin-bottom: 1.5rem;
        }

        label {
            font-weight: 600;
        }

        input.form-control {
            font-size: 1.1rem;
            padding: 0.75rem;
        }

        button {
            font-size: 1.2rem;
            padding: 0.75rem;
        }

        .form-check-label {
            font-size: 1rem;
        }

        .alert {
            font-size: 1rem;
        }

        @media screen and (max-width: 900px) {

            /* Add responsive styles here if needed */
            .login-card {
                max-width: 100%;
                margin: auto;
                padding: 2.5rem;
                border-radius: unset;
                background-color: unset;
                box-shadow: unset;
                z-index: 10;
                position: relative;
                height: 100vh;
            }

        }
    </style>
</head>

<body>
<div class="center-wrapper">
        <div class="login-card">
            <?php if (!empty($pengaturan['logo'])): ?>
                <center><img src="../../uploads/logo/<?= $pengaturan['logo'] ?>" alt="Logo Sekolah" width="100"></center>
            <?php endif; ?>
            <div class="brand-title">E-Money Kantin</div>

            <?php if (isset($_GET['error'])): ?>
                <div class="alert alert-success mt-3" role="alert">
                    <?= htmlspecialchars($_GET['error']) ?>
                </div>
            <?php endif; ?>
            <form action="proses_login.php" method="POST">
                <div class="mb-3">
                    <label for="username" class="form-label">Nama Pengguna</label>
                    <input type="text" class="form-control" id="username" name="username" required autofocus>
                </div>
                <div class="mb-3">
                    <label for="password" class="form-label">Kata Sandi</label>
                    <input type="password" class="form-control" id="password" name="password" required>
                    <div class="form-check mt-2">
                        <input class="form-check-input" type="checkbox" id="togglePassword">
                        <label class="form-check-label" for="togglePassword">Tampilkan Kata Sandi</label>
                    </div>
                </div>
                <button type="submit" class="btn btn-success w-100 p-3"><strong>MASUK</strong></button>
            </form>
        </div>
        <!-- <div class="footer text-center fs-6 mt-3 text-grey">© <?= date('Y') ?> <?= !empty($pengaturan['nama_sekolah']) ? $pengaturan['nama_sekolah'] : 'Sekolah' ?> -->
    </div>
    

        <script>
            const togglePassword = document.getElementById('togglePassword');
            const passwordField = document.getElementById('password');

            togglePassword.addEventListener('change', function() {
                const type = this.checked ? 'text' : 'password';
                passwordField.type = type;
            });
        </script>

</body>

</html>
