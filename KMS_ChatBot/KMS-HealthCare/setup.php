<?php
// File setup database - Chỉ chạy một lần để tạo database
// CẢNH BÁO: Xóa file này sau khi setup xong để bảo mật
// Dung chạy file nay nheeee
$host = 'localhost';
$user = 'root';   
$pass = ''; // XAMPP mặc định không có password

try {
    // Kết nối MySQL không chỉ định database
    $pdo = new PDO("mysql:host=$host", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Đọc và thực thi file SQL
    $sql = file_get_contents('setup_database.sql');
    
    // Tách các câu lệnh SQL
    $statements = explode(';', $sql);
    
    foreach ($statements as $statement) {
        $statement = trim($statement);
        if (!empty($statement)) {
            $pdo->exec($statement);
        }
    }
    
    echo "<!DOCTYPE html>
    <html lang='vi'>
    <head>
        <meta charset='UTF-8'>
        <meta name='viewport' content='width=device-width, initial-scale=1.0'>
        <title>Setup Database</title>
        <link href='https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css' rel='stylesheet'>
    </head>
    <body>
        <div class='container mt-5'>
            <div class='row justify-content-center'>
                <div class='col-md-6'>
                    <div class='card'>
                        <div class='card-body text-center'>
                            <div class='text-success mb-3'>
                                <i class='fas fa-check-circle' style='font-size: 48px;'></i>
                            </div>
                            <h3 class='text-success'>Setup thành công!</h3>
                            <p class='mb-4'>Database đã được tạo và cấu hình thành công.</p>
                            
                            <div class='alert alert-info'>
                                <h6>Tài khoản admin mặc định:</h6>
                                <strong>Username:</strong> admin<br>
                                <strong>Password:</strong> admin123<br>
                                <strong>Email:</strong> admin@qickmed.com
                            </div>
                            
                            <div class='alert alert-warning'>
                                <strong>Bảo mật:</strong> Hãy xóa file setup.php sau khi hoàn tất!
                            </div>
                            
                            <a href='login.php' class='btn btn-primary'>Đăng nhập ngay</a>
                            <a href='register.php' class='btn btn-outline-secondary'>Đăng ký tài khoản mới</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </body>
    </html>";
    
} catch(PDOException $e) {
    echo "<!DOCTYPE html>
    <html lang='vi'>
    <head>
        <meta charset='UTF-8'>
        <meta name='viewport' content='width=device-width, initial-scale=1.0'>
        <title>Setup Database - Lỗi</title>
        <link href='https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css' rel='stylesheet'>
    </head>
    <body>
        <div class='container mt-5'>
            <div class='row justify-content-center'>
                <div class='col-md-6'>
                    <div class='card'>
                        <div class='card-body text-center'>
                            <div class='text-danger mb-3'>
                                <i class='fas fa-exclamation-triangle' style='font-size: 48px;'></i>
                            </div>
                            <h3 class='text-danger'>Lỗi Setup!</h3>
                            <p>Có lỗi xảy ra khi setup database:</p>
                            <div class='alert alert-danger'>
                                " . $e->getMessage() . "
                            </div>
                            
                            <div class='alert alert-info'>
                                <h6>Hướng dẫn khắc phục:</h6>
                                <ol class='text-start'>
                                    <li>Kiểm tra XAMPP/WAMP đã khởi động MySQL chưa</li>
                                    <li>Kiểm tra cấu hình database trong includes/db.php</li>
                                    <li>Đảm bảo user 'root' có quyền tạo database</li>
                                </ol>
                            </div>
                            
                            <button onclick='location.reload()' class='btn btn-primary'>Thử lại</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </body>
    </html>";
}
?> 