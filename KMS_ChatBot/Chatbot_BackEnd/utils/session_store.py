import logging
from collections import defaultdict
import hashlib
logger = logging.getLogger(__name__)
import json
from datetime import timedelta

# Kết nối Redis
from redis import asyncio as aioredis
redis_client = aioredis.Redis(host='localhost', port=6379, db=0, decode_responses=True)

# ---------------------------
# CÁC HÀM LÀM VIỆC VỚI session_dict (session_id)
# ---------------------------

# Session lưu theo session_id (giả lập Redis)
session_dict = {}

# Dùng để lưu triệu chứng đầy đủ (dict) theo session/user
SYMPTOM_SESSION = defaultdict(list)

# Các khóa dùng trong session_dict
SYMPTOM_KEY = "symptoms"         # Dạng list[str] -> chỉ lưu ID hoặc tên triệu chứng
FOLLOWUP_KEY = "followup_asked"  # Dạng list[int] -> lưu ID đã hỏi follow-up

# Khóa mặc định nếu không có user/session
def resolve_session_key(user_id: str = None, session_id: str = None) -> str:
    if user_id is not None:
        return f"session:user:{str(user_id)}"
    if session_id is not None:
        return f"session:sess:{str(session_id)}"
    return "session:unknown"


# lưu session
async def save_session_data(user_id: str = None, session_id: str = None, data: dict = {}):
    key = resolve_session_key(user_id, session_id)

    # ⚠️ Đọc lại session cũ nếu có
    existing_raw = await redis_client.get(key)
    existing_data = json.loads(existing_raw) if existing_raw else {}

    # ✅ MERGE tại đây
    existing_data.update(data)

    # logger.info(f"📝 [SAVE] key = {key}")
    # logger.info(f"📝 [SAVE] data = {json.dumps(existing_data, indent=2, ensure_ascii=False)}")
    await redis_client.setex(key, timedelta(days=1), json.dumps(existing_data))


# lấy session
async def get_session_data(user_id: str = None, session_id: str = None) -> dict:
    key = resolve_session_key(user_id, session_id)
    raw = await redis_client.get(key)
    # logger.info(f"📤 [GET] key = {key}, raw = {raw}")
    return json.loads(raw) if raw else {}

# ----- Triệu chứng (ID dạng chuỗi) -----

async def get_symptoms_from_session(user_id: str = None, session_id: str = None) -> list[dict]:
    key = resolve_session_key(user_id, session_id)
    return SYMPTOM_SESSION.get(key, [])

async def update_symptoms_in_session(session_id: str, new_symptoms: list[str]) -> list[str]:
    """
    Cập nhật thêm triệu chứng mới vào session (dạng list[str]), loại bỏ trùng lặp.
    Trả về danh sách triệu chứng sau cập nhật.
    """
    session = await get_session_data(session_id)
    current = session.get(SYMPTOM_KEY, [])
    for s in new_symptoms:
        if s not in current:
            current.append(s)
    session[SYMPTOM_KEY] = current
    await save_session_data(session_id, session)
    return current

async def clear_symptoms_in_session(session_id: str):
    """Xóa toàn bộ triệu chứng khỏi session."""
    session = await get_session_data(session_id)
    session[SYMPTOM_KEY] = []
    await save_session_data(session_id, session)

# ----- Follow-up triệu chứng (ID dạng int) -----
async def get_followed_up_symptom_ids(user_id: str = None, session_id: str = None) -> list[int]:
    session = await get_session_data(user_id=user_id, session_id=session_id)
    return session.get(FOLLOWUP_KEY, [])

def hash_question(text: str) -> str:
    return hashlib.sha256(text.strip().encode()).hexdigest()

async def get_followed_up_question_hashes(session_id: str) -> list[str]:
    session = await get_session_data(session_id)
    return session.get(FOLLOWUP_KEY, [])

async def mark_followup_asked(user_id: str = None, session_id: str = None, symptom_ids: list[int] = []):
    session = await get_session_data(user_id=user_id, session_id=session_id)
    already = set(session.get(FOLLOWUP_KEY, []))
    already.update(symptom_ids)
    session[FOLLOWUP_KEY] = list(already)
    await save_session_data(user_id=user_id, session_id=session_id, data=session)

    # logger.info(f"✅ [SessionStore] Ghi followup_asked vào key: {key}")

async def clear_followup_asked_all_keys(user_id: str = None, session_id: str = None):
    session = await get_session_data(user_id=user_id, session_id=session_id)
    session[FOLLOWUP_KEY] = []
    await save_session_data(user_id=user_id, session_id=session_id, data=session)
    # logger.info(f"🧹 [SessionStore] Đã xoá followup_asked cho key: {key}")


# ---------------------------
# CÁC HÀM LÀM VIỆC VỚI SYMPTOM_SESSION (triệu chứng dạng dict)
# ---------------------------

def save_symptoms_to_session(user_id: str = None, session_id: str = None, new_symptoms: list[dict] = []) -> list[dict]:
    key = resolve_session_key(user_id, session_id)
    current_symptoms = SYMPTOM_SESSION.get(key, [])
    current_ids = {s['id'] for s in current_symptoms}

    for symptom in new_symptoms:
        if symptom['id'] not in current_ids:
            current_symptoms.append(symptom)
            current_ids.add(symptom['id'])

    SYMPTOM_SESSION[key] = current_symptoms
    return current_symptoms

async def get_symptoms_from_session(user_id: str = None, session_id: str = None) -> list[dict]:
    key = resolve_session_key(user_id, session_id)
    return SYMPTOM_SESSION.get(key, [])

async def clear_symptoms_all_keys(user_id: str = None, session_id: str = None):
    key = resolve_session_key(user_id, session_id)
    if not key:
        return

    SYMPTOM_SESSION.pop(key, None)

    session = await get_session_data(user_id=user_id, session_id=session_id)
    session[SYMPTOM_KEY] = []
    session[FOLLOWUP_KEY] = []
    await save_session_data(user_id=user_id, session_id=session_id)
    # logger.info(f"🧹 [SessionStore] Đã xoá SYMPTOM + followup cho key: {key}")

# ---------------------------
# CÁC HÀM LÀM VIỆC VỚI SYMPTOM_NOTE (ghi chú về triệu chứng của người dùng do bot tự tổng hộp từ chat)
# ---------------------------
SYMPTOM_NOTE_KEY = "symptom_notes"

async def update_symptom_note_in_session(user_id: str = None, session_id: str = None, symptom_name: str = "", note: str = ""):
    session = await get_session_data(user_id=user_id, session_id=session_id)
    notes = session.get(SYMPTOM_NOTE_KEY, {})
    notes[symptom_name] = note
    session[SYMPTOM_NOTE_KEY] = notes
    await save_session_data(user_id=user_id, session_id=session_id, data=session)

async def get_symptom_notes_from_session(user_id: str = None, session_id: str = None) -> dict:
    session = await get_session_data(user_id=user_id, session_id=session_id)
    return session.get(SYMPTOM_NOTE_KEY, {})

# ---------------------------
# HÀM LƯU TRỮ TIN NHẮN
# ---------------------------

async def update_chat_history_in_session(user_id, session_data, session_id, user_msg, bot_msg, extra_data=None):

    recent_messages = session_data.get("recent_messages", [])
    recent_user_messages = session_data.get("recent_user_messages", [])
    recent_assistant_messages = session_data.get("recent_assistant_messages", [])

    recent_messages.append(f"👤 {user_msg}")
    recent_messages.append(f"🤖 {bot_msg}")
    recent_user_messages.append(user_msg)
    recent_assistant_messages.append(bot_msg)

    # ✨ Loại bỏ lặp lại liên tiếp
    recent_user_messages = await remove_consecutive_duplicates(recent_user_messages)
    recent_assistant_messages = await remove_consecutive_duplicates(recent_assistant_messages)

    session_data["recent_messages"] = recent_messages[-12:]
    session_data["recent_user_messages"] = recent_user_messages[-6:]
    session_data["recent_assistant_messages"] = recent_assistant_messages[-6:]

    

    await save_session_data(user_id=user_id, session_id=session_id, data=session_data)

    

    # logger.info("🧾 recent_user_messages:")
    # for i, user_msg in enumerate(session_data["recent_user_messages"], 1):
    #     logger.info(f"👤 [{i}] {user_msg}")

    # logger.info("📢 recent_assistant_messages:")
    # for i, assistant_msg in enumerate(session_data["recent_assistant_messages"], 1):
    #     logger.info(f"🤖 [{i}] {assistant_msg}")

async def remove_consecutive_duplicates(messages: list[str]) -> list[str]:
    if not messages:
        return []
    result = [messages[0]]
    for msg in messages[1:]:
        if msg != result[-1]:
            result.append(msg)
    return result

# ---------------------------
# ĐÁNH DẤU ĐÃ HỎI TRIỆU CHỨNG LIÊN QUAN (related_symptom_asked)
# ---------------------------

async def mark_related_symptom_asked(session_id: str = None, user_id: str = None):
    session = await get_session_data(user_id=user_id, session_id=session_id)
    session["related_symptom_asked"] = True
    await save_session_data(user_id=user_id, session_id=session_id, data=session)


async def reset_related_symptom_flag(session_id: str = None, user_id: str = None):
    session = await get_session_data(user_id=user_id, session_id=session_id)
    session["related_symptom_asked"] = False
    await save_session_data(user_id=user_id, session_id=session_id, data=session)

