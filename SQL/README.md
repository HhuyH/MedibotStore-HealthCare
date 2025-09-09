### Gợi ý nội dung README cho thư mục SQL (ví dụ `Database/README.md`):

# Medibot Store – Database

## Giới thiệu

Phần cơ sở dữ liệu cho hệ thống Medibot Store được thiết kế để hỗ trợ:

* Quản lý bệnh nhân, bác sĩ, sản phẩm, lịch hẹn.
* Tích hợp với chatbot backend thông qua **MySQL**.

## Công nghệ

* **MySQL 8.0**
* **Diagram** (do [Anh Huy](https://github.com/Sindy0711) thiết kế)
* **SQL Scripts**: tạo schema, bảng, quan hệ, và dữ liệu mẫu.

## Cài đặt

### 1️. Cách 1: Import nhanh

Chỉ cần import file **`medicare.sql`** là đã có đầy đủ cấu trúc bảng, dữ liệu mẫu và quyền truy cập:

```bash
mysql -u root -p medicare < medicare.sql
```

---

### 2️. Cách 2: Thiết lập thủ công

1. Tạo database `medicare`:

```sql
CREATE DATABASE medicare CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

2. Chạy file **`SQL_NOTE.sql`** để tạo bảng:

```bash
mysql -u root -p medicare < SQL_NOTE.sql
```

3. Chạy file **`sample_data.sql`** để thêm dữ liệu mẫu phục vụ test:

```bash
mysql -u root -p medicare < sample_data.sql
```

4. Chạy file **`user_role.sql`** để tạo quyền truy cập cho chatbot:

```bash
mysql -u root -p medicare < user_role.sql
```

## Sơ đồ hệ thống  

Dự án **Medibot Store Database** bao gồm các sơ đồ phân tích & thiết kế hệ thống:  

- [Use Case Diagrams](./Usecase) – Mô tả các tác nhân chính và chức năng tương tác.  
- [Activity Diagrams](./ActivityDiagram) – Biểu diễn luồng xử lý chính trong hệ thống.  
- [Data Flow Diagrams (DFD)](./DFD) – Luồng dữ liệu giữa các tiến trình, tác nhân và kho dữ liệu.  
- [Entity Relationship Diagrams (ERD)](./ERD) – Thiết kế cơ sở dữ liệu với các bảng chính như `Patient`, `Doctor`, `Appointment`, `Prescription`, `Product`, …  


## Người thực hiện

* [**Hoàn Huy**](https://github.com/HhuyH) – Thiết kế SQL, tích hợp với backend.
* [**Anh Huy**](https://github.com/Sindy0711) – Thiết kế sơ đồ dữ liệu
