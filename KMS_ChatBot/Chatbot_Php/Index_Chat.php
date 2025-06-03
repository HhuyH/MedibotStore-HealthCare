<?php session_start(); ?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8" />
    <title>Chat SSE với FastAPI streaming</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f0f0f0; padding: 30px; }
        #chat-box {
            width: 100%; max-width: 600px; height: 400px;
            background: #fff; border: 1px solid #ccc;
            overflow-y: auto; padding: 10px; margin-bottom: 10px;
            white-space: pre-wrap; /* giữ xuống dòng */
        }
        #chat-box div { margin: 5px 0; padding: 6px 10px; background: #e6e6e6; border-radius: 5px; }
        form { display: flex; max-width: 600px; }
        input[type="text"] { flex: 1; padding: 10px; font-size: 16px; }
        button { padding: 10px 20px; cursor: pointer; }
    </style>
</head>
<body>

<h2>🧠 Chat Box</h2>

<!-- Form nhập tin nhắn -->
<div id="chat-box"></div>

<form id="chat-form">
  <input type="text" id="userInput" placeholder="Nhập tin nhắn..." autocomplete="off" required />
  <button type="submit">Gửi</button>
</form>


<script src="assets/chat.js"></script>

<!-- Nút Reset để xóa lịch sử chat -->
<form method="POST" action="reset.php">
  <button type="submit" style="background: red; color: white;">🔁 Reset cuộc trò chuyện</button>
</form>

</body>
</html>
