# KindMedSync – HealthCare Platform

## Giới thiệu

**KindMedSync** là một hệ thống hỗ trợ y tế thông minh, gồm:

* **Chatbot AI**: Phỏng đoán ban đầu về bệnh dựa trên triệu chứng, giúp người dùng quyết định có cần đi khám hay không. Chatbot sử dụng **FastAPI + GPT API** để xử lý hội thoại ngôn ngữ tự nhiên.
* **Website Quản lý**: Giao diện web cho phép bệnh nhân, bác sĩ và quản trị viên dễ dàng quản lý thông tin, đặt lịch hẹn, và tương tác với chatbot.

Mục tiêu dự án:

* Giảm sự do dự và chủ quan của người dân khi gặp triệu chứng.
* Tăng hiệu quả quản lý bệnh nhân, đơn thuốc, lịch hẹn.
* Tích hợp công nghệ **AI/ML** trong lĩnh vực y tế.

---

## Kiến trúc hệ thống

```
KindMedSync-HealthCare/
│── Chatbot_BackEnd/   # FastAPI backend + AI Chatbot (GPT + MySQL)
│── Web_FrontEnd/      # Giao diện web (HTML/CSS/JS hoặc framework)
│── docs/              # Tài liệu dự án
│── README.md          # README tổng quát (file hiện tại)
```

---

## Thành phần chính

### 1. **Chatbot Backend (AI + API)**

* Công nghệ: **FastAPI, GPT API, MySQL**
* Chức năng:
  • Phỏng đoán ban đầu từ triệu chứng.
  • Chatbot hội thoại tự nhiên.
  • API RESTful cho frontend.

Chi tiết: [Chatbot\_BackEnd](./Chatbot_BackEnd/README.md)

---

### 2. **Web Frontend**

* Công nghệ: **HTML/CSS/JS (hoặc framework)**
* Chức năng:
  • Quản lý thông tin bệnh nhân, bác sĩ, đơn thuốc.
  • Đặt lịch hẹn khám.
  • Tích hợp chatbot AI vào giao diện web.

Chi tiết: [Website](./Website/README.md)

---

## Cài đặt & Chạy thử

### 1️ Clone repo

```bash
git clone https://github.com/HhuyH/KindMedSync-HealthCare.git
cd KindMedSync-HealthCare
```

### 2️ Setup backend & frontend

Xem hướng dẫn chi tiết trong từng thư mục:

* [Chatbot\_BackEnd](./Chatbot_BackEnd/README.md)
* [Web\_FrontEnd](./Web_FrontEnd/README.md)

---

## Hướng phát triển

* Tích hợp **RAG (Retrieval Augmented Generation)** để tăng độ chính xác.
* Fine-tune mô hình riêng cho dữ liệu y tế Việt Nam.
* Thêm **Speech-to-Text** để hỗ trợ hội thoại bằng giọng nói.
* Phát triển ứng dụng di động (Flutter/React Native).

---

## Nhóm phát triển

Rất nên ghi rõ nhóm phát triển trong README tổng, vì điều đó:

* Giúp nhà tuyển dụng/giáo viên dễ dàng nhận biết **vai trò từng người**.
* Tạo sự minh bạch và chuyên nghiệp khi làm việc nhóm.
* Khi để kèm link GitHub, mọi người có thể click vào để xem đóng góp.

Mình gợi ý format như sau:

---

## 👨‍💻 Nhóm phát triển

* [**Hoàn Huy**](https://github.com/HhuyH) – AI Chatbot, Backend Developer, **Thiết kế SQL**
* [**Thái Đăng**](https://github.com/TdDangxkus) – Web Frontend & Backend Developer
* [**Anh Huy**](https://github.com/Sindy0711) – Database Designer (ERD & SQL Diagram)