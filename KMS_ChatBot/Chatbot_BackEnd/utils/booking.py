# utils/booking_handler.py

from utils.session_store import get_session_data, save_session_data
import pymysql
from config.config import DB_CONFIG
import logging
logger = logging.getLogger(__name__)
import json
from utils.openai_utils import chat_completion, stream_gpt_tokens
import asyncio

def extract_json(text: str) -> str:
    """
    Trích JSON đầu tiên hợp lệ từ text đầu ra của GPT.
    """
    start = text.find('{')
    while start != -1:
        for end in range(len(text) - 1, start, -1):
            try:
                candidate = text[start:end + 1]
                json.loads(candidate)
                return candidate
            except json.JSONDecodeError:
                continue
        start = text.find('{', start + 1)
    return '{}'


async def booking_appointment(
    user_message: str,
    recent_messages: list[str],
    recent_user_messages: list[str], 
    recent_assistant_messages: list[str],
    session_id=None,
    user_id=None,
):  
    # B1 Kiểm tra xem hôm này người dùng có phỏng đoán bệnh gì không
    prediction_today_details = get_today_prediction(user_id)
    
    # B2: Kiểm tra xem thông tin chi tiết dùng để đặt lịch có thiếu gì không
    info = await check_missing_booking_info(user_id=user_id, session_id=session_id)
    missing_fields = [k for k in ["full_name", "phone", "location"] if not info.get(k)]

    # B3: Gọi prompt tổng
    prompt = booking_prompt(
        recent_user_messages,
        recent_assistant_messages,
        missing_fields,
        prediction_today_details,
    )

    completion = chat_completion(messages=[{"role": "user", "content": prompt}], temperature=0.7)

    content = completion.choices[0].message.content.strip()
    # logger.info("🔎 Raw content từ GPT:\n%s", content)

    raw_json = extract_json(content)

    try:
        parsed = json.loads(raw_json)
        # logger.info("🧾 JSON từ GPT:\n%s", json.dumps(parsed, indent=2, ensure_ascii=False))
    except json.JSONDecodeError as e:
        logger.warning("⚠️ GPT trả về không phải JSON hợp lệ: %s", str(e))
        parsed = {}


    # Nếu đã đủ thông tin thì xử lý tiếp
    if parsed.get("status") == "complete":
        info = parsed.get("extracted_info", {})
        specialty = info.get("specialty")
        location = info.get("location")
        
        if not specialty:
            # Nếu không có chẩn đoán hôm nay và không có specialty → không thể gợi ý
            if not prediction_today_details:
                message = "Bạn muốn khám chuyên khoa nào nhen? Ví dụ như Nội tổng quát, Tai mũi họng, hoặc Da liễu nha."
                for chunk in stream_gpt_tokens(message):
                    yield chunk
                    await asyncio.sleep(0.065)
                return
        else:
            specialties = [specialty]

        clinics = get_clinics(location, specialties)

        if not clinics:
            message = f"Hiện tại mình chưa tìm thấy phòng khám phù hợp ở khu vực {location} cho chuyên khoa {specialty}. Bạn có muốn thử khu vực khác không nè?"
        else:
            # Gợi ý clinic theo danh sách
            lines = [f"{c['name']} - {c['address']}" for c in clinics]
            suggestion = "\n".join([f"- {line}" for line in lines])
            message = f"Mình tìm được vài phòng khám phù hợp nè:\n{suggestion}\n\nBạn muốn đặt ở đâu để mình xem lịch nha?"

        # Lưu lại context
        session = await get_session_data(user_id=user_id, session_id=session_id)
        session["booking_context"] = info
        await save_session_data(user_id=user_id, session_id=session_id, data=session)

        for chunk in stream_gpt_tokens(message):
            yield chunk
            await asyncio.sleep(0.065)
        return

# Kiểm tra thông tin con thiếu khi đặt lịch
async def check_missing_booking_info(user_id: int = None, session_id: str = None) -> dict:
    session = await get_session_data(user_id=user_id, session_id=session_id)
    context = session.get("booking_context", {})

    info = {
        "full_name": context.get("full_name") or session.get("full_name"),
        "phone": context.get("phone") or session.get("phone"),
        "location": context.get("location") or session.get("location"),
    }

    if user_id:
        conn = pymysql.connect(**DB_CONFIG)
        try:
            with conn.cursor() as cursor:
                cursor.execute("""
                    SELECT full_name, phone
                    FROM users_info
                    WHERE user_id = %s
                    LIMIT 1
                """, (user_id,))
                row = cursor.fetchone()
                if row:
                    info["full_name"] = info["full_name"] or row[0]
                    info["phone"] = info["phone"] or row[1]
        finally:
            conn.close()

    return info

def booking_prompt(
    recent_user_messages: list[str],
    recent_assistant_messages: list[str],
    missing_fields: list[str],
    prediction_today_details: str,
    schedules: str,
    all_specialty_names: list[str],
) -> str:
    last_bot_msg = recent_assistant_messages[-1] if recent_assistant_messages else ""
    last_user_msg = recent_user_messages[-1] if recent_user_messages else ""

    specialties_str = ", ".join(f'"{s}"' for s in all_specialty_names)

    prompt = f"""
        You are a smart assistant helping users schedule medical appointments in Vietnam.

        ### 🧠 Context:
        - Latest bot message: "{last_bot_msg}"
        - Latest user message: "{last_user_msg}"
        - Fields still missing: "{missing_fields}"
        - Health prediction today: "{prediction_today_details}"
        - Available schedules: "{schedules}"
        - List of valid specialties: [{specialties_str}]

        1. If prediction_today_details is empty:
        → Politely ask the user what kind of health issue or appointment they want to book.
        → When the user responds, extract the medical 'specialty' from their message.
        → The 'specialty' must match one of: [{specialties_str}]

        2. If prediction_today_details is available:
        → Determine the medical 'specialty' based on the diseases mentioned.
        → The 'specialty' must match one of: [{specialties_str}]


        3. If any of {missing_fields} is still missing (full_name, phone, location):
        → Ask the user in a friendly Vietnamese message to provide that info.
        → Try to extract those fields from user messages if possible.

        4. Once you have both 'specialty' and no missing_fields:
        → Check if user provided clinic name. If not, set "request_clinic": true.

        5. When clinic is known:
        → Check if the user mentioned an appointment time matching any available schedule.
        → If multiple doctors match that time, ask user to choose.
        → If only one doctor matches, set "appointment_id" accordingly.

        6. If all info is complete, politely confirm the booking.

        7. If user clearly confirms, set `"status": "confirmed"` and `"should_insert": true`.

        ### 📦 Output format (MUST be JSON):
        {{
            "status": "incomplete" | "complete" | "confirmed",
            "missing_fields": [...],                ← list of missing fields
            "request_clinic": true | false,
            "request_appointment_time": true | false,
            "extracted_info": {{
                "full_name": "...",
                "phone": "...",
                "location": "...",
                "specialty": "...",
                "clinic": "...",
                "appointment_id": "..."     ← from the matched schedule
            }},
            "message": "Câu trả lời thân thiện bằng tiếng Việt",
            "should_insert": true | false
        }}

        ⚠️ Output only valid JSON — no explanations or markdown.
        """.strip()
    return prompt

# lấy dự đoán bệnh hôm nay của người dùng
def get_today_prediction(user_id: int) -> dict | None:
    conn = pymysql.connect(**DB_CONFIG)
    try:
        with conn.cursor() as cursor:
            cursor.execute("""
                SELECT details
                FROM health_predictions
                WHERE user_id = %s AND DATE(prediction_date) = CURRENT_DATE
                ORDER BY prediction_date ASC
                LIMIT 1
            """, (user_id,))
            row = cursor.fetchone()
            if not row:
                return None
            try:
                return json.loads(row[0]) if row[0] else {}
            except Exception:
                return None
    finally:
        conn.close()

# Tìm danh sách phòng khám có các chuyên khoa tương ứng và (nếu có) nằm gần khu vực người dùng.
# Ưu tiên lọc theo tên quận, thành phố, tên đường có trong địa chỉ.
def get_clinics(location: str, specialties: list[str]) -> list[dict]:
    if not specialties:
        return []

    like_location = f"%{location.strip()}%" if location else "%"

    conn = pymysql.connect(**DB_CONFIG)
    try:
        with conn.cursor() as cursor:
            format_str = ",".join(["%s"] * len(specialties))
            sql = f"""
                SELECT DISTINCT c.clinic_id, c.name, c.address
                FROM clinics c
                JOIN clinic_specialties cs ON c.clinic_id = cs.clinic_id
                JOIN specialties s ON cs.specialty_id = s.specialty_id
                WHERE s.name IN ({format_str})
                  AND c.address LIKE %s
                ORDER BY c.name
                LIMIT 5
            """
            params = specialties + [like_location]
            cursor.execute(sql, params)

            return [{"id": row[0], "name": row[1], "address": row[2]} for row in cursor.fetchall()]
    finally:
        conn.close()

# Truy xuất tất cả tên chuyên ngành y tế (specialty) từ bảng specialties.
def get_all_specialty_names() -> list[str]:
    """
    Truy xuất tất cả tên chuyên ngành y tế (specialty) từ bảng specialties.
    Trả về danh sách các chuỗi tên.
    """
    conn = pymysql.connect(**DB_CONFIG)
    try:
        with conn.cursor() as cursor:
            cursor.execute("SELECT name FROM specialties ORDER BY name ASC")
            return [row[0] for row in cursor.fetchall()]
    finally:
        conn.close()

import pymysql
from config.config import DB_CONFIG

def get_doctors_by_clinic(clinic_id: int) -> list[dict]:
    """
    Lấy danh sách bác sĩ đang làm việc tại một phòng khám cụ thể,
    kèm tên đầy đủ, chuyên khoa và lịch làm việc.

    :param clinic_id: ID của phòng khám
    :return: Danh sách bác sĩ với thông tin chi tiết
    """
    conn = pymysql.connect(**DB_CONFIG)
    try:
        with conn.cursor() as cursor:
            # Lấy thông tin bác sĩ cơ bản + tên người dùng + chuyên khoa
            cursor.execute("""
                SELECT 
                    d.doctor_id,
                    ui.full_name,
                    s.name AS specialty_name,
                    d.biography
                FROM doctors d
                JOIN users_info ui ON d.user_id = ui.user_id
                JOIN specialties s ON d.specialty_id = s.specialty_id
                WHERE d.clinic_id = %s
            """, (clinic_id,))
            doctor_rows = cursor.fetchall()

            doctors = []
            for row in doctor_rows:
                doctor_id, full_name, specialty_name, biography = row

                # Truy xuất lịch làm việc của bác sĩ đó
                cursor.execute("""
                    SELECT schedule_id, day_of_week, start_time, end_time
                    FROM doctor_schedules
                    WHERE doctor_id = %s
                    ORDER BY FIELD(day_of_week, 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday')
                """, (doctor_id,))
                schedules = cursor.fetchall()
                schedule_list = [
                    {"day": day, "start": str(start), "end": str(end)}
                    for day, start, end in schedules
                ]

                doctors.append({
                    "doctor_id": doctor_id,
                    "full_name": full_name,
                    "specialty": specialty_name,
                    "biography": biography,
                    "schedules": schedule_list
                })

            return doctors
    finally:
        conn.close()
