# utils/booking.py

from utils.session_store import get_session_data, save_session_data
import pymysql
from config.config import DB_CONFIG
import logging
logger = logging.getLogger(__name__)
import json
from utils.openai_utils import chat_completion, stream_gpt_tokens
import asyncio
import datetime
from collections import defaultdict

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
    # B1: Kiểm tra thông tin còn thiếu
    basic_info = await check_missing_booking_info(user_id=user_id, session_id=session_id)
    missing_fields = [k for k in ["full_name", "phone", "location"] if not basic_info.get(k)]
    logger.info(f"📋 Thông tin thiếu: {missing_fields}")
    
    session_data = await get_session_data(user_id=user_id, session_id=session_id)

    prediction_today_details = get_today_prediction(user_id)


    logger.info(f"🧠 Dự đoán hôm nay: {prediction_today_details}")




    all_specialty_names = get_all_specialty_names()

    suggested_clinics = []
    suggested_doctors = []
    schedules = []

    # B2: Tạo prompt và gọi GPT
    prompt = booking_prompt(
        recent_user_messages,
        recent_assistant_messages,
        prediction_today_details,
        all_specialty_names=all_specialty_names,
        suggested_clinics=suggested_clinics,
        suggested_doctors=suggested_doctors,
        schedules=schedules,
        booking_info=session_data.get("booking_info", {}),
    )

    completion = chat_completion(messages=[{"role": "user", "content": prompt}], temperature=0.7)
    raw_content = completion.choices[0].message.content.strip()
    raw_json = extract_json(raw_content)

    content = completion.choices[0].message.content.strip()
    # logger.info("🔍 Raw content từ GPT:\n" + content)

    try:
        parsed = json.loads(raw_json)
        # logger.info("📦 JSON từ GPT:\n" + json.dumps(parsed, indent=2, ensure_ascii=False))
    except json.JSONDecodeError as e:
        logger.warning(f"⚠️ GPT trả về không phải JSON hợp lệ: {e}")
        yield {"message": "Xin lỗi, hiện tại hệ thống gặp lỗi khi xử lý dữ liệu. Bạn thử lại sau nhé."}
        return
    
    old_booking_info = session_data.get("booking_info", {})
    old_extracted = old_booking_info.get("extracted_info", {})
    new_extracted = parsed.get("extracted_info", {})

    # ⚠️ Merge extracted_info: Ưu tiên giữ giá trị cũ nếu GPT trả về rỗng
    merged_extracted = {**old_extracted, **{k: v for k, v in new_extracted.items() if v}}

    parsed["extracted_info"] = merged_extracted
    session_data["booking_info"] = {**old_booking_info, **parsed}

    await save_session_data(user_id=user_id, session_id=session_id, data=session_data)
    
    # 📥 Lấy từ session
    session_data = await get_session_data(user_id=user_id, session_id=session_id)
    logger.info("📦 JSON từ session:\n" + json.dumps(session_data.get("booking_info", {}), indent=2, ensure_ascii=False))
    
    booking_info = session_data.get("booking_info", {})
    extracted = booking_info.get("extracted_info", {}) or {}

    logger.info(f"📤 Thông tin trích xuất: {extracted}")

    status = booking_info.get("status", "")
    message = booking_info.get("message", "")

    should_insert = booking_info.get("should_insert", False)
    request_clinic = booking_info.get("request_clinic", False)

    specialty = extracted.get("specialty_name")
    specialty_id = extracted.get("specialty_id")
    location = extracted.get("location")
    clinic_id = extracted.get("clinic_id")
    doctor_id = extracted.get("doctor_id")

    specialties = [specialty] if specialty else []

    # 💾 Cập nhật lại context
    session_data["extracted_info"] = extracted
    await save_session_data(user_id=user_id, session_id=session_id, data=session_data)


    suggested_clinics = get_clinics(location, specialties) if specialty and request_clinic else []
    suggested_doctors = get_doctors_by_clinic(clinic_id) if clinic_id else []

    if doctor_id:
        schedules = get_doctor_schedules(doctor_id=doctor_id)
    elif clinic_id and specialty_id:
        schedules = get_doctor_schedules(clinic_id=clinic_id, specialty_id=specialty_id)
    else:
        schedules = []
    # B3: Xử lý theo từng status
    # Kiểm tra xem người dùng có đủ thông tin cơ bản không gồm tên đầy đủ và sdt
    if status == "incomplete_info":
        yield {"message": message or "Bạn có thể cung cấp thêm thông tin để mình hỗ trợ đặt lịch nha."}
        return

    # Hỏi người dùng về địa điểm để lựa chọn cơ sở khám gần nhất
    elif status == "incomplete_clinic_info":

        clinics = get_clinics(location, specialties) if specialties else []

        if not clinics and location:
            clinics = get_clinics("", specialties)

        logger.info(f"📍 Gợi ý phòng khám (sau khi xử lý): {clinics}")

        if not clinics:
            yield {"message": f"Hiện không tìm thấy phòng khám phù hợp với chuyên khoa {specialty}. Bạn thử khu vực khác nha."}
            return

        lines = [f"{c['name']} - {c['address']}" for c in clinics]
        suggestion = "\n".join([f"- {line}" for line in lines])

        yield {
            "message": f"{message}\n\n{suggestion}\n\nBạn muốn đặt ở đâu?",
        }
        return


    # Xác định bác sĩ muốn khám
    elif status == "incomplete_doctor_info":
        if not clinic_id:
            yield {"message": "Không xác định được phòng khám để tìm bác sĩ."}
            return

        doctors = get_doctors_by_clinic(clinic_id)
        logger.info(f"👨‍⚕️ Gợi ý bác sĩ: {[d['full_name'] for d in doctors]}")

        if not doctors:
            yield {"message": "Hiện không có bác sĩ nào tại phòng khám này."}
            return

        suggested = [{
            "doctor_id": d["doctor_id"],
            "full_name": d["full_name"],
            "specialty_name": d["specialty"],
            "biography": d["biography"],
            "clinic_id": clinic_id,
            "schedules": d["schedules"]
        } for d in doctors]

        names = ", ".join([d["full_name"] for d in doctors])
        yield {"message": f"Bạn muốn đặt lịch với bác sĩ nào? Dưới đây là danh sách tại phòng khám:\n{names}",}
        return

    # Xác định lịch khám
    elif status == "incomplete_schedules_info":
        schedules = get_doctor_schedules(
            doctor_id=doctor_id,
            clinic_id=clinic_id,
            specialty_id=specialty_id
        )

        if not schedules:
            yield {"message": "Xin lỗi, hiện không có lịch khám nào phù hợp. Bạn muốn chọn lại thời gian khác không?"}
            return

        formatted_schedule = format_weekly_schedule(schedules)
        yield {"message": formatted_schedule + "\n\nBạn muốn đặt vào khung giờ nào trong tuần?",}
        return

    # In ra tất cả thông tin chờ người dùng xác nhận
    elif status == "complete":
        schedule_info = {}
        schedule_id = extracted.get("schedule_id")
        if schedule_id:
            schedule_info = get_schedule_by_id(schedule_id)

        lines = [
            f"Họ tên: {extracted.get('full_name')}",
            f"SĐT: {extracted.get('phone')}",
            f"Khu vực: {extracted.get('location')}",
            f"Chuyên khoa: {extracted.get('specialty_name')}",
            f"Phòng khám: {extracted.get('clinic_name')}",
            f"Bác sĩ: {extracted.get('doctor_name')}",
            f"Lịch hẹn: {schedule_info.get('formatted', 'Chưa rõ')}"
        ]
        logger.info("✅ Đã đủ thông tin. Chờ người dùng xác nhận.")
        yield{"message": "✅ Bạn đã chọn đầy đủ thông tin:\n" + "\n".join(lines) + "\n\nBạn xác nhận đặt lịch này chứ?",}
        return

    # Thây đổi thông tin như bác sĩ lịch hẹn nếu người dùng yêu cầu
    elif status == "modifying_info":
        target = parsed.get("modification_target")

        if target == "doctor":
            if not clinic_id:
                yield {"message": "Không xác định được phòng khám hiện tại để gợi ý bác sĩ mới."}
                return
            doctors = get_doctors_by_clinic(clinic_id)
            if not doctors:
                yield {"message": "Không có bác sĩ nào tại phòng khám này."}
                return
            names = [d["full_name"] for d in doctors]
            suggested = "\n".join(f"- {name}" for name in names)
            yield {"message": "Dưới đây là danh sách bác sĩ bạn có thể chọn lại:\n" + suggested}
            return

        elif target == "schedule":
            schedules = get_doctor_schedules(doctor_id=doctor_id, clinic_id=clinic_id, specialty_id=specialty_id)
            if not schedules:
                yield {"message": "Không có lịch khám mới nào để thay đổi. Bạn muốn giữ lịch hiện tại chứ?"}
                return
            formatted = [
                f"Bác sĩ {row['full_name']} - {row['day_of_week']} từ {row['start_time']} đến {row['end_time']}"
                for row in schedules
            ]
            yield {"message": "Dưới đây là các lịch khám khác bạn có thể chọn lại:\n" + "\n".join(formatted)}
            return

        elif target == "clinic":
            if not specialty:
                yield {"message": "Không xác định được chuyên khoa để tìm phòng khám mới."}
                return
            clinics = get_clinics(location, [specialty])
            if not clinics:
                yield {"message": "Không tìm được phòng khám nào mới với chuyên khoa hiện tại."}
                return
            lines = [f"{c['name']} - {c['address']}" for c in clinics]
            suggestion = "\n".join(f"- {line}" for line in lines)
            yield {"message": "Dưới đây là các phòng khám bạn có thể chọn lại:\n" + suggestion}
            return

        elif target == "specialty":
            all_specialties = get_all_specialty_names()
            specialties_str = "\n".join(f"- {name}" for name in all_specialties)
            yield {"message": f"Bạn muốn khám chuyên khoa nào khác? Dưới đây là danh sách để chọn lại:\n{specialties_str}"}
            return

        else:
            yield {"message": "Bạn muốn thay đổi thông tin nào? (ví dụ: bác sĩ, phòng khám, chuyên khoa, hoặc lịch hẹn)"}
            return

    # Xác nhận lịch khám và insert vào table lịch khám
    elif status == "confirmed" and should_insert:
        doctor_id = extracted.get("doctor_id")
        clinic_id = extracted.get("clinic_id")
        schedule_id = extracted.get("schedule_id")

        if not (doctor_id and clinic_id and schedule_id):
            yield {"message": "Thiếu thông tin để tạo lịch hẹn. Vui lòng kiểm tra lại."}
            return

        schedule_info = get_schedule_by_id(schedule_id)
        formatted_time = schedule_info.get("formatted", "Không rõ")

        appointment_id = insert_appointment(
            user_id=user_id,
            doctor_id=doctor_id,
            clinic_id=clinic_id,
            schedule_id=schedule_id,
            reason=prediction_today_details or ""
        )
        logger.info(f"📅 Đặt lịch thành công. Appointment ID: {appointment_id}")

        yield {
            "message": (
                f"✅ Đã đặt lịch thành công! Mã lịch hẹn của bạn là #{appointment_id}.\n"
                f"Lịch khám: {formatted_time}\n"
                f"Chúc bạn sức khỏe tốt!"
            ),
            "should_insert": False  # để tránh tạo trùng lần sau
        }
        return

    # Stream câu trả lời
    if message:
        for chunk in stream_gpt_tokens(message):
            yield chunk
            await asyncio.sleep(0.065)
        return

def booking_prompt(
    recent_user_messages: list[str],
    recent_assistant_messages: list[str],
    prediction_today_details: str,
    all_specialty_names: list[str],
    suggested_clinics: list[str],
    suggested_doctors: list[str],
    schedules: list[str],
    booking_info,
) -> str:
    last_bot_msgs = recent_assistant_messages[-6:] if recent_assistant_messages else []
    last_user_msgs = recent_user_messages[-6:] if recent_user_messages else []


    # print("Các chuyên khoa:")
    # for specialty in all_specialty_names:
    #     print("-", specialty)


    specialties_str = ", ".join(f'"{s}"' for s in all_specialty_names)
    extracted = booking_info.get("extracted_info", {}) or {}
    
    prompt = f"""
        You are a smart assistant helping users schedule medical appointments in Vietnam.

        ### 🧠 Context:
        - Latest bot message: "{last_bot_msgs}"
        - Latest user message: "{last_user_msgs}"

        - 'extracted_info' so far: {{
            "full_name": "{extracted.get('full_name', '')}",
            "phone": "{extracted.get('phone', '')}",
            "location": "{extracted.get('location', '')}",
            "specialty_id": "{extracted.get('specialty_id', '')}",
            "specialty_name": "{extracted.get('specialty_name', '')}",
            "clinic_id": "{extracted.get('clinic_id', '')}",
            "clinic_name": "{extracted.get('clinic_name', '')}",
            "schedule_id": "{extracted.get('schedule_id', '')}",
            "doctor_id": "{extracted.get('doctor_id', '')}",
            "doctor_name": "{extracted.get('doctor_name', '')}"
        }}

        - Health prediction today: "{prediction_today_details}"
        - List of valid specialties: [{specialties_str}]
        - List of the fit clinics for user: [{suggested_clinics}]
        - Available schedules: "{schedules}"
        - Available doctors: "{suggested_doctors}"

        SYSTEM INSTRUCTION (luôn chạy đúng thứ tự):

        You are a medical appointment assistant. Follow these rules **strictly and step-by-step**, and DO NOT skip ahead.


        ------------------------------------------------------------------
        Set "status": "incomplete_info" if:
        - 'specialty_name' is not determined
        OR
        - Any required fields are missing: full_name, phone, or location

        Then follow the logic below step-by-step:
        If "extracted_info.specialty_name" is already provided, skip STEP 1 and 2.

        STEP 1. If "prediction_today_details" is empty:
        → Politely ask the user **only** about the kind of health issue or appointment they want to book.
        → Wait for the user's response.
        → Try to extract the medical 'specialty_name' from their message.
        → The 'specialty_name' must match one of: [{specialties_str}] and map to the corresponding "specialty_id".
        → ❗ If the user’s response is unclear or no matching 'specialty_name' can be found, politely ask them again to clarify the health issue.

        STEP 2. If "prediction_today_details" is available:
        → Determine the medical 'specialty_name' based on the diseases mentioned.
        → The 'specialty_name' must match one of: [{specialties_str}] and map to the corresponding "specialty_id".

        STEP 3 — Required Field Check

        Check `extracted_info` for missing fields. A field is considered missing if null, empty string (""), or not present.

        Required fields:
        - full_name
        - phone
        - location

        ❗ Do NOT ask for a field if it already exists and is non-empty.

        → full_name:
        - Ask only if missing or empty.
        - Use natural Vietnamese. Never repeat if already provided.

        → phone:
        - Ask only if missing or empty.
        - One question at a time, in Vietnamese.

        → location:
        - If missing and first time asking, ask where the user lives or wants to find a clinic.
        - Be warm, natural, and human. No templates.
        - Examples (do not copy): “Bạn ở khu vực nào?”, “Bạn muốn tìm phòng khám ở đâu?”

        🧷 Only ask 1 field per message. Always wait for user reply before next.



        Important:
        - Do **not** ask multiple questions in the same message.
        - Always wait for the user to respond before proceeding to the next missing field.


        ------------------------------------------------------------------
        
        STEP 4. Set status = "incomplete_clinic_info" only if:
            - 'specialty_name' is known
            - Both "full_name" and "phone" are already provided in 'extracted_info'
            - 'location' is optional, and can still be missing

        → Proceed to check whether the user has provided a clinic name that matches one in the 'suggested_clinics' list.
        → If not, set "request_clinic": true and ask politely.

        STEP 5. Once the list of matching clinics ('suggested_clinics') is shown to the user:
        → The user may reply with:
            - A clinic name
            - A partial address (e.g., "đường Nguyễn Thị Minh Khai", or "Quận 1")

        → If the user's reply matches multiple clinics:
            - Ask the user politely (in Vietnamese) to choose by **clinic name**.

        → Once the exact clinic is determined:
            - Set "clinic_name" and "clinic_id" in `extracted_info` using the exact match from `suggested_clinics`.

        ------------------------------------------------------------------
        STEP 6. When the clinic is selected by the user:

        → Ask the user: "Bạn muốn chọn bác sĩ cụ thể để xem lịch khám, hay chọn một ngày cụ thể trước?"

        → Based on the user's reply:
            - If they mention a doctor name or specialty → set `status = "incomplete_doctor_info"`
            - If they mention a date/time → set `status = "incomplete_schedules_info"`

        → If status == "incomplete_doctor_info":
            - Ask the user to specify a doctor (if not already provided).
            - The user may reply with a partial doctor name; try to match it from `suggested_doctors`.
            - If multiple matches (e.g., "Bác sĩ Nam"):
                → Show matching results:
                    - "Nguyễn Hoàng Nam"
                    - "Trần Đình Nam"
                    - "Lê Hoài Nam"
                → Ask the user to confirm by full name or provide doctor_id.

            - Once the doctor is selected:
                - Set `"doctor_id"` and `"doctor_name"` in `extracted_info`.
                - Optionally also update `"clinic_id"` if known.

            - Then update `status = "incomplete_schedules_info"` to proceed.

        → If status == "incomplete_schedules_info":
            - Ask for preferred appointment date/time (if not yet provided).
            - Once `schedule_time` is provided:
                → Search in schedules (filtered by clinic and optionally doctor).
                → If no matches:
                    - Respond: "Xin lỗi, không có lịch khám nào phù hợp với thời gian đó. Bạn có muốn chọn thời gian khác không?"
                → If multiple doctors are available:
                    - Ask the user to choose one doctor.
                → If exactly one doctor matches:
                    - Set `"doctor_id"`, `"doctor_name"`, and `"schedule_id"` in `extracted_info`.

        ------------------------------------------------------------------
        STEP 7. If all required information is complete, politely confirm the booking and set `"status": "complete"`.
           - Display all extracted info for confirmation.
           - Ask the user if they want to confirm or change any detail.

        STEP 8: If the user wants to change any part of the booking (e.g., doctor, schedule, clinic, or specialty):

        → Then:
            - Set `"status": "modifying_info"`
            - Set `"modification_target"`: one of `"doctor"`, `"schedule"`, `"clinic"`, or `"specialty"`
            - Respond with a friendly message asking the user to specify the updated value for that part.
            - Do NOT modify other parts of `"extracted_info"` unless user gives a new value.

        🚫 If you detect a modification intent, SKIP STEP 10. DO NOT confirm the booking yet.

        STEP 9: Only proceed here if the user clearly confirms the booking without asking to modify anything.

        → Then:
            - Set `"status": "confirmed"`
            - Set `"should_insert": true`
            - Respond with a warm confirmation message in Vietnamese.

        🚫 If there's any indication the user wants to change doctor, schedule, clinic, or specialty → DO NOT confirm. Go to STEP 9 instead.


        ### 📦 Output format (MUST be JSON):
        {{
            "status": "incomplete_info" | "incomplete_clinic_info" | "incomplete_doctor_info" | "incomplete_schedules_info" | "complete" | "modifying_info" | "confirmed",
            "request_clinic": true | false,
            "request_appointment_time": true | false,
            "modification_target": "doctor" | "schedule" | "clinic" | "specialty" | null, ← only for `modifying_info`
            "extracted_info": {{
                "full_name": "...",
                "phone": "...",
                "location": "...",
                "specialty_id": "...",
                "specialty_name": "...",
                "clinic_id": "...",
                "clinic_name": "...",
                "schedule_id": "...",
                "doctor_id": "...",
                "doctor_name": "..."
            }},
            "message": "Câu trả lời thân thiện bằng tiếng Việt",
            "should_insert": true | false
        }}

        ⚠️ Output only valid JSON — no explanations or markdown.
""".strip()
    return prompt

# Kiểm tra thông tin con thiếu khi đặt lịch
async def check_missing_booking_info(user_id: int = None, session_id: str = None) -> dict:
    session = await get_session_data(user_id=user_id, session_id=session_id)
    booking_info = session.get("booking_info", {})
    extracted = booking_info.get("extracted_info", {}) or {}

    # Ưu tiên lấy từ extracted_info và session
    full_name = extracted.get("full_name") or session.get("full_name")
    phone = extracted.get("phone") or session.get("phone")
    location = extracted.get("location") or session.get("location")

    # Nếu thiếu, lấy từ DB
    if user_id and (not full_name or not phone):
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
                    if not full_name:
                        full_name = row[0]
                    if not phone:
                        phone = row[1]
        finally:
            conn.close()

    # Nếu có thêm thông tin → cập nhật lại vào extracted_info và lưu session
    updated_extracted = {
        **extracted,
        "full_name": full_name or "",
        "phone": phone or "",
        "location": location or "",
    }

    session["booking_info"] = {
        **booking_info,
        "extracted_info": updated_extracted
    }

    await save_session_data(user_id=user_id, session_id=session_id, data=session)
    # logger.info("📋 [CHECK INFO] Extracted info before return:\n" + json.dumps({
    #     "full_name": full_name,
    #     "phone": phone,
    #     "location": location
    # }, indent=2, ensure_ascii=False))

    return {
        "full_name": full_name,
        "phone": phone,
        "location": location
    }

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
            cursor.execute("SELECT specialty_id, name FROM specialties ORDER BY name ASC")
            return [{"id": row[0], "specialty_name": row[1]} for row in cursor.fetchall()]
    finally:
        conn.close()

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

def get_doctor_schedules(doctor_id=None, clinic_id=None, specialty_id=None):
    """
    Lấy danh sách lịch khám của bác sĩ.
    - Nếu cung cấp doctor_id → lấy lịch bác sĩ đó
    - Nếu không cung cấp doctor_id → lọc theo clinic_id & specialty_id

    Trả về danh sách dict chứa thông tin bác sĩ, phòng khám và lịch làm việc.
    """
    conn = pymysql.connect(**DB_CONFIG)
    try:
        with conn.cursor(pymysql.cursors.DictCursor) as cursor:
            if doctor_id:
                # Truy xuất lịch của 1 bác sĩ cụ thể
                sql = """
                SELECT 
                    d.doctor_id,
                    u.full_name,
                    s.name AS specialty_name,
                    d.clinic_id,
                    ds.schedule_id,
                    ds.day_of_week,
                    ds.start_time,
                    ds.end_time
                FROM doctors d
                JOIN users_info u ON d.user_id = u.user_id
                JOIN specialties s ON d.specialty_id = s.specialty_id
                JOIN doctor_schedules ds ON d.doctor_id = ds.doctor_id
                WHERE d.doctor_id = %s
                ORDER BY ds.day_of_week, ds.start_time;
                """
                cursor.execute(sql, (doctor_id,))
            else:
                # Truy xuất lịch của tất cả bác sĩ cùng chuyên khoa tại 1 phòng khám
                sql = """
                SELECT 
                    d.doctor_id,
                    u.full_name,
                    s.name AS specialty_name,
                    d.clinic_id,
                    ds.schedule_id,
                    ds.day_of_week,
                    ds.start_time,
                    ds.end_time
                FROM doctors d
                JOIN users_info u ON d.user_id = u.user_id
                JOIN specialties s ON d.specialty_id = s.specialty_id
                JOIN doctor_schedules ds ON d.doctor_id = ds.doctor_id
                WHERE d.clinic_id = %s AND d.specialty_id = %s
                ORDER BY d.doctor_id, ds.day_of_week, ds.start_time;
                """
                cursor.execute(sql, (clinic_id, specialty_id))

            return cursor.fetchall()
    finally:
        conn.close()

def insert_appointment(
    user_id: int,
    doctor_id: int,
    clinic_id: int,
    schedule_id: int,
    reason: str = "",
    is_guest: bool = False,
    guest_id: int = None
) -> int:
    """
    Tạo một lịch hẹn mới trong bảng appointments.

    Nếu là người dùng chưa đăng nhập (guest), truyền is_guest=True và cung cấp guest_id.
    """
    # Lấy thời gian từ bảng doctor_schedules
    conn = pymysql.connect(**DB_CONFIG)
    try:
        with conn.cursor() as cursor:
            # Lấy thời gian cụ thể từ schedule_id
            cursor.execute("""
                SELECT day_of_week, start_time
                FROM doctor_schedules
                WHERE schedule_id = %s
                LIMIT 1
            """, (schedule_id,))
            row = cursor.fetchone()
            if not row:
                raise ValueError("Lịch khám không tồn tại.")

            day_of_week, start_time = row

            # Tìm ngày tiếp theo ứng với day_of_week (ví dụ: "Tuesday")
            day_map = {
                "Monday": 0, "Tuesday": 1, "Wednesday": 2,
                "Thursday": 3, "Friday": 4, "Saturday": 5, "Sunday": 6
            }
            today = datetime.datetime.now()
            today_weekday = today.weekday()
            target_weekday = day_map[day_of_week]

            days_ahead = (target_weekday - today_weekday + 7) % 7
            if days_ahead == 0:
                days_ahead = 7  # Đặt lịch cho tuần tới nếu trùng ngày

            appointment_date = today + datetime.timedelta(days=days_ahead)
            appointment_time = datetime.datetime.combine(appointment_date.date(), start_time)

            # Thêm vào bảng appointments
            if is_guest:
                cursor.execute("""
                    INSERT INTO appointments (guest_id, doctor_id, clinic_id, appointment_time, reason)
                    VALUES (%s, %s, %s, %s, %s)
                """, (guest_id, doctor_id, clinic_id, appointment_time, reason))
            else:
                cursor.execute("""
                    INSERT INTO appointments (user_id, doctor_id, clinic_id, appointment_time, reason)
                    VALUES (%s, %s, %s, %s, %s)
                """, (user_id, doctor_id, clinic_id, appointment_time, reason))

            conn.commit()
            return cursor.lastrowid  # Trả về ID của lịch hẹn mới tạo
    finally:
        conn.close()

def get_schedule_by_id(schedule_id: int) -> dict:
    """
    Trả về thông tin lịch khám + định dạng dễ hiểu (ngày, giờ, buổi), bao gồm dịch ngày sang tiếng Việt.
    """
    EN_TO_VI_DAY_MAP = {
        "Monday": "Thứ Hai",
        "Tuesday": "Thứ Ba",
        "Wednesday": "Thứ Tư",
        "Thursday": "Thứ Năm",
        "Friday": "Thứ Sáu",
        "Saturday": "Thứ Bảy",
        "Sunday": "Chủ Nhật"
    }

    conn = pymysql.connect(**DB_CONFIG)
    try:
        with conn.cursor(pymysql.cursors.DictCursor) as cursor:
            cursor.execute("""
                SELECT day_of_week, start_time, end_time
                FROM doctor_schedules
                WHERE schedule_id = %s
                LIMIT 1
            """, (schedule_id,))
            row = cursor.fetchone()

            if not row:
                return {}

            day_en = row["day_of_week"]
            start = row["start_time"]
            end = row["end_time"]

            # Dịch ngày sang tiếng Việt nếu có
            day_vi = EN_TO_VI_DAY_MAP.get(day_en, day_en)

            # Xác định buổi dựa vào giờ bắt đầu
            hour = start.hour
            if hour < 11:
                period = "Buổi sáng"
            elif hour < 14:
                period = "Buổi trưa"
            elif hour < 18:
                period = "Buổi chiều"
            else:
                period = "Buổi tối"

            return {
                "day_of_week": day_vi,
                "start_time": start.strftime("%H:%M"),
                "end_time": end.strftime("%H:%M"),
                "period": period,
                "formatted": f"{period} {day_vi} ({start.strftime('%H:%M')} - {end.strftime('%H:%M')})"
            }
    finally:
        conn.close()

def format_weekly_schedule(schedules: list[dict]) -> str:
    day_map = {
        "Monday": "Thứ 2",
        "Tuesday": "Thứ 3",
        "Wednesday": "Thứ 4",
        "Thursday": "Thứ 5",
        "Friday": "Thứ 6",
        "Saturday": "Thứ 7",
        "Sunday": "Chủ nhật"
    }

    grouped = defaultdict(list)
    for s in schedules:
        day = s["day_of_week"]
        start = s["start_time"].strftime("%H:%M")
        end = s["end_time"].strftime("%H:%M")
        doctor = s["full_name"]
        grouped[day].append(f"- {doctor}: {start} - {end}")

    lines = ["📅 Lịch khám trong tuần:"]
    for eng_day in ["Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday", "Sunday"]:
        if grouped[eng_day]:
            lines.append(f"\n{day_map[eng_day]}:")
            lines.extend(grouped[eng_day])

    return "\n".join(lines)
