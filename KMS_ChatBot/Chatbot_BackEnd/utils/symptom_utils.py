import pymysql
from rapidfuzz import fuzz, process
from utils.openai_client import chat_completion
from utils.symptom_session import get_symptoms_from_session
import json
from datetime import date
from config.config import DB_CONFIG
import re
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
    Bạn là một trợ lý y tế. Hãy đọc câu sau và liệt kê các triệu chứng sức khỏe mà người nói đang mô tả, dù họ dùng cách nói dân gian, từ lóng hay không rõ ràng. Trả kết quả dưới dạng danh sách JSON, ví dụ: ["Ho", "Sốt", "Táo bón"]. Nếu không có triệu chứng rõ ràng, hãy trả về []. 

    Ví dụ:
    - "Tôi bị ho quá trời" → ["Ho"]
    - "Khó đi cầu, cảm giác đầy bụng" → ["Táo bón", "Đầy bụng"]
    - "Cổ đau rát, nuốt khó" → ["Đau họng"]

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
        print("🤖 GPT reply:", repr(content))
        if debug:
            print("🧠 GPT raw reply:", repr(content))

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
            Người dùng nói: "{text}"
            Bạn là một trợ lý y tế thân thiện. Câu trên là mô tả mơ hồ về tình trạng sức khỏe. Hãy phản hồi lại bằng lời nhắn gần gũi, nhẹ nhàng, không quá trang trọng. Tránh nói "chào bạn" hoặc "mình rất tiếc". Thay vào đó, hãy thể hiện sự quan tâm một cách tự nhiên và gợi mở để người dùng mô tả rõ hơn các triệu chứng như đau ở đâu, khó chịu như thế nào. Tránh dùng từ chuyên môn, và hãy nói bằng tiếng Việt đời thường."""
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

async def generate_friendly_followup_question(symptoms: list[dict], session_key: str = None) -> str:
    symptom_ids = [s['id'] for s in symptoms]
    all_symptom_names = [s['name'] for s in symptoms]
    # Lấy toàn bộ triệu chứng trong session để hiển thị đầy đủ
    all_symptoms = symptoms
    if session_key:
        session_symptoms = await get_symptoms_from_session(session_key)
        if session_symptoms:
            all_symptoms = session_symptoms

    all_symptom_names = [s['name'] for s in all_symptoms]
    symptom_text = join_symptom_names_vietnamese(all_symptom_names)

    followup_questions = []

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
            for name, question in results:
                if question:
                    followup_questions.append(f"🩺 Về triệu chứng *{name}*: {question.strip()}")
    finally:
        conn.close()

    if followup_questions:
        greeting = f"😌 Mình đã ghi nhận bạn đang gặp triệu chứng: **{symptom_text}**.\n"
        closing = "\nBạn có thể chia sẻ thêm để mình hỗ trợ chính xác hơn nhé:"
        return greeting + closing + "\n\n" + "\n".join(followup_questions)

    # Fallback GPT nếu DB không có follow-up câu hỏi
    symptom_prompt = join_symptom_names_vietnamese([s['name'] for s in symptoms])
    prompt = (
        f"Bạn là trợ lý y tế thân thiện. Người dùng có các triệu chứng: {symptom_prompt}. "
        "Hãy đặt một câu hỏi gợi mở, nhẹ nhàng để người dùng chia sẻ thêm thông tin (ví dụ mức độ, thời gian, điều gì làm nặng hơn). "
        "Tránh dùng từ chuyên môn và viết bằng tiếng Việt."
    )

    response = chat_completion([
        {"role": "system", "content": "Bạn là trợ lý y tế, cần giao tiếp thân thiện, dễ hiểu."},
        {"role": "user", "content": prompt}
    ])

    return f"😌 Mình đã ghi nhận bạn đang gặp triệu chứng: **{symptom_text}**.\n{response.strip()}"

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
def looks_like_followup(text: str) -> bool:
    """
    Nhận diện xem text có phải là câu bổ sung thông tin cho triệu chứng đã nêu không.
    """
    text = text.lower().strip()

    # 1. Câu ngắn (thường là bổ sung)
    if len(text.split()) <= 6:
        return True

    # 2. Chứa các từ khóa mô tả thời gian, mức độ, hoàn cảnh, màu sắc...
    followup_keywords = [
        "ban dem", "buoi toi", "buoi sang", "mau xanh", "mau vang", "mau trong",
        "luc nao", "thuong xuyen", "doi luc", "nang hon", "nhẹ hơn",
        "khi nam", "khi van dong", "khi di lai", "khi hit", "khi an",
        "co mui", "kho chiu", "co mui la", "rat nhieu", "mot chut"
    ]

    for kw in followup_keywords:
        if kw in text:
            return True

    # 3. Có chứa mô tả đơn giản nhưng không đủ để nhận diện là triệu chứng mới
    if re.match(r"^(khi|vao|luc|co|hay|thuong).*", text):
        return True

    return False

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


