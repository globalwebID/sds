<?php if(!isset($_COOKIE['ADMIN_KEY']) && !isset($_COOKIE['KEY'])){
  http_response_code(400);
  echo json_encode(['error' => 'File tidak ditemukan atau error']);
}else{

$uploadDir      = '../../../sw-content/artikel/';
if (!is_dir($uploadDir)) {
  mkdir($uploadDir, 0755, true);
}

    if (isset($_FILES['file']) && $_FILES['file']['error'] === UPLOAD_ERR_OK) {
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        $fileType = mime_content_type($_FILES['file']['tmp_name']);
    
        if (!in_array($fileType, $allowedTypes)) {
            http_response_code(400);
            echo json_encode(['error' => 'Hanya file gambar yang diperbolehkan (JPG, PNG, GIF, WEBP).']);
            exit;
        }
    
        $filename = time() . '-' . preg_replace('/[^a-zA-Z0-9.\-_]/', '', $_FILES['file']['name']);
        $targetPath = $uploadDir . $filename;
    
        if (move_uploaded_file($_FILES['file']['tmp_name'], $targetPath)) {
            echo json_encode(['location' => '../sw-content/artikel/' . $filename]);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Upload gagal']);
        }

    }
}
?>