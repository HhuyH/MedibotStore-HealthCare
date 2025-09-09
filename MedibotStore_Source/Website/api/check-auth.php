<?php
header('Content-Type: application/json');
session_start();

echo json_encode([
    'authenticated' => isset($_SESSION['user_id']),
    'user_id' => $_SESSION['user_id'] ?? null
]);
?> 