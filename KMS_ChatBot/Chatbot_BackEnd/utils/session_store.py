# session_store.py (bản RAM-only - tạm thời dùng khi chưa có Redis)

import asyncio

# Đây là RAM store giả lập Redis
session_dict = {}

async def get_session_data(session_id: str) -> dict:
    return session_dict.get(session_id, {})

async def save_session_data(session_id: str, data: dict):
    session_dict[session_id] = data

# session_store.py
SYMPTOM_KEY = "symptoms"

async def get_symptoms_from_session(session_id: str):
    session = await get_session_data(session_id)
    return session.get(SYMPTOM_KEY, [])

async def update_symptoms_in_session(session_id: str, new_symptoms: list):
    session = await get_session_data(session_id)
    current = session.get(SYMPTOM_KEY, [])
    for s in new_symptoms:
        if s not in current:
            current.append(s)
    session[SYMPTOM_KEY] = current
    await save_session_data(session_id, session)
    return current

async def clear_symptoms_in_session(session_id: str):
    session = await get_session_data(session_id)
    session[SYMPTOM_KEY] = []
    await save_session_data(session_id, session)
