<?php
session_start();
include '../../includes/db.php';

// Kiểm tra đăng nhập và quyền admin
if (!isset($_SESSION['user_id']) || $_SESSION['role_id'] != 1) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

try {
    $deleted_files = 0;
    
    // Danh sách thư mục temp
    $temp_dirs = [
        '../../temp/',
        '../../tmp/',
        '../../cache/temp/',
        '../../assets/temp/'
    ];
    
    foreach ($temp_dirs as $dir) {
        if (is_dir($dir)) {
            $files = glob($dir . '*');
            foreach ($files as $file) {
                if (is_file($file)) {
                    $filename = basename($file);
                    // Không xóa file .gitkeep và file quan trọng
                    if ($filename !== '.gitkeep' && $filename !== 'index.html' && $filename !== 'index.php') {
                        if (unlink($file)) {
                            $deleted_files++;
                        }
                    }
                }
            }
        }
    }
    
    // Xóa session files cũ (nếu có)
    $session_path = session_save_path();
    if ($session_path && is_dir($session_path)) {
        $session_files = glob($session_path . '/sess_*');
        foreach ($session_files as $file) {
            if (is_file($file) && filemtime($file) < strtotime('-1 day')) {
                if (unlink($file)) {
                    $deleted_files++;
                }
            }
        }
    }
    
    echo json_encode([
        'success' => true, 
        'message' => "Đã xóa {$deleted_files} file tạm thời"
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false, 
        'message' => 'Lỗi: ' . $e->getMessage()
    ]);
}
?> 