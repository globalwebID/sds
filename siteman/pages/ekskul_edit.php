<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = intval($_POST['id']);
    $nama = trim($_POST['nama_ekskul']);
    $nama_pembina = $_POST['nama_pembina'];

    if (!empty($nama)) {
        $stmt = $conn->prepare("UPDATE ekstrakurikuler SET nama_ekskul = ?, nama_pembina = ? WHERE id = ?");
        $stmt->bind_param("ssi", $nama, $nama_pembina, $id);
        $stmt->execute();

        if ($stmt->affected_rows > 0) {
            $_SESSION['msg'] = ['type' => 'success', 'text' => '✅ Ekstrakurikuler berhasil diperbarui.'];
        } else {
            $_SESSION['msg'] = ['type' => 'warning', 'text' => '⚠️ Tidak ada perubahan data.'];
        }
    } else {
        $_SESSION['msg'] = ['type' => 'danger', 'text' => '❌ Nama tidak boleh kosong.'];
    }
}

header("Location: ekskul");
exit;
