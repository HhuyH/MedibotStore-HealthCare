# utils/booking.py

from utils.session_store import get_session_data, save_session_data
import pymysql
from config.config import DB_CONFIG
import logging
logger = logging.getLogger(__name__)
import json
from utils.openai_utils import chat_completion, stream_gpt_tokens
import asyncio
from collections import defaultdict
import unicodedata
import re
from datetime import timedelta, time, datetime
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
    schedule_id = extracted.get("schedule_id", "")

    basic_info = await get_missing_booking_info(user_id=user_id, extracted=extracted)
    missing_fields = [k for k in ["full_name", "phone", "location"] if not basic_info.get(k)]
    logger.info(f"📋 Thông tin thiếu: {missing_fields}")

    # 📌 Merge lại trước khi tạo prompt
    merged_extracted = {**extracted, **{k: v for k, v in basic_info.items() if v}}

    session_data["booking_info"] = {
        **booking_info,
        "extracted_info": merged_extracted
    }

    # Ưu tiên lấy clinic từ specialties hiện tại
    if specialties:
        suggested_clinics = get_clinics(location, specialties)
        session_data["suggested_clinics"] = suggested_clinics
        await save_session_data(user_id=user_id, session_id=session_id, data=session_data)
    # Nếu specialties chưa có nhưng session đã lưu từ trước → dùng lại
    elif session_data.get("suggested_clinics"):
        suggested_clinics = session_data.get("suggested_clinics")
    # Không có gì hết → để trống
    else:
        suggested_clinics = []

    # Ưu tiên lấy tất cả bắc sĩ từ cơ sỡ đó
    if clinic_id:
        suggested_doctors = get_doctors(clinic_id)
        session_data["suggested_doctors"] = suggested_doctors

        suggested_doctors = [{
            "doctor_id": d["doctor_id"],
            "full_name": d["full_name"],
            "specialty_name": d["specialty"],
            "biography": d["biography"],
            "clinic_id": clinic_id,
        } for d in suggested_doctors]

        session_data["suggested_doctors"] = suggested_doctors

        await save_session_data(user_id=user_id, session_id=session_id, data=session_data)

    # Nếu có bác sĩ dc lưu trong session thì lấy
    elif session_data.get("suggested_doctors"):
        suggested_doctors = session_data.get("suggested_doctors")
    else:
        suggested_doctors = []

    # Lay lich kham
    if doctor_id and clinic_id and specialty_id:
        schedules = get_doctor_schedules(
            doctor_id=doctor_id,
            clinic_id=clinic_id,
            specialty_id=specialty_id
        )
        if schedules:
            session_data["schedules_info"] = serialize_schedules(schedules)
            await save_session_data(user_id=user_id, session_id=session_id, data=session_data)
        else:
            schedules = []

    elif schedule_id:
        schedule_detail = get_schedule_by_id(schedule_id)
        if schedule_detail:
            schedules = [schedule_detail]  # Đưa về dạng list cho đồng nhất
            session_data["schedules_info"] = serialize_schedules(schedules)
            await save_session_data(user_id=user_id, session_id=session_id, data=session_data)
        else:
            schedules = []

    elif session_data.get("schedules_info"):
        schedules = session_data["schedules_info"]

    else:
        schedules = []


    
    # logger.info("🔍 Suggested clinics trước khi chuyền vào prompt:\n" + json.dumps(suggested_clinics, indent=2, ensure_ascii=False))
    safe_schedules = serialize_for_logging(schedules)

    # logger.info("🔍 lịch trích được trước khi chuyền vào prompt:\n" + json.dumps(safe_schedules, indent=2, ensure_ascii=False))
    
    status = booking_info.get("status", "")

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
        status=status,
    )

    import tiktoken
    encoding = tiktoken.encoding_for_model("gpt-4")
    token_count = len(encoding.encode(prompt))
    print("🔢 Token count:", token_count)

    # print("BOOKING PROMPT:" )
    # print(prompt)

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
    # 🔁 Reload session_data để chắc chắn lấy dữ liệu mới nhất
    session_data = await get_session_data(user_id=user_id, session_id=session_id)
    
    booking_info = session_data.get("booking_info", {})
    extracted = booking_info.get("extracted_info", {}) or {}

    # logger.info(f"📤 Thông tin trích xuất: {extracted}")

    status = booking_info.get("status", "")
    message = booking_info.get("message", "")

    should_insert = booking_info.get("should_insert", False)

    specialty = extracted.get("specialty_name")
    specialty_id = extracted.get("specialty_id")
    location = extracted.get("location")
    clinic_id = extracted.get("clinic_id")
    doctor_id = extracted.get("doctor_id")
    schedule_id = extracted.get("schedule_id")

    if isinstance(specialty, list):
        specialties = specialty
    elif specialty:
        specialties = [specialty]
    else:
        specialties = []

    # 🧾 Log giá trị truyền vào get_clinics và get_doctors_by_clinic
    logger.info(f"📥 Input to get_clinics → location: {location}, specialties: {specialties}")
    logger.info(f"📥 Input to get_doctors_by_clinic → clinic_id: {clinic_id}")

    # 🔍 Gợi ý phòng khám và bác sĩ
    suggested_clinics = get_clinics(location, specialties) if specialty else []
    suggested_doctors = get_doctors(clinic_id) if clinic_id else []

    # 🧾 Log kết quả
    # logger.info("👨‍⚕️ Suggested doctors:\n" + json.dumps(suggested_doctors, indent=2, ensure_ascii=False))


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
        await save_session_data(user_id=user_id, session_id=session_id, data=session_data)

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

        if not doctors:
            yield {"message": "Hiện không có bác sĩ nào phù hợp tại phòng khám này."}
            return

        suggested_doctors = [{
            "doctor_id": d["doctor_id"],
            "full_name": d["full_name"],
            "specialty_name": d["specialty"],
            "biography": d["biography"],
            "clinic_id": clinic_id,
        } for d in doctors]

        session_data["suggested_doctors"] = suggested_doctors
        await save_session_data(user_id=user_id, session_id=session_id, data=session_data)

        if len(doctors) > 1:
            names = ", ".join([d["full_name"] for d in doctors])
            yield {"message": f"{message}\n\n{suggested_doctors}"}
        else:
            yield {"message": message}
        return

    # Xác định lịch khám
    elif status == "incomplete_schedules_info":
        # Lấy lịch khám dựa vào thông tin sẵn có
        if doctor_id:
            # Ưu tiên lấy theo bác sĩ nếu đã rõ
            schedules = get_doctor_schedules(
                doctor_id=doctor_id,
                clinic_id=clinic_id,
                specialty_id=specialty_id
            )
        elif schedule_id:
            # Nếu chỉ có schedule_id → truy xuất chi tiết lịch khám
            schedule_detail = get_schedule_by_id(schedule_id)
            if not schedule_detail:
                yield {"message": "Không tìm thấy lịch khám tương ứng. Bạn muốn chọn lại không?"}
                return
            schedules = [schedule_detail]  # Đưa về dạng danh sách để xử lý thống nhất
        else:
            # Nếu không có đủ thông tin
            yield {"message": "Xin vui lòng chọn bác sĩ hoặc lịch khám trước khi tiếp tục."}
            return

        if not schedules:
            yield {"message": "Xin lỗi, hiện không có lịch khám nào phù hợp. Bạn muốn chọn lại thời gian khác không?"}
            return

        # Lưu thông tin lịch vào session
        session_data["schedules_info"] = serialize_schedules(schedules)

        # Nếu schedule chỉ có 1 thì không cần hiển thị lại danh sách
        if len(schedules) > 1:
            formatted_schedule = format_weekly_schedule(schedules)
            yield {
                "message": f"{message}\n\n{formatted_schedule}"
            }
        else:
            yield {
                "message": message
            }

        # Lưu lại session
        await save_session_data(user_id=user_id, session_id=session_id, data=session_data)
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
            yield {"message": message + "\n" + suggested}
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
            yield {"message": message + "\n" + "\n".join(formatted)}
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
            yield {"message": message + "\n" + suggestion}
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
    status: str,
) -> str:
    last_bot_msgs = recent_assistant_messages[-3:] if recent_assistant_messages else []
    last_user_msgs = recent_user_messages[-3:] if recent_user_messages else []
    # print("Các chuyên khoa:")
    # for specialty in all_specialty_names:
    #     print("-", specialty)
    extracted = booking_info.get("extracted_info", {}) or {}

    full_name = extracted.get("full_name", "").strip()
    phone = extracted.get("phone", "").strip()

    minimal_clinics = [
        {
            "clinic_id": c["clinic_id"],
            "clinic_name": c["clinic_name"],
            "address": c["address"]
        }for c in suggested_clinics
    ]

    # logger.info("🔍 Suggested clinics đã được chuyền vào prompt:\n" + json.dumps(minimal_clinics, indent=2, ensure_ascii=False))

    specialties_str = ", ".join(f'"{s}"' for s in all_specialty_names)
    extracted = booking_info.get("extracted_info", {}) or {}

    # logger.info("latest_bot_message" + json.dumps(last_bot_msgs, ensure_ascii=False))
    # logger.info("latest_user_message" + json.dumps(last_user_msgs, ensure_ascii=False))

    # "suggested_clinics": {json.dumps(suggested_clinics, ensure_ascii=False)},
    # Nhiệm vụ và cách giả trị dã có
    prompt = f"""
        You are a smart assistant helping users schedule medical appointments in Vietnam.

        ### 📋 CONTEXT (structured as JSON):

        
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
        "available_schedules": {json.dumps(serialize_schedules(schedules), ensure_ascii=False)},
        "available_doctors": {json.dumps(suggested_doctors, ensure_ascii=False)}
        

        ### 🎯 SYSTEM INSTRUCTION:

        You are a medical appointment assistant. Follow these rules **strictly and step-by-step**, and DO NOT skip ahead.
        If full_name and phone already exist in extracted_info, you MUST assume they are already collected, even if user just sent them.
        Do NOT ask again.

        Only ask what's still missing.

    """.strip()

    if (not full_name or not phone) and status != "incomplete_clinic_info":
        print("⚠️ Thiếu họ tên hoặc số điện thoại.")
        prompt += f"""
            ------------------------------------------------------------------

            🔥 CRITICAL RULE (MUST FOLLOW):

            → As soon as all required fields are present in `extracted_info`:
            - specialty_name (non-empty)
            - full_name (non-empty)
            - phone (non-empty)

            ✅ Then you MUST:
            - Set "status": "incomplete_clinic_info"
            - Do NOT keep "status": "incomplete_info"
            - Do NOT ask any more questions

            This rule applies EVEN IF the last required field (e.g., phone) was just extracted from the latest user message.

            🚫 Failure to update status will break the booking process.

            Set "status": "incomplete_info" if:
            - 'specialty_name' is not determined
            OR
            - Any required fields are missing: full_name, phone
            (Note: location is optional *only if the user refuses or cannot provide it clearly*.
            → You MUST still ask for location if it is missing. Only skip if the user gives vague or refusing answers.)

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
            → Return a list of relevant 'specialty_name' values (if any), each mapped to its corresponding "specialty_id".
            → For example, if the prediction includes “đau ngực” and “khó thở”, the result might be: ["Tim mạch", "Hô hấp"].
            → Each 'specialty_name' must strictly match one of: [{specialties_str}].
            → If multiple specialties apply, provide a short and clear list (maximum 3 items) and ask the user to choose one.
            → Example message to user (in Vietnamese):
            - “Triệu chứng của bạn có thể liên quan đến các chuyên khoa sau: Tim mạch, Hô hấp. Bạn muốn đặt khám ở khoa nào?”
            → Do NOT auto-select a specialty for the user unless they explicitly ask for a recommendation.

            ⚠️ Only include medical specialties in the `specialty_name` list.
            Do NOT include locations, dates, times, or any unrelated strings.
            The values in `specialty_name` must only come from the predefined list: [{specialties_str}].
            Do NOT add any inferred patterns like "%TP.HCM%" or similar — this is invalid.

            STEP 3 — Required Field Check
            ❗ You MUST follow this strict order when checking for missing information:

            1️⃣ First, ensure `specialty_name` is extracted.
                - If not available, STOP and ask (via STEP 1 or 2).
                - Do NOT proceed to any other fields until `specialty_name` is available.

            2️⃣ Then check `location`.
                - If missing, ask user for their location in a polite, natural way.
                - Normalize to one of the known database values like “TP.HCM”, “Hà Nội”, etc.

            3️⃣ Once both `specialty_name` and `location` are known, check:
                - `full_name` → then → `phone`

            Only proceed to the next step once the previous field is filled or skipped.

            Check `extracted_info` for missing fields. A field is considered missing if null, empty string (""), or not present.

            Required fields:
            - location
            - full_name
            - phone

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
            - "tp hcm", "tphcm", "hcm", "Sài Gòn", "minh song o tphcm" → "TP.HCM"
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

            ✅ As soon as all required fields are filled — meaning:
            - `specialty_name`
            - `location`
            - `full_name`
            - `phone`

            → You MUST immediately set:
            "status": "incomplete_clinic_info"

            → This MUST be done **even if** the final field (e.g., phone) was just extracted from the latest user message.

            → Do NOT keep "status": "incomplete_info" in this case.

            ⚠️ GPT Reminder:

            You are NOT just reading existing values from `extracted_info`.

            You are also responsible for recognizing what fields you have JUST extracted in the current user message.

            → If, as a result of this turn, a previously missing required field (such as `phone` or `full_name`) is now extracted, and all other required fields are already filled:

            ✅ Then you MUST:
            - Immediately switch "status" to "incomplete_clinic_info"
            - Do NOT keep or return "status": "incomplete_info"

            🚫 Do NOT ignore that a field (like phone) was just extracted in this turn. You must treat that as having completed the required info.


            → Do NOT ask further questions. The backend will handle the next step.


        """.strip()
    
    elif status == "incomplete_clinic_info":
        print("Chọn bệnh viện khám")
        prompt += f"""
            ------------------------------------------------------------------
            STEP 4. Set status = "incomplete_clinic_info" only if:
                - 'specialty_name' is known
                - Both "full_name" and "phone" are already provided in 'extracted_info'

            🔒 This is a required step in the booking pipeline. GPT **must execute this step** and return exactly as instructed.

            ❌ You may not skip or shortcut this step.

            In this step you are a careful assistant helping match a clinic.

            The list of available clinics "suggested_clinics" is below (each with: `clinic_id`, `clinic_name`, `address`):

            {json.dumps(minimal_clinics, indent=2, ensure_ascii=False)}

            Based ONLY on the user message: "{last_user_msgs}"

            → Identify if there's a best match by **clinic name** or **address**
            → If match found: return both `clinic_id` and `clinic_name`
            ❗UNDER NO CIRCUMSTANCES may you include `"address"` in the output.

            → If multiple match: leave blank and ask to clarify
            → If no match: leave blank and ask user to reselect

            ⚠️ STRICT RULES — DO NOT:
            - Generate or relist the clinics again (the UI already did this)
            - Return `address` — only return `clinic_id`and `clinic_name`

            ---

            ✅ You MUST match based on one of the following:

            - Clinic name mentioned in the user reply (e.g., “Chợ Rẫy”, “Hòa Hảo”, "cho ray", "benh vien cho ray"...)
            - Partial address match (e.g., “Nguyễn Thị Minh Khai”, “Quận 5”,"Q6","q5"...)
            - Generic confirmation (e.g., “ok”, “đúng rồi”, “chỗ đó”,"được", "duoc") **only if** `suggested_clinics` has exactly ONE clinic

            ---

            📌 Picking method:

            - Normalize user input (lowercase, remove accents if needed)
            - Compare to both `clinic_name` and `address` of each clinic
            - Select the **best matching clinic_id**
            - If multiple clinics partially match, do not pick — ask the user to clarify

            ❗CRITICAL:

            - If the user reply matches exactly one clinic in `suggested_clinics`, you MUST return its `clinic_id` and 'clinic_name'.

            ---

            ⚙️ Output Rules:

            1. ✅ **Exactly ONE match** found:
            ```json
            "extracted_info": {{
                "clinic_id": "2",
                "clinic_name":"Bệnh viện Chợ Rẫy"
            }}

            2. ⚠️ **Multiple matches** found:
            "extracted_info": {{
                "clinic_id": "",
                "clinic_name":""
            }}
            → Ask the user to clarify by full name or exact location

            3. ❌ **No match** found:
                ```json
                "clinic_id": ""
                ```
            → Ask user to reselect from the shown list already show by UI

            4. ✅ Only ONE clinic in suggested_clinics + generic confirmation:
            → Accept and return its clinic_id immediately

            ---

            🧪 Example (valid output when user said "Chợ Rẫy"):

            `suggested_clinics`:
            ```json
            [
            {{
                "clinic_id": 2,
                "clinic_name": "Bệnh viện Chợ Rẫy",
                "address": "201B Nguyễn Chí Thanh, Quận 5, TP.HCM"
            }},
            ...
            ]
            📘 Examples of Matching:

            🟢 Case 1 — Match by clinic name:
            - User reply: "cho ray", "bệnh viện chợ rẫy", "Chợ Rẫy"
            - Match with:
            {{
                "clinic_id": 2,
                "clinic_name": "Bệnh viện Chợ Rẫy",
                "address": "201B Nguyễn Chí Thanh, Quận 5, TP.HCM"
            }}
            - Output:
            ```json
            "extracted_info": {{
                "clinic_id": "2",
                "clinic_name":"Bệnh viện Chợ Rẫy"
            }}

            🟢 Case 2 — Match by partial address:
            - User reply: "Nguyễn Thị Minh Khai" Or "Quận 1" Or "Pasteur"
            - Match with:
            {{
                "clinic_id": 5,
                "clinic_name": "Phòng khám đa khoa Pasteur",
                "address": "27 Nguyễn Thị Minh Khai, Quận 1, TP.HCM"
            }}
            - Output:
            ```json
            "extracted_info": {{
                "clinic_id": "5",
                "clinic_name": "Phòng khám đa khoa Pasteur"
            }}

            🟢 Case 3 — One clinic only + confirmation:
            - suggested_clinics has only 1 clinic.
            - User reply: "ok", "đúng rồi", "chọn chỗ đó", "được", "đặt luôn"
            → Accept that clinic.

            🔴 Case 4 — Multiple possible matches:
            - User reply: "quận 5" → matches Chợ Rẫy and another clinic also in Quận 5.
            → Ambiguous. Leave fields empty. Ask user to confirm by full name.

            🔴 Case 5 — No match:
            - User reply: "Vinmec"
            → Not in suggested_clinics. Leave fields empty. Ask user to select again.

            🧷 FINAL INSTRUCTION (non-negotiable):

            → If you successfully identify **exactly ONE matching clinic** from `suggested_clinics` based on the user's **latest message**:

            - You MUST return:
                - `"clinic_id"` and `"clinic_name"` inside `"extracted_info"`
                - And set `"status": "ask_for_doctor_or_schedules"`

            ❗This MUST be done **immediately**, even if the match just occurred from the current message.

            ❗Do NOT keep `"status": "incomplete_clinic_info"` in this case.

            Your output **must include**:

            ```json
            {{
                "extracted_info": {{
                    "clinic_id": "<string clinic_id>",
                    "clinic_name": "<string clinic_name>"
                }},
                "status": "ask_for_doctor_or_schedules"
            }}

            → If no confident match is found (zero or multiple), leave "clinic_id" and "clinic_name" empty, and set:

            "status": "incomplete_clinic_info"

            ⚠️ Do NOT keep "status" as "incomplete_clinic_info" once a valid clinic is identified.

            ⚠️ Do NOT skip the "status" field under any circumstance.

        """.strip()
        
    elif status == "ask_for_doctor_or_schedules":
        prompt += f"""
            ------------------------------------------------------------------
            STEP 6A. Determine Next Action (Doctor vs. Schedule)

            You are currently at `"status": "ask_for_doctor_or_schedules"`.  
            The user has already selected a specialty and a clinic.

            → Your goal is to determine the user’s intent:
            Do they want to:
            1. Choose a **doctor**?
            2. Choose a **time/schedule**?
            3. Or have not expressed a clear intent yet?

            Ask user "What you want to see to show the list" not help them to choose.

            You MUST analyze `last_user_messages` to identify their preference.

            ▶️ If the user message contains **intent to choose a doctor**, such as:

                - "Mình muốn chọn bác sĩ"
                - "Bệnh viện này có bác sĩ nào?"
                - "Cho mình xem danh sách bác sĩ"
                - "Tôi muốn khám với bác sĩ Linh"
                - "Có bác sĩ nữ không?"
                - "Ai giỏi nhất ở đây?"
                - "cho minh xem danh sach bac si di"

            → Then:
            - Set `"status": "incomplete_doctor_info"`
            - Do **not** repeat any previous messages.
            - The backend will show the list of doctors based on the current `clinic_id`.

            ▶️ If the user message contains **intent to choose a schedule**, such as:

                - "Cho mình xem lịch khám"
                - "Có khám thứ Bảy không?"
                - "Mình rảnh chiều mai"
                - "Mình muốn đặt lịch vào sáng thứ Hai"
                - "Có lịch khám vào cuối tuần không?"

            → Then:
            - Set `"status": "incomplete_schedules_info"`
            - Do **not** repeat any previous messages.
            - The backend will show the available schedules for this clinic and specialty.

            ▶️ If the user does **not express a clear intent**, you MUST ask them in a helpful, polite, and natural tone. Example:

            > "Bạn muốn chọn bác sĩ trước, hay xem lịch khám trước ạ? Mình sẽ hiển thị danh sách tương ứng để bạn lựa chọn nhé."

            → Keep `"status": "ask_for_doctor_or_schedules"` in this case.

            🔒 Critical Notes:

            - 🚫 Do **NOT** set `status` back to `"incomplete_info"` at this point.
            - 🚫 Do **NOT** ask again for full_name, phone, or location.
            - 🚫 Do **NOT** clear any previously extracted info.
            - 🧠 The system will **automatically show** the doctor or schedule list based on the selected status.

            ✅ This step ensures the booking continues smoothly toward completion.

        """.strip()

    elif status == "incomplete_doctor_info":
        logging.info("🧑‍⚕️ Danh sách bác sĩ gợi ý: %s", suggested_doctors)
        prompt += f"""
            ------------------------------------------------------------------
            STEP 6B. If "status" is "incomplete_doctor_info":

            When the user chooses a doctor, you MUST determine their intent and extract doctor information **only from the current `suggested_doctors` list**.

            User input (in `last_user_msgs`) may be:
            - A full doctor name (e.g., "Nguyễn Hoàng Nam")
            - A partial name (e.g., "Dr Linh", "bác sĩ Nam", "Nam")
            - A natural sentence that includes confirmation and a name (e.g., "ok đặt bác sĩ Linh", "chọn bác sĩ Nam", "đặt lịch bác Hoàng")
            - A generic confirmation (e.g., "ok", "sure", "choose that doctor", "yes") — only if there is **exactly one doctor** in the list

            You MUST:

            - Normalize the user input:
            - Remove accents
            - Convert to lowercase
            - Remove polite or functional phrases that do not help identify the doctor (e.g., "dr", "bác sĩ", "bs", "đặt", "chọn", "ok", "luôn", etc.)
            - Focus on identifying the meaningful name inside the sentence

            - Then, compare the cleaned result with each `doctor["doctor_name"]` (also normalized the same way)

            ### Matching logic:

            - ✅ If exactly one match is found:
            - Set `"doctor_id"` and `"doctor_name"` in `extracted_info`
            - Set `"status"` to `"incomplete_schedules_info"`
            - Prompt user to choose a schedule (e.g., "Bạn muốn đặt vào thời gian nào?")

            - ⚠️ If multiple matches:
            - DO NOT set `"doctor_id"` or `"doctor_name"`
            - Ask the user to clarify using the full doctor name

            - 🔄 If there is only ONE doctor in `suggested_doctors` and the user gives a generic confirmation:
            - Automatically select that doctor
            - Set `"status"` to `"incomplete_schedules_info"`
            - Prompt for schedule selection

            ❗CRITICAL WARNING:  
            You MUST extract `doctor_id` only from the current `suggested_doctors` list.  
            If `doctor_id` is missing or invalid, the booking flow will break.  
            DO NOT proceed to schedule selection without a valid doctor.

            🧷 Final instruction:
            - Check both the current `extracted_info` and new info from the user message.
            - If both `doctor_id` and `schedule_id` are known, then set `"status"` to `"complete"` and stop asking further.

            🚫 Do NOT ask any additional questions.
            🚫 Do NOT leave `"status"` in `"incomplete_info"` or any other intermediate state once both are known.

            """.strip()
    
    elif status == "incomplete_schedules_info":
        logging.info("📅 Danh sách lịch khám gợi ý: %s", schedules)
        prompt += f"""
            ------------------------------------------------------------------
            STEP 6C. If `status == "incomplete_schedules_info"`:

            📥 Once the user chooses a date/time in `last_user_msgs` for an appointment:

            - Parse the user's message to extract `schedule_time` (e.g., “thứ hai tuần sau”, “sáng mai”, “14h ngày 12/7”)

            🔍 Then search for matching schedule(s) from the provided `available_schedules`, using:

            - ✅ Required: `clinic_id`
            - ⚠️ Optional: `doctor_id` (if it was selected earlier)

            🎯 Matching outcomes:

            1. ❌ **No matching schedules found**:
            - Reply:
                > *"Xin lỗi, không có lịch khám nào phù hợp với thời gian đó. Bạn có muốn chọn thời gian khác không?"*
            - Do not set `schedule_id`

            2. ⚠️ **Multiple matching schedules (across different doctors)**:
            - Reply:
                > *"Có nhiều bác sĩ phù hợp với thời gian này. Bạn muốn chọn bác sĩ nào?"*
            - Only allow selection from `available_doctors`
            - Do not set `schedule_id` yet
            - Keep `status = "incomplete_doctor_info"`

            3. ✅ **Exactly one matching schedule found**:
            - Immediately set:
                ```json
                "schedule_id": "...",
                "doctor_id": "...",
                "doctor_name": "...",
                "status": "complete"
                ```
            - Reply:
                > *"Đã tìm thấy lịch khám phù hợp. Đang tiến hành đặt lịch..."*


            4. ✅ **Only one available schedule in total** (not just a match):
            - Reply:
                > *"Mình tìm được một lịch khám duy nhất là [day_of_week] lúc [start_time]. Bạn có muốn đặt lịch này không?"*
            - If user confirms:
                - You MUST return:
                ```json
                "schedule_id": "...",
                "doctor_id": "...",
                "doctor_name": "...",
                "status": "complete"
                ```

            ❗CRITICAL WARNING:  
            - All logic must be strictly based on the provided `available_schedules`.
            - If you detect only **one valid schedule** and the user confirms, but you **fail to set `status = "complete"`**, the system will crash.
            - Do NOT hallucinate or invent any schedule outside the provided list.

        """.strip()

    elif status == "complete":
        print("Xác nhận lịch khám")
        prompt += f"""
            ------------------------------------------------------------------
            STEP 7. Final Booking Confirmation

            ✅ All required information has been collected successfully.
            → Do NOT repeat or display booking details again — the UI has already shown them.

            Please now analyze the user's latest message to determine if:

            1️⃣ They clearly confirm the booking (e.g., "ok", "xác nhận", "đặt luôn", "đồng ý", etc.)
                → Then you MUST set:
                - "status": "confirmed"
                - "should_insert": true
                - Respond with a warm and polite confirmation message in Vietnamese

            2️⃣ They want to modify any part of the booking (e.g., doctor, schedule, clinic, or specialty)
                → Then you MUST set:
                - "status": "modifying_info"

            ❌ If there's any ambiguity, do NOT confirm yet.
            → Wait for user clarification before setting `"status": "confirmed"`.

            ⚠️ CRITICAL:
            - Do NOT ask for confirmation again if the user already gave a clear yes.
            - Do NOT proceed to confirmation if they mention wanting to change anything.
        """.strip()

    elif status == "modifying_info":
        prompt += f"""
            ------------------------------------------------------------------
            STEP 8. Modify Booking Information (User-Initiated)

            The user wants to change a specific part of their booking (e.g., doctor, schedule, clinic, or specialty).

            → You MUST:
            1️⃣ Analyze the user's latest message to determine **which field** they want to modify.
                - Set `"modification_target"` as one of: "doctor", "schedule", "clinic", or "specialty"

            2️⃣ Respond with a friendly Vietnamese message asking them to provide the new value for that specific field.

            3️⃣ Wait for the user's reply with the updated information.

            4️⃣ Once the user provides the new value:
                - Update that specific field in `extracted_info`
                - Do NOT change unrelated fields

            5️⃣ Check if all required booking information is now complete:
                - full_name
                - phone
                - location
                - specialty_name
                - clinic_id
                - doctor_id
                - schedule_id

                → If ALL of these are present and valid:
                - You MUST set `"status": "complete"`

            🧷 Important Rules:
            - You MUST infer `modification_target` directly from the user’s message.
            - Do NOT ask "What do you want to change?" — infer and act.
            - Only ask for the updated value once the target is identified.
            - you do NOT need to generate a detailed or user-facing message.
            - The frontend/UI will handle communication.

            ✅ When the user finishes providing the new information, and the booking is complete again:
            - Set `"status": "complete"`
            - Prepare to reconfirm in the next step.
        """.strip()

    elif status == "confirmed":
        prompt += f"""
            ------------------------------------------------------------------
            STEP 9. Final Confirmation

            The user has clearly confirmed the booking without requesting any change.

            → You MUST:
            - Set "status": "confirmed"
            - Set "should_insert": true
            - Respond with a warm, friendly confirmation message in Vietnamese.

            ✅ The system will now insert the appointment into the database.
        """.strip()

    prompt += f"""

        ⚠️ DO NOT modify or re-analyze `specialty_name` after STEP 2.
        → From STEP 3 onward, `specialty_name` is considered FINAL and must NOT be altered.
        → Absolutely NEVER add values like "%tphcm%", city names, or anything inferred from `location`.

        ### 📦 Output format (MUST be JSON):
        {{
            "status": "waiting_complete_info"| "incomplete_info" | "incomplete_clinic_info" | "ask_for_doctor_or_schedules" | "incomplete_doctor_info" | "incomplete_schedules_info" | "complete" | "modifying_info" | "confirmed",
            "request_appointment_time": true | false,
            "modification_target": "doctor" | "schedule" | "clinic" | "specialty" | null, ← only for `modifying_info`
            "extracted_info": {{
                "full_name": "...",
                "phone": "...",
                "location": "...",
                "specialty_id": ["..."],
                "specialty_name": ["..."],  
                "clinic_id": ["..."],
                "clinic_name": "...",
                "schedule_id": ["..."],
                "doctor_id": ["..."],
                "doctor_name": ["..."]
            }},
            "message": "Câu trả lời thân thiện bằng tiếng Việt",
            "should_insert": true | false
        }}

        ⚠️ Output only valid JSON — no explanations or markdown.    
    """.strip()
    
    
    
    
    #Step 7 - Display all extracted info for confirmation.

    # logger.info("📄 Full booking prompt:\n" + prompt)

    return prompt

# Kiểm tra thông tin con thiếu khi đặt lịch
async def get_missing_booking_info(user_id: int = None, extracted: dict = {}) -> dict:
    extracted = extracted or {}

    full_name = extracted.get("full_name")
    phone = extracted.get("phone")
    location = extracted.get("location")

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
                    full_name = full_name or row[0]
                    phone = phone or row[1]
        finally:
            conn.close()

    return {
        "full_name": full_name or "",
        "phone": phone or "",
        "location": location or ""
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
                    ui.full_name as doctor_name,
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
    Trả về thông tin lịch khám + định dạng dễ hiểu (ngày, giờ, buổi), bao gồm doctor_id và dịch ngày sang tiếng Việt.
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
                SELECT doctor_id, day_of_week, start_time, end_time
                FROM doctor_schedules
                WHERE schedule_id = %s
                LIMIT 1
            """, (schedule_id,))
            row = cursor.fetchone()

            if not row:
                return {}

            doctor_id = row["doctor_id"]
            day_en = row["day_of_week"]
            start = row["start_time"]
            end = row["end_time"]

            day_vi = EN_TO_VI_DAY_MAP.get(day_en, day_en)

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
                "doctor_id": doctor_id,
                "day_of_week": day_vi,
                "start_time": start.strftime("%H:%M"),
                "end_time": end.strftime("%H:%M"),
                "period": period,
                "formatted": f"{period} {day_vi} ({start.strftime('%H:%M')} - {end.strftime('%H:%M')})"
            }
    finally:
        conn.close()

def format_time(value):
    if isinstance(value, timedelta):
        total_seconds = int(value.total_seconds())
        hours = total_seconds // 3600
        minutes = (total_seconds % 3600) // 60
        return f"{hours:02}:{minutes:02}"
    elif isinstance(value, (time, datetime)):
        return value.strftime("%H:%M")
    else:
        return str(value)  # fallback nếu cần debug

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
        start = format_time(s["start_time"])
        end = format_time(s["end_time"])
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
    elif isinstance(obj, timedelta):
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

def serialize_schedules(schedules: list[dict]) -> list[dict]:
    result = []
    for s in schedules:
        s = s.copy()  # tránh modify trực tiếp
        if isinstance(s.get("start_time"), (time, timedelta)):
            s["start_time"] = str(s["start_time"])
        if isinstance(s.get("end_time"), (time, timedelta)):
            s["end_time"] = str(s["end_time"])
        result.append(s)
    return result

