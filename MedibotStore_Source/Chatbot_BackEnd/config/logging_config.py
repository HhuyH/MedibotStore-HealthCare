import logging

def configure_logging():
    logging.basicConfig(
        level=logging.INFO,  # Thứ tự mức độ log: CRITICAL > ERROR > WARNING > INFO > DEBUG level=logging.DEBUG → tất cả các loại log
        format="%(asctime)s [%(levelname)s] %(name)s: %(message)s",
        datefmt="%Y-%m-%d %H:%M:%S",
    )

    # Giảm log rác từ uvicorn (tuỳ chọn)
    logging.getLogger("uvicorn.access").setLevel(logging.WARNING)


# | Mức độ                | Dùng khi...                                     | Ví dụ                        |
# | --------------------- | ----------------------------------------------- | ---------------------------- |
# | `logger.debug(...)`   | Log kỹ thuật chi tiết (chỉ hiện khi debug mode) | Dữ liệu input, SQL query...  |
# | `logger.info(...)`    | Log các sự kiện bình thường có ích để theo dõi  | User gửi message, intent...  |
# | `logger.warning(...)` | Có vấn đề nhẹ cần để ý                          | Parse lỗi nhẹ, dữ liệu thiếu |
# | `logger.error(...)`   | Lỗi nghiêm trọng cần xử lý                      | Exception, lỗi API           |
