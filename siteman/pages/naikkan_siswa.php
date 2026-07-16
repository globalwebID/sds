<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $siswaId = $_POST['siswa_id'];
    $kelas_id = (int)$_POST['kelas_id']; // kelas saat ini
    $tahunAjaran = $_POST['tahun_ajaran'];

    // Cek apakah siswa sudah terdaftar di tahun ajaran baru
    $cek = mysqli_query($conn, "SELECT * FROM siswa_kelas WHERE siswa_id = '$siswaId' AND tahun_ajaran = '$tahunAjaran'");
    if (mysqli_num_rows($cek) > 0) {
        header("Location: siswa_tidak_naik?status=gagal&msg=Siswa sudah terdaftar di tahun ajaran $tahunAjaran");
        exit;
    }

    // Ambil kelas asal dan jurusan-nya
    $kelasLama = mysqli_fetch_assoc(mysqli_query($conn, "
        SELECT k.*, j.nama_jurusan 
        FROM kelas k 
        JOIN jurusan j ON k.jurusan_id = j.id 
        WHERE k.id = '$kelas_id'
    "));

    if (!$kelasLama) {
        header("Location: siswa_tidak_naik?status=gagal&msg=Kelas asal tidak ditemukan.");
        exit;
    }

    $tingkatBaru = $kelasLama['tingkat_id'] + 1;
    $namaJurusan = $kelasLama['nama_jurusan'];

    // Cari jurusan_id baru berdasarkan nama jurusan dan tahun ajaran baru
    $jurusanBaru = mysqli_fetch_assoc(mysqli_query($conn, "
        SELECT DISTINCT j.id FROM jurusan j
        JOIN kelas k ON k.jurusan_id = j.id
        WHERE j.nama_jurusan = '$namaJurusan' AND k.tahun_ajaran = '$tahunAjaran'
        LIMIT 1
    "));

    if (!$jurusanBaru) {
        header("Location: siswa_tidak_naik?status=gagal&msg=Jurusan '$namaJurusan' belum tersedia di tahun ajaran $tahunAjaran");
        exit;
    }

    $jurusanIdBaru = $jurusanBaru['id'];

    // Cari kelas tujuan berdasarkan tingkat dan jurusan yang sama
    $kelasTujuan = mysqli_fetch_assoc(mysqli_query($conn, "
        SELECT id FROM kelas 
        WHERE tingkat_id = '$tingkatBaru' 
        AND jurusan_id = '$jurusanIdBaru'
        AND tahun_ajaran = '$tahunAjaran'
        LIMIT 1
    "));

    if (!$kelasTujuan) {
        header("Location: siswa_tidak_naik?status=gagal&msg=Kelas tingkat $tingkatBaru belum tersedia di jurusan '$namaJurusan' tahun $tahunAjaran");
        exit;
    }

    $kelasIdBaru = $kelasTujuan['id'];

    // Update naik_kelas pada entri lama
    mysqli_query($conn, "
        UPDATE siswa_kelas 
        SET naik_kelas = 1 
        WHERE siswa_id = '$siswaId' AND kelas_id = '$kelas_id'
    ");

    // Insert entri siswa baru
    $simpan = mysqli_query($conn, "
        INSERT INTO siswa_kelas (siswa_id, kelas_id, tahun_ajaran, naik_kelas)
        VALUES ('$siswaId', '$kelasIdBaru', '$tahunAjaran', 1)
    ");

    if ($simpan) {
        $_SESSION['success'] = "Siswa berhasil dinaikkan ke kelas tingkat $tingkatBaru.";
    } else {
        $_SESSION['error'] = "Gagal menyimpan data siswa ke kelas baru.";
    }

    header("Location: siswa_tidak_naik");
    exit;
}
