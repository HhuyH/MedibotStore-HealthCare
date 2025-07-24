# auth_utils.py

from typing import List
from config.intents import INTENT_PIPELINES, VALID_INTENTS, INTENT_MAPPING
import logging
logger = logging.getLogger(__name__)

# ---- Phân quyền theo role ----
ROLE_INTENT_PERMISSIONS = {
    "Admin": VALID_INTENTS,  # full quyền
    "Doctor": [
        "general_chat", "health_query", "medical_history", "patient_summary_request"
    ],
    "Patient": [
        "general_chat", "health_query", "health_advice", "suggest_product",
        "booking_request", "medical_history", "products", "services",
        "orders", "order_items_details","symptom_query"
    ],
    "Guest": [
        "general_chat", "health_query"
    ]
}


# -----------------------------------------------

def normalize_role(role):
    if role is None:
        return "Guest"
    if not isinstance(role, str) or role.strip() == "":
        return "Guest"
    return role

# Kiểm tra xem intent có hợp lệ với vai trò hiện tại hay không
def has_intent_permission(role: str, intent: str) -> bool:
    role = normalize_role(role)
    allowed_intents = ROLE_INTENT_PERMISSIONS.get(role, [])
    return intent in allowed_intents

# Nếu người dùng không có quyền với intent gốc, ép về 'general_chat'
def enforce_permission(role: str, intent: str) -> str:
    if has_intent_permission(role, intent):
        return intent
    return "general_chat"


def get_pipeline(intent: str) -> List[str]:
    """
    Lấy pipeline xử lý tương ứng với intent
    """
    pipeline_key = INTENT_MAPPING.get(intent, "general_chat")
    return INTENT_PIPELINES.get(pipeline_key, ["chat"])


# Optional: Log cho mục đích debug
def log_intent_handling(user_id, username, role, original_intent, final_intent):
    logger.info(f"[Auth] User '{username}' (ID: {user_id}) | Role: {role} | "f"Intent: {original_intent} → {final_intent}")