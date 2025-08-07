let typingIndicator = null;

function appendMessage(message, sender = "user") {
    const wrapper = document.createElement("div");
    wrapper.className = sender === "user" ? "message user" : "message bot";

    const avatar = document.createElement("div");
    avatar.className = "message-avatar";
    avatar.innerHTML = sender === "user"
        ? '<i class="fas fa-user"></i>'
        : '<i class="fas fa-robot"></i>';

    const content = document.createElement("div");
    content.className = "message-content";

    if (typeof message === "string") {
        content.innerHTML = marked.parse(message);
    } else if (message instanceof HTMLElement) {
        content.appendChild(message); // ✅ Hỗ trợ DOM element
    } else {
        content.textContent = String(message); // fallback
    }

    wrapper.appendChild(avatar);
    wrapper.appendChild(content);

    document.getElementById("chat-box").appendChild(wrapper);
    scrollToBottom();
}


function scrollToBottom() {
    const chatBox = document.getElementById("chat-box");
    setTimeout(() => {
        chatBox.scrollTop = chatBox.scrollHeight;
    }, 50);
}

// Navigation functions
function goHome() {
    window.location.href = '../index.php';
}

function logout() {
    if (confirm('Bạn có chắc muốn đăng xuất?')) {
        window.location.href = '../logout.php';
    }
}

function refreshChat() {
    if (confirm('Làm mới trò chuyện? Tất cả tin nhắn sẽ bị xóa.')) {
        location.reload();
    }
}

function closeChat() {
    if (confirm('Đóng trò chuyện và quay về trang chủ?')) {
        window.location.href = '../index.php';
    }
}

function showTyping() {
    console.log('Show typing indicator');
    hideTyping();

    typingIndicator = document.createElement('div');
    typingIndicator.className = 'message bot typing-indicator'; // ✅ Bubble ngoài cùng
    
    const avatar = document.createElement('div');
    avatar.className = 'message-avatar';
    avatar.innerHTML = '<i class="fas fa-robot"></i>';

    const content = document.createElement('div');
    content.className = 'message-content typing'; // ✅ Bubble hiển thị nội dung chính

    // 👇 Chỉ có 1 lớp .message-content chứa 3 chấm
    content.innerHTML = `
        <div class="typing-dots">
            <div class="typing-dot"></div>
            <div class="typing-dot"></div>
            <div class="typing-dot"></div>
        </div>
    `;

    typingIndicator.appendChild(avatar);
    typingIndicator.appendChild(content);

    document.getElementById("chat-box").appendChild(typingIndicator);
    scrollToBottom();
}


function hideTyping() {
    console.log('Hide typing indicator');
    if (typingIndicator && typingIndicator.parentNode) {
        typingIndicator.parentNode.removeChild(typingIndicator);
        typingIndicator = null;
    }
}


function normalizeMarkdown(input) {
    // Replace \n\n or đơn \n bằng <br><br> nếu muốn tách đoạn rõ hơn
    return input
        .replace(/(🔴|🟠|🟡)(\s*)<strong>/g, "\n\n$1 $2<strong>")
        .replace(/\n{2,}/g, "<br><br>")
        .replace(/\n/g, "<br>");
}




const userInfo = JSON.parse(localStorage.getItem("userInfo")); // Được lưu sau khi login

// Nếu chưa có session_id → tạo và lưu vào localStorage
if (!userInfo.session_id) {
    const newSessionId = "guest_" + crypto.randomUUID();  // Hoặc dùng Date.now() nếu cần đơn giản hơn
    userInfo.session_id = newSessionId;
    localStorage.setItem("userInfo", JSON.stringify(userInfo));
}

async function sendChatStream({ message}, onUpdate) {
    const userInfo = JSON.parse(localStorage.getItem("userInfo")) || {};
    const { user_id, username, role, session_id} = userInfo;

    const payload = {
        message,
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
    const decoder = new TextDecoder("utf-8", { fatal: false, ignoreBOM: true });
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
                    onUpdate(parsed);  // ✅ Gửi object JSON, không phải text thuần nữa
                } catch (err) {
                    // console.warn("Không phải JSON, hiển thị raw text:", jsonStr);
                    if (jsonStr.trim() !== "") {
                        onUpdate(jsonStr); // fallback plain text, nhưng tránh string rỗng
                    }
                }
            }

        }

        buffer = parts[parts.length - 1];
    }
}

// Hiển thị lại hội thoại cũ
async function loadChatLogs() {
    const userInfo = JSON.parse(localStorage.getItem("userInfo")) || {};
    const session_id = userInfo.session_id;
    const user_id = userInfo.user_id;
    const guest_id = userInfo.guest_id;

    try {
        // 🧾 Ưu tiên lấy từ DB
        const url = user_id
            ? `http://127.0.0.1:8000/chat/logs?user_id=${user_id}&limit=30`
            : `http://127.0.0.1:8000/chat/logs?guest_id=${guest_id}&limit=30`;

        const response = await fetch(url);
        const logs = await response.json();

        if (Array.isArray(logs) && logs.length > 0) {
            for (const log of logs) {
                const sender = log.sender === "user" ? "user" : "bot";
                if (log.message && typeof log.message === "object" && log.message.description) {
                    // Hiển thị description trước
                    appendMessage(log.message.description, sender);

                    // Nếu có data bảng → render bảng
                    if (Array.isArray(log.message.data) && log.message.data.length > 0) {
                        const tableElement = renderTableWithPagination(log.message.data);
                        appendMessage(tableElement, sender);
                    }
                } else {
                    appendMessage(log.message, sender);
                }

            }
            return; // ✅ Không cần fallback nếu có log từ DB
        }

        // 🔁 Nếu không có gì từ DB → fallback sang session RAM
        const fallbackRes = await fetch(`http://127.0.0.1:8000/chat/history?session_id=${session_id}&user_id=${user_id || ""}`);
        const fallbackData = await fallbackRes.json();
        const recent = fallbackData.recent_messages || [];

        for (const line of recent) {
            if (typeof line === "string") {
                if (line.startsWith("👤 ")) {
                    appendMessage(line.slice(2).trim(), "user");  // Bỏ emoji user
                } else if (line.startsWith("🤖 ")) {
                    appendMessage(line.slice(2).trim(), "bot");   // Bỏ emoji bot
                }
            }
        }

    } catch (error) {
        console.error("❌ Lỗi khi tải lại hội thoại:", error);
    }
}

function renderTable(data) {
    if (!Array.isArray(data) || data.length === 0) return "";

    // Tạo phần header (lấy key từ object đầu tiên)
    const headers = Object.keys(data[0]);
    let table = `<table class="chat-result-table">
        <thead><tr>${headers.map(h => `<th>${h}</th>`).join("")}</tr></thead>
        <tbody>`;

    // Tạo các dòng dữ liệu
    data.forEach(row => {
        table += `<tr>${headers.map(h => `<td>${row[h] ?? ""}</td>`).join("")}</tr>`;
    });

    table += "</tbody></table>";

    return table;
}

// rnderTable khi reload chat logs
function renderTableWithPagination(tableData, sender = "bot") {
    const rowsPerPage = 10;
    let currentPage = 1;
    const totalPages = Math.ceil(tableData.length / rowsPerPage);

    const container = document.createElement("div");
    container.className = "chat-table-wrapper";

    const table = document.createElement("table");
    table.className = "chat-result-table";

    const headers = Object.keys(tableData[0]);
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
    table.appendChild(tbody);

    container.appendChild(table);

    const pagination = document.createElement("div");
    pagination.className = "pagination";
    container.appendChild(pagination);

    function renderPage(page) {
        tbody.innerHTML = "";
        const start = (page - 1) * rowsPerPage;
        const pageRows = tableData.slice(start, start + rowsPerPage);

        pageRows.forEach(row => {
            const tr = document.createElement("tr");
            headers.forEach(h => {
                const td = document.createElement("td");
                td.textContent = row[h];
                tr.appendChild(td);
            });
            tbody.appendChild(tr);
        });

        updatePagination(page);
    }

    function updatePagination(page) {
        pagination.innerHTML = "";

        const prev = document.createElement("button");
        prev.textContent = "← Trước";
        prev.disabled = page === 1;
        prev.onclick = () => {
            currentPage--;
            renderPage(currentPage);
            scrollToBottom();
        };
        pagination.appendChild(prev);

        for (let i = 1; i <= totalPages; i++) {
            const btn = document.createElement("button");
            btn.textContent = i;
            btn.disabled = i === page;
            btn.onclick = () => {
                currentPage = i;
                renderPage(currentPage);
                scrollToBottom();
            };
            pagination.appendChild(btn);
        }

        const next = document.createElement("button");
        next.textContent = "Tiếp →";
        next.disabled = page === totalPages;
        next.onclick = () => {
            currentPage++;
            renderPage(currentPage);
            scrollToBottom();
        };
        pagination.appendChild(next);
    }

    scrollToBottom();

    renderPage(currentPage);
    // appendMessage(container.ouerHTML, sender);  // hoặc gắn trực tiếp nếu appendMessage không hỗ trợ DOM element
    return container; // Trả về DOM element để gắn vào chat box
}




document.addEventListener('DOMContentLoaded', () => {
    loadChatLogs();
    const input = document.getElementById("userInput");
    
    input.addEventListener("keydown", function (e) {
    if (e.key === "Enter" && !e.shiftKey) {
        e.preventDefault();
        document.getElementById("chat-form").dispatchEvent(new Event("submit", { bubbles: true }));
    }
    });
    
    input.addEventListener("input", function () {
        this.style.height = "auto";
        this.style.height = Math.min(this.scrollHeight, 120) + "px";
    });

    document.getElementById("chat-form").addEventListener("submit", async function (e) {
        e.preventDefault();

        const input = document.getElementById("userInput");
        const message = input.value.trim();
        if (!message) return;

        // Lấy userInfo từ localStorage ngay đây
        const userInfo = JSON.parse(localStorage.getItem("userInfo")) || {};
        const role = userInfo.role || "guest";
        
        appendMessage(message, "user");
        input.value = "";
        input.disabled = true;

        // Tạo payload chung có thêm userInfo
        const payload = {
            message: message,
            user_id: userInfo.user_id || null,
            username: userInfo.username || null,
            role: role,
            session_id: userInfo.session_id
        };

        showTyping(); // ✅ tạo bubble

        let fullBotReply = "";

        try {
            await sendChatStream(payload, (text) => {
                let parsed;
                try {
                    parsed = typeof text === "string" ? JSON.parse(text) : text;
                } catch {
                    parsed = null;
                }

                const delta = typeof parsed?.natural_text === "string" ? parsed.natural_text : "";

                fullBotReply += delta;

                const markdownText = normalizeMarkdown(fullBotReply).replace(/\n/g, "\n\n");
                const isHTML = markdownText.trim().startsWith("<");

                const html = isHTML
                    ? markdownText // dùng trực tiếp HTML từ GPT
                    : marked.parse(markdownText)
                        .replace(/<p>\s*<\/p>/g, "")
                        .replace(/<p>(&nbsp;|\s)*<\/p>/g, "");

                let content = typingIndicator.querySelector(".message-content");
                if (!content) {
                    content = document.createElement("div");
                    content.className = "message-content";
                    typingIndicator.appendChild(content);
                }

                // ✅ Cập nhật nội dung text trước
                content.innerHTML = html;

                // Nếu có bảng và chưa gắn bảng → tạo bảng
                if (parsed?.table && Array.isArray(parsed.table) && parsed.table.length > 0 && !content.querySelector("table")) {
                    // Pagination setup
                    const rowsPerPage = 10;
                    let currentPage = 1;
                    let totalPages = Math.ceil(parsed.table.length / rowsPerPage);

                    // Tạo bảng như bình thường
                    const table = document.createElement("table");
                    table.className = "chat-result-table";

                    // Tạo header
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

                    // Tạo tbody – nhưng KHÔNG thêm toàn bộ rows ở đây
                    const tbody = document.createElement("tbody");
                    table.appendChild(tbody);

                    // Hàm render rows theo trang
                    function renderTablePage(page) {
                        tbody.innerHTML = ""; // clear old rows
                        const start = (page - 1) * rowsPerPage;
                        const end = start + rowsPerPage;
                        const pageRows = parsed.table.slice(start, end);

                        pageRows.forEach(row => {
                            const tr = document.createElement("tr");
                            headers.forEach(h => {
                                const td = document.createElement("td");
                                td.textContent = row[h];
                                tr.appendChild(td);
                            });
                            tbody.appendChild(tr);
                        });
                    }

                    // Gọi hàm lần đầu tiên
                    renderTablePage(currentPage);

                    // Tạo pagination control
                    const pagination = document.createElement("div");
                    pagination.className = "pagination";

                    function updatePaginationControls() {
                        pagination.innerHTML = "";

                        // Previous button
                        const prevBtn = document.createElement("button");
                        prevBtn.textContent = "← Trước";
                        prevBtn.disabled = currentPage === 1;
                        prevBtn.onclick = () => {
                            currentPage--;
                            renderTablePage(currentPage);
                            updatePaginationControls();
                            scrollToBottom();
                        };
                        pagination.appendChild(prevBtn);

                        // Page numbers (1, 2, 3, ...)
                        for (let i = 1; i <= totalPages; i++) {
                            const pageBtn = document.createElement("button");
                            pageBtn.textContent = i;
                            if (i === currentPage) pageBtn.disabled = true;
                            pageBtn.onclick = () => {
                                currentPage = i;
                                renderTablePage(currentPage);
                                updatePaginationControls();
                                scrollToBottom();
                            };
                            pagination.appendChild(pageBtn);
                        }

                        // Next button
                        const nextBtn = document.createElement("button");
                        nextBtn.textContent = "Tiếp →";
                        nextBtn.disabled = currentPage === totalPages;
                        nextBtn.onclick = () => {
                            currentPage++;
                            renderTablePage(currentPage);
                            updatePaginationControls();
                            scrollToBottom();
                        };
                        pagination.appendChild(nextBtn);
                    }

                    updatePaginationControls();

                    // Gắn table và pagination vào content
                    const tableWrapper = document.createElement("div");
                    tableWrapper.className = "chat-table-wrapper";
                    tableWrapper.appendChild(table);
                    tableWrapper.appendChild(pagination);

                    content.appendChild(tableWrapper);
                    scrollToBottom();
                }

                // Nếu có SQL và chưa gắn → thêm khối SQL vào cuối
                if (parsed?.sql_query && !content.querySelector(".chat-sql-text")) {
                    const sqlDiv = document.createElement("pre");
                    sqlDiv.textContent = "[SQL nội bộ]\n" + parsed.sql_query;
                    sqlDiv.className = "chat-sql-text";
                    content.appendChild(sqlDiv);
                }

                scrollToBottom();
            });

        } catch (err) {
            typingIndicator.textContent += "\n[Error xảy ra khi nhận dữ liệu]";
            console.error(err);
        } finally {
            input.disabled = false;
            input.focus();
            typingIndicator = null;
        }
    });
});

function updateTypingBubble(text) {
    // console.log("🔄 Gọi update bubble với:", text); // ✅ Log kiểm tra

    const markdownText = normalizeMarkdown(text).replace(/\n/g, "\n\n");
    // console.log("📄 markdownText:", markdownText);

    const isHTML = markdownText.trim().startsWith("<");

    const html = isHTML
        ? markdownText // dùng trực tiếp HTML từ GPT
        : marked.parse(markdownText)
            .replace(/<p>\s*<\/p>/g, "")
            .replace(/<p>(&nbsp;|\s)*<\/p>/g, "");
    // console.log("📦 html:", html);

    let content = typingIndicator.querySelector(".message-content");
    if (!content) {
        content = document.createElement("div");
        content.className = "message-content";
        typingIndicator.appendChild(content);
    }

    // Xoá toàn bộ nội dung cũ
    content.innerHTML = "";

    // Tạo wrapper để gom văn bản đầu ra
    const wrapper = document.createElement("div");
    wrapper.className = "message-body-wrapper";
    wrapper.innerHTML = html;
    content.appendChild(wrapper);

}



const resetBtn = document.getElementById("reset-chat");

if (resetBtn) {
    resetBtn.addEventListener("click", async () => {
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
                body: JSON.stringify({ session_id, user_id }),
            });

            const data = await response.json();

            if (response.ok && data.status === "success") {
                // ✅ Xoá toàn bộ nội dung khung chat
                document.getElementById("chat-box").innerHTML = "";

                // Gửi thông báo nếu muốn
                // appendMessage("🔄 Cuộc hội thoại đã được đặt lại!", "bot");
            } else {
                throw new Error(data.message || "Reset thất bại.");
            }
        } catch (err) {
            appendMessage("❌ Không thể reset hội thoại: " + err.message, "bot");
            console.error("Lỗi reset:", err);
        }
    });
}
