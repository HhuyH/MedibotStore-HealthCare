from collections import defaultdict
import logging
logger = logging.getLogger(__name__)

# Tạm lưu triệu chứng theo user_id hoặc session_id
SYMPTOM_SESSION = defaultdict(list)

# Thêm triệu chứng mới vào session (theo user_id hoặc session_id)
# new_symptoms: list các dict dạng {"id":..., "name":...}
def save_symptoms_to_session(key, new_symptoms):
    current_symptoms = SYMPTOM_SESSION.get(key, [])
    current_ids = {s['id'] for s in current_symptoms}

    for symptom in new_symptoms:
        if symptom['id'] not in current_ids:
            current_symptoms.append(symptom)
            current_ids.add(symptom['id'])
        else:
            logger.info(f"Triệu chứng '{symptom['name']}' (ID {symptom['id']}) đã có. Bỏ qua.")

    SYMPTOM_SESSION[key] = current_symptoms
    return current_symptoms

async def get_symptoms_from_session(key):
    return SYMPTOM_SESSION.get(key, [])

def clear_symptoms_from_session(key):
    if key in SYMPTOM_SESSION:
        del SYMPTOM_SESSION[key]

def clear_symptoms_all_keys(user_id=None, session_id=None):
    if session_id:
        clear_symptoms_from_session(session_id)
    if user_id:
        clear_symptoms_from_session(user_id)
