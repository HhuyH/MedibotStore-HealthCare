
import openai
import unidecode
import sys
import os

# Thêm đường dẫn thư mục cha vào sys.path
sys.path.append(os.path.abspath(os.path.join(os.path.dirname(__file__), '..')))
from prompts.db_schema.load_schema import user_core_schema, schema_modules
from prompts.prompts import build_system_prompt

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

# Danh sách từ khóa nhân diện dạng intent
# Từ khóa liên quan đến vấn đề y tế
symptom_keywords = [
    "đau", "sốt", "ho", "khó thở", "nôn", "buồn nôn", "chóng mặt", "nhức đầu", 
    "tiêu chảy", "mệt", "khó chịu", "cảm", "ngứa", "phát ban", "đau họng", "hoa mắt", 
    "đầy bụng", "khó ngủ", "khó tiêu", "đau ngực", "chảy máu", "mất ngủ"
]
        
async def detect_intent(user_message: str, session_key: str = None, last_intent: str = None) -> str:
    prompt = (
        "Xác định intent chính của câu sau trong các loại:\n"
        + ", ".join(VALID_INTENTS) +
        f"\nCâu: {user_message}\nIntent:"
    )

    try:
        # Nếu user xác nhận và trước đó hỏi triệu chứng → giữ nguyên intent
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

        # Nếu GPT trả sai định dạng
        if "intent chính của câu" in raw_intent:
            print("⚠️ GPT trả sai format → fallback xử lý theo rule-based")
            raw_intent = ""

        mapped_intent = INTENT_MAPPING.get(raw_intent, raw_intent)
        print(f"🧭 GPT intent: {raw_intent} → Pipeline intent: {mapped_intent}")

        # ✅ Nếu intent hợp lệ → return luôn, không xét override nữa
        if mapped_intent in VALID_INTENTS:
            print(f"🎯 Intent phát hiện cuối cùng: {mapped_intent}")
            return mapped_intent

        # Tự động nhận biết nếu message chứa triệu chứng
        def gpt_detect_symptom_intent(user_message: str) -> bool:
            prompt = (
                "Hãy xác định xem câu sau có phải là người dùng đang mô tả triệu chứng sức khỏe không.\n"
                "Chỉ trả lời YES hoặc NO.\n\n"
                f"Câu: \"{user_message}\"\n"
                "Trả lời: "
            )
            response = chat_completion(
                [{"role": "user", "content": prompt}],
                max_tokens=5,
                temperature=0
            )
            result = response.choices[0].message.content.strip().lower()
            return result.startswith("yes")


        if not raw_intent or mapped_intent not in VALID_INTENTS:
            if gpt_detect_symptom_intent(user_message):
                if last_intent in [None, "general_chat", "unknown"]:
                    print("🩺 Override intent → 'symptom_query' do phát hiện triệu chứng trong câu")
                    return "symptom_query"

            
        # Fallback giữ lại intent cũ nếu mapped chưa hợp lệ
        if mapped_intent not in INTENT_MAPPING.values():
            if last_intent in INTENT_MAPPING:
                print(f"🔁 Fallback giữ intent cũ → {last_intent}")
                return last_intent
            else:
                print("❓ Không detect được intent hợp lệ → Trả về 'general_chat'")
                return "general_chat"
            
        if last_intent == "symptom_query" and len(user_message.strip().split()) <= 5:
            print("🔁 Câu trả lời ngắn và đang follow-up → giữ intent là 'symptom_query'")
            return "symptom_query"


        # Trả về intent cuối cùng sau xử lý
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



