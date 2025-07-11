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
import unicodedata
import re

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
    # logger.info(f"📋 Thông tin thiếu: {missing_fields}")
    
    session_data = await get_session_data(user_id=user_id, session_id=session_id)
    # 📥 Lấy từ session
    logger.info("📦 JSON từ session trước khi chuyền vào prompt:\n" + json.dumps(session_data.get("booking_info", {}), indent=2, ensure_ascii=False))
    
    booking_info = session_data.get("booking_info", {})
    extracted = booking_info.get("extracted_info", {}) or {}

    prediction_today_details = get_today_prediction(user_id)


    logger.info(f"🧠 Dự đoán hôm nay: {prediction_today_details}")

    all_specialty_names = get_all_specialty_names()

    specialties = extracted.get("specialty_name")
    location = extracted.get("location", "")
    clinic_id = extracted.get("clinic_id", "")
    doctor_id = extracted.get("doctor_id", "")
    specialty_id = extracted.get("specialty_id", "")

    # Ưu tiên lấy clinic từ specialties hiện tại
    if specialties:
        suggested_clinics = get_clinics(location, specialties)
    # Nếu specialties chưa có nhưng session đã lưu từ trước → dùng lại
    elif session_data.get("suggested_clinics"):
        suggested_clinics = session_data.get("suggested_clinics")
    # Không có gì hết → để trống
    else:
        suggested_clinics = []


    # Ưu tiên lấy tất cả bắc sĩ từ cơ sỡ đó
    if clinic_id:
        suggested_doctors = get_doctors(clinic_id)
    # Nếu có bác sĩ dc lưu trong session thì lấy
    elif session_data.get("suggested_doctors"):
        suggested_doctors = session_data.get("suggested_doctors")
    else:
        suggested_doctors = []

    if doctor_id and clinic_id and specialty_id:
        schedules = get_doctor_schedules(
            doctor_id=doctor_id,
            clinic_id=clinic_id,
            specialty_id=specialty_id
        )
    elif session_data.get("schedules_info"):
        schedules = session_data.get("schedules_info")
    else:
        schedules = []

    logger.info("🔍 Suggested clinics trước khi chuyền vào prompt:\n" + json.dumps(suggested_clinics, indent=2, ensure_ascii=False))
    safe_schedules = serialize_for_logging(schedules)

    # logger.info("🔍 lịch trích được trước khi chuyền vào prompt:\n" + json.dumps(safe_schedules, indent=2, ensure_ascii=False))
    print("Tin nhan cua nguoi dung: " + ", ".join(recent_user_messages))

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

    import tiktoken
    encoding = tiktoken.encoding_for_model("gpt-4")
    token_count = len(encoding.encode(prompt))
    print("🔢 Token count:", token_count)

    completion = chat_completion(messages=[{"role": "user", "content": prompt}], temperature=0.7)
    raw_content = completion.choices[0].message.content.strip()
    raw_json = extract_json(raw_content)

    # logger.info("🔍 Raw content từ GPT:\n" + raw_content)

    try:
        parsed = json.loads(raw_json)
        logger.info("📦 JSON từ GPT:\n" + json.dumps(parsed, indent=2, ensure_ascii=False))
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
    
    booking_info = session_data.get("booking_info", {})
    extracted = booking_info.get("extracted_info", {}) or {}

    # logger.info(f"📤 Thông tin trích xuất: {extracted}")

    status = booking_info.get("status", "")
    message = booking_info.get("message", "")

    should_insert = booking_info.get("should_insert", False)
    request_clinic = booking_info.get("request_clinic", False)

    specialty = extracted.get("specialty_name")
    specialty_id = extracted.get("specialty_id")
    location = extracted.get("location")
    clinic_id = extracted.get("clinic_id")
    doctor_id = extracted.get("doctor_id")

    if isinstance(specialty, list):
        specialties = specialty
    elif specialty:
        specialties = [specialty]
    else:
        specialties = []

    # Nếu GPT chỉ trả về clinic_name nhưng không trả về clinic_id
    if extracted.get("clinic_name") and not extracted.get("clinic_id"):
        for c in session_data.get("suggested_clinics", []):
            if normalize(c["clinic_name"]) == normalize(extracted["clinic_name"]):
                extracted["clinic_id"] = str(c["clinic_id"])
                break


    # 💾 Cập nhật lại context
    session_data["extracted_info"] = extracted
    await save_session_data(user_id=user_id, session_id=session_id, data=session_data)


    # 🧾 Log giá trị truyền vào get_clinics và get_doctors_by_clinic
    logger.info(f"📥 Input to get_clinics → location: {location}, specialties: {specialties}")
    logger.info(f"📥 Input to get_doctors_by_clinic → clinic_id: {clinic_id}")

    # 🔍 Gợi ý phòng khám và bác sĩ
    suggested_clinics = get_clinics(location, specialties) if specialty and request_clinic else []
    suggested_doctors = get_doctors(clinic_id) if clinic_id else []

    # 🧾 Log kết quả
    logger.info("👨‍⚕️ Suggested doctors:\n" + json.dumps(suggested_doctors, indent=2, ensure_ascii=False))


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
        # match = match_clinic(recent_user_messages[-1:] if recent_user_messages else [], suggested_clinics)
        # if match:
        #     session_data["extracted_info"]["clinic_id"] = str(match["clinic_id"])
        #     session_data["extracted_info"]["clinic_name"] = match["clinic_name"]
        #     session_data["status"] = "waiting_complete_info"
        #     yield {"message": f"Mình đã ghi nhận bạn chọn {match['clinic_name']}. Tiếp theo bạn muốn chọn bác sĩ hay chọn thời gian khám?"}
        #     return
    
        clinics = get_clinics(location, specialties) if specialties else []
        # logger.info("🔍 Suggested clinics:\n" + json.dumps(suggested_clinics, indent=2, ensure_ascii=False))
        
        if not clinics and location:
            clinics = get_clinics("", specialties)

        if not clinics:
            yield {"message": f"Hiện không tìm thấy phòng khám phù hợp với chuyên khoa {specialty}. Bạn thử khu vực khác nha."}
            return
        
        session_data["suggested_clinics"] = clinics

        # Hiển thị cả danh sách chuyên khoa của từng phòng khám (nếu có)
        lines = []
        for c in clinics:
            name = c['clinic_name']
            address = c['address']
            specialties_list = c.get('specialties', [])

            if len(specialties_list) > 1:
                specialty_str = f" ({', '.join(specialties_list)})"
            else:
                specialty_str = ""

            lines.append(f"{name} - {address}{specialty_str}")

        suggestion = "\n".join([f"- {line}" for line in lines])


        yield {
            "message": f"{message}\n\n{suggestion}",
        }
        return

    # Xác định bác sĩ muốn khám
    elif status == "incomplete_doctor_info":
        if not clinic_id:
            yield {"message": "Không xác định được phòng khám để tìm bác sĩ."}
            return

        doctors = get_doctors(clinic_id=clinic_id, specialty=specialty)
        logger.info(f"👨‍⚕️ Gợi ý bác sĩ: {[d['full_name'] for d in doctors]}")

        if not doctors:
            yield {"message": "Hiện không có bác sĩ nào phù hợp tại phòng khám này."}
            return

        suggested = [{
            "doctor_id": d["doctor_id"],
            "full_name": d["full_name"],
            "specialty_name": d["specialty"],
            "biography": d["biography"],
            "clinic_id": clinic_id,
        } for d in doctors]

        session_data["suggested_doctors"] = suggested

        if len(doctors) > 1:
            names = ", ".join([d["full_name"] for d in doctors])
            yield {"message": f"{message}\n\n{suggested}"}
        else:
            yield {"message": message}
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

        session_data["schedules_info"] = schedules

        if len(schedules) > 1:
            formatted_schedule = format_weekly_schedule(schedules)
            yield {"message": f"{message}\n\n{formatted_schedule}"}
        else:
            yield {"message": message}
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
            doctors = get_doctors(clinic_id)
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
    last_bot_msgs = recent_assistant_messages[-3:] if recent_assistant_messages else []
    last_user_msgs = recent_user_messages[-3:] if recent_user_messages else []

    # logger.info("🔍 Suggested clinics đã được chuyền vào prompt:\n" + json.dumps(suggested_clinics, indent=2, ensure_ascii=False))
    # print("Các chuyên khoa:")
    # for specialty in all_specialty_names:
    #     print("-", specialty)


    specialties_str = ", ".join(f'"{s}"' for s in all_specialty_names)
    extracted = booking_info.get("extracted_info", {}) or {}

    prompt = f"""
        You are a smart assistant helping users schedule medical appointments in Vietnam.

        ### 📋 CONTEXT (structured as JSON):

        {{
        "latest_bot_message": {json.dumps(last_bot_msgs, ensure_ascii=False)},
        "latest_user_message": {json.dumps(last_user_msgs, ensure_ascii=False)},
        "extracted_info": {{
            "full_name": {json.dumps(extracted.get("full_name", ""), ensure_ascii=False)},
            "phone": {json.dumps(extracted.get("phone", ""), ensure_ascii=False)},
            "location": {json.dumps(extracted.get("location", ""), ensure_ascii=False)},
            "specialty_id": {json.dumps(extracted.get("specialty_id", []), ensure_ascii=False)},
            "specialty_name": {json.dumps(extracted.get("specialty_name", []), ensure_ascii=False)},
            "clinic_id": {json.dumps(extracted.get("clinic_id", ""), ensure_ascii=False)},
            "clinic_name": {json.dumps(extracted.get("clinic_name", ""), ensure_ascii=False)},
            "schedule_id": {json.dumps(extracted.get("schedule_id", ""), ensure_ascii=False)},
            "doctor_id": {json.dumps(extracted.get("doctor_id", ""), ensure_ascii=False)},
            "doctor_name": {json.dumps(extracted.get("doctor_name", ""), ensure_ascii=False)}
        }},
        "health_prediction_today": {json.dumps(prediction_today_details, ensure_ascii=False)},
        "valid_specialties": {json.dumps(specialties_str, ensure_ascii=False)},
        "suggested_clinics": {json.dumps(suggested_clinics, ensure_ascii=False)},
        "available_schedules": {json.dumps(schedules, ensure_ascii=False)},
        "available_doctors": {json.dumps(suggested_doctors, ensure_ascii=False)}
        }}

        ### 🎯 SYSTEM INSTRUCTION:

        You are a medical appointment assistant. Follow these rules **strictly and step-by-step**, and DO NOT skip ahead.

    """.strip()

    prompt += f"""
        ------------------------------------------------------------------
        Set "status": "incomplete_info" if:
        - 'specialty_name' is not determined
        OR
        - Any required fields are missing: full_name, phone
        (Note: location is optional and may be missing — do NOT block progress because of it.)

        Then follow the logic below step-by-step:
        If "extracted_info.specialty_name" is already provided, skip STEP 1 and 2.

        STEP 1. If "prediction_today_details" is empty:
        → Politely ask the user **only** about the kind of health issue or appointment they want to book.
        → Wait for the user's response.
        → Try to extract one or more medical 'specialty_name' values from their message.
        → Each 'specialty_name' must match one of: [{specialties_str}] and map to its corresponding "specialty_id".
        → If multiple specialties apply (e.g., "đau ngực" → ["Tim mạch", "Hô hấp"]), return all of them as a list.
        → ❗ If the user’s response is unclear or no valid specialty can be determined, politely ask them again to clarify the health issue.

        STEP 2. If "prediction_today_details" is available:
        → Use it to infer the possible medical specialties related to the symptoms or diagnosis.
        → Return a list of matching 'specialty_name' values (if any), mapped to their corresponding "specialty_id".
        → For example, if the prediction includes “đau ngực” and “khó thở”, the result might be ["Tim mạch", "Hô hấp"].
        → Each 'specialty_name' must match one of: [{specialties_str}].

        ⚠️ Only include medical specialties in the `specialty_name` list.
        Do NOT include locations, dates, times, or any unrelated strings.
        The values in `specialty_name` must only come from the predefined list: [{specialties_str}].
        Do NOT add any inferred patterns like "%TP.HCM%" or similar — this is invalid.

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
        - If `location` is empty, try to extract it from the user's most recent message or recent conversation context.
        - Accept short answers (e.g., “tphcm”, “Hà Nội”, “Đà Nẵng”) as valid location inputs.
        - Normalize common variants into **the exact canonical form used in the database**. For example:
        - "tp hcm", "tphcm", "hcm", "Sài Gòn" → "TP.HCM"
        - "hn", "ha noi" → "Hà Nội"
        - "danang", "đà nẵng", "da nang" → "Đà Nẵng"
        - Remove extra whitespace and punctuation if needed. Final output should match the actual value stored in the database.
        - If the input is ambiguous (e.g., “thành phố Vĩnh Thành”), and it's unclear whether such a place exists, gently confirm with the user (e.g., “Bạn đang nói đến thành phố Vĩnh Phúc phải không?”).
        - If the user replies vaguely (e.g., “ở đâu cũng được”, “gì cũng được”) or refuses to provide a location, you may **skip asking** and proceed.
        - If location cannot be determined confidently, ask again in a **natural, warm, and helpful tone**, such as:
        - “Bạn ở khu vực nào để mình giúp tìm phòng khám gần nhất?”
        - “Bạn muốn tìm bệnh viện hay phòng khám ở khu vực nào?”
        - “Mình cần biết bạn ở đâu để gợi ý địa điểm phù hợp nhé.”


        ❗ Never repeat the same location question in the same conversation flow unless new context is provided.


        🧷 Only ask 1 field per message. Always wait for user reply before next.

        Important:
        - Do **not** ask multiple questions in the same message.
        - Always wait for the user to respond before proceeding to the next missing field.
    """.strip()

    prompt += f"""
        ------------------------------------------------------------------
        
        STEP 4. Set status = "incomplete_clinic_info" only if:
            - 'specialty_name' is known
            - Both "full_name" and "phone" are already provided in 'extracted_info'
            - 'location' is optional, and can still be missing

        → Proceed to check whether the user has provided a clinic name that matches one in the 'suggested_clinics' list.
        → If not, set "request_clinic": true and ask politely.

        ------------------------------------------------------------------

        **STEP 5: Clinic Selection Logic**

        Once the list of matching clinics (`suggested_clinics`) has already been shown to the user:

        You MUST identify the user's selected clinic **only** based on their latest reply (`last_user_msgs`) and the provided `suggested_clinics`.

        ⚠️ DO NOT guess, generate, or reference any clinic that is not in `suggested_clinics`.

        ⚠️ DO NOT re-list the clinics. The UI already displays them.

        User may reply with:

        * A clinic name (e.g., “Bệnh viện Chợ Rẩy”, “cho ray”)
        * A partial address (e.g., “Nguyễn Thị Minh Khai”, “Quận 5”)
        * A generic confirmation (e.g., “ok”, “đúng rồi”, “chọn chỗ đó”) if only one clinic is in the list

        You may:

        * Compare the user reply with the clinic\_name or address of each item in `suggested_clinics`, allowing for minor differences in accents, case, or spacing.
        * You may ignore accents and case only if necessary, but preserve the original spelling and formatting in the final clinic\_name result.
        * Use both name and address contextually to identify the best match, even if the input is not identical.

        **⚙️ Matching results:**

        * ✅ If **exactly one** match is found:

        * Set inside `extracted_info`:

        ```json
        "extracted_info": {{
            ...
            "clinic_id": "<matched clinic_id as string>",
            "clinic_name": "<matched clinic_name (exact text)>"
        }}
        ```

        * ⚠️ If **multiple matches** are found:

        * Leave both `clinic_id` and `clinic_name` empty
        * Politely ask user to clarify by full clinic name

        * ❌ If **no match** is found:

        * Leave both fields empty
        * Politely ask user to choose again from the list

        * ✅ If `suggested_clinics` has only one item, and user gives any kind of confirmation:

        * Accept that clinic and fill `clinic_id`, `clinic_name` accordingly

        ---

        **📌 Matching example (final JSON format):**

        If the user message matches a clinic from `suggested_clinics` (e.g. user said "Chợ Rẩy"), and the matched clinic is:

        ```json
        {{
            "clinic_id": 2,
            "clinic_name": "Bệnh viện Chợ Rẩy",
            "address": "201B Nguyễn Chí Thanh, Quận 5, TP.HCM"
        }}
        ```

        Then you MUST return the following inside `"extracted_info"`:

        ```json
        "extracted_info": {{
        ...
            "clinic_id": "2",
            "clinic_name": "Bệnh viện Chợ Rẩy"
        }}
        ```

        ⚠️ **Both fields are required.** Do **not** return only `clinic_name` or only `clinic_id`.

        ⚠️ **Do not** leave them empty or wait for further confirmation if the match is clear from user input.

        ✅ Return the values immediately if a valid match exists in `suggested_clinics`.

        ️❗This output is mandatory for the booking to proceed.



    """.strip()

    prompt += f"""

        ------------------------------------------------------------------
        STEP 6A. Determine Next Action (Doctor vs. Schedule)
            After the user has selected both specialty and clinic:

            You MUST analyze their message 'last_user_msgs' to decide the next action:

            If the user mentions:

            A doctor name, a phrase like “chọn bác sĩ”, or anything indicating they want to pick a doctor:
            → You MUST set "status": "incomplete_doctor_info"

            If the user mentions:

            A specific date, a weekday (e.g., "thứ hai"), a time (e.g., “buổi sáng”), or any phrase like “muốn đặt lịch ngày mai”:
            → You MUST set "status": "incomplete_schedules_info"

            ❗If you fail to set the correct status, the system will be unable to proceed.

            → Do not repeat the previous message. Just update the status field.

        STEP 6B. If `status == "incomplete_doctor_info"`

            Once the user chooses to select a doctor:

            You MUST identify their intent and extract doctor information based on the available `suggested_doctors` list.

            The user may reply in 'last_user_msgs' with:

            - A full doctor name (e.g., “Nguyễn Hoàng Nam”)
            - A partial name (e.g., “bác sĩ Nam”, “Hoài Nam”)
            - A generic confirmation (e.g., “ok”, “đặt bác sĩ đó”) if only one doctor is available

            You MUST:

            - Normalize user input (remove accents, convert to lowercase)
            - Compare with each `doctor["full_name"]` in `suggested_doctors`

            Matching behavior:

            - If exactly one match is found:
            → Set `"doctor_id"` and `"doctor_name"` from the matched doctor

            - If multiple matches are found:
            → Do **not** set `"doctor_id"` or `"doctor_name"`
            → Ask the user to clarify using the full doctor name

            - If only one doctor exists in `suggested_doctors`, and the user replies with any confirmation:
            → Set `"doctor_id"` and `"doctor_name"` using that doctor

            ❗CRITICAL WARNING:
            If `suggested_doctors` contains only one doctor, and the user gives any affirmative confirmation,
            you MUST return both `"doctor_id"` and `"doctor_name"` in `extracted_info`, and update the `status == "incomplete_schedules_info"`.

            → If you fail to do this, the scheduling pipeline will crash and all progress may be lost.

        STEP 6C. If `status == "incomplete_schedules_info"`

            Once the user chooses to select a date/time in 'last_user_msgs' for appointment:

            - Ask for preferred date/time (e.g., “thứ hai tuần sau”, “sáng mai”, “14h ngày 12/7”)

            Once `schedule_time` is provided:

            - Search for matching schedules (filtered by clinic, and optionally doctor)

            - If no match found:
            → Reply: *“Xin lỗi, không có lịch khám nào phù hợp với thời gian đó. Bạn có muốn chọn thời gian khác không?”*

            - If multiple doctors are available:
            → Ask the user to select a doctor

            - If exactly one matching doctor is found:
            → Set:
                - `"doctor_id"`
                - `"doctor_name"`
                - `"schedule_id"`

            If only one schedule is available:
            → Ask: *“Mình tìm được một lịch khám duy nhất là \[day\_of\_week] lúc \[start\_time]. Bạn có muốn đặt lịch này không?”*
            → If the user replies with any confirmation:
            → Set `"schedule_id"`, `"doctor_id"`, and `"doctor_name"`

            ❗CRITICAL WARNING:
            If you detect only one matching schedule and the user confirms, but you fail to return the correct
            `"schedule_id"`, `"doctor_id"` and `"doctor_name"`, the system will crash immediately.

        ------------------------------------------------------------------
        STEP 7. If all required information is complete, politely confirm the booking and set `"status": "complete"`.
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
            "status": "waiting_complete_info"| "incomplete_info" | "incomplete_clinic_info" | "incomplete_doctor_info" | "incomplete_schedules_info" | "complete" | "modifying_info" | "confirmed",
            "request_clinic": true | false,
            "request_appointment_time": true | false,
            "modification_target": "doctor" | "schedule" | "clinic" | "specialty" | null, ← only for `modifying_info`
            "extracted_info": {{
                "full_name": "...",
                "phone": "...",
                "location": "...",
                "specialty_id": ["..."],
                "specialty_name": ["..."],  
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
    
    #Step 7            - Display all extracted info for confirmation.
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

    conn = pymysql.connect(**DB_CONFIG)
    try:
        with conn.cursor() as cursor:
            format_str = ",".join(["%s"] * len(specialties))
            sql = f"""
                SELECT DISTINCT c.clinic_id, c.name, c.address,
                                GROUP_CONCAT(DISTINCT s.name SEPARATOR ', ') AS specialties
                FROM clinics c
                JOIN clinic_specialties cs ON c.clinic_id = cs.clinic_id
                JOIN specialties s ON cs.specialty_id = s.specialty_id
                WHERE s.name IN ({format_str})
            """
            params = specialties

            # Nếu có location, thêm điều kiện AND c.address LIKE %...%
            if location and location.strip():
                sql += " AND c.address LIKE %s"
                like_location = f"%{location.strip()}%"
                params.append(like_location)

            sql += """
                GROUP BY c.clinic_id
                ORDER BY c.name
                LIMIT 5
            """

            cursor.execute(sql, params)
            return [
                {
                    "clinic_id": row[0],
                    "clinic_name": row[1],
                    "address": row[2],
                    "specialties": row[3].split(", ") if row[3] else []
                }
                for row in cursor.fetchall()
            ]
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

def get_doctors(clinic_id: int = None, specialty: list[str] = None) -> list[dict]:
    """
    Lấy danh sách bác sĩ theo phòng khám và/hoặc chuyên khoa.

    :param clinic_id: ID của phòng khám (có thể None)
    :param specialties: Danh sách tên chuyên khoa (có thể None)
    :return: Danh sách bác sĩ với tên đầy đủ, chuyên khoa, tiểu sử
    """
    conn = pymysql.connect(**DB_CONFIG)
    try:
        with conn.cursor() as cursor:
            query = """
                SELECT 
                    d.doctor_id,
                    ui.full_name,
                    s.name AS specialty_name,
                    d.biography
                FROM doctors d
                JOIN users_info ui ON d.user_id = ui.user_id
                JOIN specialties s ON d.specialty_id = s.specialty_id
            """

            conditions = []
            params = []

            if clinic_id is not None:
                conditions.append("d.clinic_id = %s")
                params.append(clinic_id)

            if specialty:
                placeholders = ','.join(['%s'] * len(specialty))
                conditions.append(f"s.name IN ({placeholders})")
                params.extend(specialty)

            if conditions:
                query += " WHERE " + " AND ".join(conditions)

            cursor.execute(query, tuple(params))
            rows = cursor.fetchall()

            doctors = []
            for row in rows:
                doctor_id, full_name, specialty_name, biography = row
                doctors.append({
                    "doctor_id": doctor_id,
                    "full_name": full_name,
                    "specialty": specialty_name,
                    "biography": biography
                })

            return doctors
    finally:
        conn.close()

def get_doctor_schedules(doctor_id: int = None, clinic_id: int = None, specialty_id: list[str] = None) -> list[dict]:
    """
    Lấy danh sách lịch khám của bác sĩ.
    - Nếu cung cấp doctor_id → lấy lịch bác sĩ đó
    - Nếu không cung cấp doctor_id → lọc theo clinic_id & specialty_id (có thể là list)

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
                # Chuẩn hóa specialty_id thành list
                if not specialty_id:
                    raise ValueError("specialty_id is required when doctor_id is not provided")

                if not isinstance(specialty_id, list):
                    specialty_id = [str(specialty_id)]
                else:
                    specialty_id = [str(sid) for sid in specialty_id]

                if len(specialty_id) == 1:
                    # Truy vấn theo 1 chuyên khoa
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
                    cursor.execute(sql, (clinic_id, specialty_id[0]))
                else:
                    # Truy vấn theo nhiều chuyên khoa
                    placeholders = ','.join(['%s'] * len(specialty_id))
                    sql = f"""
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
                    WHERE d.clinic_id = %s AND d.specialty_id IN ({placeholders})
                    ORDER BY d.doctor_id, ds.day_of_week, ds.start_time;
                    """
                    cursor.execute(sql, [clinic_id] + specialty_id)

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

def serialize_for_logging(obj):
    if isinstance(obj, list):
        return [serialize_for_logging(item) for item in obj]
    elif isinstance(obj, dict):
        return {
            key: serialize_for_logging(value)
            for key, value in obj.items()
        }
    elif isinstance(obj, datetime.timedelta):
        return str(obj)
    else:
        return obj
    
def normalize(text):
    if not text:
        return ""

    # Chuyển về Unicode chuẩn (NFKD)
    text = unicodedata.normalize('NFKD', text)

    # Bỏ dấu tiếng Việt
    text = ''.join([c for c in text if not unicodedata.combining(c)])

    # Viết thường, bỏ ký tự đặc biệt, khoảng trắng thừa
    text = text.lower()
    text = re.sub(r'[^\w\s]', '', text)   # bỏ ký tự đặc biệt
    text = re.sub(r'\s+', ' ', text)      # thay nhiều khoảng trắng bằng 1
    return text.strip()

def match_clinic(user_input, suggested_clinics):
    user_norm = normalize(user_input)
    matched = []

    for clinic in suggested_clinics:
        name_norm = normalize(clinic["clinic_name"])
        address_norm = normalize(clinic.get("address", ""))
        if user_norm in name_norm or user_norm in address_norm:
            matched.append(clinic)

    if len(matched) == 1:
        return matched[0]
    return None
