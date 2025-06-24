<?php
header('Content-Type: application/json');

$stats = [
    'total_lines' => 0,
    'cart_actions' => 0,
    'api_calls' => 0,
    'errors' => 0,
    'db_queries' => 0
];

$logFiles = glob('*.log');

foreach ($logFiles as $file) {
    if (file_exists($file)) {
        $lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        $stats['total_lines'] += count($lines);
        
        foreach ($lines as $line) {
            if (strpos($line, '[CART]') !== false) {
                $stats['cart_actions']++;
            } elseif (strpos($line, '[API]') !== false) {
                $stats['api_calls']++;
            } elseif (strpos($line, '[ERROR]') !== false) {
                $stats['errors']++;
            } elseif (strpos($line, '[DB]') !== false) {
                $stats['db_queries']++;
            }
        }
    }
}

echo json_encode($stats);
?> 