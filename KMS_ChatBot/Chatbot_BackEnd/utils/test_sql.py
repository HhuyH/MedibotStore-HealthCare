# test_sql.py
from sql_executor import run_sql_query
from config import DB_CONFIG
import pymysql

def test_connection():
    try:
        conn = pymysql.connect(**DB_CONFIG)
        print("✅ Kết nối MySQL thành công!")
        conn.close()
    except Exception as e:
        print("❌ Lỗi kết nối MySQL:", e)

def test_sample_query():
    result = run_sql_query("SELECT * FROM users;")
    if result['status'] == 'success':
        print("🎯 Kết quả truy vấn:", result['data'])
    else:
        print("❌ Lỗi truy vấn:", result['error'])



if __name__ == "__main__":
    test_connection()
    test_sample_query()
