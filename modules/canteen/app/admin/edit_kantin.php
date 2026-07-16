<?php
include 'inc/fungsi.php';

$id = (int)($_POST['id'] ?? $_GET['id'] ?? 0);
if (!$id) {
    header("Location: kantin.php");
    exit;
}

// Ambil data kantin
$stmt=$conn->prepare('SELECT * FROM kantin WHERE id=? LIMIT 1');$stmt->bind_param('i',$id);$stmt->execute();$kantin=$stmt->get_result()->fetch_assoc();$stmt->close();
if (!$kantin) {
    header("Location: kantin.php");
    exit;
}

// Ambil user kantin
$stmt=$conn->prepare("SELECT * FROM users WHERE id_kantin=? AND role='kantin' LIMIT 1");$stmt->bind_param('i',$id);$stmt->execute();$user=$stmt->get_result()->fetch_assoc();$stmt->close();

// Update proses
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!sds_csrf_verify((string)($_POST['csrf'] ?? ''))) { http_response_code(419); exit('Sesi formulir berakhir.'); }
    $nama = $_POST['nama'];
    $lokasi = $_POST['lokasi'];
    $username = $_POST['username'];
    $new_password = $_POST['password'];

    // Proses gambar
    if (!empty($_FILES['gambar']['name'])) {
        $extension=sds_validate_upload($_FILES['gambar'],['jpg','jpeg','png','webp'],5*1024*1024);
        $gambar='kantin_'.bin2hex(random_bytes(12)).'.'.$extension;
        $destination=dirname(__DIR__).'/images/kantin/'.$gambar;
        if(!is_dir(dirname($destination)))mkdir(dirname($destination),0755,true);
        if(!move_uploaded_file((string)$_FILES['gambar']['tmp_name'],$destination))throw new RuntimeException('Gagal menyimpan gambar.');
    } else {
        $gambar = $kantin['gambar'];
    }

    // Update kantin
    $stmt = $conn->prepare("UPDATE kantin SET nama=?, lokasi=?, gambar=? WHERE id=?");
    $stmt->bind_param("sssi", $nama, $lokasi, $gambar, $id);
    $stmt->execute();

    // Update user kantin
    if ($user) {
        if (!empty($new_password)) {
            $hashed = password_hash($new_password, PASSWORD_DEFAULT);// Ganti dengan password_hash di produksi
            $stmt_user = $conn->prepare("UPDATE users SET username=?, password=? WHERE id=?");
            $stmt_user->bind_param("ssi", $username, $hashed, $user['id']);
        } else {
            $stmt_user = $conn->prepare("UPDATE users SET username=? WHERE id=?");
            $stmt_user->bind_param("si", $username, $user['id']);
        }
        $stmt_user->execute();
    }

    header("Location: kantin.php?success=Data berhasil diperbarui");
    exit;
}
