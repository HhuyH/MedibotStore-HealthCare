<?php
session_start(); 
// Khởi động phiên làm việc PHP (session) để lưu trữ dữ liệu xuyên suốt các lần truy cập (ví dụ: lịch sử chat).

// Kiểm tra nếu chưa có mảng lưu lịch sử chat trong session thì khởi tạo mảng rỗng
if (!isset($_SESSION['messages'])) {
    $_SESSION['messages'] = [];
}

// Hàm gọi API FastAPI để gửi tin nhắn và nhận phản hồi từ chatbot
function Fast_api_response($user_input) {
    $url = 'http://127.0.0.1:8000/chat'; // Địa chỉ URL của API FastAPI (nơi chạy server chatbot)

    // Chuẩn bị dữ liệu gửi đi dưới dạng JSON
    $data = json_encode(['message' => $user_input]);

    // Khởi tạo cURL để gửi yêu cầu HTTP POST
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);       // Yêu cầu cURL trả về kết quả dưới dạng chuỗi, không in ra trực tiếp
    curl_setopt($ch, CURLOPT_POST, true);                 // Thiết lập phương thức gửi là POST
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']); // Header thông báo gửi dữ liệu dạng JSON
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);          // Gán dữ liệu JSON gửi lên server

    $response = curl_exec($ch);  // Thực hiện gọi API và nhận phản hồi
    curl_close($ch);             // Đóng kết nối cURL

    // Giải mã dữ liệu JSON nhận được thành mảng PHP. Nếu lỗi trả về false
    return json_decode($response, true);
}

// Xử lý khi người dùng gửi tin nhắn (dữ liệu POST và có trường 'message')
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['message'])) {
    // Lấy tin nhắn người dùng, dùng hàm htmlspecialchars để tránh mã độc (XSS)
    $user_message = htmlspecialchars($_POST['message']);
    
    // Lưu tin nhắn người dùng vào session, với định dạng: "👤 Bạn: tin nhắn"
    $_SESSION['messages'][] = "👤 Bạn: " . $user_message;

    // Gọi API FastAPI với tin nhắn người dùng, nhận phản hồi
    $response = Fast_api_response($user_message);

    if (!$response) {
        // Nếu lỗi gọi API hoặc không nhận được phản hồi
        $_SESSION['messages'][] = "🤖 Bot: Lỗi khi gọi API hoặc không nhận được phản hồi.";
    } else {
        // Lấy phản hồi từ API, key trả về là 'reply'
        $bot_reply = $response['reply'] ?? 'Không có phản hồi từ bot.';
        
        // Lưu phản hồi vào session, dùng htmlspecialchars để tránh mã độc
        $_SESSION['messages'][] = "🤖 Bot: " . htmlspecialchars($bot_reply);

        // Nếu API có trả thêm key 'sql', hiển thị luôn (phần này bạn có thể bỏ nếu không dùng)
        if (!empty($response['sql'])) {
            $_SESSION['messages'][] = "💾 SQL: " . htmlspecialchars($response['sql']);
        }
    }
}

?>