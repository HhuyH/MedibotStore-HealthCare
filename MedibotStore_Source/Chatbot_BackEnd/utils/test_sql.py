import pymysql

DB_CONFIG = {
    "host": "localhost",
    "user": "chatbot_user",
    "password": "StrongPassword123",
    "database": "medicare",
    "charset": 'utf8mb4',
    "cursorclass": pymysql.cursors.Cursor
}

try:
    conn = pymysql.connect(**DB_CONFIG)
    print("Kết nối OK")
    with conn.cursor() as cursor:
        cursor.execute("SHOW TABLES;")
        print("Tables:", cursor.fetchall())
except Exception as e:
    print("Lỗi:", e)
finally:
    if conn:
        conn.close()
