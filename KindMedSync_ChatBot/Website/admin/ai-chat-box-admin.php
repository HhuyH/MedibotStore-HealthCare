<?php
session_start();
include '../includes/db.php';

// Check admin permission
if (!isset($_SESSION['user_id']) || $_SESSION['role_id'] != 1) {
    header('Location: ../login.php');
    exit;
}

?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AI Chat Box - MediSync Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="assets/css/admin.css" rel="stylesheet">
    <link href="assets/css/sidebar.css" rel="stylesheet">
    <link href="assets/css/header.css" rel="stylesheet">
    <style>
        .chat-wrapper {
            background: #fff;
            border-radius: 1rem;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
            height: calc(100vh - 100px);
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }

        .chat-header {
            padding: 1rem;
            background: #f8f9fa;
            border-bottom: 1px solid #e9ecef;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .chat-title {
            font-size: 1.25rem;
            font-weight: 600;
            color: #2d3748;
            margin: 0;
        }

        .chat-subtitle {
            font-size: 0.875rem;
            color: #718096;
            margin: 0;
        }

        .chat-actions {
            display: flex;
            gap: 0.5rem;
        }

        .action-btn {
            padding: 0.5rem 1rem;
            border: none;
            border-radius: 0.5rem;
            background: #fff;
            color: #4a5568;
            font-size: 0.875rem;
            cursor: pointer;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .action-btn:hover {
            background: #edf2f7;
        }

        .action-btn.danger {
            color: #e53e3e;
        }

        .chat-messages {
            flex: 1;
            overflow-y: auto;
            padding: 1.5rem;
        }

        .message {
            display: flex;
            flex-direction: column;
            max-width: 70%;
            margin-bottom: 1.5rem;
            opacity: 0;
            transform: translateY(10px);
            animation: fadeIn 0.3s forwards;
        }

        @keyframes fadeIn {
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .message.user {
            align-self: flex-end;
            text-align: right;
        }

        .message.ai {
            align-self: flex-start;
            text-align: left;
        }

        .message-content {
            padding: 1rem 1.25rem;
            border-radius: 1.2rem;
            position: relative;
            line-height: 1.5;
            font-size: 0.95rem;
            word-wrap: break-word;
        }

        .message.user .message-content {
            background: #2563eb;
            color: white;
            border-bottom-right-radius: 0.3rem;
            margin-left: auto;
            box-shadow: 0 2px 5px rgba(37, 99, 235, 0.2);
        }

        .message.ai .message-content {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-bottom-left-radius: 0.3rem;
            margin-right: auto;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
        }

        .message.ai .message-content i {
            color: #2563eb;
        }

        .message-time {
            font-size: 0.75rem;
            color: #94a3b8;
            margin-top: 0.25rem;
            padding: 0 0.5rem;
        }

        .message.user .message-time {
            margin-left: auto;
        }

        .message.ai .message-time {
            margin-right: auto;
        }

        /* Thêm mũi tên cho bubble chat */
        .message.user .message-content:after {
            content: '';
            position: absolute;
            bottom: 0;
            right: -0.5rem;
            width: 0.75rem;
            height: 0.75rem;
            background: #2563eb;
            clip-path: polygon(0 0, 0% 100%, 100% 100%);
        }

        .message.ai .message-content:after {
            content: '';
            position: absolute;
            bottom: 0;
            left: -0.5rem;
            width: 0.75rem;
            height: 0.75rem;
            background: #f8fafc;
            border-left: 1px solid #e2e8f0;
            border-bottom: 1px solid #e2e8f0;
            clip-path: polygon(0 100%, 100% 100%, 100% 0);
        }

        .input-area {
            padding: 1rem;
            background: #fff;
            border-top: 1px solid #e9ecef;
        }

        .input-container {
            display: flex;
            gap: 0.5rem;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 0.75rem;
            padding: 0.5rem;
        }

        .message-input {
            flex: 1;
            border: none;
            background: transparent;
            padding: 0.5rem;
            resize: none;
            max-height: 100px;
            font-size: 0.95rem;
        }

        .message-input:focus {
            outline: none;
        }

        .input-actions {
            display: flex;
            align-items: flex-end;
            gap: 0.5rem;
        }

        .send-btn {
            padding: 0.5rem 1rem;
            border: none;
            border-radius: 0.5rem;
            background: #2563eb;
            color: white;
            cursor: pointer;
            transition: all 0.2s;
        }

        .send-btn:hover {
            background: #1d4ed8;
        }

        .typing-indicator {
            display: none;
            padding: 1rem;
            color: #64748b;
            font-size: 0.875rem;
        }

        .typing-indicator i {
            margin-right: 0.5rem;
        }

        /* Custom Scrollbar */
        .chat-messages::-webkit-scrollbar {
            width: 6px;
        }

        .chat-messages::-webkit-scrollbar-track {
            background: #f1f1f1;
        }

        .chat-messages::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 3px;
        }

        .chat-messages::-webkit-scrollbar-thumb:hover {
            background: #94a3b8;
        }
    </style>
</head>
<body>
    <?php include 'includes/headeradmin.php'; ?>
    <?php include 'includes/sidebaradmin.php'; ?>

    <main class="main-content">
        <div class="container-fluid">
            <div class="chat-wrapper">
                <!-- Chat Header -->
                <div class="chat-header">
                    <div>
                        <h1 class="chat-title">
                            <i class="fas fa-robot me-2"></i>AI Health Assistant
                        </h1>
                        <p class="chat-subtitle">Trợ lý AI chuyên về y tế và sức khỏe</p>
                    </div>
                    <div class="chat-actions">
                        <button onclick="clearChat()" class="action-btn">
                            <i class="fas fa-trash-alt"></i>
                            <span>Xóa chat</span>
                        </button>
                        <button onclick="exportChat()" class="action-btn">
                            <i class="fas fa-download"></i>
                            <span>Xuất chat</span>
                        </button>
                    </div>
                </div>

                <!-- Chat Messages -->
                <div class="chat-messages" id="chatMessages">
                    <div class="message ai">
                        <div class="message-content">
                            <i class="fas fa-robot me-2"></i>Xin chào! Tôi là AI Assistant. Tôi có thể giúp gì cho bạn?
                        </div>
                        <div class="message-time">Hôm nay, <?php echo date('H:i'); ?></div>
                    </div>
                </div>

                <!-- Typing Indicator -->
                <div class="typing-indicator" id="typingIndicator">
                    <i class="fas fa-robot"></i>AI đang trả lời...
                </div>

                <!-- Input Area -->
                <div class="input-area">
                    <form id="chatForm" onsubmit="sendMessage(event)">
                        <div class="input-container">
                            <textarea 
                                id="messageInput" 
                                class="message-input" 
                                placeholder="Nhập tin nhắn của bạn..." 
                                rows="1"
                                required
                            ></textarea>
                            <div class="input-actions">
                                <button type="submit" class="send-btn">
                                    <i class="fas fa-paper-plane"></i>
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </main>

    <?php include 'includes/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/admin.js"></script>
    <script>
        let chatHistory = [];
        let isWaitingForResponse = false;

        // Auto-resize textarea
        const messageInput = document.getElementById('messageInput');
        messageInput.addEventListener('input', function() {
            this.style.height = 'auto';
            this.style.height = (this.scrollHeight) + 'px';
        });

        function sendMessage(event) {
            event.preventDefault();
            if (isWaitingForResponse) return;

            const message = messageInput.value.trim();
            if (!message) return;

            addMessage(message, 'user');
            messageInput.value = '';
            messageInput.style.height = 'auto';
            
            isWaitingForResponse = true;
            messageInput.disabled = true;
            document.querySelector('.send-btn').disabled = true;
            document.getElementById('typingIndicator').style.display = 'block';

            fetch('../Chatbot_BackEnd/main.py', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    message: message,
                    user_id: '<?php echo $_SESSION['user_id']; ?>',
                    role: 'admin'
                })
            })
            .then(response => response.json())
            .then(data => {
                setTimeout(() => {
                    document.getElementById('typingIndicator').style.display = 'none';
                    addMessage(data.response, 'ai');
                    isWaitingForResponse = false;
                    messageInput.disabled = false;
                    document.querySelector('.send-btn').disabled = false;
                    messageInput.focus();
                }, Math.random() * 1000 + 500);
            })
            .catch(error => {
                console.error('Error:', error);
                setTimeout(() => {
                    document.getElementById('typingIndicator').style.display = 'none';
                    addMessage('Xin lỗi, đã có lỗi xảy ra. Vui lòng thử lại sau.', 'ai');
                    isWaitingForResponse = false;
                    messageInput.disabled = false;
                    document.querySelector('.send-btn').disabled = false;
                    messageInput.focus();
                }, 500);
            });
        }

        function addMessage(message, type) {
            const chatMessages = document.getElementById('chatMessages');
            const messageDiv = document.createElement('div');
            messageDiv.className = `message ${type}`;
            
            const now = new Date();
            const timeString = now.getHours().toString().padStart(2, '0') + ':' + 
                             now.getMinutes().toString().padStart(2, '0');

            messageDiv.innerHTML = `
                <div class="message-content">
                    ${type === 'ai' ? '<i class="fas fa-robot me-2"></i>' : ''}${message}
                </div>
                <div class="message-time">Hôm nay, ${timeString}</div>
            `;

            chatMessages.appendChild(messageDiv);
            chatMessages.scrollTop = chatMessages.scrollHeight;

            chatHistory.push({
                message: message,
                type: type,
                time: timeString
            });
        }

        function clearChat() {
            if (confirm('Bạn có chắc chắn muốn xóa toàn bộ cuộc trò chuyện?')) {
                const chatMessages = document.getElementById('chatMessages');
                chatMessages.innerHTML = `
                    <div class="message ai">
                        <div class="message-content">
                            <i class="fas fa-robot me-2"></i>Xin chào! Tôi là AI Assistant. Tôi có thể giúp gì cho bạn?
                        </div>
                        <div class="message-time">Hôm nay, ${new Date().getHours()}:${new Date().getMinutes()}</div>
                    </div>
                `;
                chatHistory = [];
            }
        }

        function exportChat() {
            const chatContent = chatHistory.map(msg => {
                return `[${msg.time}] ${msg.type === 'user' ? 'Bạn' : 'AI'}: ${msg.message}`;
            }).join('\n');

            const blob = new Blob([chatContent], { type: 'text/plain' });
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = `chat-history-${new Date().toISOString().slice(0,10)}.txt`;
            document.body.appendChild(a);
            a.click();
            window.URL.revokeObjectURL(url);
            document.body.removeChild(a);
        }

        // Handle Enter key
        messageInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                document.getElementById('chatForm').dispatchEvent(new Event('submit'));
            }
        });
    </script>
</body>
</html>
