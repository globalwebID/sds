<?php
require_once __DIR__ . '/../../config/runtime.php';
sds_session_start();
if (isset($conn) && $conn instanceof mysqli && session_id() !== '') {
    $sessionHash=hash('sha256',session_id());
    $stmt=$conn->prepare('DELETE FROM sds_admin_sessions WHERE session_hash=?');
    $stmt->bind_param('s',$sessionHash);$stmt->execute();$stmt->close();
}
sds_session_destroy();
header('Location: index');
