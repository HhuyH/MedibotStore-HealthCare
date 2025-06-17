import pymysql
import logging
logger = logging.getLogger(__name__)
import json
from datetime import date
from rapidfuzz import fuzz, process
import re
from utils.openai_utils import chat_completion
from utils.openai_client import chat_completion
from utils.symptom_session import get_symptoms_from_session
from config.config import DB_CONFIG
from utils.text_utils import normalize_text

SYMPTOM_LIST = []  # Cache triệu chứng toàn cục

# Nhận diện câu trả lời mơ hồ với ngôn ngữ không chuẩn (lóng, sai chính tả...)
def is_vague_response(text: str) -> bool:
    vague_phrases = [
        "khong biet", "khong ro", "toi khong ro", "hinh nhu", "chac vay",
        "toi nghi la", "co the", "cung duoc", "hoi hoi", "chac la", "hem biet", "k biet", "k ro"
    ]
    text_norm = normalize_text(text)

    for phrase in vague_phrases:
        if phrase in text_norm or fuzz.partial_ratio(phrase, text_norm) > 85:
            return True
    return False

# Load danh sách symptoms từ db lên gồm id và name
def load_symptom_list():
    """
    Load danh sách triệu chứng từ DB, bao gồm ID, tên và alias đã normalize.
    Lưu vào biến toàn cục SYMPTOM_LIST.
    """
    global SYMPTOM_LIST
    try:
        conn = pymysql.connect(**DB_CONFIG)
        with conn.cursor() as cursor:
            cursor.execute("SELECT symptom_id, name, alias FROM symptoms")
            results = cursor.fetchall()

            SYMPTOM_LIST = []
            for row in results:
                symptom_id, name, alias_raw = row
                aliases = [normalize_text(name)]

                if alias_raw:
                    aliases += [normalize_text(a.strip()) for a in alias_raw.split(',') if a.strip()]

                SYMPTOM_LIST.append({
                    "id": symptom_id,
                    "name": name,
                    "aliases": aliases
                })

            print(f"✅ SYMPTOM_LIST nạp {len(SYMPTOM_LIST)} triệu chứng:")
            # for s in SYMPTOM_LIST:
            #     print(f" - {s['name']}: {s['aliases']}")
    
    except Exception as e:
        print(f"❌ Lỗi khi load SYMPTOM_LIST từ DB: {e}")
    
    finally:
        if conn:
            conn.close()

# Lấy và load danh sách đã được lấy 1 lần duy nhất mà ko cần gọi lại quá nhiều hoặc gọi khi không cần thiết
def get_symptom_list():
    global SYMPTOM_LIST
    if not SYMPTOM_LIST:
        print("🔁 Loading SYMPTOM_LIST for the first time...")
        load_symptom_list()
    return SYMPTOM_LIST

# Refresh symptom list neu có symptom mới được thêm vào
def refresh_symptom_list():
    global SYMPTOM_LIST
    SYMPTOM_LIST = []
    load_symptom_list()

def extract_symptoms(text):
    text_norm = normalize_text(text)
    found = []
    seen_ids = set()
    for symptom in SYMPTOM_LIST:
        for keyword in symptom["aliases"]:
            if keyword in text_norm and symptom["id"] not in seen_ids:
                found.append({"id": symptom["id"], "name": symptom["name"]})
                seen_ids.add(symptom["id"])
                break
    return found

def extract_symptoms_gpt(text, session_key=None, debug=False):
    prompt = f"""
        Bạn là một trợ lý y tế thông minh. Hãy đọc kỹ câu sau và cố gắng nhận diện **mọi triệu chứng sức khỏe có thể có**, dù người nói dùng cách diễn đạt không rõ ràng, mơ hồ, dân dã hay không chắc chắn.

        Nếu trong câu có bất kỳ từ hoặc cụm từ nào **gợi ý triệu chứng phổ biến** (như: mệt, đau, nhức, khó chịu, chóng mặt, đầy bụng, buồn nôn…), thì **hãy đưa triệu chứng đó vào kết quả**, ngay cả khi chưa thật rõ ràng.

        Đừng bỏ qua triệu chứng chỉ vì câu nói chưa chắc chắn hoặc nói kiểu: “chắc là”, “không biết có phải không”.

        Trả kết quả dưới dạng danh sách JSON, ví dụ: ["Ho", "Sốt", "Táo bón"]. Nếu thật sự không có triệu chứng nào dù đã cố gắng suy luận, hãy trả về [].

        Ví dụ:
        - "Tôi bị ho quá trời" → ["Ho"]
        - "Khó đi cầu, cảm giác đầy bụng" → ["Táo bón", "Đầy bụng"]
        - "Cổ đau rát, nuốt khó" → ["Đau họng"]
        - "Tôi cảm thấy mệt mỏi chung chung thôi" → ["Mệt mỏi"]
        - "Mấy nay thấy không khỏe" → ["Mệt mỏi"]

        Câu: "{text}"
        Trả lời:
        """

    try:
        reply = chat_completion(
            [{"role": "user", "content": prompt}],
            temperature=0.3,
            max_tokens=150
        )
        content = reply.choices[0].message.content.strip()
        logging.debug("🧠 GPT raw reply: %r", content)

        if content.startswith("```json"):
            content = content.replace("```json", "").replace("```", "").strip()
        elif content.startswith("[") is False:
            content = content.split("[")[-1]
            content = "[" + content if not content.startswith("[") else content

        try:
            names = json.loads(content)
        except json.JSONDecodeError:
            if debug:
                print(f"❌ Không thể parse JSON từ: {content}")
            return [], "Xin lỗi, tôi không hiểu rõ các triệu chứng bạn mô tả."

        if not names:
            # Nếu không có triệu chứng rõ ràng → yêu cầu GPT tạo câu hỏi làm rõ
            vague_prompt = f""" 
                The user just said: "{text}"

                You are a friendly health assistant. The sentence above is a vague description of their health condition. Reply in a warm, natural, and casual way — like a friend checking in — to encourage them to be more specific about their symptoms. Avoid using medical terms. Don't apologize, and don't say "hello."

                Instead, gently ask questions like: When did you start feeling this way? Are you experiencing any other discomfort?

                If they say "tired," you can ask: How are you feeling tired? Are you dizzy or sleepy?

                Reply with only a short, simple, and natural question in Vietnamese.
                """ 
            clarification = chat_completion(
                [{"role": "user", "content": vague_prompt}],
                temperature=0.4,
                max_tokens=100
            )
            clarification_text = clarification.choices[0].message.content.strip()
            return [], clarification_text

        matched = []
        unmatched = []
        seen_ids = set()

        for name in names:
            norm_name = normalize_text(name)
            found_match = False

            # Ưu tiên khớp với tên chính
            for symptom in SYMPTOM_LIST:
                if normalize_text(symptom["name"]) == norm_name:
                    if symptom["id"] not in seen_ids:
                        matched.append({"id": symptom["id"], "name": symptom["name"]})
                        seen_ids.add(symptom["id"])
                        found_match = True
                        break

            # Nếu chưa khớp tên chính → thử alias
            if not found_match:
                for symptom in SYMPTOM_LIST:
                    if any(norm_name == alias for alias in symptom["aliases"]):
                        if symptom["id"] not in seen_ids:
                            matched.append({"id": symptom["id"], "name": symptom["name"]})
                            seen_ids.add(symptom["id"])
                            found_match = True
                            break

            if not found_match:
                unmatched.append(name)

        # Nếu vẫn unmatched → fuzzy gợi ý
        suggestion = None
        if unmatched:
            all_names = [normalize_text(s["name"]) for s in SYMPTOM_LIST]
            name_map = {normalize_text(s["name"]): s["name"] for s in SYMPTOM_LIST}

            fuzzy_suggestions = set()
            for name in unmatched:
                norm = normalize_text(name)
                match, score = process.extractOne(norm, all_names, scorer=fuzz.ratio)
                if score >= 80:
                    fuzzy_suggestions.add(name_map[match])

            if fuzzy_suggestions:
                joined = ' hoặc '.join(fuzzy_suggestions)
                suggestion = f"Ý bạn có phải là {joined} không?"
            else:
                joined = ' hoặc '.join(unmatched)
                suggestion = f"Mình chưa rõ. Bạn có đang nhắc tới: {joined} không?"

        return matched, suggestion

    except Exception as e:
        if debug:
            print("❌ GPT symptom extraction failed:", str(e))
        return [], "Xin lỗi, có lỗi xảy ra khi phân tích triệu chứng."

# lưu triệu chứng vào database lưu vào user_symptom_history khi đang thực hiện chẩn đoán kết quả
def save_symptoms_to_db(user_id: int, symptoms: list[dict], note: str = "") -> list[int]:
    conn = pymysql.connect(**DB_CONFIG)
    saved_symptom_ids = []

    try:
        with conn.cursor() as cursor:
            for symptom in symptoms:
                symptom_id = symptom.get("id")
                if not symptom_id:
                    continue  # Bỏ qua nếu thiếu ID

                cursor.execute("""
                    INSERT INTO user_symptom_history (user_id, symptom_id, record_date, notes)
                    VALUES (%s, %s, %s, %s)
                """, (user_id, symptom_id, date.today(), note))
                
                saved_symptom_ids.append(symptom_id)

        conn.commit()
    finally:
        conn.close()

    return saved_symptom_ids

def generate_symptom_note(prompt: str) -> str:
    # Bước 1: Đảm bảo đầu vào có ngữ cảnh rõ ràng
    full_prompt = f"User reports: {prompt.strip()}"

    messages_en = [
        {"role": "system", "content": "You are a medical assistant. Summarize the symptoms described by the user into a short, clear, and objective medical note. Do not diagnose."},
        {"role": "user", "content": "I've been having headaches and dizziness for the past two days."},
        {"role": "assistant", "content": "Patient reports headaches and dizziness lasting for two days."},
        {"role": "user", "content": full_prompt}
    ]
    response_en = chat_completion(messages_en)
    english_note = response_en.choices[0].message.content.strip()

    # Bước 2: Dịch sang tiếng Việt
    messages_translate = [
        {"role": "system", "content": "Hãy dịch đoạn văn bản y tế sau sang tiếng Việt, giữ nguyên giọng văn chuyên nghiệp."},
        {"role": "user", "content": english_note}
    ]
    response_vi = chat_completion(messages_translate)
    vietnamese_note = response_vi.choices[0].message.content.strip()

    return vietnamese_note

# Tạo câu hỏi tiếp theo nhẹ nhàng, thân thiện, gợi ý người dùng chia sẻ thêm thông tin dựa trên các triệu chứng đã ghi nhận.
def join_symptom_names_vietnamese(names: list[str]) -> str:
    if not names:
        return ""
    if len(names) == 1:
        return names[0]
    if len(names) == 2:
        return f"{names[0]} và {names[1]}"
    return f"{', '.join(names[:-1])} và {names[-1]}"

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

# Kiểm tra xem câu tiếp theo có bổ sung cho triêu chứng ko
def looks_like_followup_with_gpt(text: str, context: str = "") -> bool:
    prompt = f""" 
        You are an AI assistant that helps identify intent in health care conversations.

        The user has started a conversation about health symptoms. Now they have said the following sentence:

        "{text}"

        Is this a continuation of the previous context — for example, adding more symptoms, describing their feeling, or explaining progression — or not?

        Answer with "YES" or "NO" only.
        """ 

    response = chat_completion([
        {"role": "system", "content": "Bạn là AI phân tích hội thoại."},
        {"role": "user", "content": prompt}
    ], temperature=0.0, max_tokens=5)

    answer = response.choices[0].message.content.strip().lower()
    return "yes" in answer

# Tự động nhận biết nếu message chứa triệu chứng hay không
def gpt_detect_symptom_intent(text: str) -> bool:
    prompt = (
        "Please determine whether the following sentence is a description of health symptoms.\n"
        "Answer with YES or NO only.\n\n"
        f"Sentence: \"{text}\"\n"
        "Answer: "
    )
    response = chat_completion(
        [{"role": "user", "content": prompt}],
        max_tokens=5,
        temperature=0
    )
    result = response.choices[0].message.content.strip().lower()
    return result.startswith("yes")

# Tạo 1 câu hỏi thân thiện về triệu chứng đã trích xuất được
async def generate_friendly_followup_question(symptoms: list[dict], session_key: str = None) -> str:

    symptom_ids = [s['id'] for s in symptoms]
    all_symptoms = symptoms

    if session_key:
        session_symptoms = await get_symptoms_from_session(session_key)
        if session_symptoms:
            all_symptoms = session_symptoms

    all_symptom_names = [s['name'] for s in all_symptoms]
    symptom_text = join_symptom_names_vietnamese(all_symptom_names)

    # Truy vấn follow-up từ DB
    conn = pymysql.connect(**DB_CONFIG)
    try:
        with conn.cursor() as cursor:
            format_strings = ','.join(['%s'] * len(symptom_ids))
            cursor.execute(f"""
                SELECT name, followup_question
                FROM symptoms
                WHERE symptom_id IN ({format_strings})
            """, symptom_ids)

            results = cursor.fetchall()
    finally:
        conn.close()

    if results:
        names = []
        questions = []
        for name, question in results:
            if question:
                names.append(name)
                questions.append(question.strip())

        related = get_related_symptoms_by_disease(symptom_ids)
        # Lọc để không đề xuất lại các triệu chứng đã có trong follow-up
        followup_symptom_names = set(name.lower() for name, _ in results)
        related_filtered = [
            s for s in related if s['name'].lower() not in followup_symptom_names
        ]
        related_names = [s['name'] for s in related_filtered]

        gpt_prompt = f"""
            You are a warm and understanding doctor. The patient has shared the following symptoms: {', '.join(names)}.

            Here are the follow-up questions you'd normally ask:
            {chr(10).join([f"- {n}: {q}" for n, q in zip(names, questions)])}

            Now write a single, fluent, caring conversation in Vietnamese to follow up with the patient.

            Instructions:
            - Combine all follow-up questions into one natural Vietnamese message.
            - Connect questions smoothly. If symptoms are related, group them in one paragraph.
            - Vary transitions. You may use phrases like "Bên cạnh đó", "Một điều nữa", or "Thêm vào đó", but each only once.
            - Ask about related symptoms (e.g. {', '.join(related_names[:3])}) only once — at the most relevant point in the conversation.
            - If you already mentioned related symptoms, DO NOT repeat them again.
            - Do not add them again at the end under any phrasing like "Ngoài ra..." or "Bạn có gặp thêm...".
            - Avoid repeating sentence structure. Keep it soft, natural, and human.
            - No greetings or thank yous — continue mid-conversation.

            Your response must be in Vietnamese.
            """
        try:
            response = chat_completion([
                {"role": "user", "content": gpt_prompt}
            ], temperature=0.4, max_tokens=200)

            return response.choices[0].message.content.strip()
        except Exception as e:
            # fallback nếu GPT lỗi
            return "Bạn có thể chia sẻ thêm về các triệu chứng để mình hỗ trợ tốt hơn nhé?"

    # Nếu không có câu hỏi follow-up từ DB → fallback
    symptom_prompt = join_symptom_names_vietnamese([s['name'] for s in symptoms])
    fallback_prompt = (
        f"You are a helpful medical assistant. The user reported the following symptoms: {symptom_prompt}. "
        "Write a natural, open-ended follow-up question in Vietnamese to ask about timing, severity, or other related details. "
        "Avoid technical language. No greetings — just ask naturally."
    )

    response = chat_completion([
        {"role": "user", "content": fallback_prompt}
    ])
    fallback_text = response.choices[0].message.content.strip()
    return fallback_text

# 1 câu trả lời mơ hồ từ người nói không xác định được follow up hàm này để kiểm tra xem câu đó có phải vẫn nằm trong trieu chung ko
def gpt_looks_like_symptom_followup_uncertain(text: str) -> bool:
    prompt = f""" 
        You are an AI assistant that determines whether the following message from a user in a health-related conversation sounds like a vague or uncertain follow-up to previous symptom discussion.

        Message: "{text}"

        Examples of vague/uncertain replies: "không chắc", "có thể", "tôi không biết", "vẫn chưa rõ", "can't tell", "một chút", "kind of", etc.

        Is this message an uncertain continuation of a prior symptom conversation — meaning the user might still be talking about symptoms but isn't describing clearly?

        Answer only YES or NO.
        """ 


    response = chat_completion([
        {"role": "user", "content": prompt}
    ], temperature=0.0, max_tokens=5)

    answer = response.choices[0].message.content.strip().lower()
    return "yes" in answer

def load_followup_keywords():
    """
    Trả về dict: {normalized symptom name → follow-up question}
    """
    conn = pymysql.connect(**DB_CONFIG)
    keyword_map = {}

    try:
        with conn.cursor() as cursor:
            cursor.execute("""
                SELECT name, followup_question
                FROM symptoms
                WHERE followup_question IS NOT NULL
            """)
            results = cursor.fetchall()
            for name, question in results:
                norm_name = normalize_text(name)
                keyword_map[norm_name] = question
    finally:
        conn.close()

    return keyword_map


