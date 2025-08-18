# utils/patient_summary.py

import pymysql
import json
from datetime import datetime
import re
from utils.openai_client import chat_completion
from config.config import DB_CONFIG
from datetime import datetime, timedelta
from utils.text_utils import normalize_text

def generate_patient_summary(user_id: int, for_date: str = None, action: str = "show_latest") -> dict:
    conn = pymysql.connect(**DB_CONFIG)
    symptom_rows = []
    prediction_rows = []
    prediction_date = None

    try:
        with conn.cursor() as cursor:
            # 1. Load triệu chứng (giữ nguyên)
            values = [user_id]
            date_filter = ""
            if for_date:
                try:
                    date_obj = datetime.strptime(for_date, "%d/%m/%Y").date()
                    date_filter = "AND h.record_date = %s"
                    values.append(date_obj)
                except:
                    date_obj = None
            else:
                date_obj = None

            cursor.execute(f"""
                SELECT s.name, h.record_date, h.notes
                FROM user_symptom_history h
                JOIN symptoms s ON h.symptom_id = s.symptom_id
                WHERE h.user_id = %s {date_filter}
                ORDER BY h.record_date DESC
                LIMIT 20
            """, tuple(values))
            symptom_rows = cursor.fetchall()

            # 2. Load dự đoán bệnh
            if action == "show_latest":
                cursor.execute("""
                    SELECT prediction_id, prediction_date
                    FROM health_predictions
                    WHERE user_id = %s
                    ORDER BY prediction_date DESC
                    LIMIT 1
                """, (user_id,))
                latest_pred = cursor.fetchone()

                if latest_pred:
                    latest_id, latest_date = latest_pred
                    cursor.execute("""
                        SELECT %s AS prediction_date, d.disease_name_raw, d.confidence, d.disease_summary, d.disease_care
                        FROM prediction_diseases d
                        WHERE d.prediction_id = %s
                    """, (latest_date, latest_id))
                    prediction_rows = cursor.fetchall()
                    prediction_date = latest_date.strftime("%d/%m/%Y")

            elif action == "show_all":
                cursor.execute("""
                    SELECT p.prediction_date, d.disease_name_raw, d.confidence, d.disease_summary, d.disease_care
                    FROM health_predictions p
                    JOIN prediction_diseases d ON p.prediction_id = d.prediction_id
                    WHERE p.user_id = %s
                    ORDER BY p.prediction_date DESC
                """, (user_id,))
                prediction_rows = cursor.fetchall()

                if prediction_rows:
                    prediction_date = prediction_rows[0][0].strftime("%d/%m/%Y")

            elif action == "ask_for_date" and date_obj:
                cursor.execute("""
                    SELECT p.prediction_date, d.disease_name_raw, d.confidence, d.disease_summary, d.disease_care
                    FROM health_predictions p
                    JOIN prediction_diseases d ON p.prediction_id = d.prediction_id
                    WHERE p.user_id = %s AND DATE(p.prediction_date) = %s
                    ORDER BY p.prediction_date DESC
                """, (user_id, date_obj))
                prediction_rows = cursor.fetchall()

                if prediction_rows:
                    prediction_date = prediction_rows[0][0].strftime("%d/%m/%Y")

    finally:
        conn.close()

    # Chuẩn bị dữ liệu cho prompt
    symptom_lines = []
    for name, date, note in symptom_rows:
        line = f"- {name} ({date.strftime('%d/%m/%Y')})"
        if note:
            line += f": {note.strip()}"
        symptom_lines.append(line)

    disease_lines = []
    for _, name, conf, summary, care in prediction_rows:
        percent = int(conf * 100)
        icon = "🔴" if conf >= 0.85 else "🟠" if conf >= 0.6 else "🟡"
        name_text = name.title() if name else "Không rõ"
        summary_text = summary.strip() if summary else "Không có mô tả."
        
        disease_block = f"{icon} <strong>{name_text}</strong> {summary_text}"
        if care:
            disease_block += f" Gợi ý: {care.strip()}"
        disease_lines.append(disease_block)

    # Prompt yêu cầu HTML đẹp
    gpt_prompt = f"""
        You are a medical assistant helping summarize a patient's clinical history for a Vietnamese doctor.

        Below is the patient's recent health data:

        🩺 Triệu chứng được báo cáo:
        {chr(10).join(symptom_lines) if symptom_lines else "(Không có triệu chứng gần đây)"}

        🧠 Dự đoán từ AI:
        {chr(10).join(disease_lines) if disease_lines else "(Không có phỏng đoán nào từ AI)"}

        Your task:
        - Write a structured clinical summary in **Vietnamese**, targeting a medical professional.
        - Use appropriate Vietnamese medical terminology when describing symptoms and diagnoses.
        - Keep the tone factual, clear, and professional — not overly friendly.

        Formatting requirements (HTML output):
        - Use <strong> to highlight each symptom name and disease name.
        - Use emoji to indicate AI confidence:
            • 🔴 for high confidence
            • 🟠 for moderate confidence
            • 🟡 for low confidence
        - The summary should have two parts:
            1. A paragraph listing all reported symptoms with dates.  
            • Highlight each symptom using <strong>.  
            • Include notes if available.

            2. A set of blocks describing AI-predicted diseases:  
            • Each block starts with emoji + <strong>disease name</strong>
            • Then a concise clinical description of the disease in Vietnamese  
            • If available, continue with care advice in Vietnamese.
            • After each AI-predicted disease, include a risk assessment based on AI prediction (e.g., “Khả năng mắc bệnh X: 80%”).  
            • Provide a short, professional clinical comment to support the doctor’s decision-making.  
            • Do not include general lifestyle advice; focus on clinical guidance.


        Additional style rules:
        - Do not use symbols like "--" or "→".
        - If care advice exists, embed it as a sentence beginning with **Gợi ý:**  
        (e.g., "Gợi ý: Bệnh nhân nên đo huyết áp và theo dõi thêm.")
        - Do not write in English. Output must be fully in Vietnamese.
        - Do not include Markdown — only use valid HTML tags.

        Final output:
        - One HTML block in Vietnamese
        - Well-structured, readable by doctors

        Do not include general lifestyle advice, hydration tips, or informal comments. Focus on clinical data and AI risk assessment for the doctor.

    """

    try:
        reply = chat_completion(
            [{"role": "user", "content": gpt_prompt}],
            temperature=0.4,
            max_tokens=700
        )
        summary_html = reply.choices[0].message.content.strip()

        summary_html = re.sub(r"^```html|```$", "", summary_html).strip()

        # print(" GPT raw output:\n", reply.choices[0].message.content)
    except Exception as e:
        summary_html = "⚠️ Không thể tạo tóm tắt. GPT gặp lỗi hoặc dữ liệu không đủ."

    summary_data = {
        "symptom_count": len(symptom_rows),
        "prediction_count": len(prediction_rows),
        "symptom_dates": list({d[1].strftime("%d/%m/%Y") for d in symptom_rows}),
        "latest_prediction_date": prediction_date or "N/A"
    }

    return {
        "markdown": summary_html, 
        "summary_data": summary_data,
        "raw_data": {
            "symptoms": symptom_rows,
            "prediction_diseases": prediction_rows
        }
    }


def gpt_decide_patient_summary_action(user_message: str, summary_data: dict) -> dict:
    """
    Dựa vào nội dung bác sĩ hỏi + dữ liệu hồ sơ bệnh nhân,
    GPT quyết định nên:
    - Hiển thị toàn bộ
    - Gợi ý lọc theo ngày
    - Yêu cầu thêm thông tin định danh
    """
    prompt = f"""
        You are a helpful assistant supporting a doctor who wants to view a patient's health summary.

        Here is the doctor's request:
        "{user_message}"

        Available data for the patient:
        - Symptom count: {summary_data.get("symptom_count", 0)}
        - Prediction count: {summary_data.get("prediction_count", 0)}
        - Symptom dates: {summary_data.get('symptom_dates', [])}
        - Latest prediction date: {summary_data.get('latest_prediction_date', 'N/A')}

        Decide what we should do next.

        You must return one of the following actions:
        - "show_all": if it's fine to show the full summary right away
        - "ask_for_date": if it seems too long or unclear, suggest choosing a specific date
        - "ask_for_user_info": if identifying information seems missing or too vague

        Instructions:

        - If the number of symptoms is more than 5, or there are multiple predictions, and the user did not specify a date, you should normally prefer "ask_for_date".

        - ❗BUT — if the user explicitly requests to view everything (e.g., “xem toàn bộ”, “cho tôi toàn bộ”, “toàn bộ tình hình”, “xem chi tiết hết”, “xem tất cả”, “full thông tin”, “toàn bộ phỏng đoán”, “tổng thể”),  
        then you **must return "show_all"** regardless of data size or missing date.

        - Also use "show_all" if the user asks to see the latest summary (e.g., “mới nhất”, “gần nhất”).

        - Use "ask_for_user_info" only if the user’s message is too vague or lacks identifying information.
        - If you detect the user's intent is to see the full or complete patient summary, you MUST return `"action": "show_all"` without exception.




        Return only a JSON object in this format:
        ```json
        {{
        "action": "show_all" | "ask_for_date" | "ask_for_user_info",
        "message": "Câu trả lời ngắn gọn bằng tiếng Việt để phản hồi bác sĩ"
        }}
    """.strip()
    try:
        reply = chat_completion(
            [{"role": "user", "content": prompt}],
            temperature=0.3,
            max_tokens=200
        )
        content = reply.choices[0].message.content.strip()

        # Nếu GPT trả về kèm ```json thì cắt ra
        if content.startswith("```json"):
            content = content.replace("```json", "").replace("```", "").strip()

        return json.loads(content)

    except Exception as e:
        return {
            "action": "show_all",
            "message": "Mình sẽ hiển thị toàn bộ thông tin gần nhất cho bác sĩ xem nha."
        }

# Hàm này sẽ:
# Dựa vào nội dung bác sĩ hỏi và dữ liệu hồ sơ bệnh nhân,
def patient_summary_action(user_message: str, summary_data: dict) -> dict:
    normalized_msg = normalize_text(user_message)
    symptom_count = summary_data.get("symptom_count", 0)
    prediction_count = summary_data.get("prediction_count", 0)

    # Các cụm từ gợi ý rõ ràng muốn xem toàn bộ
    FULL_KEYWORDS = [
        "xem toan bo", "toan bo tinh hinh", "xem tat ca", "full thong tin",
        "xem chi tiet het", "toan bo thong tin", "xem het", "xem toan the",
        "toan bo phong doan", "thong tin day du", "chi tiet nhat", "xem du lieu day du",
        "tat ca", "du lieu day du"
    ]
    RECENT_KEYWORDS = [
        "gan nhat", "moi nhat", "xem hom nay", "xem gan day", "xem lan cuoi", "xem lan gan nhat"
    ]

    if any(kw in normalized_msg for kw in RECENT_KEYWORDS):
        return {
            "action": "show_latest",
            "message": "Mình sẽ hiển thị thông tin gần nhất cho bác sĩ xem nha."
        }

    if any(kw in normalized_msg for kw in FULL_KEYWORDS):
        return {
            "action": "show_all",
            "message": "Mình sẽ hiển thị toàn bộ thông tin gần nhất cho bác sĩ xem nha."
        }

    if symptom_count > 5 or prediction_count > 2:
        return {
            "action": "ask_for_date",
            "message": "Xin bác sĩ cho biết ngày cụ thể mà bác muốn xem thông tin bệnh án của người dùng này."
        }

    return {
        "action": "show_all",
        "message": "Mình sẽ hiển thị thông tin gần nhất nha."
    }

def find_user_id_by_info(name: str = None, email: str = None, phone: str = None) -> dict | None:
    """
    Tìm user_id từ tên, email hoặc số điện thoại (có thể là đuôi).
    Trả về:
    {
        "user_id": int | None,
        "matched_by": "email" | "phone" | "name",
        "ambiguous": bool
    }
    """
    conn = pymysql.connect(**DB_CONFIG)
    try:
        with conn.cursor() as cursor:
            # 📧 Ưu tiên tìm theo email (rõ nhất)
            if email:
                cursor.execute("SELECT user_id FROM users_info WHERE email = %s", (email,))
                row = cursor.fetchone()
                if row:
                    return {"user_id": row[0], "matched_by": "email", "ambiguous": False}

            # 📱 Tìm theo số điện thoại
            if phone:
                if len(phone) >= 8:
                    # SĐT đầy đủ
                    cursor.execute("SELECT user_id FROM users_info WHERE phone = %s", (phone,))
                    row = cursor.fetchone()
                    if row:
                        return {"user_id": row[0], "matched_by": "phone", "ambiguous": False}
                else:
                    # Chỉ là đuôi số
                    cursor.execute("SELECT user_id FROM users_info WHERE phone LIKE %s", (f"%{phone}",))
                    results = cursor.fetchall()
                    if len(results) == 1:
                        return {"user_id": results[0][0], "matched_by": "phone", "ambiguous": False}
                    elif len(results) > 1:
                        return {"user_id": None, "matched_by": "phone_suffix", "ambiguous": True}

            # 👤 Tìm theo tên
            if name:
                cursor.execute("SELECT user_id FROM users_info WHERE full_name = %s", (name,))
                results = cursor.fetchall()
                if len(results) == 1:
                    return {"user_id": results[0][0], "matched_by": "name", "ambiguous": False}
                elif len(results) > 1:
                    return {"user_id": None, "matched_by": "name", "ambiguous": True}

    finally:
        conn.close()

    return None

def extract_date_from_text(text: str) -> str | None:
    """
    Trích xuất ngày từ văn bản. Trả về định dạng dd/mm/yyyy hoặc None nếu không tìm thấy.
    Hỗ trợ:
    - ngày 25/6, 05/01/2024
    - hôm qua, hôm kia, hôm nay, hôm trước, bữa kia
    - x ngày/hôm trước
    """
    text = text.lower().strip()
    today = datetime.today()
    date_result = None

    # 📌 Pattern dd/mm hoặc dd/mm/yyyy
    match = re.search(r'(\d{1,2})[\/\-](\d{1,2})(?:[\/\-](\d{2,4}))?', text)
    if match:
        day, month, year = match.groups()
        year = year or str(today.year)
        try:
            date_obj = datetime.strptime(f"{int(day):02d}/{int(month):02d}/{int(year)}", "%d/%m/%Y")
            return date_obj.strftime("%d/%m/%Y")
        except:
            pass

    # 📚 Từ khóa tương đương
    yesterday_words = ["hôm qua", "hôm trước", "bữa trước", "ngày hôm qua"]
    day_before_yesterday_words = ["hôm kia", "ngày kia", "bữa kia", "hôm bữa"]

    if any(kw in text for kw in yesterday_words):
        date_result = today - timedelta(days=1)
    elif any(kw in text for kw in day_before_yesterday_words):
        date_result = today - timedelta(days=2)
    elif "hôm nay" in text:
        date_result = today
    else:
        # ⏳ x ngày trước
        match = re.search(r'(\d+)\s*(ngày|hôm)\s*trước', text)
        if match:
            days = int(match.group(1))
            date_result = today - timedelta(days=days)

    if date_result:
        return date_result.strftime("%d/%m/%Y")
    return None


# Hàm này sẽ:
# Trích:
# 👤 Tên người (nếu có)
# 📧 Email (nếu có)
# 📱 Số điện thoại (có thể chỉ là đuôi 3–5 số)
def extract_name_email_phone(text: str) -> dict:
    """
    Trích tên, email, và số điện thoại (hoặc đuôi) từ chuỗi văn bản.
    Trả về dict {'name': ..., 'email': ..., 'phone': ...}
    """
    name = None
    email = None
    phone = None

    # 📧 Tìm email
    email_match = re.search(r'\b[\w\.-]+@[\w\.-]+\.\w{2,}\b', text)
    if email_match:
        email = email_match.group()

    # 📱 Tìm số điện thoại đầy đủ (10-11 số)
    phone_match = re.search(r'\b\d{8,11}\b', text)
    if phone_match:
        phone = phone_match.group()
    else:
        # Nếu không có sđt đầy đủ, tìm cụm kiểu "đuôi xxx" hoặc "...cuối là 456"
        phone_hint = re.search(r'(đuôi|cuối là|ending with)?\s*([0-9]{3,5})\b', text)
        if phone_hint:
            phone = phone_hint.group(2)

    # 👤 Tìm tên sau các từ khóa như "bệnh nhân", "tên là", "xem hồ sơ"
    name_match = re.search(r"(?:bệnh nhân|tên|hồ sơ|người tên)\s+([A-ZĐ][a-zàáạảãăâđêèéẹẻẽôơòóọỏõùúụủũưỳýỵỷỹ\s]+)", text, re.UNICODE)
    if name_match:
        name = name_match.group(1).strip()

    return {
        "name": name,
        "email": email,
        "phone": phone
    }

def extract_name_email_phone_gpt(text: str) -> dict:

    """
    Dùng GPT để trích xuất tên, email, và số điện thoại (hoặc đuôi số) từ đoạn văn.
    Trả về dict {'name': ..., 'email': ..., 'phone': ...}
    """
    prompt = f"""
    You are an assistant helping to extract identifying information about a patient mentioned in the following message.

    Message:
    "{text}"

    Extract the following if present:
    - Full name of the patient
    - Email address
    - Phone number (can be full or partial, e.g. "ending in 899", "last 3 digits 517")

    Return your answer as a JSON object like this:
    ```json
    {{
        "name": "Nguyen Van A",
        "email": "nguyenvana@example.com",
        "phone": "899"
    }}

    If any field is missing, return it as null or an empty string.
    """.strip()

    try:
        response = chat_completion(
            [{"role": "user", "content": prompt}],
            temperature=0.2,
            max_tokens=150
        )
        content = response.choices[0].message.content.strip()

        # Cắt bỏ ```json nếu có
        if content.startswith("```json"):
            content = content.replace("```json", "").replace("```", "").strip()

        result = json.loads(content)

        return {
            "name": result.get("name", "").strip() or None,
            "email": result.get("email", "").strip() or None,
            "phone": result.get("phone", "").strip() or None
        }

    except Exception as e:
        print(f"❌ Lỗi khi gọi GPT extract name/email/phone: {e}")
        return {"name": None, "email": None, "phone": None}
    

    from utils.name_utils import extract_name_email_phone

def resolve_user_id_from_message(msg_text: str) -> dict:
    """
    Trích thông tin định danh từ nội dung tin nhắn và tìm user_id tương ứng.
    Trả về dict gồm user_id, cách match, và cờ ambiguous.
    """
    try:
        extracted = extract_name_email_phone_gpt(msg_text)
        name = extracted.get("name")
        email = extracted.get("email")
        phone = extracted.get("phone")
    except:
        name = email = phone = None

    return find_user_id_by_info(name=name, email=email, phone=phone) or {
        "user_id": None, "matched_by": None, "ambiguous": False
    }
