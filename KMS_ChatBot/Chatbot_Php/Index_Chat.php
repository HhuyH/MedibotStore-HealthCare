<?php session_start(); ?>
<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8" />
  <title>✨ Trò Chuyện Sức Khỏe AI</title>
  <script src="https://cdn.jsdelivr.net/npm/marked/marked.min.js"></script>
  <style>
    body {
      font-family: Arial, sans-serif;
      background: #f9f9f9;
      padding: 40px;
      display: flex;
      flex-direction: column;
      align-items: center;
    }

    h2 {
      margin-bottom: 20px;
      color: #333;
    }

    #chat-box {
      width: 100%;
      max-width: 1000px;
      height: 450px;
      background: #ffffff;
      border: 1px solid #ccc;
      border-radius: 8px;
      overflow-y: auto;
      padding: 15px;
      margin-bottom: 15px;
      box-shadow: 0 4px 8px rgba(0, 0, 0, 0.05);
      white-space: pre-wrap;
    }

    #chat-box div {
      margin: 6px 0;
      padding: 10px 12px;
      background: #e9f1fb;
      border-radius: 6px;
      color: #333;
    }

    form {
      display: flex;
      justify-content: center;
      max-width: 700px;
      width: 100%;
      gap: 10px;
      margin-bottom: 10px;
    }

    input[type="text"] {
      flex: 1;
      padding: 10px 14px;
      font-size: 16px;
      border-radius: 6px;
      border: 1px solid #ccc;
    }

    button {
      padding: 10px 20px;
      font-size: 16px;
      border: none;
      border-radius: 6px;
      background-color: #007bff;
      color: white;
      cursor: pointer;
      transition: background-color 0.3s ease;
    }

    button:hover {
      background-color: #0056b3;
    }

    .reset-form button {
      background: #dc3545;
      margin-top: 10px;
    }

    .reset-form button:hover {
      background: #a71d2a;
    }

    #chat-box {
      display: flex;
      flex-direction: column;
    }

    #chat-box .user-msg {
      align-self: flex-end;
      background-color: #dcf8c6;
      border-radius: 12px 12px 0 12px;
      max-width: 70%;
      display: inline-block;       /* Không chiếm hết chiều ngang */
      padding: 8px 12px;
      font-size: 15px;
      line-height: 1.3;
      word-break: break-word;
      box-sizing: border-box;
    }

    #chat-box .bot-msg {
      align-self: flex-start; /* Bot bên trái */
      background-color: #f1f0f0;
      border-radius: 12px 12px 12px 0;
      max-width: 70%;
    }

    #chat-box table {
      width: 100%;
      border-collapse: collapse;
      margin-top: 10px;
    }

    #chat-box th, #chat-box td {
      border: 1px solid #ccc;
      padding: 8px;
      text-align: left;
    }

    #chat-box th {
      background-color: #f2f2f2;
    }


  </style>
</head>
<body>

  <!-- Log out -->
  <button id="logoutBtn" style="background:#dc3545; color:#fff; padding:8px 16px; border:none; border-radius:6px; cursor:pointer;">
    🚪 Logout
  </button>

    <!-- Đặt chỗ hiện info user -->
  <div id="user-info" style="position: fixed; top: 10px; right: 10px; background: #eee; padding: 10px 15px; border-radius: 6px;"></div>


  <h2>🧠 Trò Chuyện Sức Khỏe AI</h2>

  <div id="chat-box"></div>

  <!-- Biểu mẫu nhập tin nhắn -->
  <form id="chat-form">
    <input type="text" id="userInput" placeholder="Nhập tin nhắn..." autocomplete="off" required />
    <button type="submit">Gửi</button>
  </form>

  <!-- Reset chat -->
  <form method="POST" action="reset.php" class="reset-form">
    <button type="submit">🔁 Reset cuộc trò chuyện</button>
  </form>

  <!-- Gắn file JS -->
  <script src="assets/chat.js"></script>
  <script src="assets/logout.js"></script>
  
  <script>
    // Lấy userInfo từ localStorage
    window.addEventListener('DOMContentLoaded', () => {
      const userInfo = JSON.parse(localStorage.getItem("userInfo"));

      if (userInfo) {
        const userDiv = document.getElementById("user-info");
        userDiv.innerHTML = `
          <strong>Xin chào:</strong> ${userInfo.username} <br/>
          <strong>Vai trò:</strong> ${userInfo.role} <br/>
          <strong>ID:</strong> ${userInfo.user_id}
        `;
      } else {
        document.getElementById("user-info").innerText = "Chưa đăng nhập hoặc session hết hạn.";
      }
    });
  </script>


</body>
</html>
