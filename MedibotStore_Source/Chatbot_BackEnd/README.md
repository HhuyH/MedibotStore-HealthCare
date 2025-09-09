# KindMedSync – HealthCare Chatbot

## Giới thiệu

**Medibot Store Chatbot** là hệ thống **AI hỗ trợ y tế** xây dựng trên **FastAPI**, tích hợp **GPT API** để phỏng đoán ban đầu về bệnh dựa trên triệu chứng người dùng.
Mục tiêu:

* Giúp người dùng quyết định có nên đi khám hay không.
* Giảm bớt sự chủ quan hoặc chần chừ trước triệu chứng cơ bản.
* Hỗ trợ truy vấn dữ liệu bệnh nhân, bác sĩ, đơn thuốc bằng **ngôn ngữ tự nhiên**.

---

## Tính năng chính

* **Phân tích triệu chứng**: Phân tích triệu chứng, đặt câu hỏi follow-up và đưa ra lời khuyên sức khỏe cơ bản.
* **Tư vấn sức khỏe cá nhân hóa**: Gợi ý cách cải thiện vấn đề mà người dùng đang gặp phải, dựa trên mong muốn và dữ liệu đã có.
* **Đặt lịch khám**: Tạo lịch khám trực tuyến qua chatbot, xác nhận đầy đủ thông tin trước khi lưu vào hệ thống.
* **Gợi ý sản phẩm y tế**: Đề xuất thực phẩm chức năng, thiết bị y tế phù hợp với tình trạng người dùng.
* **Báo cáo cho bác sĩ**: Tổng hợp dữ liệu sức khỏe từ người dùng và gửi báo cáo đến bác sĩ.
* **Tác vụ dành cho Admin**: Chatbot hỗ trợ truy vấn dữ liệu (sản phẩm, đơn hàng, bệnh nhân, …) bằng ngôn ngữ tự nhiên.
* **Tích hợp GPT API**: Nâng cao khả năng trả lời hội thoại và xử lý ngôn ngữ tự nhiên.
* **Quản lý dữ liệu**: Lưu trữ thông tin bệnh nhân, bác sĩ, đơn thuốc với **MySQL**.
* **Redis**: Quản lý session và tăng tốc độ phản hồi của chatbot.
* **FastAPI + REST API**: Cung cấp endpoint rõ ràng, dễ tích hợp với web hoặc mobile frontend.

---

## Công nghệ sử dụng

* **Python 3.10+**
* **FastAPI**
* **GPT API (OpenAI)**
* **MySQL**
* **Redis (Session)**
* **Uvicorn** (server)

---

## Cấu trúc thư mục

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

## Cài đặt & Chạy thử

### 1️ Clone repo

```bash
git clone https://github.com/HhuyH/KindMedSync-HealthCare.git
cd KindMedSync_ChatBot/Chatbot_BackEnd
```

### 2️ Tạo file `.env`

```ini
OPENAI_API_KEY=your_openai_api_key
```

### 3️ Cài đặt thư viện

```bash
pip install -r requirements.txt
```

### 4️ Chạy server

```bash
uvicorn main:app --reload
```

API sẽ chạy tại: `http://127.0.0.1:8000`

---

## Hướng phát triển

* Tích hợp hoàn toàn với **RAG (Retrieval Augmented Generation)** để nâng cao độ chính xác.
* Xem xét **fine-tune một mô hình riêng** phù hợp dữ liệu y tế nội bộ.
* Tích hợp **speech-to-text** để hỗ trợ hội thoại bằng giọng nói.
* Thêm **API authentication & role-based access** cho bệnh nhân / bác sĩ.

---

## Người thực hiện

* **Lê Nguyễn Hoàn Huy** – AI Chatbot & Backend Developer