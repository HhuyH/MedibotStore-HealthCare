<?php
require_once '../db.php';
require_once '../functions/product_functions.php';

header('Content-Type: application/json');

if (!isset($_GET['q']) || empty($_GET['q'])) {
    echo json_encode([]);
    exit;
}

$search = trim($_GET['q']);
$suggestions = [];

try {
    $sql = "SELECT p.id, p.name, p.slug, c.name as category_name 
            FROM products p 
            LEFT JOIN categories c ON p.category_id = c.id
            WHERE p.name LIKE ? 
               OR p.description LIKE ? 
               OR c.name LIKE ?
            LIMIT 5";
            
    $search_param = "%$search%";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sss", $search_param, $search_param, $search_param);
    $stmt->execute();
    
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $suggestions[] = [
            'id' => $row['id'],
            'title' => $row['name'],
            'url' => "/shop/product.php?slug=" . $row['slug'],
            'category' => $row['category_name']
        ];
    }
    
    echo json_encode($suggestions);
} catch (Exception $e) {
    error_log("Search suggestion error: " . $e->getMessage());
    echo json_encode([]);
} 