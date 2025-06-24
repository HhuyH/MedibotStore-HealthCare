<?php
header('Content-Type: application/json');

require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/functions/product_functions.php';

// Lấy ID sản phẩm từ URL
$product_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Lấy thông tin sản phẩm
$product = getProductDetails($product_id);

if (!$product) {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'Không tìm thấy sản phẩm']);
    exit;
}

// Trả về thông tin sản phẩm
echo json_encode([
    'success' => true,
    'data' => $product
]); 