# utils/patient_summary.py

import pymysql
import json
from datetime import datetime
import re
from utils.openai_client import chat_completion
from config.config import DB_CONFIG
from datetime import datetime, timedelta

def generate_patient_summary(user_id: int, for_date: str = None) -> dict:
    """
    Trả về:
    - markdown: nội dung tóm tắt hiển thị
    - summary_data: số lượng triệu chứng, dự đoán, các mốc ngày → để GPT quyết định hành động
    - raw_data: dữ liệu gốc (optional)
    """
    conn = pymysql.connect(**DB_CONFIG)
    symptom_rows = []
    prediction_data = None
    prediction_count = 0
    try:
        with conn.cursor() as cursor:
            # 📅 Chuẩn hóa ngày nếu có
            date_filter = ""
            values = [user_id]
            if for_date:
                try:
                    date_obj = datetime.strptime(for_date, "%d/%m/%Y").date()
                    date_filter = "AND h.record_date = %s"
                    values.append(date_obj)
                except:
                    print("⚠️ Ngày không hợp lệ. Bỏ qua lọc ngày.")
                    date_obj = None
            else:
                date_obj = None

            # 🔍 Lấy triệu chứng
            cursor.execute(f"""
                SELECT s.name, h.record_date, h.notes
                FROM user_symptom_history h
                JOIN symptoms s ON h.symptom_id = s.symptom_id
                WHERE h.user_id = %s {date_filter}
                ORDER BY h.record_date DESC
                LIMIT 10
            """, tuple(values))
            symptom_rows = cursor.fetchall()

            # 🔍 Dự đoán AI
            pred_query = """
                SELECT prediction_date, details
                FROM health_predictions
                WHERE user_id = %s
            """
            pred_params = [user_id]

            if date_obj:
                pred_query += " AND DATE(prediction_date) = %s"
                pred_params.append(date_obj)

            pred_query += " ORDER BY prediction_date DESC"
            cursor.execute(pred_query, tuple(pred_params))
            pred_results = cursor.fetchall()

            if pred_results:
                prediction_count = len(pred_results)
                row = pred_results[0]
                prediction_data = {
                    "prediction_date": row[0].strftime("%d/%m/%Y"),
                    "details": json.loads(row[1])
                }

    finally:
        conn.close()

    # 📦 Chuẩn bị metadata
    symptom_dates = list({d[1].strftime("%d/%m/%Y") for d in symptom_rows})
    latest_pred_date = prediction_data["prediction_date"] if prediction_data else None

    summary_data = {
        "symptom_count": len(symptom_rows),
        "prediction_count": prediction_count,
        "symptom_dates": symptom_dates,
        "latest_prediction_date": latest_pred_date or "N/A"
    }

    # 📝 Format Markdown
    lines = ["## 🧾 Hồ sơ tóm tắt bệnh nhân"]

    if symptom_rows:
        lines.append("\n🩺 **Triệu chứng đã ghi nhận:**")
        for name, date, note in symptom_rows:
            date_str = date.strftime("%d/%m/%Y")
            note_part = f" ({note.strip()})" if note else ""
            lines.append(f"- {name} — {date_str}{note_part}")
    else:
        lines.append("\n🩺 **Triệu chứng đã ghi nhận:** (không có dữ liệu gần đây)")

    if prediction_data:
        lines.append(f"\n🤖 **Dự đoán gần nhất từ AI** ({prediction_data['prediction_date']}):")
        diseases = prediction_data["details"].get("diseases", [])
        for d in diseases:
            name = d.get("name", "Không rõ")
            conf = int(d.get("confidence", 0.0) * 100)
            summary = d.get("summary", "").strip()
            care = d.get("care", "").strip()
            lines.append(f"- **{name}** (~{conf}%): {summary}")
            if care:
                lines.append(f"  → Gợi ý: {care}")
    else:
        lines.append("\n🤖 **Dự đoán gần nhất từ AI:** (chưa có dữ liệu)")

    lines.append("\n📌 Nếu triệu chứng trở nặng, hãy tư vấn thêm với bác sĩ hoặc đi khám ngay.")

    return {
        "markdown": "\n".join(lines),
        "summary_data": summary_data,
        "raw_data": {
            "symptoms": symptom_rows,
            "prediction": prediction_data
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
        - If the number of symptoms is more than 5, or there are multiple predictions, and the user did not specify a date, you should prefer "ask_for_date".
        - Only use "show_all" if the amount of information is small, or if the user clearly asked for the latest summary.
        - If the user message is vague or you can't identify which patient they mean, choose "ask_for_user_info".

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
