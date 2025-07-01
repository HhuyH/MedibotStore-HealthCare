import asyncio
# from util.health_care import health_talk

 
async def simulate_chat_session():
    session_id = "test-session-123"
    user_id = 4  # ID user giả định
    had_conclusion = False
    stored_symptoms_name = []
    stored_symptoms = []
    recent_user_messages = []
    recent_assistant_messages = []
    recent_messages = []

    test_messages = [
        "Sáng nay mình đột nhiên bị tê một bên mặt, nói chuyện hơi khó và tay trái cũng yếu đi. Trước đó vài ngày mình có bị đau đầu dữ dội, nhưng tưởng chỉ do mất ngủ thôi",
        "lut minh ngoi lau thi bi",
        "sau khi minh thuyet trinh song sang nay",
        "khi minh cam 1 vat nan thi cam giac khong co suc",
        "tu lut minh ngu day sang nay",
        "khi minh dang lam viec thi tu nhin khong co suc gi lun"
    ]

    for msg in test_messages:
        print(f"\n👤 User: {msg}")
        async for chunk in health_talk(
            user_id=user_id,
            session_id=session_id,
            user_message=msg,
            had_conclusion=had_conclusion,
            stored_symptoms_name=stored_symptoms_name,
            stored_symptoms=stored_symptoms,
            recent_messages=recent_messages,
            recent_user_messages=recent_user_messages,
            recent_assistant_messages=recent_assistant_messages,
        ):
            print(f"🤖 Bot: {chunk}", end="")

        # (Tuỳ vào logic của bạn, có thể cần update lại recent_xxxx từ session nếu bạn dùng session_store thực sự)

# Chạy thử
if __name__ == "__main__":
    asyncio.run(simulate_chat_session())
