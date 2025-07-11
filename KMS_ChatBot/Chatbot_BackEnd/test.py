import asyncio
from utils.health_care import health_talk

async def simulate_chat_session():
    user_id = 4
    session_id = "test-session-01"
    chat_id = None
    session_context = {"diagnosed_today": False}

    stored_symptoms = []
    recent_messages = []
    recent_user_messages = []
    recent_assistant_messages = []

    messages = [
        "Sáng nay mình đột nhiên bị tê một bên mặt, nói chuyện hơi khó và tay trái cũng yếu đi. Trước đó vài ngày mình có bị đau đầu dữ dội, nhưng tưởng chỉ do mất ngủ thôi",
        "lut minh ngoi lau thi bi",
        "sau khi minh thuyet trinh song sang nay",
        "khi minh cam 1 vat nan thi cam giac khong co suc",
        "tu lut minh ngu day sang nay",
        "khi minh dang lam viec thi tu nhin khong co suc gi lun"
    ]

    for msg in messages:
        print(f"👤 User: {msg}")
        async for chunk in health_talk(
            user_message=msg,
            stored_symptoms=stored_symptoms,
            recent_messages=recent_messages,
            recent_user_messages=recent_user_messages,
            recent_assistant_messages=recent_assistant_messages,
            session_id=session_id,
            user_id=user_id,
            chat_id=chat_id,
            session_context=session_context
        ):
            print(chunk, end='', flush=True)

        # Cập nhật lại history
        recent_messages.append(f"👤 {msg}")
        recent_user_messages.append(msg)
        if len(recent_messages) > 6:
            recent_messages = recent_messages[-6:]
            recent_user_messages = recent_user_messages[-6:]

# Run
if __name__ == "__main__":
    asyncio.run(simulate_chat_session())
