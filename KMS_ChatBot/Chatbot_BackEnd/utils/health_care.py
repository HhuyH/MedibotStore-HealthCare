import json
import pymysql
from datetime import date
import logging
import re
logger = logging.getLogger(__name__)
from config.config import DB_CONFIG
from utils.openai_client import chat_completion
from utils.symptom_utils import get_symptom_list, extract_symptoms_gpt, generate_related_symptom_question, save_symptoms_to_db
from utils.symptom_session import get_symptoms_from_session, save_symptoms_to_session
from utils.session_store import get_followed_up_symptom_ids, mark_followup_asked
from prompts.prompts import build_diagnosis_controller_prompt

async def gpt_health_talk(user_message: str, stored_symptoms: list[dict], recent_messages: list[str], session_key=None, user_id=None, chat_id=None) -> dict:
    # 1. Trích triệu chứng mới từ message
    new_symptoms, _ = extract_symptoms_gpt(user_message, session_key=session_key)
    if new_symptoms:
        stored_symptoms += new_symptoms
        stored_symptoms = save_symptoms_to_session(session_key, stored_symptoms)

        logger.info(f"[📝] Triệu chứng mới lưu vào session {session_key}: {[s['name'] for s in new_symptoms]}")

    # 2. GPT quyết định hành vi
    controller = await decide_health_action(user_message, [s['name'] for s in stored_symptoms], recent_messages)

    if controller.get("trigger_diagnosis"):
        logger.info("⚡ GPT xác định đủ điều kiện chẩn đoán")
        diseases = predict_disease_based_on_symptoms(stored_symptoms)

        if diseases:
            logger.info(f"✅ GPT đã dự đoán {len(diseases)} bệnh: {[d['name'] for d in diseases]}")

            if user_id:
                # 💾 Lưu triệu chứng vào lịch sử người dùng (ngoài lưu dự đoán)
                note = generate_symptom_note(recent_messages)

                save_symptoms_to_db(user_id, stored_symptoms, note=note)


                # 💾 Lưu kết quả chẩn đoán
                save_prediction_to_db(user_id, stored_symptoms, diseases, chat_id)

            diagnosis_text = generate_diagnosis_summary(diseases)

            return {
                "symptoms": new_symptoms,
                "followup_question": None,
                "trigger_diagnosis": True,
                "diagnosis_summary": diagnosis_text,
                "message": diagnosis_text,
                "end": controller.get("end", False)
            }

    # 3. Nếu còn triệu chứng chưa follow-up
    if controller.get("ask_followup", True):

        # Nếu đã hỏi triệu chứng liên quan trước đó và người dùng vừa phủ định rõ ràng → không hỏi gì nữa, kết luận nhẹ
        if is_user_response_negative_or_uncertain(user_message):
            summary = generate_light_diagnosis_message(stored_symptoms)
            return {
                "symptoms": [],
                "followup_question": None,
                "trigger_diagnosis": True,
                "diagnosis_summary": summary,
                "message": summary,
                "end": True
            }

        followup, targets = await generate_friendly_followup_question(
            stored_symptoms, session_key, recent_messages, return_with_targets=True
        )

        if not targets:
            # 🔁 Nếu follow-up hết → hỏi thêm triệu chứng liên quan
            symptom_ids = [s["id"] for s in stored_symptoms]
            related = get_related_symptoms_by_disease(symptom_ids)

            print(f"[DEBUG] Triệu chứng đã có: {symptom_ids}")
            # print(f"[DEBUG] Gợi ý liên quan từ DB: {related}")

            if related:
                related_names = [s["name"] for s in related][:4]
                followup_related = await generate_related_symptom_question(related_names)
                return {
                    "symptoms": [],
                    "followup_question": followup_related,
                    "trigger_diagnosis": False,
                    "diagnosis_summary": None,
                    "message": followup_related,
                    "end": False
                }

        # ✅ Nếu vẫn còn câu hỏi follow-up hợp lệ
        return {
            "symptoms": new_symptoms,
            "followup_question": followup,
            "trigger_diagnosis": False,
            "diagnosis_summary": None,
            "message": followup,
            "end": controller.get("end", False)
        }

    # 4. Nếu user trả lời mơ hồ → Gợi ý triệu chứng liên quan
    is_vague = gpt_looks_like_symptom_followup_uncertain(user_message)
    if is_vague:
        symptom_ids = [s["id"] for s in stored_symptoms]
        related = get_related_symptoms_by_disease(symptom_ids)

        # Đã từng hỏi rồi mà user tiếp tục mơ hồ hoặc phủ định → kết luận
        if is_user_response_negative_or_uncertain(user_message):
            logger.info(f"[⚠️] Phát hiện phản hồi phủ định hoặc không rõ: '{user_message}' → Kết luận nhẹ.")
            # Tự động kết luận nhẹ
            summary = generate_light_diagnosis_message(stored_symptoms)

            # 💾 Lưu triệu chứng vào lịch sử người dùng
            note = generate_symptom_note(recent_messages)

            save_symptoms_to_db(user_id, stored_symptoms, note=note)
            return {
                "symptoms": [],
                "followup_question": None,
                "trigger_diagnosis": True,
                "diagnosis_summary": summary,
                "message": summary,
                "end": True
            }

    # 5. Fallback nếu chẳng còn gì để hỏi
    return {
        "symptoms": new_symptoms,
        "followup_question": None,
        "trigger_diagnosis": False,
        "diagnosis_summary": None,
        "message": controller.get("message", "Bạn có thể chia sẻ thêm để mình hiểu rõ hơn nhé?"),
        "end": controller.get("end", False)
    }



def is_user_response_negative_or_uncertain(text: str) -> bool:
    """
    Kiểm tra xem phản hồi của người dùng có mang tính phủ định hoặc mơ hồ hay không.
    Bao gồm các biểu thức phổ biến bằng tiếng Việt và tiếng Anh.
    """

    text = text.lower().strip()

    # Các cụm từ phủ định hoặc không chắc chắn phổ biến
    patterns = [
        r"\bkhông\b", r"\bkhông có\b", r"\bko\b", r"\bk có\b", r"\bko có\b", r"\bko co\b",
        r"\bk rõ\b", r"\bkhông rõ\b", r"\bkhông chắc\b", r"\bkhông biết\b", r"\bk biết\b",
        r"\bmình không rõ\b", r"\bchưa biết\b", r"\bk bik\b", r"\bk bít\b", r"\bko ro\b",
        r"\btôi không biết\b", r"\btôi không chắc\b",
        # English equivalents
        r"\bno\b", r"\bnot sure\b", r"\bi don't know\b", r"\bi'm not sure\b", r"\bidk\b", r"\bno idea\b"
    ]

    for pattern in patterns:
        if re.search(pattern, text):
            return True

    return False


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


# hàm tạo ghi chú cho triệu chứng khi thêm vào database
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
                ORDER BY match_count DESC
            """, symptom_ids)

            results = cursor.fetchall()
            if not results:
                return []

            max_match = results[0][4]  # match_count cao nhất
            predicted = []
            for disease_id, name, desc, guideline, match_count in results:
                confidence = round(match_count / max_match, 2)
                predicted.append({
                    "disease_id": disease_id,
                    "name": name,
                    "description": desc or "",
                    "treatment_guidelines": guideline or "",
                    "confidence": confidence
                })

            return predicted
    finally:
        conn.close()

# lưu phỏng đoán bệnh vào database lưu vào health_records user_symptom_history khi đang thực hiện chẩn đoán kết quả
def save_prediction_to_db(user_id: int, symptoms: list[dict], diseases: list[dict], chat_id: int = None):
    conn = pymysql.connect(**DB_CONFIG)
    try:
        with conn.cursor() as cursor:
            # Ghi nhận health_records đơn giản với notes mô tả triệu chứng
            note = "Triệu chứng ghi nhận: " + ", ".join([s['name'] for s in symptoms])
            record_date = date.today()

            cursor.execute("""
                INSERT INTO health_records (user_id, record_date, notes)
                VALUES (%s, %s, %s)
            """, (user_id, record_date, note))
            record_id = cursor.lastrowid

            # Ghi vào bảng health_predictions
            confidence_score = max([d["confidence"] for d in diseases], default=0.0)
            prediction_details = {
                "symptoms": [s['name'] for s in symptoms],
                "summary": "AI predicted diseases based on reported symptoms"
            }

            cursor.execute("""
                INSERT INTO health_predictions (user_id, record_id, chat_id, confidence_score, details)
                VALUES (%s, %s, %s, %s, %s)
            """, (user_id, record_id, chat_id, confidence_score, json.dumps(prediction_details)))
            prediction_id = cursor.lastrowid

            # Ghi từng bệnh dự đoán vào bảng prediction_diseases
            for d in diseases:
                cursor.execute("""
                    INSERT INTO prediction_diseases (prediction_id, disease_id, confidence)
                    VALUES (%s, %s, %s)
                """, (prediction_id, d["disease_id"], d["confidence"]))

        conn.commit()
    finally:
        conn.close()

# Tạo đoạn văn tư vấn từ danh sách bệnh, bao gồm mô tả ngắn và gợi ý chăm sóc.
def generate_diagnosis_summary(diseases: list[dict]) -> str:
    if not diseases:
        return "Mình chưa có đủ thông tin để đưa ra chẩn đoán. Bạn có thể chia sẻ thêm triệu chứng nhé."

    lines = ["Dựa trên những gì bạn chia sẻ, đây là một số tình trạng có thể liên quan. Bạn có thể theo dõi như sau:\n"]

    # Tìm các bệnh thiếu thông tin
    missing_info_names = [d["name"] for d in diseases[:3] if not d.get("description") or not d.get("treatment_guidelines")]
    info_map = {}

    if missing_info_names:
        conn = pymysql.connect(**DB_CONFIG)
        try:
            with conn.cursor() as cursor:
                format_strings = ','.join(['%s'] * len(missing_info_names))
                cursor.execute(f"""
                    SELECT name, description, treatment_guidelines
                    FROM diseases
                    WHERE name IN ({format_strings})
                """, missing_info_names)
                for name, desc, care in cursor.fetchall():
                    info_map[name] = {
                        "description": desc or "",
                        "treatment_guidelines": care or ""
                    }
        finally:
            conn.close()

    for d in diseases[:3]:
        name = d.get("name", "Không xác định")
        desc = (d.get("description") or "").strip()
        care = (d.get("treatment_guidelines") or "").strip()
        confidence = d.get("confidence", 0.0)

        # Bổ sung từ DB nếu thiếu
        if (not desc or not care) and name in info_map:
            if not desc:
                desc = info_map[name]["description"]
            if not care:
                care = info_map[name]["treatment_guidelines"]

        warning = " ⚠️ Cần lưu ý" if confidence >= 0.9 else ""
        lines.append(f"- **{name}** (Độ phù hợp: {int(confidence * 100)}%){warning}")
        lines.append(f"   • Mô tả sơ lược: {desc[:100]}..." if desc else "   • Chưa có mô tả chi tiết về bệnh này.")
        lines.append(f"   • Gợi ý chăm sóc: {care[:100]}..." if care else "   • Hiện chưa có hướng dẫn chăm sóc cụ thể.")
        lines.append("")  # khoảng cách

    lines.append("👉 Nếu bạn cảm thấy không ổn hoặc triệu chứng kéo dài, hãy cân nhắc đến gặp bác sĩ để kiểm tra kỹ hơn.")
    return "\n".join(lines)

# Tạo câu hỏi tiếp theo nhẹ nhàng, thân thiện, gợi ý người dùng chia sẻ thêm thông tin dựa trên các triệu chứng đã ghi nhận.
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
    You are a warm and understanding doctor. Below is the recent conversation with the patient:
    {context}

    The patient has shared the following symptoms: {', '.join(names)}.

    Here are the follow-up questions you'd normally ask:
    {chr(10).join([f"- {n}: {q}" for n, q in zip(names, questions)])}

    Now write a single, fluent, caring message in Vietnamese to gently follow up with the patient.

    Instructions:
    - Combine all follow-up questions into one natural Vietnamese message.
    - Connect questions smoothly. If symptoms are related, group them in one paragraph.
    - Vary transitions. You may use phrases like "Bên cạnh đó", "Một điều nữa", or "Thêm vào đó", but each only once.
    - Do not ask about any additional or related symptoms in this message.
    - Avoid repeating sentence structure. Keep it soft, natural, and human.
    - No greetings or thank yous — continue mid-conversation.
    - If the user has already described the symptom clearly (e.g., "sáng nay", "lúc đó", "vừa ngủ dậy"), treat that as valid context and avoid repeating.
    - If the last message already asked about these symptoms, **do not repeat the exact same list**. Rephrase or follow up differently (e.g., ask about timing, severity, or impact on daily life).
    - If you're unsure what else to ask, it's okay to acknowledge what the user has said and invite them to share more freely.

    Your response must be in Vietnamese.
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

async def decide_health_action(user_message: str, symptom_names: list[str], recent_messages: list[str]) -> dict:
    prompt = build_diagnosis_controller_prompt(symptom_names, recent_messages)

    try:
        response = chat_completion([
            {"role": "user", "content": prompt}
        ], temperature=0.3, max_tokens=400)

        content = response.choices[0].message.content.strip()

        # Clean nếu GPT bọc trong ```json
        if content.startswith("```json"):
            content = content.replace("```json", "").replace("```", "").strip()

        # Xử lý fallback nếu không phải JSON
        try:
            parsed = json.loads(content)
        except json.JSONDecodeError as je:
            logger.error(f"[❌] JSON decode lỗi: {je} | Nội dung: {content}")
            return {
                "trigger_diagnosis": False,
                "diagnosis_text": None,
                "message": "Bạn có thể mô tả thêm để mình hiểu rõ hơn nhé?",
                "end": False
            }

        return {
            "trigger_diagnosis": parsed.get("trigger_diagnosis", False),
            "diagnosis_text": parsed.get("diagnosis_text"),
            "message": parsed.get("message"),
            "end": parsed.get("trigger_diagnosis", False)
        }

    except Exception as e:
        logger.error(f"[❌] Lỗi hệ thống trong decide_health_action: {e}")
        return {
            "trigger_diagnosis": False,
            "diagnosis_text": None,
            "message": "Bạn có thể chia sẻ thêm để mình hiểu rõ hơn nhé?",
            "end": False
        }

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

# Kiểm tra xem câu tiếp theo có bổ sung cho triêu chứng ko
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
