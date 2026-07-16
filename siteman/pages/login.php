<?php
if (isset($_SESSION['admin_id'])) {
    $admin_id = $_SESSION['admin_id'];

    // Gunakan pencatat log terpusat agar sesuai schema log_aktivitas.
    catatLog($conn, $admin_id, 'Login', 'Login otomatis karena sesi admin masih aktif');

    // Redirect ke dashboard
    header("Location: dashboard");
    exit;
}

if (empty($_SESSION['login_csrf'])) {
    $_SESSION['login_csrf'] = bin2hex(random_bytes(32));
}

// Ambil pengaturan sekolah (misalnya hanya ada 1 baris data)
$pengaturan = [];

$result = $conn->query("SELECT * FROM pengaturan LIMIT 1");

if ($result && $result->num_rows > 0) {
    $pengaturan = $result->fetch_assoc();
} else {
    // Default jika belum ada data
    $pengaturan = [
        'nama_sekolah' => '',
        'logo' => ''
    ];
}


?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Login Admin – SMKN 1 Probolinggo</title>

    <!-- Google Font -->
    <link rel="preconnect" href="https://fonts.gstatic.com" />
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet" />

    <style>
        :root {
            --primary: #1a73e8;
            --primary-dark: rgb(4, 92, 146);
            --bg: #f4f6fb;
            --card-bg: #ffffff;
            --radius: 12px;
            --shadow: 0 8px 20px rgba(0, 0, 0, .06);
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: "Inter", sans-serif;
        }

        body {
            background: var(--bg);
            background-image: url('<?= htmlspecialchars(sds_base_url('uploads/bg/bg_smk.jpg'), ENT_QUOTES, 'UTF-8') ?>');
            display: flex;
            align-items: center;
            color: #333;
            background-size: cover;
            background-repeat: no-repeat;
        }

        .card {
            width: 100%;
            max-width: 380px;
            background: #e9e9e9;
            border-radius: var(--radius);
            box-shadow: 0 8px 20px rgb(0 0 0 / 53%);
            padding: 40px 32px;
        }

        .card header {
            text-align: center;
            margin-bottom: 32px;
        }

        .card header h1 {
            font-size: 24px;
            color: #000;
            font-weight: 600;
        }

        .input-group {
            margin-bottom: 22px;
        }

        label {
            display: block;
            font-size: 14px;
            font-weight: 500;
            margin-bottom: 6px;
        }

        input {
            width: 100%;
            padding: 12px 14px;
            border: 1.5px solid #d1d5db;
            font-size: 15px;
            transition: border .25s ease;
        }

        input:focus {
            border-color: var(--primary);
            outline: none;
        }
        
        .btn {
            width: 100%;
            padding: 12px;
            border: none;
            border-radius: 8px;
            background-color: #4CAF50;
            background-image: linear-gradient(90deg, #8BC34A 0%, #8BC34A 74%);
            color: #fff;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
            transition: background .25s ease;
        }
        .btn:hover {
            background: yellow;
            color:fff;
        }

        button {
            width: 100%;
            padding: 12px;
            border: none;
            border-radius: 8px;
            background-color: #0367a6;
            background-image: linear-gradient(90deg, #0367a6 0%, #008997 74%);
            color: #fff;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
            transition: background .25s ease;
        }

        button:hover {
            background: var(--primary-dark);
        }

        .footer {
            text-align: center;
            margin-top: 24px;
            font-size: 13px;
            color: #777;
        }

        .error {
            color: #f44336;
            font-size: 13px;
            margin-top: 6px;
        }

        .alert-danger {
            background: #ffe5e5;
            border: 1px solid #f44336;
            color: #f44336;
            padding: 10px;
            border-radius: 6px;
            margin-bottom: 16px;
            font-size: 14px;
        }
    </style>
</head>

<body>

    <div class="container" style="
    justify-items: center;
">
        <section class="card center">
            <header>
                <?php if (!empty($pengaturan['logo'])): ?>
                    <img src="../uploads/logo/<?= $pengaturan['logo'] ?>" alt="Logo Sekolah" width="80">
                <?php endif; ?>
                <h1>Super Admin</h1>
            </header>

            <?php if (!empty($_SESSION['error'])): ?>
                <div class="alert-danger"><?= htmlspecialchars($_SESSION['error']) ?></div>
                <?php unset($_SESSION['error']); ?>
            <?php endif; ?>

            <form id="loginForm" method="POST" action="login_proses" autocomplete="off">
                <input type="hidden" name="csrf" value="<?= htmlspecialchars($_SESSION['login_csrf'], ENT_QUOTES, 'UTF-8') ?>">
                <div class="input-group">
                    <!-- <label for="username">Username</label> -->
                    <input type="text" id="username" name="username" placeholder="Username" required>
                    <div id="userErr" class="error" style="display:none;">Masukkan username!</div>
                </div>

                <div class="input-group">
                    <!-- <label for="password">Kata Sandi</label> -->
                    <input type="password" id="password" name="password" placeholder="Password" required>
                    <div id="passErr" class="error" style="display:none;">Masukkan kata sandi!</div>
                </div>

                <button type="submit" class="btn-float center">Masuk</button>
                <a href="../../../monitor/dashboard_kepsek" class="btn btn-float center" target="_blank">Monitor</a>
            </form>

            <!-- <div class="footer">© <?= date('Y') ?> SMKN 1 Probolinggo</div> -->
        </section>
    </div>

    <script>
        const form = document.getElementById('loginForm');
        form.addEventListener('submit', e => {
            let valid = true;
            const username = form.username.value.trim();
            const password = form.password.value.trim();

            document.getElementById('userErr').style.display = username ? 'none' : 'block';
            document.getElementById('passErr').style.display = password ? 'none' : 'block';

            if (!username || !password) e.preventDefault();
        });
    </script>

</body>

</html>
