import asyncio
from datetime import datetime, timedelta
from models import Message
from routes.chat import chat_stream
from utils.session_store import (
    resolve_session_key,
    save_session_data,
    get_session_data,
    save_symptoms_to_session,
    get_symptoms_from_session,
)

async def test_auto_reset_session():
    # 1. Setup session giả
    session_id = "test_session_001"
    user_id = "1001"

    yesterday = (datetime.now() - timedelta(days=1)).strftime("%Y-%m-%d")
    today = datetime.now().strftime("%Y-%m-%d")

    # Tạo session giả với active_date là hôm qua
    fake_session = {
        "active_date": yesterday,
        "last_intent": "symptom_query",
        "recent_messages": ["👤 Tôi thấy hơi đau đầu", "🤖 Bạn thấy đau đầu từ khi nào vậy?"],
        "symptoms": ["Đau đầu"],
        "followup_asked": [1]
    }
    save_session_data(session_id, fake_session)

    print("🔍 [Trước khi gửi message]")
    print(await get_session_data(session_id))

    # 2. Gửi message mới (dưới dạng object Message)
    msg = Message(
        user_id=user_id,
        username="Tester",
        role="Patient",
        session_id=session_id,
        message="Tôi vẫn thấy hơi mệt mỏi",
        history=[]
    )

    # 3. Gọi hàm chat_stream để xử lý message
    print("\n🚀 [Bắt đầu test auto-reset]")
    response = await chat_stream(msg)

    print("\n📥 [Dữ liệu được stream ra]:")
    async for chunk in response.body_iterator:
        if isinstance(chunk, bytes):
            chunk = chunk.decode("utf-8")
        print(chunk.strip())


    # 4. Kiểm tra lại session sau khi xử lý
    print("\n✅ [Sau khi xử lý message]")
    new_session = await get_session_data(session_id)
    print(new_session)

    # Kiểm tra logic reset có hoạt động không
    if new_session.get("active_date") == today and not new_session.get("symptoms"):
        print("\n🎉 SUCCESS: Session đã được reset do qua ngày!")
    else:
        print("\n❌ FAIL: Session KHÔNG được reset như kỳ vọng!")

async def test_auto_reset_session_guest():
    # 1. Setup session giả cho guest
    session_id = "test_session_guest_auto"
    user_id = None  # ✅ Guest → không có user_id

    yesterday = (datetime.now() - timedelta(days=1)).strftime("%Y-%m-%d")
    today = datetime.now().strftime("%Y-%m-%d")

    # Tạo session giả với active_date là hôm qua
    fake_session = {
        "active_date": yesterday,
        "last_intent": "symptom_query",
        "recent_messages": ["👤 Tôi thấy hơi chóng mặt", "🤖 Bạn bị chóng mặt khi nào vậy?"],
        "symptoms": ["Chóng mặt"],
        "followup_asked": [3]
    }

    key = resolve_session_key(user_id=user_id, session_id=session_id)
    save_session_data(key, fake_session)
    save_symptoms_to_session(user_id=user_id, session_id=session_id, new_symptoms=[{"id": 3, "name": "Chóng mặt"}])

    print("🔍 [Trước khi gửi message — Guest]")
    print(await get_session_data(key))

    # 2. Gửi message mới
    msg = Message(
        user_id=user_id,
        username="Guest",
        role="Guest",
        session_id=session_id,
        message="Giờ vẫn thấy hơi mệt",
        history=[]
    )

    # 3. Gọi hàm chat_stream để xử lý
    print("\n🚀 [Bắt đầu test auto-reset cho Guest]")
    response = await chat_stream(msg)

    print("\n📥 [Dữ liệu được stream ra — Guest]:")
    async for chunk in response.body_iterator:
        if isinstance(chunk, bytes):
            chunk = chunk.decode("utf-8")
        print(chunk.strip())

    # 4. Kiểm tra lại session sau khi xử lý
    print("\n✅ [Sau khi xử lý message — Guest]")
    new_session = await get_session_data(key)
    print(new_session)

    symptoms_after = await get_symptoms_from_session(user_id=user_id, session_id=session_id)

    if new_session.get("active_date") == today and not new_session.get("symptoms") and symptoms_after == []:
        print("\n🎉 SUCCESS: Guest session đã được reset đúng cách do qua ngày!")
    else:
        print("\n❌ FAIL: Guest session KHÔNG được reset như kỳ vọng!")


async def test_no_reset_same_day():
    session_id = "test_session_same_day"
    today = datetime.now().strftime("%Y-%m-%d")

    save_session_data(session_id, {
        "active_date": today,
        "symptoms": ["Ho"],
        "followup_asked": [3],
        "recent_messages": ["👤 Tôi bị ho"]
    })

    msg = Message(
        user_id=2002,
        username="SameDayUser",
        role="Patient",
        session_id=session_id,
        message="Vẫn còn ho nhẹ nhẹ",
        history=[]
    )

    print("\n🔍 Test 2: Không reset nếu cùng ngày")
    response = await chat_stream(msg)
    async for chunk in response.body_iterator:
        pass

    updated = await get_session_data(session_id)
    assert updated.get("symptoms") == ["Ho"]
    assert updated.get("followup_asked") == [3]
    print("✅ Session không bị reset nếu trong cùng ngày.")


# Nếu chạy trực tiếp:
if __name__ == "__main__":
    # asyncio.run(test_auto_reset_session())
    # asyncio.run(test_auto_reset_session_guest())
    asyncio.run(test_no_reset_same_day())