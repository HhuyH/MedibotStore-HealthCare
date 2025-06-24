<?php
$file = $_GET['file'] ?? '';
$filter = $_GET['filter'] ?? '';

if (empty($file) || !file_exists($file)) {
    echo '<div style="color: #f44336;">Log file not found</div>';
    exit;
}

$lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
$lines = array_reverse($lines); // Newest first

if (empty($lines)) {
    echo '<div style="color: #858585; text-align: center;">Log file is empty</div>';
    exit;
}

foreach ($lines as $line) {
    if (!empty($filter)) {
        if (strpos($line, "[{$filter}]") === false) {
            continue;
        }
    }
    
    // Parse log line
    if (preg_match('/\[(.*?)\] \[(.*?)\] \[(.*?)\] \[(.*?)\] (.*)/', $line, $matches)) {
        $timestamp = $matches[1];
        $type = $matches[2];
        $user = $matches[3];
        $ip = $matches[4];
        $message = $matches[5];
        
        echo "<div class='log-line {$type}'>";
        echo "<span class='timestamp'>[{$timestamp}]</span>";
        echo "<span class='log-type {$type}'>{$type}</span>";
        echo "<span style='color: #858585;'>{$user}</span>";
        echo "<span style='color: #d4d4d4; margin-left: 10px;'>{$message}</span>";
        echo "</div>";
    } else {
        echo "<div class='log-line'>{$line}</div>";
    }
}
?> 