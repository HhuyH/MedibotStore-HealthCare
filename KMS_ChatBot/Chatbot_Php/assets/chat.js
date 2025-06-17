function appendMessage(message, sender = "user") {
    const div = document.createElement("div");
    div.innerHTML = marked.parse(message);
    div.className = sender === "user" ? "user-msg" : "bot-msg";
    document.getElementById("chat-box").appendChild(div);
    scrollToBottom();
}

function scrollToBottom() {
    const chatBox = document.getElementById("chat-box");
    setTimeout(() => {
        chatBox.scrollTop = chatBox.scrollHeight;
    }, 50);
}



const userInfo = JSON.parse(localStorage.getItem("userInfo")); // Được lưu sau khi login

// Nếu chưa có session_id → tạo và lưu vào localStorage
if (!userInfo.session_id) {
    const newSessionId = "guest_" + crypto.randomUUID();  // Hoặc dùng Date.now() nếu cần đơn giản hơn
    userInfo.session_id = newSessionId;
    localStorage.setItem("userInfo", JSON.stringify(userInfo));
}

// Gọi API chat không stream, trả về reply đầy đủ 1 lần
async function sendChatMessage(message, history) {
    const response = await fetch("http://127.0.0.1:8000/chat", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({
            message,
            history,
            user_id: userInfo.user_id,
            role: userInfo.role
        }),
    });


    if (!response.ok) throw new Error("Lỗi khi kết nối server");
    const data = await response.json();
    return data.reply;
}

async function sendChatStream({ message, history }, onUpdate) {
    const userInfo = JSON.parse(localStorage.getItem("userInfo")) || {};
    const { user_id, username, role, session_id} = userInfo;

    const payload = {
        message,
        history,
        user_id,
        username,
        role,
        session_id
    };

    const response = await fetch("http://127.0.0.1:8000/chat/stream", {
        method: "POST",
        headers: {
            "Content-Type": "application/json",
            "Accept": "text/event-stream",
        },
        body: JSON.stringify(payload),
    });

    if (!response.ok) {
        const errorText = await response.text();
        console.error("Lỗi chi tiết:", errorText);
        throw new Error("Lỗi khi kết nối server");
    }

    const reader = response.body.getReader();
    const decoder = new TextDecoder("utf-8");
    let buffer = "";

    while (true) {
        const { done, value } = await reader.read();
        if (done) break;

        buffer += decoder.decode(value, { stream: true });
        const parts = buffer.split("\n\n");

        for (let i = 0; i < parts.length - 1; i++) {
            const part = parts[i].trim();
            if (part.startsWith("data:")) {
                const jsonStr = part.replace(/^data:\s*/, "");
                if (jsonStr === "[DONE]") return;
                try {
                    const parsed = JSON.parse(jsonStr);
                    const textToShow = parsed.natural_text;
                    if (textToShow && textToShow.trim() !== "") {
                        onUpdate(textToShow);
                    } else {
                        // Không làm gì hoặc log debug
                        console.debug("Chunk không có natural_text hợp lệ:", parsed);
                    }
                } catch (err) {
                    console.warn("Không phải JSON, hiển thị raw text:", jsonStr);
                    if (jsonStr.trim() !== "") {
                        onUpdate(jsonStr); // fallback plain text, nhưng tránh string rỗng
                    }
                }
            }

        }

        buffer = parts[parts.length - 1];
    }
}

document.addEventListener('DOMContentLoaded', () => {
    document.getElementById("chat-form").addEventListener("submit", async function (e) {
        e.preventDefault();

        const input = document.getElementById("userInput");
        const message = input.value.trim();
        if (!message) return;

        // Lấy userInfo từ localStorage ngay đây
        const userInfo = JSON.parse(localStorage.getItem("userInfo")) || {};
        const role = userInfo.role || "guest";
        
        appendMessage(message + " 👤", "user");
        input.value = "";
        input.disabled = true;

        const history = await fetch("get_history.php", {
            credentials: "include"
        }).then(res => res.json());

        await fetch("update_history.php", {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({ role: "user", content: message }),
            credentials: "include"
        });

        // Tạo payload chung có thêm userInfo
        const payload = {
            message: message,
            user_id: userInfo.user_id || null,
            username: userInfo.username || null,
            role: role,
            history: history // Nếu backend cần lịch sử luôn thì gửi kèm
        };

        const useStreaming = true; // hoặc false tùy bạn

        if (!useStreaming) {
            try {
                // Gọi backend gửi chat, đính kèm payload
                const res = await fetch('/api/chatbot_backend', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(payload)
                });

                const data = await res.json();

                const reply = data.reply;
                appendMessage("🤖 " + reply, "bot");

                await fetch("update_history.php", {
                    method: "POST",
                    headers: { "Content-Type": "application/json" },
                    body: JSON.stringify({ role: "assistant", content: reply }),
                    credentials: "include"
                });
            } catch (err) {
                appendMessage("[Lỗi kết nối server]");
                console.error(err);
            } finally {
                input.disabled = false;
                input.focus();
            }
        } else {
            const botMessageDiv = document.createElement("div");
            botMessageDiv.className = "bot-msg";
            botMessageDiv.innerHTML = "<strong>🤖</strong> ";
            document.getElementById("chat-box").appendChild(botMessageDiv);

            let fullBotReply = "";

            try {
                await sendChatStream(payload, (text) => {
                    // parse và render như bạn đã có
                    let parsed;
                    try {
                        parsed = JSON.parse(text);
                    } catch (e) {
                        parsed = null;
                    }

                    if (parsed && parsed.natural_text) {
                        fullBotReply += parsed.natural_text;
                        botMessageDiv.innerHTML = "<strong>🤖</strong> " + marked.parse(fullBotReply);
                        
                        if (parsed.table && Array.isArray(parsed.table) && parsed.table.length > 0) {
                            const table = document.createElement("table");
                            table.className = "chat-result-table";

                            const headers = Object.keys(parsed.table[0]);
                            const thead = document.createElement("thead");
                            const trHead = document.createElement("tr");
                            headers.forEach(h => {
                                const th = document.createElement("th");
                                th.textContent = h;
                                trHead.appendChild(th);
                            });
                            thead.appendChild(trHead);
                            table.appendChild(thead);

                            const tbody = document.createElement("tbody");
                            parsed.table.forEach(row => {
                                const tr = document.createElement("tr");
                                headers.forEach(h => {
                                    const td = document.createElement("td");
                                    td.textContent = row[h];
                                    tr.appendChild(td);
                                });
                                tbody.appendChild(tr);
                            });
                            table.appendChild(tbody);
                            botMessageDiv.appendChild(table);
                        }

                        if (parsed.sql_query) {
                            const sqlDiv = document.createElement("pre");
                            sqlDiv.textContent = "[SQL nội bộ]\n" + parsed.sql_query;
                            sqlDiv.style.color = "gray";
                            sqlDiv.style.fontSize = "0.9em";
                            sqlDiv.style.marginTop = "5px";
                            document.getElementById("chat-box").appendChild(sqlDiv);
                        }

                    } else {
                        fullBotReply += text;
                        botMessageDiv.innerHTML = "<strong>🤖</strong> " + marked.parse(fullBotReply);
                    }

                    scrollToBottom();
                });

                await fetch("update_history.php", {
                    method: "POST",
                    headers: { "Content-Type": "application/json" },
                    body: JSON.stringify({ role: "assistant", content: fullBotReply }),
                    credentials: "include"
                });

            } catch (err) {
                botMessageDiv.textContent += "\n[Error xảy ra khi nhận dữ liệu]";
                console.error(err);
            } finally {
                input.disabled = false;
                input.focus();
            }
        }
    });
});



document.getElementById("reset-chat").addEventListener("click", async () => {
    const userInfo = JSON.parse(localStorage.getItem("userInfo")) || {};
    const session_id = userInfo.session_id;
    const user_id = userInfo.user_id;
    
    if (!session_id) {
        alert("Không tìm thấy session để reset.");
        return;
    }

    try {
        const response = await fetch("http://127.0.0.1:8000/chat/reset", {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({
                session_id: session_id,
                user_id: user_id 
            }),
        });

        const data = await response.json();

        if (response.ok && data.status === "success") {
            // ✅ Xoá toàn bộ nội dung khung chat
            document.getElementById("chat-box").innerHTML = "";

            // ✅ Xoá lịch sử cục bộ nếu có (ví dụ nếu bạn lưu ở localStorage)
            localStorage.removeItem("chatHistory");

            // DEBUG Thông báo cho người dùng
            // appendMessage("🔄 Cuộc hội thoại đã được đặt lại!", "bot");
        } else {
            throw new Error(data.message || "Reset thất bại.");
        }
    } catch (err) {
        appendMessage("❌ Không thể reset hội thoại: " + err.message, "bot");
        console.error("Lỗi reset:", err);
    }
});

