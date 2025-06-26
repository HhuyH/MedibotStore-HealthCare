import json
import pymysql
from datetime import date
import logging
import re
import random, asyncio
import hashlib

logger = logging.getLogger(__name__)
from config.config import DB_CONFIG
from utils.openai_utils import stream_gpt_tokens
from utils.openai_client import chat_completion
from utils.symptom_utils import get_symptom_list, extract_symptoms_gpt, generate_related_symptom_question, save_symptoms_to_db, get_related_symptoms_by_disease
from prompts.prompts import build_diagnosis_controller_prompt, build_KMS_prompt
from utils.text_utils import normalize_text
from utils.session_store import save_session_data, get_session_data, get_followed_up_symptom_ids, mark_followup_asked, save_symptoms_to_session, get_symptoms_from_session, clear_followup_asked_all_keys, clear_symptoms_all_keys

def extract_json(content: str) -> str:
    """
    Trích JSON từ đoạn text có thể chứa rác GPT.
    """
    match = re.search(r"\{[\s\S]*\}", content)
    return match.group(0).strip() if match else ""

# Hàm mới dùng prompt tổng
async def health_talk(
    user_message: str,
    stored_symptoms: list[dict],
    recent_messages: list[str],
    recent_user_messages: list[str], 
    recent_assistant_messages: list[str],
    session_key=None,
    user_id=None,
    chat_id=None,
):
    session_data = await get_session_data(session_key)

    # Step 1: Trích triệu chứng mới
    new_symptoms, fallback_message = extract_symptoms_gpt(
        user_message,
        session_key=session_key,
        recent_messages=recent_messages
    )
    logger.info("🌿 Triệu chứng trích được: %s", new_symptoms)

    if new_symptoms:
        stored_symptoms += [
            s for s in new_symptoms if s["name"] not in {sym["name"] for sym in stored_symptoms}
        ]
        save_symptoms_to_session(session_key, stored_symptoms)

    # Step 2: Lấy related symptom + câu hỏi followup
    inputs = await decide_KMS_prompt_inputs(session_key=session_key)

    # ✅ In log triệu chứng đã hỏi follow-up
    asked = await get_followed_up_symptom_ids(session_key)
    logger.info("📌 Đã hỏi follow-up các triệu chứng có ID: %s", asked)

    # logger.info("📌 related_asked = %s", session_data.get("related_asked", False))

    # Step 3: Xây prompt tổng hợp
    prompt = build_KMS_prompt(
        SYMPTOM_LIST=get_symptom_list(),
        user_message=user_message,
        stored_symptoms_name=[s["name"] for s in stored_symptoms],
        symptoms_to_ask=inputs["symptoms_to_ask"],
        recent_messages=recent_messages,
        recent_user_messages=recent_user_messages,
        recent_assistant_messages=recent_assistant_messages,
        related_symptom_names=inputs["related_symptom_names"],
        related_asked = session_data.get("related_asked", False)
    )


    # Step 4: Gọi GPT (non-stream)
    completion = chat_completion(messages=[{"role": "user", "content": prompt}], temperature=0.7)

    content = completion.choices[0].message.content.strip()
    logger.debug("🔎 Raw content từ GPT:\n%s", content)

    raw_json = extract_json(content)

    try:
        parsed = json.loads(raw_json)
        logger.debug("🧾 JSON từ GPT:\n%s", json.dumps(parsed, indent=2, ensure_ascii=False))
    except json.JSONDecodeError as e:
        logger.warning("⚠️ GPT trả về không phải JSON hợp lệ: %s", str(e))
        parsed = {}

    message = parsed.get("message", fallback_message or "Xin lỗi, mình chưa hiểu rõ lắm...")

    # Step 5: Điều phối logic từ parsed JSON
    message = parsed.get("message", fallback_message or "Bạn có thể nói rõ hơn về tình trạng của mình không?")

    action = parsed.get("action")

    # if action == "related":
    #     session_data["related_asked"] = True
    #     save_session_data(session_key, session_data)


    target_followup_id = inputs.get("target_followup_id")

    if action == "followup" and target_followup_id:
        logger.info("✅ Đánh dấu đã hỏi follow-up triệu chứng ID: %s", target_followup_id)
        await mark_followup_asked(session_key, [target_followup_id])

    end = parsed.get("end", False)

    # Log các biến phụ trợ
    logger.info("🎯 Action: %s", action)
    logger.debug("📌 Related: %s", "not null" if inputs.get("related_symptom_names") else "null")
    logger.debug("📝 Raw follow-up: %s", "not null" if inputs.get("raw_followup_question") else "null")

    if action == "diagnosis":
        
        # Tạo ghi chú ngắn cho triệu chứng
        note = generate_symptom_note(recent_messages)

        # Lưu triệu chứng vào user_symptom_history
        save_symptoms_to_db(user_id=user_id, symptoms=stored_symptoms, note=note)

        logger.info("📝 Đã lưu chẩn đoán và triệu chứng vào DB")

        # ✅ Lấy danh sách bệnh từ GPT
        diseases = parsed.get("diseases", [])
        if diseases:
            save_prediction_to_db(
                user_id=user_id,
                symptoms=stored_symptoms,
                diseases=diseases,
                chat_id=chat_id
            )

    # Step 6: Nếu cần, clear session
    if end:
        logger.info("🛑 GPT yêu cầu kết thúc session.")
        await clear_symptoms_all_keys(user_id=user_id, session_id=session_key)
        await clear_followup_asked_all_keys(user_id=user_id, session_id=session_key)

    # Step 7: Stream message từng đoạn ra ngoài
    for chunk in stream_gpt_tokens(message):
        yield chunk 
        await asyncio.sleep(0.05)

# Trả về các dữ liệu cần thiết để truyền vào build_KMS_prompt:
# - stored_symptoms
# - raw_followup_question: danh sách triệu chứng kèm câu hỏi follow-up
# - related_symptom_names: tên các triệu chứng liên quan nếu không còn follow-up
async def decide_KMS_prompt_inputs(session_key: str):
    stored_symptoms = await get_symptoms_from_session(session_key)
    next_symptom = await get_next_symptom_to_followup(session_key, stored_symptoms)

    symptoms_to_ask = [next_symptom["name"]] if next_symptom else []

    logger.info("📭 symptoms_to_ask: %s", symptoms_to_ask)

    related_symptom_names = []

    symptom_ids = [s['id'] for s in stored_symptoms]
    related = get_related_symptoms_by_disease(symptom_ids)
    stored_names = [s["name"] for s in stored_symptoms]
    related_names = [s["name"] for s in related if s["name"] not in stored_names]
    related_symptom_names = list(set(related_names))[:10]

    return {
        "symptoms_to_ask": symptoms_to_ask,
        "raw_followup_question": None,  # không dùng nữa
        "related_symptom_names": related_symptom_names or None,
        "target_followup_id": next_symptom["id"] if next_symptom else None
    }

# Chọn đúng 1 triệu chứng chưa hỏi follow-up, sau đó truyền vào GPT để nó tự hỏi theo kiểu tinh tế từng bước
async def get_next_symptom_to_followup(session_key: str, stored_symptoms: list[dict]) -> dict | None:
    """
    Trả về dict dạng: {"name": "Tên triệu chứng chưa hỏi follow-up"}
    hoặc None nếu không còn triệu chứng nào cần hỏi.
    """
    if not stored_symptoms:
        return None

    # Lấy danh sách ID đã hỏi follow-up từ session
    already_asked_ids = set(await get_followed_up_symptom_ids(session_key))
    
    # Tìm triệu chứng chưa hỏi follow-up
    for s in stored_symptoms:
        if s["id"] not in already_asked_ids:
            return {"name": s["name"], "id": s["id"]}

    return None

# Lấy những câu hỏi liên quan tới triệu chứng từ DB
async def get_followup_question_fromDB(symptom_ids: list[int], session_key: str = None) -> dict | None:
    if not symptom_ids:
        return None
    # Lấy danh sách symptom_id đã hỏi từ session
    already_asked = set()
    if session_key:
        already_asked = set(await get_followed_up_symptom_ids(session_key))

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
                "diseases": diseases  # giữ nguyên toàn bộ list từ GPT
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

                # Tìm trong bảng diseases
                cursor.execute("SELECT disease_id FROM diseases WHERE name = %s", (name,))
                row = cursor.fetchone()
                summary = d.get("summary", "")
                care = d.get("care", "")

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
                """, (prediction_id, disease_id, confidence, disease_name_raw, summary, care))

        conn.commit()
    finally:
        conn.close()

# Hàm tạo ghi chú cho triệu chứng khi thêm vào database
def generate_symptom_note(recent_messages: list[str]) -> str:
    if not recent_messages:
        return "Người dùng đã mô tả một số triệu chứng trong cuộc trò chuyện."

    context = "\n".join(f"- {msg}" for msg in recent_messages[-5:])

    prompt = f"""
        You are a helpful AI assistant supporting medical documentation.

        Below is a recent conversation with a user about their health concerns:

        {context}

        Write a short **symptom note** in **Vietnamese**, summarizing the user's main symptom(s) and any relevant context (e.g., when it started, what triggered it, how it felt).

        Instructions:
        - Your note must be in Vietnamese.
        - Keep it short (1–2 sentences).
        - Use natural, friendly, easy-to-understand language.
        - Do not use medical jargon.
        - Do not invent symptoms that were not clearly mentioned.
        - If the user was vague, still reflect that (e.g., “người dùng không rõ nguyên nhân”).

        Your output must be only the note. Do not include any explanation or format it as JSON.
    """.strip()

    try:
        response = chat_completion([
            {"role": "user", "content": prompt}
        ], temperature=0.3, max_tokens=100)

        return response.choices[0].message.content.strip()
    except Exception:
        return "Người dùng đã mô tả một số triệu chứng trong cuộc trò chuyện."









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
async def gpt_health_talk(user_message: str, stored_symptoms: list[dict], recent_messages: list[str], session_key=None, user_id=None, chat_id=None) -> dict:
    
    # 1. Xác định các triệu chứng chưa follow-up và triệu chứng liên quan (ĐƯA LÊN TRƯỚC)
    asked_ids = await get_followed_up_symptom_ids(session_key)
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
        stored_symptoms = save_symptoms_to_session(session_key, stored_symptoms)
        symptoms_saved = await get_symptoms_from_session(session_key)

        logger.info(f"[📝] Triệu chứng mới lưu vào session {session_key}: {[s['name'] for s in new_symptoms]}")
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
            stored_symptoms, session_key, recent_messages, return_with_targets=True
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
    session_key: str = None, 
    recent_messages: list[str] = [],
    return_with_targets: bool = False
) -> str | tuple[str, list[dict]]:
    if not symptoms:
        default_reply = "Bạn có thể chia sẻ thêm nếu còn triệu chứng nào khác bạn đang gặp phải nhé?"
        return (default_reply, []) if return_with_targets else default_reply

    # 📌 B1: Load các triệu chứng đã hỏi follow-up từ session
    already_asked = set()
    if session_key:
        already_asked = set(await get_followed_up_symptom_ids(session_key))

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
        if session_key and just_asked_ids and reply:
            await mark_followup_asked(session_key, just_asked_ids)

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