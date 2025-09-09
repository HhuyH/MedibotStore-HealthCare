<?php
session_start();
require_once '../includes/db.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit();
}

// Get user information
$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['full_name'] ?? $_SESSION['username'] ?? 'Người dùng';
$user_role = $_SESSION['role_name'] ?? 'patient';

$role_display = [
    'admin' => 'Quản trị viên',
    'patient' => 'Bệnh nhân', 
    'doctor' => 'Bác sĩ'
];
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>🩺 AI Health Chat - Tư vấn sức khỏe thông minh | MediSync</title>
    
    <!-- Fonts & Icons -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/marked/marked.min.js"></script>
    
    <!-- Custom Styles -->
    <link href="health-ai-chat.css" rel="stylesheet">
</head>

<body>
    <!-- Removed floating shapes for better performance -->

    <div class="chat-wrapper">
        <!-- Header -->
        <div class="chat-header">
            <div class="header-left">
                <button class="back-btn" onclick="goHome()" title="Quay lại trang chủ">
                    <i class="fas fa-arrow-left"></i>
                </button>
                <div class="chat-info">
                    <h1>AI Health Chat</h1>
                    <p>Trợ lý sức khỏe thông minh</p>
                </div>
            </div>
            
            <div class="header-right">
                <div class="user-info">
                    <div class="user-avatar">
                        <?= strtoupper(substr($user_name, 0, 1)) ?>
                    </div>
                    <div class="user-details">
                        <h4><?= htmlspecialchars($user_name) ?></h4>
                        <p><?= $role_display[$user_role] ?? 'Người dùng' ?></p>
                    </div>
                </div>
                
                <div class="header-actions">
                    <button class="action-btn close" onclick="closeChat()" title="Đóng trò chuyện">
                        <i class="fas fa-times"></i>
                    </button>
                    <button class="action-btn refresh" onclick="refreshChat()" title="Làm mới trò chuyện">
                        <i class="fas fa-sync-alt"></i>
                    </button>
                    <button class="action-btn danger" onclick="logout()" title="Đăng xuất">
                        <i class="fas fa-sign-out-alt"></i>
                    </button>
                </div>
            </div>
        </div>

        <!-- Main Chat -->
        <div class="chat-main">
            <!-- Removed duplicate chat controls - now only in header -->

            <!-- Inner Header -->
            <!-- <div class="chat-inner-header">
                <div class="inner-header-content">
                    <h1 class="chat-title">
                        <i class="fas fa-robot"></i>
                        AI Health Assistant
                    </h1>
                    <p class="chat-subtitle">
                        Chào mừng <?= htmlspecialchars($user_name) ?>! Tôi sẵn sàng tư vấn sức khỏe cho bạn
                    </p>
                    
                    <div class="status-indicator">
                        <div class="status-dot"></div>
                        AI đang online - Sẵn sàng hỗ trợ
                    </div>
                </div>
            </div> -->

            <!-- Messages Area -->
            <div class="messages-area" id="chat-box">
                <!-- Welcome -->
                <!-- <div class="welcome-card" id="welcomeCard">
                    <div class="welcome-icon">
                        <i class="fas fa-robot"></i>
                    </div>
                    <h2 class="welcome-title">Xin chào! Tôi có thể giúp gì cho bạn? 👋</h2>
                    <p class="welcome-text">
                        Tôi là trợ lý AI chuyên về y tế và sức khỏe. Hãy đặt câu hỏi hoặc chọn một chủ đề dưới đây để bắt đầu!
                    </p>
                    
                    <div class="quick-suggestions">
                        <div class="suggestion-btn" onclick="sendMessage('Tôi bị đau đầu thường xuyên, nguyên nhân có thể là gì?')">
                            <span class="suggestion-emoji">🤕</span>
                            <div class="suggestion-title">Đau đầu</div>
                            <div class="suggestion-desc">Tư vấn về triệu chứng đau đầu</div>
                        </div>
                        
                        <div class="suggestion-btn" onclick="sendMessage('Làm thế nào để chăm sóc da mặt hiệu quả?')">
                            <span class="suggestion-emoji">✨</span>
                            <div class="suggestion-title">Chăm sóc da</div>
                            <div class="suggestion-desc">Hướng dẫn chăm sóc da đúng cách</div>
                        </div>
                        
                        <div class="suggestion-btn" onclick="sendMessage('Chế độ dinh dưỡng nào phù hợp cho trẻ em?')">
                            <span class="suggestion-emoji">👶</span>
                            <div class="suggestion-title">Dinh dưỡng trẻ em</div>
                            <div class="suggestion-desc">Tư vấn dinh dưỡng cho trẻ</div>
                        </div>
                        
                        <div class="suggestion-btn" onclick="sendMessage('Cách phòng ngừa cảm cúm hiệu quả?')">
                            <span class="suggestion-emoji">🛡️</span>
                            <div class="suggestion-title">Phòng ngừa bệnh</div>
                            <div class="suggestion-desc">Tăng cường miễn dịch</div>
                        </div>
                        
                        <div class="suggestion-btn" onclick="sendMessage('Làm thế nào để chăm sóc người cao tuổi?')">
                            <span class="suggestion-emoji">👴</span>
                            <div class="suggestion-title">Chăm sóc người già</div>
                            <div class="suggestion-desc">Sức khỏe người cao tuổi</div>
                        </div>
                        
                        <div class="suggestion-btn" onclick="sendMessage('Tôi muốn tập thể dục để giảm cân, bắt đầu như thế nào?')">
                            <span class="suggestion-emoji">💪</span>
                            <div class="suggestion-title">Thể dục & giảm cân</div>
                            <div class="suggestion-desc">Lời khuyên về tập luyện</div>
                        </div>
                    </div>
                </div> -->

                <!-- Typing indicator will be dynamically created -->
            </div>

            <!-- Input Area -->
            <div class="input-area">
                <form id="chat-form">
                    <div class="input-container">
                        <textarea 
                            id="userInput" 
                            class="message-input" 
                            placeholder="Nhập câu hỏi về sức khỏe của bạn..." 
                            rows="1"
                            required
                        ></textarea>
                        <div class="input-actions">
                            <button type="button" class="attach-btn" title="Đính kèm file">
                                <i class="fas fa-paperclip"></i>
                            </button>
                            <button type="submit" class="send-btn" id="sendBtn">
                                <i class="fas fa-paper-plane"></i>
                            </button>
                                <button type="button" class="reset-btn" id="reset-chat" title="Reset tạm thời">
                                <i class="fas fa-undo-alt"></i>
                            </button>

                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- <script src="/KMS-HealthCare/assets/js/chat.js"></script> -->
    <script src="../assets/js/chat.js"></script>
</body>
</html> 