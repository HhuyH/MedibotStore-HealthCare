import json
import pymysql
from config.config import DB_CONFIG
from datetime import date

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

    lines = ["Dựa trên những gì bạn chia sẻ, đây là một số khả năng có thể gặp:\n"]

    for d in diseases[:3]:  # chỉ lấy top 3
        name = d.get("name", "Không xác định")
        desc = d.get("description", "")
        care = d.get("treatment_guidelines", "")

        lines.append(f"• **{name}**: {desc.strip()[:120]}...")  # giới hạn mô tả
        if care:
            lines.append(f"   Gợi ý chăm sóc: {care.strip()[:100]}...")
    
    lines.append("\nNếu bạn cảm thấy triệu chứng trở nặng hoặc kéo dài, bạn nên đến cơ sở y tế để kiểm tra cụ thể.")
    return "\n".join(lines)

# Lấy id bệnh từ tên bệnh được trả về từ GPT tự phỏng đoán
def get_disease_id_by_name(disease_name: str) -> int | None:
    conn = pymysql.connect(**DB_CONFIG)
    try:
        with conn.cursor() as cursor:
            cursor.execute("SELECT id FROM diseases WHERE name = %s", (disease_name,))
            row = cursor.fetchone()
            return row[0] if row else None
    finally:
        conn.close()
