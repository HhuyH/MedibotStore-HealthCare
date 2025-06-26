
import sys
import os
import logging
logger = logging.getLogger(__name__)

# Thêm đường dẫn thư mục cha vào sys.path
sys.path.append(os.path.abspath(os.path.join(os.path.dirname(__file__), '..')))
from prompts.db_schema.load_schema import user_core_schema, schema_modules
from prompts.prompts import build_system_prompt
from prompts.prompts import system_prompt_sql, build_diagnosis_controller_prompt
from utils.openai_client import chat_completion
from utils.text_utils import normalize_text
from config.intents import VALID_INTENTS, INTENT_MAPPING
import json

def get_combined_schema_for_intent(intent: str) -> str:
    intent = normalize_text(intent)  # chuẩn hóa không dấu, lowercase
    schema_parts = [user_core_schema]  # luôn load phần lõi

    keyword_map = {
        'user_profile': [
            "user", "người dùng", "tài khoản", "username", "email", "vai trò", "id người dùng"
        ],
        'medical_history': [
            "bệnh", "disease", "tiền sử", "symptom", "triệu chứng", "bệnh nền"
        ],
        'doctor_clinic': [
            "phòng khám", "clinic", "bác sĩ", "chuyên khoa", "lịch khám", "cơ sở y tế"
        ],
        'appointments': [
            "lịch hẹn", "appointment", "khám bệnh", "thời gian khám", "ngày khám"
        ],
        'ai_prediction': [
            "dự đoán", "ai", "phân tích sức khỏe", "prediction", "chatbot"
        ],
        'products': [
            "sản phẩm", "thuốc", "toa thuốc", "giá tiền", "kê đơn", "thuốc nào"
        ],
        'orders': [
            "đơn hàng", "thanh toán", "hóa đơn", "order", "lịch sử mua", "mua hàng"
        ],
        'services': [
            "dịch vụ", "gói khám", "liệu trình", "service", "gói điều trị"
        ],
        'notifications': [
            "thông báo", "notification", "tin nhắn hệ thống"
        ],
        'ai_diagnosis_result': [
            "ai đoán", "ai từng chẩn đoán", "ai dự đoán", "kết quả ai", "bệnh ai đoán", "chẩn đoán từ ai"
        ],
    }

    normalized_intent = normalize_text(intent)

    # Dò theo từ khóa để biết schema nào cần nạp
    for module_key, keywords in keyword_map.items():
        if any(kw in normalized_intent for kw in keywords):
            schema = schema_modules.get(module_key)
            if schema and schema not in schema_parts:
                schema_parts.append(schema)

    # Luật đặc biệt: nếu là lịch hẹn, luôn thêm doctor_clinic và user
    if "appointment" in normalized_intent or "lịch hẹn" in normalized_intent:
        for extra in ["doctor_clinic", "user_profile"]:
            schema = schema_modules.get(extra)
            if schema and schema not in schema_parts:
                schema_parts.append(schema)

    return "\n".join(schema_parts)

# Phạt hiện đang là sử dụng chức nắng nào là chat bình thường hay là phát hiện và dự đoán bệnh
async def detect_intent(
    user_message: str,
    session_key: str = None,
    last_intent: str = None,
    recent_messages: list[str] = [],
    recent_user_messages: list[str] = [],
    recent_assistant_messages: list[str] = []
) -> str:
    # Sử dụng trực tiếp message đã tách
    last_bot_msg = recent_assistant_messages[-1] if recent_assistant_messages else ""
    last_user_msg = recent_user_messages[-1] if recent_user_messages else ""

    prompt = f"""
        Classify the user's intent in a chatbot conversation.

        Last detected intent: "{last_intent or 'unknown'}"
        
        Previous bot message (usually a follow-up question):  
        "{last_bot_msg}"

        Current user message:  
        "{last_user_msg}"

        Valid intents: {", ".join(VALID_INTENTS)}

        Instructions:

        - If the last intent was "symptom_query" and the user's current message clearly answers a previous follow-up (e.g., gives timing, severity, or symptom detail), then KEEP "symptom_query".

        - If the user is asking for general advice on how to deal with a symptom (e.g., how to sleep better, what to eat for energy), or wants wellness guidance (e.g., chăm sóc sức khỏe, tăng sức đề kháng), classify as "health_advice".

        - Only use "symptom_query" if the user is directly describing symptoms they are experiencing.

        - Use "general_chat" if the message is unrelated small talk, jokes, greetings, or off-topic.

        - If unsure, prefer to keep the previous intent (if valid).
        - If the user message sounds like a **data query or admin command** (e.g., "lấy danh sách người dùng", "xem danh sách đơn hàng", "tìm bệnh nhân"), then classify as `"sql_query"` (or appropriate admin intent).
        - If the user is asking to view a patient's health data (e.g., “xem thông tin bệnh nhân”, “hồ sơ bệnh nhân”, “tình trạng bệnh nhân”, “tình hình của bệnh nhân”, “cho tôi xem bệnh nhân tên...”) → classify as "patient_summary_request"
        - Only use `"general_chat"` if the user is making small talk, asking about the bot, or saying unrelated casual things.
        - Do NOT misclassify structured or technical requests as casual chat.
        - If unsure, prefer a more specific intent over `"general_chat"`.
        - If the previous assistant message was a follow-up question about a symptom, and the user responds with something vague or approximate (e.g. “chắc 5-10 phút”, “khoảng sáng tới giờ”, “tầm chiều hôm qua”), you MUST assume this is a continuation of the symptom discussion → KEEP "symptom_query".
        - If user says “không biết”, “chắc vậy”, “khó nói”, "không rõ", but it’s still in reply to a symptom follow-up → KEEP "symptom_query"

        Always return only ONE valid intent from the list.
        Do NOT explain your reasoning.
        Do NOT include any other words — only return the intent.

        Examples:
        - Bot: “Cảm giác đau đầu của bạn thường xuất hiện vào lúc nào?”  
          User: “Mình cũng không rõ lắm” → ✅ → intent = `symptom_query`

        - Bot: “Bạn bị bỏng vào lúc nào?”  
          User: “Hình như hôm qua” → ✅ → intent = `symptom_query`

        - Bot: “Cảm giác đau đầu của bạn kéo dài bao lâu?”  
          User: “Tầm 10 phút thôi” → ✅ → intent = `symptom_query`

        - Bot: “Bạn bị chóng mặt khi nào?”  
          User: “Giờ mấy giờ rồi ta?” → ❌ → intent = `general_chat`

        - Bot: “Bạn thấy mệt như thế nào?”  
          User: “Chắc do nắng nóng quá” → ✅ → intent = `symptom_query`

        - Bot: “Cơn đau đầu của bạn thường kéo dài bao lâu vậy?”  
          User: “tầm 5 10 phút gì đó” → ✅ → intent = `symptom_query`

        - User: “Làm sao để đỡ đau bụng?” → ✅ → intent = `health_advice`
        - User: “Ăn gì để dễ ngủ hơn?” → ✅ → intent = `health_advice`
        - User: “lấy danh sách người dùng” → ✅ → intent = `sql_query`
        - User: “cho mình xem đơn hàng gần đây nhất” → ✅ → intent = `sql_query`
        - User: “hôm nay trời đẹp ghê” → ✅ → intent = `general_chat`

        - User: “Cho tôi xem hồ sơ bệnh nhân Nguyễn Văn A” → ✅ → intent = `patient_summary_request`
        - User: “Xem tình hình bệnh nhân có sđt 0909...” → ✅ → intent = `patient_summary_request`
        - User: “Bệnh nhân đó dạo này sao rồi?” → ✅ → intent = `patient_summary_request`




        → What is the current intent?
    """

    try:
        # 🧠 Gọi GPT để phân loại intent
        response = chat_completion(
            [{"role": "user", "content": prompt}],
            max_tokens=10,
            temperature=0
        )
        raw_intent = response.choices[0].message.content.strip()
        raw_intent = raw_intent.replace("intent:", "").replace("Intent:", "").strip().lower()

        mapped_intent = INTENT_MAPPING.get(raw_intent, raw_intent)
        print(f"🧭 GPT intent: {raw_intent} → Pipeline intent: {mapped_intent}")

        # ✅ Nếu intent hợp lệ → dùng
        if mapped_intent in VALID_INTENTS:
            print(f"🎯 Intent phát hiện cuối cùng: {mapped_intent}")
            return mapped_intent

        # 🔁 Nếu không xác định được rõ → giữ intent cũ nếu có
        if mapped_intent not in INTENT_MAPPING.values():
            if last_intent in INTENT_MAPPING:
                logger.info(f"🔁 Fallback giữ intent cũ → {last_intent}")
                return last_intent
            else:
                logger.warning("❓ Không detect được intent hợp lệ → Trả về 'general_chat'")
                return "general_chat"

        # ✅ Cuối cùng: return intent hợp lệ
        logger.info(f"🎯 Intent phát hiện cuối cùng: {mapped_intent}")
        return mapped_intent

    except Exception as e:
        logger.error(f"❌ Lỗi khi detect intent: {str(e)}")
        return "general_chat"


def get_sql_prompt_for_intent(intent: str) -> str:
    schema = get_combined_schema_for_intent(intent)
    return system_prompt_sql.replace("{schema}", schema)

# Tạo message hệ thống hoàn chỉnh dựa trên intent,
# kết hợp medical prompt và SQL prompt có chèn schema phù hợp.
def build_system_message(intent: str, symptoms: list[str] = None) -> dict:
    sql_part = get_sql_prompt_for_intent(intent).strip()
    medical_part = build_system_prompt(intent, symptoms).strip()

    full_content = f"{medical_part}\n\n{sql_part}"

    return {
        "role": "system",
        "content": full_content
    }

# Xác định để chuẩn đoán bệnh
async def should_trigger_diagnosis(user_message: str, collected_symptoms: list[dict], recent_messages: list[str] = []) -> bool:

    # ✅ Nếu có từ 2 triệu chứng → luôn trigger
    if len(collected_symptoms) >= 2:
        print("✅ Rule-based: đủ 2 triệu chứng → cho phép chẩn đoán")
        return True

    # 🧠 GPT fallback nếu không rõ
    context_text = "\n".join(f"- {msg}" for msg in recent_messages[-2:])

    prompt = f"""
        You are a careful medical assistant helping diagnose possible conditions based on user-reported symptoms.

        Has the user provided enough clear symptoms or context to proceed with a diagnosis?

        Answer only YES or NO.

        ---

        Symptoms reported: {[s['name'] for s in collected_symptoms]}
        Conversation context:
        {context_text}
        User (most recent): "{user_message}"

        → Answer:
        """.strip()

    try:
        response = chat_completion(
            [{"role": "user", "content": prompt}],
            max_tokens=5,
            temperature=0
        )
        result = response.choices[0].message.content.strip().lower()
        return result.startswith("yes")
    except Exception as e:
        print("❌ GPT fallback in should_trigger_diagnosis failed:", str(e))
        return False


async def generate_next_health_action(symptoms: list[dict], recent_messages: list[str]) -> dict:

    symptom_names = [s["name"] for s in symptoms]
    prompt = build_diagnosis_controller_prompt(symptom_names, recent_messages)

    try:
        response = chat_completion([{"role": "user", "content": prompt}], max_tokens=300, temperature=0.4)
        content = response.choices[0].message.content.strip()

        if content.startswith("```json"):
            content = content.replace("```json", "").replace("```", "").strip()
        return json.loads(content)
    except Exception as e:
        print("❌ Failed to generate next health action:", e)
        return {
            "trigger_diagnosis": False,
            "message": "Mình chưa chắc chắn lắm. Bạn có thể nói rõ hơn về các triệu chứng hiện tại không?"
        }

