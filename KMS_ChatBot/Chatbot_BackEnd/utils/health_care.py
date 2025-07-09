import json
import pymysql
from datetime import date
import logging
import re
import asyncio
logger = logging.getLogger(__name__)
from config.config import DB_CONFIG
from utils.openai_utils import stream_gpt_tokens
from utils.openai_client import chat_completion
from utils.symptom_utils import (
    get_symptom_list, 
    extract_symptoms_gpt, 
    generate_related_symptom_question, 
    save_symptoms_to_db, 
    get_related_symptoms_by_disease, 
    generate_symptom_note, 
    update_symptom_note,
    get_saved_symptom_ids
)
from prompts.prompts import build_diagnosis_controller_prompt, build_KMS_prompt
from utils.text_utils import normalize_text
from utils.session_store import (
    get_symptom_notes_from_session, 
    update_symptom_note_in_session, 
    save_session_data, get_session_data, 
    get_followed_up_symptom_ids, mark_followup_asked, 
    save_symptoms_to_session, get_symptoms_from_session,
    mark_related_symptom_asked
)


import json

def extract_json(text: str) -> str:
    """
    Tách block JSON đầu tiên hợp lệ từ một đoạn text (không dùng đệ quy regex).
    """
    start = text.find('{')
    while start != -1:
        for end in range(len(text) - 1, start, -1):
            try:
                candidate = text[start:end + 1]
                parsed = json.loads(candidate)
                return candidate  # ✅ JSON hợp lệ đầu tiên
            except json.JSONDecodeError:
                continue
        start = text.find('{', start + 1)
    return '{}'




# Hàm mới dùng prompt tổng
async def health_talk(
    user_message: str,
    stored_symptoms: list[dict],
    recent_messages: list[str],
    recent_user_messages: list[str], 
    recent_assistant_messages: list[str],
    session_id=None,
    user_id=None,
    chat_id=None,
    session_context: dict = None
):
    symptom_notes_list = []
    session_data = await get_session_data(user_id=user_id, session_id=session_id)
    logger.debug("📦 Session ban đầu:\n%s", json.dumps(session_data, indent=2, ensure_ascii=False))

    # Step 1: Trích triệu chứng mới
    new_symptoms, fallback_message = extract_symptoms_gpt(
        user_message,
        recent_messages=recent_messages,
        recent_assistant_messages=recent_assistant_messages
    )
    logger.info("🌿 Triệu chứng trích được: %s", new_symptoms)

    if new_symptoms:
        stored_symptoms += [
            s for s in new_symptoms if s["name"] not in {sym["name"] for sym in stored_symptoms}
        ]
        save_symptoms_to_session(user_id, session_id, stored_symptoms)
        stored_symptoms = await get_symptoms_from_session(user_id, session_id)

    # Step 2: Lấy related symptom + câu hỏi followup
    inputs = await decide_KMS_prompt_inputs(session_id=session_id, user_id=user_id)

    # ✅ In log triệu chứng đã hỏi follow-up
    asked = await get_followed_up_symptom_ids(session_id=session_id, user_id=user_id)
    logger.info("📎 Follow-up IDs từ session: %s", asked)

    had_conclusion = session_data.get("had_conclusion", False)

    # Step 3: Xây prompt tổng hợp
    prompt = build_KMS_prompt(
        SYMPTOM_LIST=get_symptom_list(),
        user_message=user_message,
        stored_symptoms_name=[s["name"] for s in stored_symptoms],
        symptoms_to_ask=inputs["symptoms_to_ask"],
        recent_user_messages=recent_user_messages,
        recent_assistant_messages=recent_assistant_messages,
        related_symptom_names=inputs["related_symptom_names"],
        session_context=session_context,
        had_conclusion=had_conclusion
    )


    # 🔒 Đánh dấu đã hỏi related symptom (chỉ 1 lần duy nhất)
    if inputs.get("related_symptom_names"):
        await mark_related_symptom_asked(session_id=session_id, user_id=user_id)
        session_data = await get_session_data(user_id=user_id, session_id=session_id)

    # Step 4: Gọi GPT (non-stream)
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

    # 🔍 Ghi lại flag gợi ý sản phẩm nếu có
    if "should_suggest_product" in parsed:
        session_data["should_suggest_product"] = parsed["should_suggest_product"]
        logger.info("💡 Đã lưu flag gợi ý sản phẩm:\n%s", json.dumps({
            "should_suggest_product": parsed["should_suggest_product"],
        }, ensure_ascii=False))
        await save_session_data(user_id=user_id, session_id=session_id, data=session_data)


    message = parsed.get("message", fallback_message or "Xin lỗi, mình chưa hiểu rõ lắm...")

    # Step 5: Điều phối logic từ parsed JSON
    message = parsed.get("message", fallback_message or "Bạn có thể nói rõ hơn về tình trạng của mình không?")

    action = parsed.get("action")
    next_action = parsed.get("next_action")

    # ✅ Log theo logic thực tế đang xử lý
    if action == "diagnosis" or next_action == "diagnosis":
        logger.info("🎯 Action (effective): diagnosis")
    else:
        logger.info("🎯 Action: %s", action)

    # ✅ Ghi nhận kết luận để đánh dấu đã chẩn đoán hôm nay
    if action == "diagnosis":
        session_data["had_conclusion"] = True

    # 🔄 Nếu người dùng nói thêm về triệu chứng cũ → ghi chú lại vào user_symptom_history
    updated_symptom = parsed.get("updated_symptom")
    diagnosed_today = session_context.get("diagnosed_today", False) if session_context else False
    logger.info(f"⚙️ diagnosed_today = {diagnosed_today}")

    # Update note triệu chứng vào db nếu người dùng có bỏ sung thêm
    if updated_symptom and diagnosed_today:
        logger.info(f"Updated symptom = {updated_symptom}")
        try:
            success = update_symptom_note(
                user_id=user_id,
                symptom_name=updated_symptom,
                user_message=user_message
            )
            if success:
                logger.info(f"📝 Đã cập nhật ghi chú triệu chứng: {updated_symptom}")
        except Exception as e:
            logger.error(f"❌ Lỗi khi cập nhật ghi chú triệu chứng {updated_symptom}: {e}")

    target_followup_id = inputs.get("target_followup_id")
    # Đặt cơ cho những triệu chứng tương ứng khi followup đã hỏi
    if action == "followup" and target_followup_id:
        logger.info("✅ Đánh dấu đã hỏi follow-up triệu chứng ID: %s", target_followup_id)
        await mark_followup_asked(user_id, session_id, [target_followup_id])
        session_data = await get_session_data(user_id=user_id, session_id=session_id)
        # logger.info("✅ Session sau khi đánh dấu follow-up:\n%s", json.dumps(session_data, indent=2, ensure_ascii=False))


    end = parsed.get("end", False)

    # Nếu không có chẩn đoán trước đó trong ngày thì sẽ tạo note dựa theo triệu chứng
    # nếu đã chẩn đoán thì sẽ không tạo note mới
    if not diagnosed_today:
        # 📋 Tạo note
        # Step 1: lấy note cũ từ session
        existing_notes = session_data.get("symptom_notes_list", [])

        # Step 2: gọi GPT để lấy note mới (có thể chỉ 1-2 cái)
        new_notes = await generate_symptom_note(
            symptoms=stored_symptoms,
            recent_messages=recent_messages,
            existing_notes=existing_notes
        )

        # Step 3: gộp lại (override nếu có id trùng)
        note_map = {n["id"]: n for n in existing_notes}
        for n in new_notes:
            note_map[n["id"]] = n  # override or add

        symptom_notes_list = list(note_map.values())

        logger.info("📋 Updated symptom_notes_list:\n%s", json.dumps(symptom_notes_list, indent=2, ensure_ascii=False))

        # Step 4: lưu vào session
        session_data["symptom_notes_list"] = symptom_notes_list
        await save_session_data(user_id=user_id, session_id=session_id, data=session_data)

    # print("bệnh mới:", parsed.get("diseases", []))

    # Nếu action là chẩn đoán thì sẽ lưu kết quả vào DB
    # Và gọi hàm update_prediction_today_if_exists để cập nhật dự đoán bệnh hôm nay
    if action == "diagnosis" or parsed.get("next_action") == "diagnosis":
        # logger.info("🩺 Action là diagnosis hoặc next_action là diagnosis → lưu kết quả chẩn đoán.")
        update_prediction_today_if_exists(
            user_id=user_id,
            stored_symptoms=stored_symptoms,
            diseases=parsed.get("diseases", []),
            symptom_notes_list=symptom_notes_list,
            diagnosed_today=diagnosed_today,
            chat_id=chat_id
        )
        if parsed.get("next_action") == "diagnosis" and not action == "diagnosis":
            update_prediction_details(
                user_id=user_id,
            )
    

    # nếu action là post-diagnosis và next_action là diagnosis
    # thì sẽ tách message tại điểm DIAGNOSIS_SPLIT
    # nếu không có thì sẽ stream message bình thường
    if action == "post-diagnosis" and parsed.get("next_action") == "diagnosis":
        # Step 1: Lấy message đầy đủ
        full_message = parsed.get("message", "")

        logger.info("Raw message before split: %s", full_message)

        # 🔍 Tách tại điểm đánh dấu DIAGNOSIS_SPLIT
        split_point = full_message.find("DIAGNOSIS_SPLIT")


        if split_point != -1:
            message_1 = full_message[:split_point].strip()
            message_2 = full_message[split_point + len("DIAGNOSIS_SPLIT"):].strip()

            # Stream phần đầu
            for chunk in stream_gpt_tokens(message_1):
                yield chunk
                await asyncio.sleep(0.03)

            # Chờ như người suy nghĩ
            await asyncio.sleep(1.2)

            # Stream phần sau
            for chunk in stream_gpt_tokens(message_2):
                yield chunk
                await asyncio.sleep(0.03)

            # ✅ Sau đó xử lý tiếp phần bệnh nếu có
            # ... (giữ nguyên đoạn diagnosis bạn đã có)

            return  # dừng tại đây không cần yield tiếp nữa


    # Step 6: Stream message từng đoạn ra ngoài
    for chunk in stream_gpt_tokens(message):
        yield chunk 
        await asyncio.sleep(0.065)  # Giữ tốc độ stream mượt mà

# Trả về các dữ liệu cần thiết để truyền vào build_KMS_prompt:
# - stored_symptoms
# - raw_followup_question: danh sách triệu chứng kèm câu hỏi follow-up
# - related_symptom_names: tên các triệu chứng liên quan nếu không còn follow-up
async def decide_KMS_prompt_inputs(session_id: str, user_id: int):
    stored_symptoms = await get_symptoms_from_session(user_id, session_id)
    next_symptom = await get_next_symptom_to_followup(session_id, user_id, stored_symptoms)

    symptoms_to_ask = [next_symptom["name"]] if next_symptom else []
    related_symptom_names = None  # ✅ Khởi tạo mặc định

    logger.info("📭 symptoms_to_ask: %s", symptoms_to_ask)

    if not symptoms_to_ask:
        session = await get_session_data(session_id=session_id, user_id=user_id)
        if not session.get("related_symptom_asked"):
            symptom_ids = [s['id'] for s in stored_symptoms]
            related = get_related_symptoms_by_disease(symptom_ids)
            stored_names = [s["name"] for s in stored_symptoms]
            related_names = [s["name"] for s in related if s["name"] not in stored_names]
            related_symptom_names = list(set(related_names))[:10] or None  # None nếu không còn

    return {
        "symptoms_to_ask": symptoms_to_ask,
        "raw_followup_question": None,
        "related_symptom_names": related_symptom_names,
        "target_followup_id": next_symptom["id"] if next_symptom else None
    }

# Chọn đúng 1 triệu chứng chưa hỏi follow-up, sau đó truyền vào GPT để nó tự hỏi theo kiểu tinh tế từng bước
async def get_next_symptom_to_followup(session_id: str, user_id: int, stored_symptoms: list[dict]) -> dict | None:
    """
    Trả về dict dạng: {"name": "Tên triệu chứng chưa hỏi follow-up"}
    hoặc None nếu không còn triệu chứng nào cần hỏi.
    """
    if not stored_symptoms:
        return None

    # Lấy danh sách ID đã hỏi follow-up từ session
    already_asked_ids = set(await get_followed_up_symptom_ids(user_id=user_id, session_id=session_id))
    
    # Tìm triệu chứng chưa hỏi follow-up
    for s in stored_symptoms:
        if s["id"] not in already_asked_ids:
            return {"name": s["name"], "id": s["id"]}

    return None

# Lấy những câu hỏi liên quan tới triệu chứng từ DB
async def get_followup_question_fromDB(symptom_ids: list[int], user_id: int, session_id: str = None) -> dict | None:
    if not symptom_ids:
        return None
    # Lấy danh sách symptom_id đã hỏi từ session
    already_asked = set()
    if session_id:
        already_asked = set(await get_followed_up_symptom_ids(user_id=user_id, session_id=session_id))

    # Lọc ra những symptom_id chưa hỏi
    ids_to_ask = [sid for sid in symptom_ids if sid not in already_asked]
    if not ids_to_ask:
        return None

    # Truy vấn DB để lấy câu hỏi follow-up
    conn = pymysql.connect(**DB_CONFIG)
    try:
        with conn.cursor() as cursor:
            format_strings = ','.join(['%s'] * len(ids_to_ask))
            cursor.execute(f"""
                SELECT symptom_id, name, followup_question
                FROM symptoms
                WHERE symptom_id IN ({format_strings}) AND followup_question IS NOT NULL
            """, ids_to_ask)
            results = cursor.fetchall()
    finally:
        conn.close()

    # Trả về duy nhất 1 câu hỏi (chưa đánh dấu là đã hỏi)
    for symptom_id, name, question in results:
        if not question:
            continue
        logger.info(f"🔎 Follow-up chưa hỏi → chọn hỏi thêm về: {name} (ID: {symptom_id})")
        return {
            "id": symptom_id,
            "name": name,
            "followup_question": question.strip()
        }

    return None

# Dựa vào các symptom_id hiện có truy bảng disease_symptoms → lấy danh sách các disease_id có liên quan truy ngược lại → lấy thêm các symptom khác thuộc cùng bệnh (trừ cái đã có)
def get_related_symptoms_by_disease(symptom_ids: list[int]) -> list[dict]:
    if not symptom_ids:
        return []

    conn = pymysql.connect(**DB_CONFIG)
    related_symptoms = []

    try:
        with conn.cursor() as cursor:
            # B1: Lấy các disease_id liên quan tới các symptom hiện tại
            format_strings = ','.join(['%s'] * len(symptom_ids))
            cursor.execute(f"""
                SELECT DISTINCT disease_id
                FROM disease_symptoms
                WHERE symptom_id IN ({format_strings})
            """, tuple(symptom_ids))
            disease_ids = [row[0] for row in cursor.fetchall()]

            if not disease_ids:
                return []

            # B2: Lấy các symptom_id khác cùng thuộc các disease đó
            format_diseases = ','.join(['%s'] * len(disease_ids))
            cursor.execute(f"""
                SELECT DISTINCT s.symptom_id, s.name
                FROM disease_symptoms ds
                JOIN symptoms s ON ds.symptom_id = s.symptom_id
                WHERE ds.disease_id IN ({format_diseases})
                  AND ds.symptom_id NOT IN ({format_strings})
            """, tuple(disease_ids + symptom_ids))

            related_symptoms = [{"id": row[0], "name": row[1]} for row in cursor.fetchall()]

    finally:
        conn.close()

    return related_symptoms

# lưu phỏng đoán bệnh vào database lưu vào health_records user_symptom_history khi đang thực hiện chẩn đoán kết quả
def save_prediction_to_db(
    user_id: int,
    symptoms: list[dict],
    diseases: list[dict],
    chat_id: int = None
):
    """
    Lưu kết quả chẩn đoán gồm nhiều bệnh do GPT dự đoán:
    - Ghi vào health_records, health_predictions, prediction_diseases
    """
    conn = pymysql.connect(**DB_CONFIG)
    try:
        with conn.cursor() as cursor:
            note = "Triệu chứng ghi nhận: " + ", ".join([s['name'] for s in symptoms])
            record_date = date.today()

            # Ghi health_records
            cursor.execute("""
                INSERT INTO health_records (user_id, record_date, notes)
                VALUES (%s, %s, %s)
            """, (user_id, record_date, note))
            record_id = cursor.lastrowid

            # Ghi health_predictions
            confidence_score = max([d.get("confidence", 0.0) for d in diseases], default=0.0)
            prediction_details = {
                "symptoms": [s["name"] for s in symptoms],
                "predicted_diseases": [d.get("name") for d in diseases if d.get("name")]
            }

            cursor.execute("""
                INSERT INTO health_predictions (user_id, record_id, chat_id, confidence_score, details)
                VALUES (%s, %s, %s, %s, %s)
            """, (user_id, record_id, chat_id, confidence_score, json.dumps(prediction_details, ensure_ascii=False)))
            prediction_id = cursor.lastrowid

            # Ghi từng dòng prediction_diseases
            for d in diseases:
                name = d.get("name")
                confidence = d.get("confidence", 0.0)
                summary = d.get("summary", "")
                care = d.get("care", "")

                # Tìm trong bảng diseases
                cursor.execute("SELECT disease_id FROM diseases WHERE name = %s", (name,))
                row = cursor.fetchone()

                if row:
                    disease_id = row[0]
                    disease_name_raw = None
                else:
                    disease_id = -1
                    disease_name_raw = name

                cursor.execute("""
                    INSERT INTO prediction_diseases (
                        prediction_id, disease_id, confidence, disease_name_raw,
                        disease_summary, disease_care
                    ) VALUES (%s, %s, %s, %s, %s, %s)
                """, (prediction_id, disease_id, confidence, disease_name_raw, summary, care
                ))

        conn.commit()
    finally:
        conn.close()

# Lọc ra những bệnh mới chưa từng được lưu trong prediction_diseases của prediction_id
def filter_new_predicted_diseases(cursor, prediction_id: int, new_diseases: list[dict]) -> list[tuple[int, dict]]:
    """
    Lọc ra những bệnh mới chưa từng được lưu trong prediction_diseases của prediction_id.
    So sánh cả disease_id và disease_name_raw.
    Trả về list tuple (disease_id or None, disease_dict)
    """
    # 1. Lấy toàn bộ tên bệnh đã lưu
    cursor.execute("""
        SELECT d.name, pd.disease_name_raw
        FROM prediction_diseases pd
        LEFT JOIN diseases d ON pd.disease_id = d.disease_id
        WHERE pd.prediction_id = %s
    """, (prediction_id,))
    existing_names = set()
    for name, raw in cursor.fetchall():
        if name:
            existing_names.add(name.strip().lower())
        if raw:
            existing_names.add(raw.strip().lower())

    # 2. Lọc danh sách mới
    filtered = []
    for d in new_diseases:
        name = d["name"].strip()
        name_lc = name.lower()

        if name_lc not in existing_names:
            # Tra ID trong bảng diseases
            cursor.execute("SELECT disease_id FROM diseases WHERE name = %s", (name,))
            row = cursor.fetchone()
            disease_id = row[0] if row else None
            filtered.append((disease_id, d))

    return filtered

# Cập nhật dự đoán bệnh hôm nay nếu đã có chẩn đoán trước đó
# Nếu chưa có thì sẽ tạo mới
# - Lưu triệu chứng mới nếu có
# - Lọc bệnh mới chưa có trong prediction_diseases
# nếu có bệnh mới
# - Nếu không có bệnh mới thì không làm gì cả
# nếu có bệnh mới thì sẽ thêm vào prediction_diseases
def update_prediction_today_if_exists(
    user_id: int,
    stored_symptoms: list[dict],
    diseases: list[dict],
    symptom_notes_list: list[dict],
    diagnosed_today: bool,
    chat_id: str
) -> None:
    if not diseases:
        logger.warning("⚠️ Không có bệnh nào trong kết quả chẩn đoán.")
        return

    conn = pymysql.connect(**DB_CONFIG)
    try:
        with conn.cursor() as cursor:
            today_str = date.today().strftime("%Y-%m-%d")
            cursor.execute("""
                SELECT prediction_id FROM health_predictions
                WHERE user_id = %s AND DATE(prediction_date) = %s
            """, (user_id, today_str))
            row = cursor.fetchone()

            if row:
                prediction_id = row[0]

                # Lưu triệu chứng khác với những triệu chứng đã lưu trước đó trong này
                saved_ids = get_saved_symptom_ids(user_id)
                symptoms_to_save = [
                    {"id": note["id"], "note": note["note"]}
                    for note in symptom_notes_list
                    if note["id"] not in saved_ids
                ]
                # logger.debug(f"[DEBUG] saved symptom_ids today: {saved_ids}")
                if symptoms_to_save:
                    save_symptoms_to_db(user_id=user_id, symptoms=symptoms_to_save)

                # 🧠 Lọc bệnh mới chưa có
                new_diseases = filter_new_predicted_diseases(cursor, prediction_id, diseases)
                if new_diseases:
                    for disease_id, d in new_diseases:
                        if disease_id is None:
                            disease_id = -1
                            disease_name_raw = d.get("name")
                        else:
                            disease_name_raw = None

                        cursor.execute("""
                            INSERT INTO prediction_diseases (
                                prediction_id, disease_id, confidence, disease_name_raw,
                                disease_summary, disease_care
                            ) VALUES (%s, %s, %s, %s, %s, %s)
                        """, (
                            prediction_id,
                            disease_id,
                            d.get("confidence", 0.0),
                            disease_name_raw,
                            d.get("summary", ""),
                            d.get("care", "")
                        ))
                    logger.info(f"🆕 Đã thêm {len(new_diseases)} bệnh mới và cập nhật lại details.")
                else:
                    logger.info("✅ Không có bệnh mới để thêm vào hôm nay.")
                conn.commit()
            else:
                logger.info("🆕 Chưa có chẩn đoán hôm nay → tạo mới.")

                # lưu triệu chứng mới nếu có
                saved_ids = get_saved_symptom_ids(user_id)
                symptoms_to_save = [
                    {"id": note["id"], "note": note["note"]}
                    for note in symptom_notes_list
                    if note["id"] not in saved_ids
                ]
                if symptoms_to_save:
                    save_symptoms_to_db(user_id=user_id, symptoms=symptoms_to_save)

                # lưu phỏng đoán vào health_predictions
                save_prediction_to_db(
                    user_id=user_id,
                    symptoms=stored_symptoms,
                    diseases=diseases,
                    chat_id=chat_id
                )

    finally:
        conn.close()

# Cập nhật lại trường details trong health_predictions nếu cần thiết
# - Lấy triệu chứng từ user_symptom_history trong ngày hôm nay
# - Lấy bệnh từ prediction_diseases của prediction_id hôm nay
def update_prediction_details(user_id: int) -> bool:
    from datetime import date
    today_str = date.today().isoformat()

    conn = pymysql.connect(**DB_CONFIG)
    try:
        with conn.cursor() as cursor:
            # 1️⃣ Lấy prediction_id hôm nay
            cursor.execute("""
                SELECT prediction_id
                FROM health_predictions
                WHERE user_id = %s AND DATE(prediction_date) = %s
                ORDER BY prediction_date DESC
                LIMIT 1
            """, (user_id, today_str))
            row = cursor.fetchone()
            if not row:
                return False  # ❌ Không có prediction hôm nay

            prediction_id = row[0]

            # 2️⃣ Lấy danh sách triệu chứng từ user_symptom_history
            cursor.execute("""
                SELECT s.name
                FROM user_symptom_history h
                JOIN symptoms s ON h.symptom_id = s.symptom_id
                WHERE h.user_id = %s AND h.record_date = %s
            """, (user_id, today_str))
            symptoms = [row[0] for row in cursor.fetchall()]

            # 3️⃣ Lấy danh sách bệnh từ prediction_diseases
            cursor.execute("""
                SELECT COALESCE(d.name, pd.disease_name_raw)
                FROM prediction_diseases pd
                LEFT JOIN diseases d ON pd.disease_id = d.disease_id
                WHERE pd.prediction_id = %s
            """, (prediction_id,))
            diseases = [row[0] for row in cursor.fetchall() if row[0]]

            # 4️⃣ Cập nhật lại field details
            new_details = {
                "symptoms": symptoms,
                "predicted_diseases": diseases
            }

            cursor.execute("""
                UPDATE health_predictions
                SET details = %s
                WHERE prediction_id = %s
            """, (json.dumps(new_details, ensure_ascii=False), prediction_id))

            conn.commit()
            return True
    finally:
        conn.close()



#-------------- dưới đây là nhừng hàm được sử dung cho việc chia theo controller không tôt không lien mạch bot gần như ko quyết định chính xác việc cần thực hiện --------------------------------------------------

# Dự đoán bệnh dựa trên list triệu chứng
# Trả về danh sách các bệnh với độ phù hợp (confidence 0-1) danh sách bệnh gồm: id, tên, độ phù hợp, mô tả, hướng dẫn điều trị.
def predict_disease_based_on_symptoms(symptoms: list[dict]) -> list[dict]:
    conn = pymysql.connect(**DB_CONFIG)
    try:
        with conn.cursor() as cursor:
            symptom_ids = [s['id'] for s in symptoms]
            if not symptom_ids:
                return []

            format_strings = ','.join(['%s'] * len(symptom_ids))

            # B1: Lấy danh sách bệnh có triệu chứng khớp
            cursor.execute(f"""
                SELECT 
                    ds.disease_id,
                    d.name,
                    d.description,
                    d.treatment_guidelines,
                    COUNT(*) AS match_count
                FROM disease_symptoms ds
                JOIN diseases d ON ds.disease_id = d.disease_id
                WHERE ds.symptom_id IN ({format_strings})
                GROUP BY ds.disease_id
            """, symptom_ids)

            matches = cursor.fetchall()
            if not matches:
                return []

            disease_ids = [row[0] for row in matches]
            disease_id_str = ','.join(['%s'] * len(disease_ids))

            # B2: Lấy tổng số triệu chứng của từng bệnh
            cursor.execute(f"""
                SELECT disease_id, COUNT(*) as total_symptoms
                FROM disease_symptoms
                WHERE disease_id IN ({disease_id_str})
                GROUP BY disease_id
            """, disease_ids)

            total_symptom_map = {row[0]: row[1] for row in cursor.fetchall()}

            # B3: Tính penalty theo số lượng input từ người dùng
            num_user_symptoms = len(symptom_ids)
            if num_user_symptoms <= 2:
                penalty = 0.75
            elif num_user_symptoms == 3:
                penalty = 0.85
            else:
                penalty = 0.9

            # B4: Tính điểm confidence
            predicted = []
            for disease_id, name, desc, guideline, match_count in matches:
                total = total_symptom_map.get(disease_id, match_count)
                raw_score = match_count / total
                confidence = min(round(raw_score * penalty, 2), 0.95)

                predicted.append({
                    "disease_id": disease_id,
                    "name": name,
                    "description": desc or "",
                    "treatment_guidelines": guideline or "",
                    "confidence": confidence
                })

            # Sắp xếp theo độ phù hợp
            predicted.sort(key=lambda x: x["confidence"], reverse=True)

            return predicted
    finally:
        conn.close()

# Hàm cũ dùng decide_health_action để quyết định hành động (có thể sẽ không dùng nữa Những chưa bỏ)
async def gpt_health_talk(user_message: str, stored_symptoms: list[dict], recent_messages: list[str], session_id=None, user_id=None, chat_id=None) -> dict:
    
    # 1. Xác định các triệu chứng chưa follow-up và triệu chứng liên quan (ĐƯA LÊN TRƯỚC)
    asked_ids = await get_followed_up_symptom_ids(user_id, session_id)
    remaining = [s["name"] for s in stored_symptoms if s["id"] not in asked_ids]
    symptom_ids = [s["id"] for s in stored_symptoms]
    related_symptoms = get_related_symptoms_by_disease(symptom_ids)
    related_names = [s["name"] for s in related_symptoms][:4] if related_symptoms else []

    # 2. GPT quyết định hành vi và trích triệu chứng mới
    new_symptoms, controller = await decide_health_action(
        user_message,
        [s['name'] for s in stored_symptoms],
        recent_messages,
        remaining_followup_symptoms=remaining,
        related_symptom_names=related_names
    )

    # Trước khi lưu, loại bỏ triệu chứng trùng ID
    if new_symptoms:

        # Gộp lại danh sách triệu chứng cũ và mới
        combined_symptoms = stored_symptoms + new_symptoms

        # Khử trùng lặp theo ID
        seen_ids = set()
        unique_symptoms = []
        for symptom in combined_symptoms:
            if symptom['id'] not in seen_ids:
                unique_symptoms.append(symptom)
                seen_ids.add(symptom['id'])

        # Cập nhật lại biến stored_symptoms
        stored_symptoms = unique_symptoms

        # Lưu lại vào session
        stored_symptoms = save_symptoms_to_session(user_id, session_id, stored_symptoms)
        symptoms_saved = await get_symptoms_from_session(user_id, session_id)

        logger.info(f"[📝] Triệu chứng mới lưu vào session {session_id}: {[s['name'] for s in new_symptoms]}")
        logger.info(f"[📝] Tổng triệu chứng hiện có (đã loại trùng): {[s['name'] for s in symptoms_saved]}")

    # --- Block 1: Chẩn đoán chính thức ---
    if controller.get("trigger_diagnosis"):
        logger.info("⚡ GPT xác định đủ điều kiện chẩn đoán")
        diseases = predict_disease_based_on_symptoms(stored_symptoms)

        if diseases:
            logger.info(f"✅ GPT đã dự đoán {len(diseases)} bệnh: {[d['name'] for d in diseases]}")
            if user_id:
                note = generate_symptom_note(recent_messages)
                save_symptoms_to_db(user_id, stored_symptoms, note=note)
                save_prediction_to_db(user_id, stored_symptoms, diseases, chat_id)

            diagnosis_text = generate_diagnosis_summary(diseases)
            return {
                "symptoms": new_symptoms,
                "followup_question": None,
                "trigger_diagnosis": True,
                "diagnosis_summary": diagnosis_text,
                "message": diagnosis_text,
                "end": True
            }

    # --- Block 2: Kết luận nhẹ nếu triệu chứng mơ hồ hoặc nhẹ ---
    if controller.get("light_summary"):
        logger.info("🌿 GPT xác định chỉ cần gửi kết luận nhẹ nhàng (light_summary)")
        summary = generate_light_diagnosis_message(stored_symptoms)
        if user_id:
            note = generate_symptom_note(recent_messages)
            save_symptoms_to_db(user_id, stored_symptoms, note=note)

        return {
            "symptoms": [],
            "followup_question": None,
            "trigger_diagnosis": False,
            "diagnosis_summary": summary,
            "message": summary,
            "end": True
        }

    # --- Block 3: Tiếp tục hỏi follow-up ---     Block này đang có vấn đề về logic cần xem xét lại
    if controller.get("ask_followup", True):
        logger.info("⚡ GPT xác định câu hỏi followup")

        followup, targets = await generate_friendly_followup_question(
            stored_symptoms, session_id, recent_messages, return_with_targets=True
        )

        if targets:
            return {
                "symptoms": new_symptoms,
                "followup_question": followup,
                "trigger_diagnosis": False,
                "diagnosis_summary": None,
                "message": followup,
                "end": controller.get("end", False)
            }

    # --- Block 4: Nếu GPT yêu cầu hỏi triệu chứng liên quan ---
    if controller.get("ask_related") and related_names:
        logger.info("⚡ GPT xác định hỏi chiệu chứng liên quan")
        followup_related = await generate_related_symptom_question(related_names)
        return {
            "symptoms": [],
            "followup_question": followup_related,
            "trigger_diagnosis": False,
            "diagnosis_summary": None,
            "message": followup_related,
            "end": False
        }

    # --- Block 5: Fallback hoặc trả lời dí dỏm ---
    if controller.get("playful_reply"):
        logger.info("😴 GPT chọn phản hồi dí dỏm hoặc nhẹ nhàng để kết thúc luồng.")
        return {
            "symptoms": [],
            "followup_question": None,
            "trigger_diagnosis": False,
            "diagnosis_summary": None,
            "message": controller["message"],
            "end": True
        }

    # --- Block 6: Fallback cuối nếu không rõ hướng đi ---
    return {
        "symptoms": new_symptoms,
        "followup_question": None,
        "trigger_diagnosis": False,
        "diagnosis_summary": None,
        "message": controller.get("message", "Bạn có thể chia sẻ thêm để mình hiểu rõ hơn nhé?"),
        "end": controller.get("end", False)
    }

# Hàm cũ quyết định chatbot sẽ làm gì (có thể sẽ không dùng nữa Những chưa bỏ)
async def decide_health_action(
    user_message,
    symptom_names: list[str],
    recent_messages: list[str],
    remaining_followup_symptoms: list[str] = None,
    related_symptom_names: list[str] = None
) -> tuple[list[dict], dict]:
    
    symptom_list = get_symptom_list()

    prompt = build_diagnosis_controller_prompt(
        symptom_list,
        user_message,
        symptom_names,
        recent_messages,
        remaining_followup_symptoms=remaining_followup_symptoms,
        related_symptom_names=related_symptom_names
    )

    try:
        response = chat_completion([
            {"role": "user", "content": prompt}
        ], temperature=0.3, max_tokens=500)

        content = response.choices[0].message.content.strip()

        if content.startswith("```json"):
            content = content.replace("```json", "").replace("```", "").strip()

        parsed = json.loads(content)

        # Parse triệu chứng mới
        extracted_names = parsed.get("symptom_extract", [])
        name_map = {normalize_text(s["name"]): s for s in symptom_list}
        matched = []
        seen_ids = set()

        for name in extracted_names:
            norm = normalize_text(name)
            s = name_map.get(norm)
            if s and s["id"] not in seen_ids:
                matched.append({"id": s["id"], "name": s["name"]})
                seen_ids.add(s["id"])

        # Parse controller như cũ
        controller = {
            "trigger_diagnosis": parsed.get("trigger_diagnosis", False),
            "ask_followup": parsed.get("ask_followup", True),
            "ask_related": parsed.get("ask_related", False),
            "light_summary": parsed.get("light_summary", False),
            "playful_reply": parsed.get("playful_reply", False),
            "diagnosis_text": parsed.get("diagnosis_text"),
            "message": parsed.get("message"),
            "end": (
                parsed.get("trigger_diagnosis", False)
                or parsed.get("light_summary", False)
                or parsed.get("playful_reply", False)
            )
        }

        return matched, controller

    except Exception as e:
        logger.error(f"[❌] Lỗi hệ thống trong decide_health_action: {e}")
        return [], {
            "trigger_diagnosis": False,
            "ask_followup": True,
            "ask_related": False,
            "light_summary": False,
            "playful_reply": False,
            "diagnosis_text": None,
            "message": "Bạn có thể chia sẻ thêm để mình hiểu rõ hơn nhé?",
            "end": False
        }

# Tạo đoạn văn tư vấn từ danh sách bệnh, bao gồm mô tả ngắn và gợi ý chăm sóc (có thể sẽ không dùng or tái sử dụng cho chức năng khác)
def generate_diagnosis_summary(diseases: list[dict]) -> str:
    if not diseases:
        return "Mình chưa có đủ thông tin để đưa ra chẩn đoán. Bạn có thể chia sẻ thêm triệu chứng nhé."

    # Chuẩn bị dữ liệu đầu vào cho GPT
    disease_lines = []
    for d in diseases[:3]:  # chỉ lấy top 3
        name = d.get("name", "Không xác định")
        conf = int(d.get("confidence", 0.0) * 100)
        desc = (d.get("description") or "").strip()[:120]
        care = (d.get("treatment_guidelines") or "").strip()[:100]
        disease_lines.append(f"- {name} (~{conf}%): {desc} | Gợi ý: {care}")

        prompt = f"""
            You are a warm, empathetic, and natural-sounding virtual health assistant.

            Based on the following possible conditions identified by AI:

            {chr(10).join(disease_lines)}

            Please write a natural, friendly health summary **in Vietnamese**, following this structure and rules:

            1. Begin gently: e.g., “Dựa trên những gì bạn chia sẻ...”

            2. Then clearly list 2–3 possible conditions related to the user's symptoms.
            - Each condition must be introduced with 📌 followed by the disease name in UPPERCASE
            - You MAY use simple Markdown (like **bold**) to highlight the disease name ONLY

            3. Next, suggest 1–2 lighter possible explanations (like posture, tiredness, stress). For example:
            “Cũng có thể chỉ là do bạn thay đổi tư thế đột ngột hoặc đang mệt mỏi nhẹ 🌿”

            4. Then provide friendly self-care suggestions, such as:
            - 🧘 Nghỉ ngơi và thư giãn
            - 🌊 Uống đủ nước
            - 💬 Theo dõi cơ thể trong 1–2 ngày tới

            5. After self-care suggestions, add a gentle reassurance like:
            “Nhưng bạn cũng đừng quá lo vì đây chỉ là những triệu chứng được phỏng đoán từ tình trạng bạn chia sẻ.”

            6. End with a final caring encouragement, like:
            “Nếu triệu chứng vẫn kéo dài, bạn nên đến gặp bác sĩ để kiểm tra kỹ hơn nhé.”

            Tone and formatting rules:
            - Use warm, calm, non-alarming language
            - Avoid medical jargon, complex terms, or test/procedure names (like EEG, MRI, etc.)
            - You MAY use up to 2–3 relevant emojis total (no more)
            - Use simple line breaks only — no extra spacing between lines
            - Do NOT use bullet-point lists or tables
            - Your response must be in Vietnamese only
        """


    try:
        response = chat_completion([{"role": "user", "content": prompt}], temperature=0.6, max_tokens=350)
        return response.choices[0].message.content.strip()
    except Exception:
        return "Dựa trên những gì bạn chia sẻ, có thể liên quan một vài tình trạng nhẹ. Bạn nên nghỉ ngơi và theo dõi thêm nhé. Nếu không đỡ, hãy đến bác sĩ để kiểm tra kỹ hơn."

# Tạo câu trả lời mềm mại khi bot nghĩ đậy không thật sự là bệnh (có thể sẽ không dùng or tái sử dụng cho chức năng khác)
def generate_light_diagnosis_message(symptoms: list[dict]) -> str:
    names = [s['name'] for s in symptoms]
    symptom_text = ", ".join(names) if names else "một vài triệu chứng"

    prompt = f"""
        You are a kind and empathetic virtual health assistant.

        The user has shared some symptoms (e.g., {symptom_text}), but their responses to follow-up questions have been vague, uncertain, or negative.

        Your job is to write a short and natural **message in Vietnamese**, gently acknowledging the situation and offering simple care advice.

        Instructions:
        - Do NOT list specific diseases or try to diagnose.
        - Assume the situation is still unclear or mild.
        - Use a natural, conversational tone — avoid sounding like a formal announcement.
        - You may start directly with something soft and empathetic, without saying “Chào bạn” or “Cảm ơn bạn”.
        - You can use friendly emojis (like 😌, 🌿, 💬) if it makes the message feel more human and reassuring — but no more than 2.
        - Suggest light care actions (e.g., nghỉ ngơi, uống nước ấm) and remind the user to watch for any changes.
        - Recommend seeing a doctor if symptoms persist or get worse.
        - Do NOT repeat the full list of symptoms; refer to them generally (e.g., "vài triệu chứng bạn đã nói").
        - End with a soft and comforting sentence like “Bạn cứ yên tâm theo dõi thêm nha.” or similar.
        - Do NOT use Markdown, JSON, or medical jargon.

        Output: Your entire message must be in Vietnamese only.
        """.strip()

    try:
        response = chat_completion([
            {"role": "user", "content": prompt}
        ], temperature=0.4, max_tokens=150)

        return response.choices[0].message.content.strip()
    except Exception:
        return "Có thể đây chỉ là tình trạng nhẹ thôi, bạn cứ nghỉ ngơi và theo dõi thêm nhé. Nếu không đỡ thì nên đi khám cho yên tâm nha."









# Tạo câu hỏi tiếp theo nhẹ nhàng, thân thiện, gợi ý người dùng chia sẻ thêm thông tin dựa trên các triệu chứng đã ghi nhận.(Bỏ?)
def join_symptom_names_vietnamese(names: list[str]) -> str:
    if not names:
        return ""
    if len(names) == 1:
        return names[0]
    if len(names) == 2:
        return f"{names[0]} và {names[1]}"
    return f"{', '.join(names[:-1])} và {names[-1]}"

FOLLOWUP_KEY = "followup_asked"

# ✅ generate_friendly_followup_question trả về cả câu hỏi + danh sách triệu chứng chưa hỏi follow-up
async def generate_friendly_followup_question(
    symptoms: list[dict], 
    session_id: str = None, 
    recent_messages: list[str] = [],
    return_with_targets: bool = False
) -> str | tuple[str, list[dict]]:
    if not symptoms:
        default_reply = "Bạn có thể chia sẻ thêm nếu còn triệu chứng nào khác bạn đang gặp phải nhé?"
        return (default_reply, []) if return_with_targets else default_reply

    # 📌 B1: Load các triệu chứng đã hỏi follow-up từ session
    already_asked = set()
    if session_id:
        already_asked = set(await get_followed_up_symptom_ids(session_id))

    # 📌 B2: Lọc triệu chứng chưa hỏi
    symptoms_to_ask = [s for s in symptoms if s['id'] not in already_asked]
    if not symptoms_to_ask:
        default_reply = "Bạn có thể chia sẻ thêm nếu còn triệu chứng nào khác bạn đang gặp phải nhé?"
        return (default_reply, []) if return_with_targets else default_reply

    # 📌 B3: Truy DB lấy follow-up question
    symptom_ids_to_ask = [s['id'] for s in symptoms_to_ask]
    conn = pymysql.connect(**DB_CONFIG)
    try:
        with conn.cursor() as cursor:
            format_strings = ','.join(['%s'] * len(symptom_ids_to_ask))
            cursor.execute(f"""
                SELECT name, followup_question, symptom_id
                FROM symptoms
                WHERE symptom_id IN ({format_strings})
            """, symptom_ids_to_ask)
            results = cursor.fetchall()
    finally:
        conn.close()

    names, questions, just_asked_ids = [], [], []
    for name, question, sid in results:
        if question:
            names.append(name)
            questions.append(question.strip())
            just_asked_ids.append(sid)

    if not questions:
        default_reply = "Bạn có thể chia sẻ thêm nếu còn triệu chứng nào khác bạn đang gặp phải nhé?"
        return (default_reply, []) if return_with_targets else default_reply

    context = "\n".join(f"- {msg}" for msg in recent_messages[-3:]) if recent_messages else "(no prior messages)"

    gpt_prompt = f"""
        You are a warm and understanding assistant helping a user who may not feel well. Below is the recent conversation with the user:
        {context}

        The user has shared the following symptoms: {', '.join(names)}.

        Here are the follow-up questions you would normally ask:
        {chr(10).join([f"- {n}: {q}" for n, q in zip(names, questions)])}

        Now write a **single, natural, caring message in Vietnamese** to gently follow up with the user.

        Instructions:
        - Combine all follow-up questions into one fluent Vietnamese message.
        - Start the message naturally. You may:
        - Jump straight into the follow-up question, or
        - Use a light, symptom-specific transition such as:
            - “À, [triệu chứng] ha…”
            - “Về chuyện [triệu chứng]…”
            - "Um…”
            - Or a soft emoji like 🫁 (for breathing), 💭 (thinking), 🌀 (dizzy), 😵‍💫 (lightheaded)
        - Make sure the symptom name in the transition matches what the user reported (e.g., use “chóng mặt” if they mentioned dizziness).
        - Do not insert the word “ho” unless the user’s symptom is cough.
        - Use varied connectors such as “Bên cạnh đó”, “Một điều nữa”, “Thêm vào đó” — each only once.
        - Avoid repeating sentence structure — write naturally.
        - Do NOT ask about other or related symptoms.
        - Do NOT greet or thank — just continue the conversation.
        - If the user already gave context (e.g. time, severity), don’t repeat that — go deeper if needed.
        - Refer to yourself as “mình” — not “tôi”.
        - Keep the tone warm, friendly, and caring like a thoughtful assistant — not a formal doctor.

        Your response must be in Vietnamese only.
        """.strip()




    try:
        response = chat_completion([
            {"role": "user", "content": gpt_prompt}
        ], temperature=0.4, max_tokens=200)

        reply = response.choices[0].message.content.strip()
        if session_id and just_asked_ids and reply:
            await mark_followup_asked(session_id,  just_asked_ids)

        return (reply, symptoms_to_ask) if return_with_targets else reply

    except Exception:
        default_reply = "Bạn có thể chia sẻ thêm để mình hỗ trợ tốt hơn nhé?"
        return (default_reply, []) if return_with_targets else default_reply











# Kiểm tra xem câu tiếp theo có bổ sung cho triêu chứng ko (BỎ)
def gpt_looks_like_symptom_followup_uncertain(text: str) -> bool:
    prompt = f""" 
        You are an AI assistant that determines whether the following message from a user in a health-related conversation sounds like a vague or uncertain follow-up to previous symptom discussion.

        Message: "{text}"

        These replies may contain vague expressions, indirect timing, unclear feelings, or conversational hesitation — often seen in real user input. 

        Examples of vague/uncertain replies:
        - "không chắc", "có thể", "tôi không biết", "vẫn chưa rõ", "can't tell", "một chút", "kind of", "chắc là vậy", "không rõ lắm", "thỉnh thoảng", "đôi khi bị", "hơi hơi", "cũng không biết nữa", "khó nói lắm"
        - "vừa ngủ dậy", "sáng nay", "lúc đó", "sau khi ăn", "xong thì thấy mệt", "đang nằm thì bị", "đi ngoài xong bị", "vừa đứng lên", "lúc đứng dậy", "trong lúc ấy", "sau khi uống nước", "khi đang tập", "vừa mới...", "xong rồi thì..."
        - "thấy người lạ lạ", "khó tả lắm", "không giống mọi khi", "cảm thấy hơi lạ", "cảm giác không quen", "mệt kiểu khác", "đầu óc không tỉnh táo lắm", "cảm thấy hơi khó chịu", "đang nằm thì thấy..."

        Is this message an uncertain continuation of a prior symptom conversation — meaning the user might still be talking about symptoms but isn't describing clearly?

        Answer only YES or NO.
    """ 

    response = chat_completion([
        {"role": "user", "content": prompt}
    ], temperature=0.0, max_tokens=5)

    answer = response.choices[0].message.content.strip().lower()
    return "yes" in answer

# Kiểm tra xem câu tiếp theo có bổ sung cho triêu chứng ko (BỎ)
def looks_like_followup_with_gpt(text: str, context: str = "") -> bool:
    prompt = f""" 
        You are an AI assistant that helps identify intent in health care conversations.

        Here is the previous context:
        "{context}"

        The user has now said:
        "{text}"

        Is this a continuation of the prior health-related context — such as adding more symptoms, describing progression, or providing clarification?

        Answer only YES or NO.
    """ 

    response = chat_completion([
        {"role": "system", "content": "Bạn là AI phân tích hội thoại."},
        {"role": "user", "content": prompt}
    ], temperature=0.0, max_tokens=5)

    answer = response.choices[0].message.content.strip().lower()
    return "yes" in answer