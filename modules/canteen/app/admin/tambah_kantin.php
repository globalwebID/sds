<?php
include 'inc/fungsi.php';

$error = '';

// Handle submit form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!sds_csrf_verify((string)($_POST['csrf'] ?? ''))) { http_response_code(419); exit('Sesi formulir berakhir.'); }
    $nama       = trim((string)($_POST['nama'] ?? ''));
    $lokasi     = trim((string)($_POST['lokasi'] ?? ''));
    $username   = trim((string)($_POST['username'] ?? ''));
    // $password   = md5($_POST['password']); // Ganti dengan password_hash() di produksi
$password = password_hash((string)($_POST['password'] ?? ''), PASSWORD_DEFAULT);


    // Cek apakah username sudah dipakai
    $cek = $conn->prepare('SELECT id FROM users WHERE username=? LIMIT 1');$cek->bind_param('s',$username);$cek->execute();$exists=$cek->get_result()->fetch_assoc();$cek->close();
    if ($exists) {
        $error = "Username sudah digunakan.";
    } else {
        // Upload gambar
        try {
            $extension=sds_validate_upload($_FILES['gambar'] ?? [],['jpg','jpeg','png','webp'],5*1024*1024);
            $gambar='kantin_'.bin2hex(random_bytes(12)).'.'.$extension;
            $destination=dirname(__DIR__).'/images/kantin/'.$gambar;
            if(!is_dir(dirname($destination)))mkdir(dirname($destination),0755,true);
            if(!move_uploaded_file((string)$_FILES['gambar']['tmp_name'],$destination))throw new RuntimeException('Gagal menyimpan gambar.');
            // Simpan kantin terlebih dahulu
            $stmt = $conn->prepare("INSERT INTO kantin (nama, lokasi, gambar) VALUES (?, ?, ?)");
            $stmt->bind_param("sss", $nama, $lokasi, $gambar);
            $stmt->execute();
            $id_kantin = $stmt->insert_id;

            // Simpan user kantin
            $stmt_user = $conn->prepare("INSERT INTO users (username, password, role, id_kantin) VALUES (?, ?, 'kantin', ?)");
            $stmt_user->bind_param("ssi", $username, $password, $id_kantin);
            $stmt_user->execute();

            $_SESSION['success'] = 'Data kantin & user berhasil ditambahkan';
            header("Location: kantin.php");
            exit;
        } catch(Throwable $e) { $error=$e->getMessage(); }
    }
}
