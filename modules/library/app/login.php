<?php
declare(strict_types=1);
require_once __DIR__ . '/auth.php';
perpus_session_start();
require_once dirname(__DIR__) . '/db.php';
perpus_ensure_user_schema($conn);
if (perpus_user()) { header('Location: ' . sds_base_url('perpustakaan/dashboard')); exit; }
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $identity = trim((string)($_POST['identity'] ?? ''));
    $wait = sds_rate_limit_check('perpus-login', $identity, 5, 300);
    if (!sds_csrf_verify($_POST['csrf'] ?? null)) $error = 'Sesi formulir berakhir. Muat ulang halaman.';
    elseif ($wait > 0) $error = 'Terlalu banyak percobaan. Coba lagi dalam ' . $wait . ' detik.';
    else {
        $stmt=$conn->prepare("SELECT * FROM perpus_users WHERE (username=? OR email=?) AND status='aktif' LIMIT 1");
        $stmt->bind_param('ss',$identity,$identity);$stmt->execute();$user=$stmt->get_result()->fetch_assoc();$stmt->close();
        if (!$user || !password_verify((string)($_POST['password'] ?? ''),(string)$user['password'])) {
            sds_rate_limit_fail('perpus-login',$identity); $error='Username/email atau password salah.';
        } else {
            sds_rate_limit_clear('perpus-login',$identity); session_regenerate_id(true);
            $_SESSION['perpus_user_id']=(int)$user['id']; $_SESSION['perpus_user_name']=(string)$user['nama_lengkap'];
            $_SESSION['perpus_username']=(string)$user['username']; $_SESSION['perpus_user_role']=(string)$user['role'];
            // Alias internal untuk kolom audit modul lama; cookie sesi tetap khusus Perpustakaan.
            $_SESSION['admin_id']=(int)$user['id'];
            $id=(int)$user['id'];$conn->query("UPDATE perpus_users SET last_login_at=NOW() WHERE id={$id}");
            $next=(string)($_SESSION['perpus_intended_url']??'');
            unset($_SESSION['perpus_intended_url']);
            if ($next==='' || !str_starts_with($next,sds_base_url('perpustakaan/'))) $next=sds_base_url('perpustakaan/dashboard');
            header('Location: '.$next);exit;
        }
    }
}
$notice=(string)($_SESSION['perpus_login_notice']??'');unset($_SESSION['perpus_login_notice']);
$school='Perpustakaan';$logo='';$q=$conn->query('SELECT nama_sekolah,logo FROM pengaturan LIMIT 1');if($q&&($x=$q->fetch_assoc())){$school=(string)$x['nama_sekolah'];if(!empty($x['logo']))$logo=sds_base_url('uploads/logo/'.rawurlencode(basename((string)$x['logo'])));}
function lh($v):string{return htmlspecialchars((string)$v,ENT_QUOTES,'UTF-8');}
?><!doctype html><html lang="id"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="theme-color" content="#123a31"><title>Login Perpustakaan · <?=lh($school)?></title><link rel="stylesheet" href="assets/css/login.css?v=2.7.0"></head><body><main class="login-shell"><section class="login-brand" aria-label="Identitas sekolah"><div class="book"><?php if($logo):?><img src="<?=lh($logo)?>" alt="Logo <?=lh($school)?>"><?php else:?>&#128214;<?php endif;?></div><p>RUANG KERJA MANDIRI</p><h1><?=lh($school)?></h1><span>Perpustakaan</span><small>Kelola koleksi, anggota, sirkulasi, dan layanan literasi sekolah dalam satu ruang kerja.</small><div class="login-features" aria-hidden="true"><span>Koleksi</span><span>Sirkulasi</span><span>Anggota</span></div></section><section class="login-card"><div class="login-card-head"><span class="login-kicker">PERPUSTAKAAN</span><h2>Selamat datang</h2><p>Masuk menggunakan akun admin atau staf perpustakaan.</p></div><?php if($error):?><div class="alert" role="alert"><?=lh($error)?></div><?php endif;?><?php if($notice):?><div class="alert notice" role="status"><?=lh($notice)?></div><?php endif;?><form method="post"><input type="hidden" name="csrf" value="<?=lh(sds_csrf_token())?>"><label for="identity">Username atau email<input id="identity" name="identity" required autofocus autocomplete="username" placeholder="Masukkan username atau email"></label><label for="password">Password<input id="password" type="password" name="password" required autocomplete="current-password" placeholder="Masukkan password"></label><button>Masuk ke Perpustakaan <span aria-hidden="true">→</span></button></form><a class="opac" href="opac/">Buka katalog publik (OPAC) <span aria-hidden="true">↗</span></a><p class="login-help">Akun staf dikelola oleh admin perpustakaan.</p></section></main><footer>© <?=date('Y')?> <?=lh($school)?></footer></body></html>
