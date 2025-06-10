
import openai
from unidecode import unidecode
import sys
import os

# Thêm đường dẫn thư mục cha vào sys.path
sys.path.append(os.path.abspath(os.path.join(os.path.dirname(__file__), '..')))
from .db_schema.load_schema import user_core_schema, schema_modules
from prompts.prompts import build_system_prompt

from config import MODEL

from prompts.prompts import system_prompt_sql
from utils.openai_client import chat_completion, chat_stream

def remove_accents(text: str) -> str:
    return unidecode(text).lower()

def get_combined_schema_for_intent(intent: str) -> str:
    schema_parts = [user_core_schema]  # luôn load phần lõi
    intent = remove_accents(intent)  # chuẩn hóa không dấu, lowercase

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
        k: [remove_accents(word) for word in v]
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
        k: [remove_accents(word) for word in v]
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
    "product_query"
]

INTENT_MAPPING = {
    "medical_history": "health_query",
    "ai_prediction": "health_query",
    "appointments": "health_query",
    "prescription_products": "health_query",
    "health_query": "health_query",

    "products": "product_query",
    "order_items_details": "product_query",
    "orders": "product_query",
    "user_profile": "product_query",
    "services": "product_query",

    "notifications": "general_chat",
}


def detect_intent(user_message: str) -> str:
    prompt = (
        "Xác định intent chính của câu sau trong các loại:\n"
        + ", ".join(VALID_INTENTS) +
        f"\nCâu: {user_message}\nIntent:"
    )

    try:
        response = chat_completion(
            [{"role": "user", "content": prompt}],
            max_tokens=10,
            temperature=0
        )
        raw_intent = response.choices[0].message.content.strip().lower()
        mapped_intent = INTENT_MAPPING.get(raw_intent, raw_intent)
        print(f"🧭 GPT intent: {raw_intent} → Pipeline intent: {mapped_intent}")

        # ✅ Rule override bằng keyword nếu có nghi ngờ là SQL/user info
        normalized = remove_accents(user_message)

        sql_like_keywords = [
            "select", "user_id", "ten dang nhap", "email", "vai tro", "dia chi email", "thong tin nguoi dung",
            "lay thong tin", "id nguoi dung", "query", "from users", "thong tin user"
        ]

        if any(kw in normalized for kw in sql_like_keywords):
            print("🔁 Override intent → 'product_query' do phát hiện keyword liên quan SQL/user_info")
            return "product_query"

        return mapped_intent

    except Exception as e:
        print("❌ Lỗi khi detect intent:", str(e))
        return "general_chat"

def get_sql_prompt_for_intent(intent: str) -> str:
    schema = get_combined_schema_for_intent(intent)
    return system_prompt_sql.replace("{schema}", schema)

def build_system_message(intent: str, symptoms: list[str] = None) -> dict:
    """
    Tạo message hệ thống hoàn chỉnh dựa trên intent,
    kết hợp medical prompt và SQL prompt có chèn schema phù hợp.
    """
    sql_part = get_sql_prompt_for_intent(intent).strip()
    medical_part = build_system_prompt(intent, symptoms).strip()

    full_content = f"{medical_part}\n\n{sql_part}"

    return {
        "role": "system",
        "content": full_content
    }

