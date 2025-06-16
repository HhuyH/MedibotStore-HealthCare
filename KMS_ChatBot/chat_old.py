from fastapi import APIRouter, Body
from fastapi.responses import StreamingResponse
from models import Message, ResetRequest
from utils.openai_utils import stream_chat
from utils.limit_history import limit_history_by_tokens, refresh_system_context
from utils.intent_utils import detect_intent, build_system_message, should_trigger_diagnosis
from utils.auth_utils import has_permission, normalize_role
from utils.symptom_utils import extract_symptoms_gpt, get_symptom_list, save_symptoms_to_db, generate_symptom_note, generate_friendly_followup_question, get_related_symptoms_by_disease, looks_like_followup
from utils.sql_executor import run_sql_query
from utils.symptom_session import save_symptoms_to_session, get_symptoms_from_session, clear_symptoms_from_session, clear_symptoms_all_keys
from utils.disease_utils import predict_disease_based_on_symptoms, save_prediction_to_db, generate_diagnosis_summary
from utils.session_store import get_session_data, save_session_data
from config import DB_CONFIG
import re
import json
import asyncio
from rapidfuzz import fuzz
import unicodedata


# Chuẩn hóa để bắt đươc trường hợp
def normalize_text(text):
    text = text.lower()
    text = unicodedata.normalize('NFD', text)
    text = ''.join(c for c in text if unicodedata.category(c) != 'Mn')
    text = re.sub(r'[^\w\s]', '', text)
    text = re.sub(r'\s+', ' ', text).strip()
    return text

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

router = APIRouter()

INTENT_PIPELINES = {
    # 🩺 Truy vấn liên quan đến triệu chứng, lịch sử bệnh, AI chẩn đoán
    "symptom_query": ["symptom_extract", "chat"],

    # ✅ Phán đoán cuối cùng → lấy triệu chứng từ session & lưu DB
    "final_diagnosis": ["final_diagnosis"],

    # 📦 Truy vấn sản phẩm, đơn hàng, dịch vụ
    "product_query": ["sql"],

    # 👤 Truy vấn thông tin người dùng
    "user_query": ["sql"],

    # 💬 Trò chuyện tự do, thông báo, phản hồi tự nhiên
    "general_chat": ["chat"]
}

symptom_list = get_symptom_list()

@router.post("/chat/stream")
async def chat_stream(msg: Message = Body(...)):
    role = normalize_role(msg.role)
    # print(f"ID: {msg.user_id} User: ({msg.username}) Session:({msg.session_id}) với vai trò {role} gửi: {msg.message}")
    print(f"ID: {msg.user_id} gửi: {msg.message}")
    if not has_permission(role, "chat"):
        async def denied_stream():
            yield "data: ⚠️ Bạn không được phép thực hiện chức năng này.\n\n"
            await asyncio.sleep(1)
            yield "data: 😅 Vui lòng liên hệ admin để biết thêm chi tiết.\n\n"
        return StreamingResponse(denied_stream(), media_type="text/event-stream")

    session_data = await get_session_data(msg.session_id)
    last_intent = session_data.get("last_intent", None)

    intent = (await detect_intent(msg.message, session_key=msg.session_id, last_intent=last_intent)).lower().strip()
    session_data["last_intent"] = intent
    await save_session_data(msg.session_id, session_data)

    intent = intent.replace("intent:", "").strip()
    print("🎯 Intent phát hiện:", intent)

    pipeline = INTENT_PIPELINES.get(intent, [])

    symptoms = []
    suggestion = None
    session_key = msg.user_id or msg.session_id
    stored_symptoms = await get_symptoms_from_session(session_key)

    updated_session_data = None  # Sẽ lưu lại nếu cần

    async def event_generator():
        nonlocal symptoms, suggestion, updated_session_data
        buffer = ""
        sql_query = None
        natural_text = ""

        for step in pipeline:
            if step == "symptom_extract":
                symptoms, suggestion = extract_symptoms_gpt(msg.message, session_key)
                print("✅ Triệu chứng trích được:", symptoms)

                if not symptoms:
                    if stored_symptoms and looks_like_followup(msg.message):
                        print("⏭️ Câu trả lời có vẻ là bổ sung cho triệu chứng đã có.")
                        symptoms = stored_symptoms

                        asked_ids = session_data.get("asked_followup_ids", [])
                        unasked_symptoms = [s for s in stored_symptoms if s['id'] not in asked_ids]

                        if not unasked_symptoms:
                            print("✅ Tất cả triệu chứng đã hỏi follow-up. Không hỏi lại.")

                            session_symptoms = await get_symptoms_from_session(session_key)
                            if session_symptoms:
                                symptoms = session_symptoms

                            if await should_trigger_diagnosis(msg.message, symptoms):
                                print("⚡ GPT xác định đã đủ điều kiện chẩn đoán → chuyển sang final_diagnosis")

                                note = generate_symptom_note(msg.message)
                                save_symptoms_to_db(msg.user_id, symptoms, note)
                                diseases = predict_disease_based_on_symptoms(symptoms)
                                save_prediction_to_db(msg.user_id, symptoms, diseases, getattr(msg, "chat_id", None))
                                summary = generate_diagnosis_summary(diseases)

                                yield f"data: {json.dumps({'natural_text': summary})}\n\n"
                                yield "data: [DONE]\n\n"
                                return

                            yield f"data: {json.dumps({'natural_text': 'Cảm ơn bạn đã chia sẻ. Bạn còn cảm thấy gì khác thường nữa không?'})}\n\n"
                            yield "data: [DONE]\n\n"
                            return

                    # 👉 Nếu không phải bổ sung, và có gợi ý từ GPT → gửi câu hỏi gợi mở
                    elif suggestion:
                        print("🤖 GPT gợi ý phản hồi khi không có triệu chứng:", suggestion)
                        yield f"data: {json.dumps({'natural_text': suggestion})}\n\n"
                        yield "data: [DONE]\n\n"
                        return

                    # Nếu không có gì luôn thì fallback nhẹ
                    else:
                        yield f"data: {json.dumps({'natural_text': 'Mình chưa rõ bạn đang gặp triệu chứng gì. Bạn có thể mô tả cụ thể hơn được không?'})}\n\n"
                        yield "data: [DONE]\n\n"
                        return

                # Lưu triệu chứng mới nếu có
                existing_ids = {s['id'] for s in stored_symptoms}
                incoming_ids = {s['id'] for s in symptoms}
                only_existing = incoming_ids.issubset(existing_ids)

                if not only_existing:
                    updated = save_symptoms_to_session(session_key, symptoms)
                    print(f"🧾 Đã lưu tạm triệu chứng:", updated)
                else:
                    print("ℹ️ Không có triệu chứng mới, giữ nguyên danh sách cũ")
                    symptoms = stored_symptoms

                # Kiểm tra các triệu chứng chưa follow-up
                asked_ids = session_data.get("asked_followup_ids", [])
                unasked_symptoms = [s for s in symptoms if s['id'] not in asked_ids]

                if not unasked_symptoms:
                    print("✅ Tất cả triệu chứng đã hỏi follow-up. Không hỏi lại.")

                    if await should_trigger_diagnosis(msg.message, symptoms):
                        print("⚡ GPT xác định đã đủ điều kiện chẩn đoán → chuyển sang final_diagnosis")

                        note = generate_symptom_note(msg.message)
                        save_symptoms_to_db(msg.user_id, symptoms, note)
                        diseases = predict_disease_based_on_symptoms(symptoms)
                        save_prediction_to_db(msg.user_id, symptoms, diseases, getattr(msg, "chat_id", None))
                        summary = generate_diagnosis_summary(diseases)

                        yield f"data: {json.dumps({'natural_text': summary})}\n\n"
                        yield "data: [DONE]\n\n"
                        return

                    symptom_names = [s['name'] for s in symptoms]
                    response_text = (
                        f"Vậy là bạn đang gặp các triệu chứng như: {', '.join(symptom_names)}.\n\n"
                        "Bạn còn cảm thấy gì khác thường nữa không? Nếu không thì mình có thể kiểm tra thử xem bạn đang gặp vấn đề gì nhé!"
                    )
                    yield f"data: {json.dumps({'natural_text': response_text})}\n\n"
                    yield "data: [DONE]\n\n"
                    return

                followup_question = await generate_friendly_followup_question(unasked_symptoms, session_key=session_key)
                session_data["asked_followup_ids"] = asked_ids + [s['id'] for s in unasked_symptoms]

                combined_ids = list(existing_ids.union(incoming_ids))
                related = get_related_symptoms_by_disease(combined_ids)
                if related:
                    rel_names = [s['name'] for s in related[:3]]
                    followup_question += f" Ngoài ra, bạn có gặp thêm triệu chứng nào như: {', '.join(rel_names)} không?"

                yield f"data: {json.dumps({'natural_text': followup_question})}\n\n"
                yield "data: [DONE]\n\n"
                return

            elif step in ["chat", "sql"]:
                limited_history, system_message_dict = refresh_system_context(intent, stored_symptoms, msg.history)

                if stored_symptoms:
                    system_message_dict.update(build_system_message(intent, [s['name'] for s in stored_symptoms]))
                    limited_history.clear()
                    limited_history.extend(limit_history_by_tokens(system_message_dict, msg.history))

                buffer = ""  # 🔁 Gom nội dung GPT stream
                sql_query = None  # Tạm thời giữ lại nếu cần dùng

                async for chunk in stream_chat(msg.message, limited_history, system_message_dict):
                    delta = chunk.choices[0].delta
                    content = getattr(delta, "content", None)
                    if content:
                        buffer += content  # 🔁 Gom lại cho phân tích
                        yield f"data: {json.dumps({'natural_text': content})}\n\n"  # ✅ Stream từng đoạn

                # 🔍 Sau khi GPT stream xong → phân tích nếu cần (KHÔNG YIELD lại buffer)
                cleaned_buffer = buffer.strip()

                if cleaned_buffer.startswith("{") and "sql_query" in cleaned_buffer:
                    try:
                        parsed = json.loads(cleaned_buffer)
                        sql_query = parsed.get("sql_query")
                        natural_text = parsed.get("natural_text", "").strip()
                    except Exception as e:
                        print("⚠️ JSON parse lỗi:", e)

                if sql_query:
                    print("🛠️ Phát hiện có SQL. Đang thực thi...")
                    result = run_sql_query(sql_query)
                    if result.get("status") == "success":
                        rows = result.get("data", [])
                        if rows:
                            headers = rows[0].keys()
                            header_row = "| " + " | ".join(headers) + " |"
                            separator_row = "| " + " | ".join(["---"] * len(headers)) + " |"
                            data_rows = [
                                "| " + " | ".join(str(row[h]) for h in headers) + " |"
                                for row in rows
                            ]
                            result_text = "\n📊 Kết quả:\n" + "\n".join([header_row, separator_row] + data_rows) + "\n"
                        else:
                            result_text = "\n📊 Kết quả: Không có dữ liệu.\n"
                        yield f"data: {json.dumps({'natural_text': result_text, 'table': rows})}\n\n"
                    else:
                        yield f"data: {json.dumps({'natural_text': f'⚠️ Lỗi SQL: {result.get('error')}'})}\n\n"

        # ✅ Lưu session nếu có cập nhật
        if updated_session_data:
            await save_session_data(msg.session_id, updated_session_data)

        yield "data: [DONE]\n\n"
    

    return StreamingResponse(event_generator(), media_type="text/event-stream")


@router.post("/chat/reset")
async def reset_session(data: ResetRequest):
  
    session_id = data.session_id
    user_id = data.user_id  # cần truyền lên từ client

    await save_session_data(session_id, {})
    clear_symptoms_all_keys(user_id=user_id, session_id=session_id)

    return {"status": "success", "message": "Đã reset session!"}




