<?php
if (isset($_SESSION['error'])) {
    echo '<div style="color: red; margin-bottom: 15px;">' . $_SESSION['error'] . '</div>';
    unset($_SESSION['error']);
}

// Tahun ajaran aktif disediakan oleh master Tahun Ajaran SDS.
$tahunAjaran = (string)($tahunAjaran ?? '');


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tahun = $_POST['tahun_ajaran'];
    $kelas = $_POST['nama_kelas'];
    $walas = $_POST['wali_kelas'];
    $kuota = (int)$_POST['kuota'];
    $jurusan_id = (int)$_POST['jurusan_id'];
    $tingkat_id = (int)$_POST['tingkat_id'];

    // Validasi tingkat_id
    if (empty($tingkat_id)) {
        $_SESSION['error'] = "Tingkat kelas wajib dipilih.";
        header("Location: kuota_kelas");
        exit;
    }

    // Cek apakah kelas dengan nama dan tahun ajaran yang sama sudah ada
    $cek = $conn->prepare("SELECT COUNT(*) FROM kelas WHERE tahun_ajaran = ? AND nama_kelas = ? AND tingkat_id = ?");
    $cek->bind_param("ssi", $tahun, $kelas, $tingkat_id);
    $cek->execute();
    $cek->bind_result($jumlah);
    $cek->fetch();
    $cek->close();

    if ($jumlah > 0) {
        $_SESSION['error'] = "Kelas '$kelas' untuk tahun ajaran $tahun dan tingkat yang dipilih sudah ada.";
        header("Location: kuota_kelas");
        exit;
    } else {
        if (isset($_SESSION['admin_id'])) {
            $keterangan = "Menambah data kelas ($kelas) untuk tahun ajaran $tahun, jurusan ID: $jurusan_id, tingkat ID: $tingkat_id, kuota: $kuota, walas: $walas";
            catatLog($conn, $_SESSION['admin_id'], 'Tambah Kelas', $keterangan);
        }

        $stmt = $conn->prepare("INSERT INTO kelas (tahun_ajaran, nama_kelas, wali_kelas, kuota, jurusan_id, tingkat_id) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sssiii", $tahun, $kelas, $walas, $kuota, $jurusan_id, $tingkat_id);
        $stmt->execute();
        $stmt->close();

        $_SESSION['success'] = "Kelas <strong>$kelas</strong> berhasil <strong>ditambahkan.</strong>";
        header("Location: kuota_kelas");
        exit;
    }
}
