
import openai
import unidecode
import sys
import os
import asyncio

# Thêm đường dẫn thư mục cha vào sys.path
sys.path.append(os.path.abspath(os.path.join(os.path.dirname(__file__), '..')))
from prompts.db_schema.load_schema import user_core_schema, schema_modules
from prompts.prompts import build_system_prompt
from utils.symptom_utils import looks_like_followup_with_gpt, gpt_detect_symptom_intent, gpt_looks_like_symptom_followup_uncertain
from prompts.prompts import system_prompt_sql
from utils.openai_client import chat_completion
from utils.text_utils import normalize_text
from config.intents import VALID_INTENTS, INTENT_MAPPING

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
async def detect_intent(user_message: str, session_key: str = None, last_intent: str = None) -> str:
    prompt = (
        "Xác định intent chính của câu sau trong các loại:\n"
        + ", ".join(VALID_INTENTS) +
        f"\nCâu: {user_message}\nIntent:"
    )

    try:
        # Giữ lại intent nếu user xác nhận và đang hỏi về triệu chứng
        if is_confirmation(user_message) and last_intent == "symptom_query":
            print("🔁 User xác nhận triệu chứng → Giữ intent là 'symptom_query'")
            return "symptom_query"

        # Gọi GPT để phân loại intent
        response = chat_completion(
            [{"role": "user", "content": prompt}],
            max_tokens=10,
            temperature=0
        )
        raw_intent = response.choices[0].message.content.strip()
        raw_intent = raw_intent.replace("intent:", "").replace("Intent:", "").strip().lower()

        # Nếu GPT trả về format không hợp lệ
        if "intent chính của câu" in raw_intent:
            print("⚠️ GPT trả sai format → fallback xử lý theo rule-based")
            raw_intent = ""

        mapped_intent = INTENT_MAPPING.get(raw_intent, raw_intent)
        print(f"🧭 GPT intent: {raw_intent} → Pipeline intent: {mapped_intent}")

        # Nếu intent hợp lệ → trả luôn
        if mapped_intent in VALID_INTENTS:
            print(f"🎯 Intent phát hiện cuối cùng: {mapped_intent}")
            return mapped_intent

        # Nếu không rõ intent, kiểm tra câu có mô tả triệu chứng không
        if not raw_intent or mapped_intent not in VALID_INTENTS:
            # Case 1: Câu này giống mô tả triệu chứng
            if gpt_detect_symptom_intent(user_message):
                print("🩺 GPT nhận đây là mô tả triệu chứng mới → intent = 'symptom_query'")
                return "symptom_query"

            # Case 2: Nếu trước đó là symptom_query, kiểm tra xem đây có phải follow-up không
            if last_intent == "symptom_query":
                is_followup = await asyncio.to_thread(looks_like_followup_with_gpt, user_message)
                if is_followup:
                    print("🔁 GPT xác định đây là follow-up triệu chứng → giữ intent là 'symptom_query'")
                    return "symptom_query"

        # Nếu không có intent hợp lệ → fallback theo intent trước
        if mapped_intent not in INTENT_MAPPING.values():
            if last_intent in INTENT_MAPPING:
                print(f"🔁 Fallback giữ intent cũ → {last_intent}")
                return last_intent
            else:
                print("❓ Không detect được intent hợp lệ → Trả về 'general_chat'")
                return "general_chat"

        # Trường hợp đặc biệt: câu rất ngắn nhưng đang follow-up triệu chứng
        if last_intent == "symptom_query":
            if await asyncio.to_thread(gpt_looks_like_symptom_followup_uncertain, user_message):
                print("🤔 GPT xác định đây là câu trả lời mơ hồ tiếp tục chẩn đoán → giữ intent 'symptom_query'")
                return "symptom_query"

        # Trả về intent cuối cùng
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
async def should_trigger_diagnosis(user_message: str, collected_symptoms: list[dict]) -> bool:
    prompt = (
        "Bạn là trợ lý y tế. Hãy xác định người dùng đã mô tả xong triệu chứng chưa để chuyển sang bước chẩn đoán.\n"
        "Chỉ trả lời YES hoặc NO.\n\n"
        "Ví dụ:\n"
        "- Triệu chứng: ['Sốt', 'Ho']\n"
        "- Người dùng: 'hết rồi' → YES\n"
        "- Người dùng: 'không có gì thêm' → YES\n"
        "- Người dùng: 'còn đau bụng nữa' → NO\n"
        "- Người dùng: 'ờ để xem' → NO\n\n"
        f"Triệu chứng: {[s['name'] for s in collected_symptoms]}\n"
        f"Người dùng: \"{user_message}\"\n\n"
        "Trả lời: "
    )

    response = chat_completion(
        [{"role": "user", "content": prompt}],
        max_tokens=5,
        temperature=0
    )

    result = response.choices[0].message.content.strip().lower()
    return result.startswith("yes")



