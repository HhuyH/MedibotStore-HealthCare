from fastapi import FastAPI
from fastapi.middleware.cors import CORSMiddleware
from routes import chat
import signal
import sys
from config.logging_config import configure_logging

configure_logging()

app = FastAPI()
# Cấu hình CORS
origins = [
    "http://localhost",
    "http://localhost:3000",
    "http://127.0.0.1",
]

app.add_middleware(
    CORSMiddleware,
    allow_origins=["*"],       # Cho phép frontend truy cập
    allow_credentials=True,
    allow_methods=["*"],         # Cho phép mọi phương thức (GET, POST,...)
    allow_headers=["*"],         # Cho phép mọi header
)

app.include_router(chat.router)



def handle_shutdown():
    print("🛑 Server shutting down...")
    sys.exit(0)

signal.signal(signal.SIGINT, lambda sig, frame: handle_shutdown())
