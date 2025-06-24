<?php
header('Content-Type: application/json');

$file = $_GET['file'] ?? '';

if (empty($file) || !file_exists($file)) {
    echo json_encode(['success' => false, 'message' => 'File not found']);
    exit;
}

if (file_put_contents($file, '') !== false) {
    echo json_encode(['success' => true, 'message' => 'Log cleared']);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to clear log']);
}
?> 