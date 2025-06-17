
import openai
import unidecode
import sys
import os
import asyncio

# Thêm đường dẫn thư mục cha vào sys.path
sys.path.append(os.path.abspath(os.path.join(os.path.dirname(__file__), '..')))
from prompts.db_schema.load_schema import user_core_schema, schema_modules
from prompts.prompts import build_system_prompt
from utils.symptom_utils import gpt_detect_symptom_intent
from utils.health_care import gpt_looks_like_symptom_followup_uncertain, looks_like_followup_with_gpt
from prompts.prompts import system_prompt_sql, build_diagnosis_controller_prompt
from utils.openai_client import chat_completion
from utils.text_utils import normalize_text
from config.intents import VALID_INTENTS, INTENT_MAPPING
import json

def is_confirmation(text):
        norm = normalize_text(text)
        return norm in {"dung roi", "uh", "um", "dung", "đúng rồi", "vâng", "phải", "ừ"}

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
async def detect_intent(user_message: str, session_key: str = None, last_intent: str = None, recent_messages: list[str] = []) -> str:
    # Lấy câu trước (nếu có) để tạo context
    previous_msg = recent_messages[-1] if recent_messages else ""

    prompt = f"""
    Classify the user's intent in a chatbot conversation.

    Previous user intent: "{last_intent or 'unknown'}"
    Previous message: "{previous_msg}"
    Current message: "{user_message}"

    Valid intents: {", ".join(VALID_INTENTS)}

    Instructions:
    - If the previous intent was "symptom_query", and the user's current message is vague, uncertain, or negative (e.g. "không", "không rõ", "not sure", "no idea"), then assume they are still replying to a symptom-related follow-up — not starting a new topic.
    - Do NOT switch to "general_chat" too quickly unless it's clearly off-topic or small talk.
    - If the message sounds like a follow-up, continuation, or clarification — keep the same intent.
    - Only choose ONE valid intent. Do not explain your reasoning. Do not include extra words.
    """

    try:
        # ✅ Trường hợp xác nhận triệu chứng → giữ intent
        if is_confirmation(user_message) and last_intent == "symptom_query":
            print("🔁 User xác nhận triệu chứng → Giữ intent là 'symptom_query'")
            return "symptom_query"

        # 🧠 Gọi GPT để phân loại intent
        response = chat_completion(
            [{"role": "user", "content": prompt}],
            max_tokens=10,
            temperature=0
        )
        raw_intent = response.choices[0].message.content.strip()
        raw_intent = raw_intent.replace("intent:", "").replace("Intent:", "").strip().lower()

        # Nếu GPT trả sai format
        if "intent chính của câu" in raw_intent:
            print("⚠️ GPT trả sai format → fallback xử lý theo rule-based")
            raw_intent = ""

        mapped_intent = INTENT_MAPPING.get(raw_intent, raw_intent)
        print(f"🧭 GPT intent: {raw_intent} → Pipeline intent: {mapped_intent}")

        # ✅ Nếu câu là phủ định trong luồng health → vẫn giữ 'symptom_query'
        lower_msg = user_message.lower().strip()
        negation_phrases = ["không", "không có", "ko", "ko có", "k có", "không rõ", "không biết", "k rõ", "k biết", "k bít"]
        if last_intent == "symptom_query" and any(p in lower_msg for p in negation_phrases):
            print("🔁 Người dùng phủ định trong luồng symptom → giữ intent 'symptom_query'")
            return "symptom_query"

        # ✅ Nếu GPT trả 'general_chat' nhưng trước là symptom → kiểm tra lại
        if mapped_intent == "general_chat" and last_intent == "symptom_query":
            is_followup = await asyncio.to_thread(looks_like_followup_with_gpt, user_message, previous_msg)
            is_uncertain = await asyncio.to_thread(gpt_looks_like_symptom_followup_uncertain, user_message)

            if is_followup:
                print("🔁 GPT xác định đây là follow-up triệu chứng → giữ intent 'symptom_query'")
                return "symptom_query"

            if is_uncertain:
                print("🤔 GPT xác định đây là câu trả lời mơ hồ tiếp tục chẩn đoán → giữ intent 'symptom_query'")
                return "symptom_query"

            print("⛔️ GPT cho rằng đây là general_chat, và không phải follow-up → giữ 'general_chat'")

        # ✅ Nếu intent hợp lệ → dùng
        if mapped_intent in VALID_INTENTS:
            print(f"🎯 Intent phát hiện cuối cùng: {mapped_intent}")
            return mapped_intent

        # ❓ Nếu không rõ intent → fallback
        if not raw_intent or mapped_intent not in VALID_INTENTS:
            if gpt_detect_symptom_intent(user_message):
                print("🩺 GPT nhận đây là mô tả triệu chứng mới → intent = 'symptom_query'")
                return "symptom_query"

            if last_intent == "symptom_query":
                is_followup = await asyncio.to_thread(looks_like_followup_with_gpt, user_message, previous_msg)
                is_uncertain = await asyncio.to_thread(gpt_looks_like_symptom_followup_uncertain, user_message)

                if is_followup:
                    print("🔁 GPT xác định đây là follow-up triệu chứng → giữ intent 'symptom_query'")
                    return "symptom_query"

                if is_uncertain:
                    print("🤔 GPT xác định đây là câu trả lời mơ hồ tiếp tục chẩn đoán → giữ intent 'symptom_query'")
                    return "symptom_query"

        # 🔁 Nếu không xác định được rõ → giữ intent cũ nếu có
        if mapped_intent not in INTENT_MAPPING.values():
            if last_intent in INTENT_MAPPING:
                print(f"🔁 Fallback giữ intent cũ → {last_intent}")
                return last_intent
            else:
                print("❓ Không detect được intent hợp lệ → Trả về 'general_chat'")
                return "general_chat"

        # ✅ Cuối cùng: return intent hợp lệ
        print(f"🎯 Intent phát hiện cuối cùng: {mapped_intent}")
        return mapped_intent

    except Exception as e:
        print("❌ Lỗi khi detect intent:", str(e))
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

