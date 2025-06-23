# session_store.py - RAM-only session store (tạm thời dùng khi chưa có Redis)

import asyncio
import logging
from collections import defaultdict

logger = logging.getLogger(__name__)

# ---------------------------
# CẤU HÌNH SESSION TẠM TRÊN RAM
# ---------------------------

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

async def get_session_data(session_id: str) -> dict:
    """Truy xuất dữ liệu session từ RAM."""
    return session_dict.get(session_id, {})

def save_session_data(session_id: str, data: dict):
    """Lưu dữ liệu session vào RAM."""
    session_dict[session_id] = data

# ----- Triệu chứng (ID dạng chuỗi) -----

async def get_symptoms_from_session(session_id: str) -> list[str]:
    """Lấy danh sách triệu chứng từ session (dạng list[str])."""
    session = await get_session_data(session_id)
    return session.get(SYMPTOM_KEY, [])

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

async def get_followed_up_symptom_ids(session_id: str) -> list[int]:
    """Lấy danh sách symptom_id đã được hỏi follow-up trong session hiện tại."""
    session = await get_session_data(session_id)
    return session.get(FOLLOWUP_KEY, [])

async def mark_followup_asked(session_id: str, symptom_ids: list[int]):
    """
    Đánh dấu rằng các symptom_id đã được hỏi follow-up.
    Đảm bảo không bị trùng lặp.
    """
    session = await get_session_data(session_id)
    already = set(session.get(FOLLOWUP_KEY, []))
    already.update(symptom_ids)
    session[FOLLOWUP_KEY] = list(already)
    save_session_data(session_id, session)

async def clear_followup_asked_all_keys(user_id: str = None, session_id: str = None):
    """
    Xóa danh sách các symptom_id đã được hỏi follow-up khỏi session_dict
    theo cả user_id và session_id nếu được cung cấp.
    """

    keys_to_clear = set(filter(None, [user_id, session_id]))

    for key in keys_to_clear:
        session = await get_session_data(key)
        session[FOLLOWUP_KEY] = []
        save_session_data(key, session)


# ---------------------------
# CÁC HÀM LÀM VIỆC VỚI SYMPTOM_SESSION (triệu chứng dạng dict)
# ---------------------------

def save_symptoms_to_session(key: str, new_symptoms: list[dict]) -> list[dict]:
    """
    Thêm triệu chứng dạng dict vào SYMPTOM_SESSION theo key (user_id hoặc session_id).
    Loại bỏ trùng lặp theo symptom['id'].
    """
    current_symptoms = SYMPTOM_SESSION.get(key, [])
    current_ids = {s['id'] for s in current_symptoms}

    for symptom in new_symptoms:
        if symptom['id'] not in current_ids:
            current_symptoms.append(symptom)
            current_ids.add(symptom['id'])
        else:
            logger.debug(f"Triệu chứng '{symptom['name']}' (ID {symptom['id']}) đã có. Bỏ qua.")

    SYMPTOM_SESSION[key] = current_symptoms
    return current_symptoms

async def get_symptoms_from_session(key: str) -> list[dict]:
    """Lấy danh sách triệu chứng (dict) từ SYMPTOM_SESSION theo key."""
    return SYMPTOM_SESSION.get(key, [])

async def clear_symptoms_all_keys(user_id: str = None, session_id: str = None):
    """
    Xóa triệu chứng và các symptom đã hỏi follow-up khỏi session_dict,
    đồng thời dọn sạch cache SYMPTOM_SESSION nếu có.
    """

    keys_to_clear = set(filter(None, [user_id, session_id]))

    for key in keys_to_clear:
        # Xóa khỏi SYMPTOM_SESSION nếu tồn tại
        SYMPTOM_SESSION.pop(key, None)

        # Xóa triệu chứng và follow-up khỏi session_dict
        session = await get_session_data(key)
        session[SYMPTOM_KEY] = []
        session[FOLLOWUP_KEY] = []
        save_session_data(key, session)
