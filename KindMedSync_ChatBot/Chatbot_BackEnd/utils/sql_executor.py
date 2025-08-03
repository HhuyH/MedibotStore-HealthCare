# utils/sql_executor.py
import pymysql
from config.config import DB_CONFIG
from decimal import Decimal
import re

from datetime import datetime

def run_sql_query(query: str):
    # Kết nối cơ sở dữ liệu MySQL
    conn = pymysql.connect(**DB_CONFIG)
    try:
        with conn.cursor() as cursor:
            # Thực thi câu truy vấn SQL
            cursor.execute(query)
            result = cursor.fetchall()
            columns = [desc[0] for desc in cursor.description]

        data = []
        # Xử lý từng dòng kết quả, chuyển về dict JSON-friendly
        for row in result:
            item = {}
            for i, col in enumerate(columns):
                value = row[i]
                # Chuyển Decimal → float để JSON hóa
                if isinstance(value, Decimal):
                    value = float(value)
                # Chuyển datetime → string định dạng
                elif isinstance(value, datetime):
                    value = value.strftime("%Y-%m-%d %H:%M:%S")  # format tùy ý
                item[col] = value
            data.append(item)

        return {"status": "success", "data": data}
    except Exception as e:
        # Nếu lỗi, trả về status error và message
        return {"status": "error", "error": str(e)}
    finally:
        # Đóng kết nối an toàn
        conn.close()

def extract_sql(text):
    code_block = re.search(r"```sql\s+(.*?)```", text, re.IGNORECASE | re.DOTALL)
    if code_block:
        return code_block.group(1).strip()
    select_stmt = re.search(r"(SELECT\s+.+?;)", text, re.IGNORECASE | re.DOTALL)
    if select_stmt:
        return select_stmt.group(1).strip()
    return None