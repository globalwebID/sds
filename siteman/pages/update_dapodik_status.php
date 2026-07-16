<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (empty($_SESSION['admin_id']) || !sds_csrf_verify((string)($_POST['csrf'] ?? ''))) { http_response_code(419); exit('Permintaan tidak valid.'); }
    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    $sudah_dapodik = isset($_POST['sudah_dapodik']) ? 1 : 0;

    if ($id > 0) {
        $stmt = $conn->prepare("UPDATE pendaftaran_siswa SET sudah_dapodik = ? WHERE id = ?");
        $stmt->bind_param('ii', $sudah_dapodik, $id);
        $stmt->execute();
        $stmt->close();
    }
}
$_SESSION['success'] = "Status DAPODIK berhasil diperbarui.";
header("Location: student_view?id=$id");
exit;
