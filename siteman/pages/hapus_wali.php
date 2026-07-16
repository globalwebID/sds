<?php
$id = $_GET['id'] ?? null;

if ($id) {
    // Query UPDATE untuk menghapus data wali
    $sql = "UPDATE pendaftaran_siswa SET 
        nama_wali = NULL,
        nik_wali = NULL,
        tahun_lahir_wali = NULL,
        pendidikan_wali = NULL,
        pekerjaan_wali = NULL,
        penghasilan_wali = NULL
    WHERE id = ?";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);

    if ($stmt->execute()) {
        $_SESSION['success'] = "Data wali berhasil dihapus.";
        header("Location: student_view?id=$id#dataWali");
        exit;
    } else {
        $_SESSION['error'] = "Data wali Gagal dihapus.";
        header("Location: student_view?id=$id#dataWali");
    }
} else {
    echo "ID siswa tidak ditemukan.";
}
