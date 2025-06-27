# session_store.py - RAM-only session store (tạm thời dùng khi chưa có Redis)

import asyncio
import logging
from collections import defaultdict
import hashlib
logger = logging.getLogger(__name__)

# ---------------------------
# CẤU HÌNH SESSION TẠM TRÊN RAM
# ---------------------------

def resolve_session_key(user_id: str = None, session_id: str = None) -> str:
    """
    Trả về key dùng trong session_dict và SYMPTOM_SESSION.
    Ưu tiên user_id nếu có, fallback session_id.
    """
    return str(user_id) if user_id else str(session_id)

# Session lưu theo session_id (giả lập Redis)
session_dict = {}

# Dùng để lưu triệu chứng đầy đủ (dict) theo session/user
SYMPTOM_SESSION = defaultdict(list)

# Các khóa dùng trong session_dict
SYMPTOM_KEY = "symptoms"         # Dạng list[str] -> chỉ lưu ID hoặc tên triệu chứng
FOLLOWUP_KEY = "followup_asked"  # Dạng list[int] -> lưu ID đã hỏi follow-up

# ---------------------------
# CÁC HÀM LÀM VIỆC VỚI session_dict (session_id)
# ---------------------------

async def get_session_data(user_id: str = None, session_id: str = None) -> dict:
    """Truy xuất dữ liệu session từ RAM."""
    key = resolve_session_key(user_id, session_id)
    return session_dict.get(key, {})


def save_session_data(user_id: str = None, session_id: str = None, data: dict = {}):
    """Lưu dữ liệu session vào RAM."""
    key = resolve_session_key(user_id, session_id)
    session_dict[key] = data

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
    save_session_data(session_id, session)
    return current

async def clear_symptoms_in_session(session_id: str):
    """Xóa toàn bộ triệu chứng khỏi session."""
    session = await get_session_data(session_id)
    session[SYMPTOM_KEY] = []
    save_session_data(session_id, session)

# ----- Follow-up triệu chứng (ID dạng int) -----
async def get_followed_up_symptom_ids(user_id: str = None, session_id: str = None) -> list[int]:
    key = resolve_session_key(user_id, session_id)
    session = await get_session_data(key)
    return session.get(FOLLOWUP_KEY, [])

def hash_question(text: str) -> str:
    return hashlib.sha256(text.strip().encode()).hexdigest()

async def get_followed_up_question_hashes(session_id: str) -> list[str]:
    session = await get_session_data(session_id)
    return session.get(FOLLOWUP_KEY, [])

async def mark_followup_asked(user_id: str = None, session_id: str = None, symptom_ids: list[int] = []):
    key = resolve_session_key(user_id, session_id)
    if not key:
        return

    session = await get_session_data(key)
    already = set(session.get(FOLLOWUP_KEY, []))
    already.update(symptom_ids)
    session[FOLLOWUP_KEY] = list(already)
    save_session_data(key, session)
    logger.info(f"✅ [SessionStore] Ghi followup_asked vào key: {key}")

async def clear_followup_asked_all_keys(user_id: str = None, session_id: str = None):
    key = resolve_session_key(user_id, session_id)
    if not key:
        return

    session = await get_session_data(key)
    session[FOLLOWUP_KEY] = []
    save_session_data(key, session)
    logger.info(f"🧹 [SessionStore] Đã xoá followup_asked cho key: {key}")


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

    session = await get_session_data(key)
    session[SYMPTOM_KEY] = []
    session[FOLLOWUP_KEY] = []
    save_session_data(key, session)
    logger.info(f"🧹 [SessionStore] Đã xoá SYMPTOM + followup cho key: {key}")
