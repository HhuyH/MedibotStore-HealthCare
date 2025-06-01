import os
import openai
from dotenv import load_dotenv

#Tải tệp .env và ghi đè các biến môi trường hiện có
load_dotenv(override=True)

#Lấy OpenAI API từ
OPENAI_API_KEY = os.getenv('OPENAI_API_KEY')

#Kiểm tra có API ko ko có báo lổi
if not OPENAI_API_KEY:
    raise ValueError("OpenAI API key not found in environment variables.")

openai.api_key = OPENAI_API_KEY

#Đặt model sẽ dùng
MODEL = "gpt-4o-mini"