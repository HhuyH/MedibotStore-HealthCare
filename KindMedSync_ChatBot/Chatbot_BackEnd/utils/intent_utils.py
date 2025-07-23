
import sys
import os
import logging
import re
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
from unidecode import unidecode

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

def normalize(text: str) -> str:
    return unidecode(text.lower())


# Phạt hiện đang là sử dụng chức nắng nào là chat bình thường hay là phát hiện và dự đoán bệnh
async def detect_intent(
    last_intent: str = None,
    recent_user_messages: list[str] = [],
    recent_assistant_messages: list[str] = [],
    diagnosed_today: bool = False,
    stored_symptoms: list[str] = [],
    should_suggest_product: bool = False,
) -> str:
    # Sử dụng trực tiếp message đã tách
    last_bot_msg = recent_assistant_messages[-1] if recent_assistant_messages else ""
    last_user_msg = recent_user_messages[-1] if recent_user_messages else ""
    diagnosed_today_flag = "True" if diagnosed_today else "False"
    # logger.info(f"[Intent Debug] Recent User: {last_user_msg}")
    # logger.info(f"[Intent Debug] Recent Bot: {last_bot_msg}")

    prompt = f"""
        You are a medical assistant bot that classifies user intents based on their messages.

        Last detected intent: "{last_intent or 'unknown'}"
        
        Previous bot message:
        "{last_bot_msg}"

        Current user message:  
        "{last_user_msg}"

        Valid intents: {", ".join(VALID_INTENTS)}

        Diagnosed today: {diagnosed_today_flag}

        Previously stored symptoms: {", ".join(stored_symptoms) if stored_symptoms else "None"}

        ----------------------------
        🛡️ CONTEXTUAL OVERRIDE RULES (high priority)

        DO NOT change the intent in the following cases:

        1. If `last_intent` == `booking_request`, and the user's message:

        - Provides a **name** (e.g., "Tôi tên là An")
        - Mentions a **person** (e.g., "Bác sĩ Minh")
        - Includes a **phone number** (e.g., "0901234567")
        - Contains a **location or address** (e.g., "TPHCM", "Quận 1", "ở đường X")
        - Specifies a **date or time** (e.g., "ngày mai", "10h sáng", "Thứ 3")
        - Mentions **a symptom** (e.g., "đau đầu", "sốt", "khó thở")
        - Asks to **view doctor suggestions** (e.g., "cho mình xem danh sách bác sĩ", "có bác sĩ nào không", "gợi ý bác sĩ", "xem bác sĩ", "bác sĩ nào khám tim")
        - Mentions **a medical specialty** or **a type of appointment** (e.g., "khám tim mạch", "khám da liễu", "khám nội tiết", "mình muốn khám tổng quát")

        → Then:
        - ❗ DO NOT classify as `user_profile`, `sql_query`, or `general`.
        - ✅ **Always preserve intent as `booking_request`**, even if the message overlaps with other categories (e.g., symptoms, location, time).


        2. If the `last_bot_msg` contains confirmation questions like:
        - "Bạn xác nhận đặt lịch này chứ"
        - "Bạn có muốn xác nhận không"
        - "Tôi sẽ đặt lịch khám như sau, bạn đồng ý chứ?"

        → Then:
        - Any short affirmative reply like "ok", "được", "đồng ý", "xác nhận", "yes", "chốt", "đặt luôn"
        **must be interpreted as confirmation**, and intent **must remain** as `booking_request`.

        🚫 NEVER change to `user_profile`, `general`, or `sql_query` in such cases.

        ⚠️ If uncertain or ambiguous, default to previous intent and do NOT switch context.



        🚫 INTENT GUARDRAIL: DO NOT MISCLASSIFY

        If the user's previous interaction involves a booking flow 
        (e.g., the assistant just asked about symptoms, specialty, clinic, full name, phone, or location),
        → Then: Any simple reply such as a name, a phone number (e.g., "0901xxxxxx"), or a location (e.g., "TPHCM", "Quận 1") 
        **MUST be treated as part of the current booking conversation.**

        ❌ Absolutely FORBIDDEN to return the following intents in such cases:
        - `user_profile`
        - `sql_query`
        - `general`

        → These intents are NEVER valid unless the user explicitly says something like:
            - "Tôi muốn cập nhật thông tin cá nhân"
            - "Chạy truy vấn SQL"
            - "Tôi muốn xem hồ sơ của tôi"
            - "Tôi có câu hỏi khác"
            - "Lấy danh sách..."

        ✅ If the message is ambiguous, short, or just contains a number or location:
        → Always assume it's a follow-up to the assistant's last question.
        → Default to keeping the intent as `booking_request` if `last_intent` is `booking_request` or the previous `last_intent`.

        ⚠️ Remember: misclassifying a booking reply as another intent may **break the flow** and lead to user confusion or data loss.


        ---> INSTRUCTION: <---

        First, analyze what kind of information the assistant was trying to elicit from the user in its last message, based on the combination of:

        - `last_bot_msg` → what the assistant said last
        - `last_intent` → what the current dialogue is about (e.g., booking, symptom_query, etc.)

        Infer the **type of user reply expected**, such as:
            - location
            - symptom details (time, severity, context)
            - confirmation
            - product interest
            - appointment type
            - general agreement

        Then compare the actual user reply (`last_user_msg`) to see if it fits that expected type.

        → If it matches the expected type, and the topic has not changed, KEEP the `last_intent`.

        Before classifying the current user message, always consider what kind of information the assistant was asking for in the `last_bot_msg`.

        If the assistant's last message is a **follow-up request for information** to continue the current intent (e.g., asking for location, time, confirmation, symptom details, etc.), and the user's message provides the requested information (even vaguely):

        → Then KEEP the current `last_intent`. Do NOT classify as a new intent.

        - If "{should_suggest_product}" = true`, then classify as `"suggest_product"`.

        - If should_suggest_product = false:
            - If the message sounds like a general wellness request (e.g., “có cách nào cải thiện?”, “làm sao để đỡ hơn?”, “ăn gì tốt cho da?”), classify as "health_advice"
            - If the message explicitly asks for product suggestions (e.g., “có sản phẩm nào?”, “cho mình xem sản phẩm”, “thuốc nào hỗ trợ?”), classify as "suggest_product"
            
        - If the message is a data/admin request like “lấy danh sách sản phẩm”, “xem toàn bộ thuốc”, “liệt kê các gói dịch vụ” → classify as `"sql_query"`- If `should_suggest_product = false` but the user message sounds like they are asking for help with products (e.g., “có thuốc nào không?”, “cho mình xem thử sản phẩm hỗ trợ”, “gợi ý sản phẩm giúp mình với”), then also classify as `"suggest_product"`.

        - Typical phrases that may indicate product interest include:
            • “cho mình xem thử”
            • “có thuốc nào không”
            • “gợi ý sản phẩm”
            • “có sản phẩm nào”
            • “giúp mình với”
            • “giảm triệu chứng”
            • “hỗ trợ điều trị”
            • “có gì làm đỡ hơn không”

        - If the last intent was "symptom_query" and the user's current message clearly answers a previous follow-up (e.g., gives timing, severity, or symptom detail), then KEEP "symptom_query".
        - If the user is asking for general advice on how to deal with a symptom (e.g., how to sleep better, what to eat for energy), or wants wellness guidance (e.g., chăm sóc sức khỏe, tăng sức đề kháng), classify as "health_advice".
        - Use "symptom_query" if the user is describing a health symptom — even casually or in a vague way — such as “mình bị đau đầu quá”, “cảm thấy chóng mặt”, “đau nhức khắp người”.
        - If the message contains common symptom phrases like “mình cảm thấy đau đầu”, “bị chóng mặt quá”, “mình nhức mỏi lắm”, “mình đau bụng quá” — even if not phrased formally — classify as "health_query"
        - Use "general_chat" if the message is unrelated small talk, jokes, greetings, or off-topic.
        - If unsure, prefer to keep the previous intent (if valid).
        - If the user message sounds like a **data query or admin command** (e.g., "lấy danh sách người dùng", "xem danh sách đơn hàng", "tìm bệnh nhân"), then classify as `"sql_query"` (or appropriate admin intent).
        - If the user is asking to view a patient's health data (e.g., “xem thông tin bệnh nhân”, “hồ sơ bệnh nhân”, “tình trạng bệnh nhân”, “tình hình của bệnh nhân”, “cho tôi xem bệnh nhân tên...”) → classify as "patient_summary_request"
        - If the user is asking for a specific patient's health data or status, classify as "patient_summary_request".
        - Only use `"general_chat"` if the user is making small talk, asking about the bot, or saying unrelated casual things.
        - Do NOT misclassify structured or technical requests as casual chat.
        - If unsure, prefer a more specific intent over `"general_chat"`.
        - If the previous assistant message was a follow-up question about a symptom, and the user replies with:
            • vague timing like “tầm 5–10 phút”, “khoảng sáng nay”, “chắc tầm chiều qua”  
            • contextual clues like “lúc hoạt động nhiều”, “khi nằm”, “vào buổi sáng”, “sau khi đứng dậy”  
            • short confirmations like “đúng rồi”, “cũng có thể”, “hình như vậy”, “ờ ha”  
        → KEEP "symptom_query"
        - If the previous assistant message was a symptom-related follow-up, and the user replies vaguely or uncertainly (e.g. “mình không rõ”, “khó nói”, “cũng không chắc”, “chắc vậy”, “mình cũng không biết”) → KEEP "symptom_query"
        - If the user's message sounds like a guess or personal explanation for a symptom (e.g., “chắc là do...”, “có lẽ vì...”, “hôm nay mình chưa ăn gì nên...”):
            • If diagnosed_today = True → KEEP "symptom_query"
            • If diagnosed_today = False AND symptom is in stored_symptoms → STILL KEEP "symptom_query"
            • Otherwise → treat as a vague continuation, not "health_advice"
        - If the user's message is short and dismissive like “không có”, “hết rồi”, “chỉ vậy thôi”, “không thêm gì nữa”, and it follows a bot's symptom-related question → KEEP "symptom_query"
        
        - If the user message contains phrases like “chưa ăn gì”, “vừa đứng lên”, “mới ngủ dậy”, “buổi sáng”, and the previous assistant message was a symptom-related question (e.g., “khi nào bạn thấy chóng mặt?”), then the user is likely providing context for their symptom.

        → In this case, KEEP "symptom_query". Do NOT classify as "general_chat" even if the user message is short.

        - If the user appears to be replying directly to the assistant's previous question (check `last_bot_msg`), especially about timing, context, or possible cause — then KEEP the previous intent unless there's a clear topic change.
        
        - If the previous assistant message offers multiple types of support (e.g., both suggesting health products and offering to help book a medical appointment),
        and the user's reply is vague, short, or ambiguous (e.g., general confirmations, non-specific agreement, or unclear intent),
        → classify as "general_chat", so the assistant can ask a follow-up question to clarify what the user needs help with.

        - If the user message contains intent to **book a medical appointment**, such as:
            • “cho mình đặt lịch khám”
            • “muốn gặp bác sĩ”
            • “đặt lịch khám với bác sĩ”
            • “có lịch khám không”
            • “tư vấn giúp mình đặt lịch”
            • “mình muốn đi khám”
            • “muốn đặt khám chỗ nào gần”
            • “mình cần đặt lịch khám tổng quát”
            • “tư vấn bác sĩ để mình đi khám”
        → classify as `"booking"`

        - Chỉ phân loại là `"booking"` nếu người dùng **thể hiện rõ mong muốn được đặt lịch khám bệnh**, không chỉ đơn thuần hỏi tư vấn triệu chứng.


        Always return only ONE valid intent from the list.
        Do NOT explain your reasoning.
        Do NOT include any other words — only return the intent.

        Examples:
        - Diagnosed today = True
          User: "à hình như mình hiểu tại sao mình cảm thấy chống mặt rồi" → ✅ → intent = `symptom_query`
          User: "chắc là do hôm qua mình ăn linh tinh" → ✅ → intent = `symptom_query`
          User: "giờ mình mới nhớ ra, hôm qua bị trúng mưa" → ✅ → intent = `symptom_query`
          User: "Giờ mình mới nhớ là sáng giờ chưa ăn gì, chắc vậy mà chóng mặt" → ✅ if "Chóng mặt" is in stored_symptoms → intent = "symptom_query"
          User: "Chắc do hôm qua mệt nên vậy" → ✅ if "Mệt" was previously mentioned → intent = "symptom_query"

        - User: “lấy danh sách sản phẩm” → ✅ → intent = `sql_query`

          
        - Bot: “Bạn thấy tê tay bắt đầu từ lúc nào?”  
          User: “nó tự nhiên xuất hiện thôi” → ✅ → intent = `symptom_query`

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

        - User: “Cho tôi xem bệnh nhân tên Nguyễn Anh Tuấn”
            → intent = `patient_summary_request`
            - Bot: “Bạn muốn xem thông tin bệnh nhân Nguyễn Anh Tuấn vào ngày nào?”
            - User: “ngày 25/3”
            → ✅ → intent = `patient_summary_request`

        - should_suggest_product = true  
        User: “Cho mình xem thử sản phẩm hỗ trợ nha”  
        → ✅ → intent = `suggest_product`

        - should_suggest_product = true  
        User: “Bạn có thể gợi ý gì giúp giảm đau họng không?”  
        → ✅ → intent = `suggest_product`

        - should_suggest_product = false  
        User: “Có thuốc nào giảm khàn tiếng không?”  
        → ✅ → intent = `suggest_product`

        - should_suggest_product = false  
        User: “Cho em xem sản phẩm nào giúp dịu cổ họng nha”  
        → ✅ → intent = `suggest_product`

        - should_suggest_product = false  
        User: “Bữa giờ mình ho nhiều quá, có sản phẩm nào giúp dễ chịu hơn không?”  
        → ✅ → intent = `suggest_product`

        - should_suggest_product = false  
        User: “Bạn có thể gợi ý sản phẩm nào giúp giảm đau họng nhẹ không?”  
        → ✅ → intent = `suggest_product`

        - should_suggest_product = false  
        User: “Mình bị khàn tiếng mấy hôm nay, có loại nào giúp giọng đỡ hơn không?”  
        → ✅ → intent = `suggest_product`

        - last_intent = "booking"
        last_bot_msg = "Bạn muốn tìm phòng khám ở khu vực nào?"
        user message = "mình sống ở TPHCM"
        → ✅ intent = 'booking_request'


        → What is the current intent?
    """

    try:
        # 🧠 Gọi GPT để phân loại intent
        response = chat_completion(
            [{"role": "user", "content": prompt}],
            max_tokens=10,
            temperature=0
        )

        raw = response.choices[0].message.content.strip().lower()
        match = re.search(r"(?:intent:)?\s*([\w_]+)", raw)
        raw_intent = match.group(1).strip() if match else raw

        mapped_intent = INTENT_MAPPING.get(raw_intent, raw_intent)
        logger.info(f"🧭 GPT intent: {raw_intent} → Pipeline intent: {mapped_intent}")

        # ✅ Nếu intent hợp lệ → dùng
        if mapped_intent in VALID_INTENTS:
            return mapped_intent

        # 🔁 Nếu không xác định được rõ → giữ intent cũ nếu có
        if mapped_intent not in INTENT_MAPPING.values():
            if last_intent in INTENT_MAPPING:
                logger.info(f"🔁 Fallback giữ intent cũ → {last_intent}")
                return last_intent
            else:
                logger.warning("❓ Không detect được intent hợp lệ → Trả về 'general_chat'")
                return "general_chat"

        LOCATION_QUESTION_KEYWORDS = [
            "khu vuc",
            "dia chi",
            "ban o dau",
            "noi ban song",
            "tim phong kham gan ban",
            "muon kham o dau",
            "khu vuc nao",
            "tim gan ban",
            "dia diem ban muon",
            "o dau",
        ]

        TYPICAL_LOCATIONS_NO_ACCENT = [
            "tphcm", "sai gon", "quan", "ha noi", "da nang", "binh thanh",
            "go vap", "tan binh", "thu duc", "cau giay", "quan 1", "quan 2",
            "minh o","minh song o", "minh gan", "minh song o tphcm"
        ]

        def looks_like_location(msg: str) -> bool:
            msg = normalize(msg)
            return any(loc in msg for loc in TYPICAL_LOCATIONS_NO_ACCENT)
    
        def bot_is_asking_for_location(bot_msg: str) -> bool:
            msg = normalize(bot_msg)
            return any(kw in msg for kw in LOCATION_QUESTION_KEYWORDS)

        if last_intent == "booking_request" and bot_is_asking_for_location(last_bot_msg) and looks_like_location(last_user_msg):
            # logger.info("📍 Detected location reply in booking context → Force intent = 'booking'")
            return "booking"

        # ✅ Cuối cùng: return intent hợp lệ
        return mapped_intent

    except Exception as e:
        logger.error(f"❌ Lỗi khi detect intent: {str(e)}")
        return "general_chat"

def get_sql_prompt_for_intent(intent: str) -> str:
    schema = get_combined_schema_for_intent(intent)
    return system_prompt_sql.replace("{schema}", schema)

# Tạo message hệ thống hoàn chỉnh dựa trên intent,
# kết hợp medical prompt và SQL prompt có chèn schema phù hợp.
def build_system_message(
    intent: str,
    symptoms: list[str] = None,
    recent_user_messages: list[str] = None,
    recent_assistant_messages: list[str] = None,
    fallback_reason: str = None
) -> dict:
    sql_part = get_sql_prompt_for_intent(intent).strip()

    # ✅ Đổi tên cho đúng ngữ nghĩa
    system_part = build_system_prompt(
        intent,
        recent_user_messages=recent_user_messages,
        recent_assistant_messages=recent_assistant_messages,
        fallback_reason=fallback_reason
    ).strip()


    full_content = f"{system_part}\n\n{sql_part}"

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

