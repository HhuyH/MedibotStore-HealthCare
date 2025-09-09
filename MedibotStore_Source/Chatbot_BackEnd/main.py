from fastapi import FastAPI
from fastapi.middleware.cors import CORSMiddleware
from routes import chat
from config.logging_config import configure_logging
from contextlib import asynccontextmanager

configure_logging()

# 👉 Lifespan context manager
@asynccontextmanager
async def lifespan(app: FastAPI):
    # Khởi tạo nếu cần
    yield
    # 🔁 Clean-up khi shutdown
    print("🛑 Server is shutting down... clean up here.")

app = FastAPI(lifespan=lifespan)

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

