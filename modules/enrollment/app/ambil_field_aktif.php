<?php
require dirname(__DIR__, 3) . '/db.php';

$query = mysqli_query($conn, "SELECT name, is_required, is_active FROM form_fields");

$data = [];
while ($row = mysqli_fetch_assoc($query)) {
    if ((int)$row['is_active'] === 0) {
        $data[$row['name']] = 'hidden';
    } elseif ((int)$row['is_required'] === 1) {
        $data[$row['name']] = 'required';
    } else {
        $data[$row['name']] = 'optional';
    }
}

header('Content-Type: application/json');
echo json_encode($data);
exit;
