# Giải Quyết Vấn Đề Git Thay Đổi Dữ Liệu Khi Push Code

## 🚨 Vấn Đề Phổ Biến

Khi push code lên Git, bạn có thể gặp phải tình trạng các dòng lấy dữ liệu bị thay đổi không mong muốn. Điều này thường xảy ra do:

### 1. **Line Endings (Kết thúc dòng)**

- **Windows** sử dụng: `CRLF` (\r\n)
- **Unix/Linux/Mac** sử dụng: `LF` (\n)
- Git tự động chuyển đổi giữa các định dạng này

### 2. **Encoding Issues (Vấn đề mã hóa)**

- File có thể được lưu với encoding khác nhau
- UTF-8, UTF-8 with BOM, ANSI, etc.

### 3. **Git AutoCRLF Settings**

- Git tự động xử lý line endings dựa trên cấu hình hệ thống

## 🔧 Cách Khắc Phục

### Bước 1: Kiểm tra cấu hình Git hiện tại

```bash
git config --global core.autocrlf
git config --global core.safecrlf
```

### Bước 2: Cấu hình Git cho Windows

```bash
# Tắt tự động chuyển đổi line endings
git config --global core.autocrlf false

# Hoặc chỉ chuyển đổi khi checkout (khuyến nghị)
git config --global core.autocrlf true

# Cảnh báo khi có mixed line endings
git config --global core.safecrlf warn
```

### Bước 3: Tạo file .gitattributes

Tạo file `.gitattributes` trong thư mục gốc của project:

```
# Tự động detect text files và normalize line endings
* text=auto

# Specifically for PHP files
*.php text eol=lf

# For SQL files
*.sql text eol=lf

# For CSS/JS files
*.css text eol=lf
*.js text eol=lf

# Binary files
*.png binary
*.jpg binary
*.jpeg binary
*.gif binary
*.ico binary
*.pdf binary

# Keep Windows line endings for batch files
*.bat text eol=crlf
```

### Bước 4: Làm sạch repository

```bash
# Xóa cache Git
git rm --cached -r .

# Add lại tất cả files
git add .

# Commit với message rõ ràng
git commit -m "Fix line endings and encoding issues"

# Push lên remote
git push origin main
```

## 🎯 Cấu Hình Khuyến Nghị

### Cho Windows Development:

```bash
git config --global core.autocrlf true
git config --global core.safecrlf warn
git config --global core.editor "code --wait"
git config --global init.defaultBranch main
```

### Cho Team Development:

```bash
# EditorConfig cho consistency
# Tạo file .editorconfig
root = true

[*]
charset = utf-8
end_of_line = lf
insert_final_newline = true
trim_trailing_whitespace = true

[*.php]
indent_style = space
indent_size = 4

[*.{js,css}]
indent_style = space
indent_size = 2

[*.sql]
indent_style = space
indent_size = 2
```

## 🔍 Debug Commands

### Kiểm tra file encoding:

```bash
file -bi filename.php
```

### Kiểm tra line endings:

```bash
# Windows
git ls-files --eol

# Check specific file
git ls-files --eol | grep "filename.php"
```

### Xem changes trước khi commit:

```bash
git diff --check
git diff --ws-error-highlight=all
```

## 🚫 Những Gì Cần Tránh

1. **Không** mix line endings trong cùng một file
2. **Không** commit files với trailing whitespace
3. **Không** thay đổi encoding của files có sẵn
4. **Không** sử dụng `git add .` mà không kiểm tra changes

## 📝 Best Practices

### 1. Sử dụng IDE/Editor phù hợp:

- Visual Studio Code với extensions:
  - EditorConfig
  - GitLens
  - PHP extensions

### 2. Pre-commit hooks:

```bash
# Install pre-commit
pip install pre-commit

# Tạo .pre-commit-config.yaml
repos:
  - repo: https://github.com/pre-commit/pre-commit-hooks
    rev: v4.4.0
    hooks:
      - id: trailing-whitespace
      - id: end-of-file-fixer
      - id: check-yaml
      - id: check-json
      - id: mixed-line-ending
```

### 3. Regular maintenance:

```bash
# Weekly cleanup
git gc --aggressive
git remote prune origin
```

## 🔄 Quy Trình Làm Việc An Toàn

1. **Trước khi code:**

   ```bash
   git pull origin main
   git status
   ```

2. **Trong quá trình code:**

   ```bash
   git add -p  # Add từng phần thay vì tất cả
   git diff --staged  # Xem changes trước commit
   ```

3. **Trước khi commit:**

   ```bash
   git diff --check  # Kiểm tra whitespace issues
   git status  # Xem lại files changed
   ```

4. **Khi commit:**

   ```bash
   git commit -m "Clear, descriptive message"
   ```

5. **Trước khi push:**
   ```bash
   git log --oneline -5  # Xem lại commits
   git push origin main
   ```

## 🆘 Khôi Phục Khi Có Vấn Đề

### Nếu đã commit nhưng chưa push:

```bash
git reset --soft HEAD~1  # Undo commit nhưng giữ changes
git reset --hard HEAD~1  # Undo commit và xóa changes
```

### Nếu đã push:

```bash
git revert HEAD  # Tạo commit mới để revert
```

### Khôi phục file cụ thể:

```bash
git checkout HEAD -- filename.php
```

---

## 📞 Hỗ Trợ

Nếu vẫn gặp vấn đề, hãy:

1. Check git status và git log
2. So sánh với version trước đó
3. Sử dụng git diff để xem chính xác thay đổi gì
4. Tham khảo Git documentation

**Lưu ý:** Luôn backup code quan trọng trước khi thực hiện các thao tác Git phức tạp!
