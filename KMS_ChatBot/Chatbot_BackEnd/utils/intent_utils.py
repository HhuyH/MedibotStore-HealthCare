
import openai
import unidecode
import sys
import os

# Thêm đường dẫn thư mục cha vào sys.path
sys.path.append(os.path.abspath(os.path.join(os.path.dirname(__file__), '..')))
from prompts.db_schema.load_schema import user_core_schema, schema_modules
from prompts.prompts import build_system_prompt
from utils.symptom_session import get_symptoms_from_session
from config import MODEL

from prompts.prompts import system_prompt_sql
from utils.openai_client import chat_completion, chat_stream

def normalize_text(text: str) -> str:
    return unidecode.unidecode(text).lower().strip()

def is_confirmation(text):
        norm = normalize_text(text)
        return norm in {"dung roi", "uh", "um", "dung", "đúng rồi", "vâng", "phải", "ừ"}

def get_combined_schema_for_intent(intent: str) -> str:
    schema_parts = [user_core_schema]  # luôn load phần lõi
    intent = normalize_text(intent)  # chuẩn hóa không dấu, lowercase

    # Map nhóm từ khóa tương ứng với từng module
    keyword_map = {
        'user_profile': [
            'địa chỉ', 'họ tên', 'liên hệ', 'số điện thoại', 'email', 'khách', 'thông tin người dùng'
        ],
        'medical_history': [
            'disease', 'symptom', 'triệu chứng', 'bệnh', 'đau', 'sốt', 'mệt', 'khó thở',
            'chóng mặt', 'đau bụng', 'cảm giác', 'không khỏe', 'cảm thấy'
        ],
        'products': [
            'prescription', 'medication', 'thuốc', 'sản phẩm', 'còn hàng'
        ],
        'appointments': [
            'appointment', 'lịch hẹn', 'khám bệnh'
        ],
        'ai_prediction': [
            'ai', 'prediction', 'dự đoán', 'chatbot'
        ],
        'orders': [
            'order', 'payment', 'đơn hàng', 'thanh toán'
        ],
        'notifications': [
            'notification', 'thông báo'
        ],
        'services': [
            'service', 'gói khám', 'dịch vụ', 'gói'
        ],
    }

    keyword_map_norm = {
        k: [normalize_text(word) for word in v]
        for k, v in keyword_map.items()
    }

    extra_intent_map = {
        'prescription_products': [
            'prescription_products','Cho mình thông tin thuốc theo đơn...', 'Mình cần những lỗi thuốc nào...','thuốc theo đơn', 'loại thuốc nào', 'thuốc được kê', 'kê đơn', 'toa thuốc'
        ],
        'order_items_details' :[
            'order_items', 'order_details','cho mình thông tin chi tiết của sản phẩm...','sản phảm... sử dụng thế nào','chi tiết đơn hàng', 'sản phẩm trong đơn', 'sản phẩm đặt mua', 'hóa đơn', 'mua sản phẩm', 'sử dụng sản phẩm'
        ],
    }

    extra_intent_map_norm = {
        k: [normalize_text(word) for word in v]
        for k, v in extra_intent_map.items()
    }

    # Duyệt tất cả keyword theo module
    for module_name, keywords in keyword_map_norm.items():
        if any(kw in intent for kw in keywords):
            if module_name in schema_modules:
                if schema_modules[module_name] not in schema_parts:
                    schema_parts.append(schema_modules[module_name])

    # Bắt buộc thêm doctor_clinic nếu có lịch hẹn
    if any(kw in intent for kw in keyword_map_norm['appointments']):
        if schema_modules['doctor_clinic'] not in schema_parts:
            schema_parts.append(schema_modules['doctor_clinic'])
        if schema_modules['user_profile'] not in schema_parts:
            schema_parts.append(schema_modules['user_profile']) # liên quan đến user_id & guest_id

    # nếu người hỏi hỏi những loại thuốc nào đi kèm theo đơn thuốc thì sẽ gọi cả 2 products và prescription để lấy thông tin thuốc
    if any(kw in intent for kw in extra_intent_map_norm['prescription_products']):
        schema_parts.append(schema_modules['products'])
        schema_parts.append(schema_modules['appointments'])

    # lấy thông tin chi tiết của sản phẩm theo hóa đơn
    if any(kw in intent for kw in extra_intent_map_norm['order_items_details']):
        schema_parts.append(schema_modules['products'])
        schema_parts.append(schema_modules['orders'])

    # Xử lý đặc biệt theo tên bảng rõ ràng (table-level)
    if 'prediction_diseases' in intent:
        schema_parts.append(schema_modules['ai_prediction'])
        schema_parts.append(schema_modules['medical_history'])
        schema_parts.append(schema_modules['user_profile'])


    # Loại bỏ trùng lặp nếu có
    schema_parts = list(dict.fromkeys(schema_parts))

    return '\n'.join(schema_parts)

VALID_INTENTS = [
    "user_profile",
    "medical_history",
    "products",
    "appointments",
    "ai_prediction",
    "orders",
    "notifications",
    "services",
    "prescription_products",
    "order_items_details",
    "health_query",
    "general_chat",
    "product_query",
    "final_diagnosis"
]

INTENT_MAPPING = {
    # 🩺 Truy vấn liên quan đến sức khỏe / triệu chứng
    "medical_history": "symptom_query",
    "ai_prediction": "symptom_query",
    "appointments": "symptom_query",
    "prescription_products": "symptom_query",
    "health_query": "symptom_query",  # giữ lại alias gốc
    "final_diagnosis": "symptom_query",

    # 📦 Truy vấn sản phẩm, đơn hàng, hồ sơ
    "products": "product_query",
    "order_items_details": "product_query",
    "orders": "product_query",
    "user_profile": "user_query",  # Tách riêng user cho dễ hiểu
    "services": "product_query",

    # 💬 Trò chuyện chung / phản hồi phụ
    "notifications": "general_chat",
}


# Danh sách từ khóa nhân diện dạng intent

# Từ khóa liên quan đến vấn đề y tế
symptom_keywords = [
    "đau", "sốt", "ho", "khó thở", "nôn", "buồn nôn", "chóng mặt", "nhức đầu", 
    "tiêu chảy", "mệt", "khó chịu", "cảm", "ngứa", "phát ban", "đau họng", "hoa mắt", 
    "đầy bụng", "khó ngủ", "khó tiêu", "đau ngực", "chảy máu", "mất ngủ"
]
        
# Từ khóa liên quan đến người dùng → user_query
user_keywords = [
    "user_id", "id nguoi dung", "ten dang nhap", "tai khoan", "username",
    "email", "dia chi email", "dia chi mail", "so dien thoai", "sdt",
    "vai tro", "role", "id", "thong tin nguoi dung", "thong tin user",
    "lay thong tin", "lay du lieu", "nguoi dung la ai", "lay tai khoan",
    "lay danh sach nguoi dung", "hien thong tin nguoi dung",
    "thong tin ve user", "co bao nhieu user", "co bao nhieu nguoi dung",
    "liet ke nguoi dung"
]

# Từ khóa liên quan đến sản phẩm / đơn hàng / dịch vụ → product_query
product_keywords = [
    "don hang", "san pham", "dat mua", "gia tien", "thuoc", "toa thuoc",
    "hoa don", "dat lich", "goi kham", "dich vu", "goi", "ma don", "ten san pham",
    "xem san pham", "lich su mua", "chi tiet don hang", "thuoc nao", "ban duoc khong"
]

# Từ khóa kỹ thuật liên quan SQL → có thể dùng cho cả 2 hoặc tùy logic
sql_keywords = [
    "select", "query", "from", "where", "join", "limit"
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

        # Normal hóa message để check keyword
        normalized = normalize_text(user_message)

        # === RULE-BASED OVERRIDE ===
        if any(kw in normalized for kw in user_keywords):
            print("🔁 Override intent → 'user_query' do phát hiện keyword liên quan đến người dùng")
            return "user_query"

        if any(kw in normalized for kw in product_keywords):
            print("🔁 Override intent → 'product_query' do phát hiện keyword liên quan sản phẩm/dịch vụ")
            return "product_query"

        if any(kw in normalized for kw in sql_keywords):
            print("🔁 Override intent → 'product_query' do phát hiện keyword kỹ thuật SQL")
            return "product_query"

        # Tự động nhận biết nếu message chứa triệu chứng
        def contains_symptom_keywords(text: str) -> bool:
            norm_text = normalize_text(text)
            return any(kw in norm_text for kw in symptom_keywords)

        if not raw_intent or mapped_intent not in VALID_INTENTS:
            if contains_symptom_keywords(user_message):
                print("🩺 Override intent → 'symptom_query' do phát hiện triệu chứng trong câu")
                mapped_intent = "symptom_query"

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



