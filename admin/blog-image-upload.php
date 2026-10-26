<?php
require_once __DIR__ . '/includes/bootstrap.php';
adminRequireLogin();

header('Content-Type: application/json; charset=utf-8');
if (!adminVerifyPost()) {
    http_response_code(403);
    echo json_encode(array('ok' => false, 'message' => 'Your session has expired. Refresh the page and try again.'));
    exit;
}

list($path, $error) = blogUploadImageFile($_FILES['image'] ?? null, 'article-image');
if ($error !== '') {
    http_response_code(422);
    echo json_encode(array('ok' => false, 'message' => $error));
    exit;
}

echo json_encode(array('ok' => true, 'url' => '../' . $path));
