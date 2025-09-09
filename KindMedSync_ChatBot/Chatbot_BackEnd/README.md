# KindMedSync – HealthCare Chatbot

## 📌 Giới thiệu

**KindMedSync Chatbot** là hệ thống **AI hỗ trợ y tế** xây dựng trên **FastAPI**, tích hợp **GPT API** để phỏng đoán ban đầu về bệnh dựa trên triệu chứng người dùng.
Mục tiêu:

* Giúp người dùng quyết định có nên đi khám hay không.
* Giảm bớt sự chủ quan hoặc chần chừ trước triệu chứng cơ bản.
* Hỗ trợ truy vấn dữ liệu bệnh nhân, bác sĩ, đơn thuốc bằng **ngôn ngữ tự nhiên**.

⚠️ Repo này chỉ tập trung vào **backend + chatbot (FastAPI + GPT + MySQL)**. Phần web frontend sẽ được phát triển trong repo khác.

---

## 🚀 Tính năng chính

* **Chatbot AI**: xử lý câu hỏi y tế cơ bản, phỏng đoán ban đầu từ triệu chứng.
* **Tích hợp GPT API**: cải thiện khả năng trả lời hội thoại tự nhiên.
* **Quản lý dữ liệu**: bệnh nhân, đơn thuốc, bác sĩ với **MySQL**.
* **API chuẩn REST** qua **FastAPI** để dễ dàng tích hợp frontend/web.

---

## 🛠 Công nghệ sử dụng

* **Python 3.10+**
* **FastAPI**
* **GPT API (OpenAI)**
* **MySQL**
* **Uvicorn** (server)

---

## 📂 Cấu trúc thư mục

```
KindMedSync_ChatBot/Chatbot_BackEnd/
│── config/          # Cấu hình (DB, GPT API key, env)
│── prompts/         # Prompt thiết kế cho GPT
│── routes/          # Các API routes
│── utils/           # Hàm tiện ích, helper
│── main.py          # Entry point, khởi chạy FastAPI
│── models.py        # Định nghĩa ORM model
│── user_role.sql    # Script tạo database
│── .env             # Thông tin bảo mật (API key, DB url)
```

---

## ⚡ Cài đặt & Chạy thử

### 1️⃣ Clone repo

```bash
git clone https://github.com/HhuyH/KindMedSync-HealthCare.git
cd KindMedSync_ChatBot/Chatbot_BackEnd
```

### 2️⃣ Tạo file `.env`

```ini
OPENAI_API_KEY=your_openai_api_key
```

### 3️⃣ Cài đặt thư viện

```bash
pip install -r requirements.txt
```

### 4️⃣ Chạy server

```bash
uvicorn main:app --reload
```

API sẽ chạy tại: `http://127.0.0.1:8000`

---

## 🔮 Hướng phát triển

* Tích hợp hoàn toàn với **RAG (Retrieval Augmented Generation)** để nâng cao độ chính xác.
* Xem xét **fine-tune một mô hình riêng** phù hợp dữ liệu y tế nội bộ.
* Tích hợp **speech-to-text** để hỗ trợ hội thoại bằng giọng nói.
* Thêm **API authentication & role-based access** cho bệnh nhân / bác sĩ.

---

## 👨‍💻 Người thực hiện

* **Lê Nguyễn Hoàn Huy** – AI Chatbot & Backend Developer