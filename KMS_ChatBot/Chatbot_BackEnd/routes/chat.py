from fastapi import APIRouter, Body
from fastapi.responses import StreamingResponse
import json
import asyncio
import logging
logger = logging.getLogger(__name__)

from models import Message,ResetRequest
from config.intents import INTENT_MAPPING, INTENT_PIPELINES
from utils.auth_utils import has_permission, normalize_role
from utils.session_store import get_session_data, save_session_data
from utils.intent_utils import detect_intent, build_system_message, should_trigger_diagnosis
from utils.symptom_utils import (
    extract_symptoms_gpt,
    generate_symptom_note,
    save_symptoms_to_db,
    generate_friendly_followup_question,
    looks_like_followup_with_gpt,
    get_symptom_list
)
from utils.symptom_session import save_symptoms_to_session, get_symptoms_from_session, clear_symptoms_all_keys
from utils.limit_history import limit_history_by_tokens, refresh_system_context
from utils.openai_utils import stream_chat
from utils.sql_executor import run_sql_query
from utils.disease_utils import predict_disease_based_on_symptoms, save_prediction_to_db, generate_diagnosis_summary


router = APIRouter()

symptom_list = get_symptom_list()

@router.post("/chat/stream")
async def chat_stream(msg: Message = Body(...)):
    role = normalize_role(msg.role)
    # logger.info(f"ID: {msg.user_id} User: ({msg.username}) Session:({msg.session_id}) với vai trò {role} gửi: {msg.message}")
    logger.info(f"📨 Nhận tin User: {msg.user_id} || Role: {role} || Message: {msg.message}")
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

    # Xác định mục tiêu người dùng để lấy chức năng phù hợp
    intent = intent.replace("intent:", "").strip()
    logger.info(f"🎯 Intent phát hiện: {intent}")

    # Xác định các bước xử lý
    pipeline = INTENT_PIPELINES.get(intent, [])
    logger.debug(f"[PIPELINE] Pipeline for intent '{intent}': {pipeline}")
    session_key = msg.user_id or msg.session_id
    stored_symptoms = await get_symptoms_from_session(session_key)

    updated_session_data = None  # Sẽ lưu lại nếu cần
    symptoms = []
    suggestion = None

    async def event_generator():
        buffer = ""
        is_json_mode = True

        nonlocal symptoms, suggestion, updated_session_data
        sql_query = None
        natural_text = ""
        for step in pipeline:
            # --- Step 1: Chat trước ---
            if step == "chat":
                limited_history, _ = refresh_system_context(intent, stored_symptoms, msg.history)
                symptoms = [s['name'] for s in stored_symptoms] if stored_symptoms else []
                system_message_dict = build_system_message(intent, symptoms)
                if stored_symptoms:
                    system_message_dict.update(build_system_message(intent, [s['name'] for s in stored_symptoms]))
                    limited_history.clear()
                    limited_history.extend(limit_history_by_tokens(system_message_dict, msg.history))

                async for chunk in stream_chat(msg.message, limited_history, system_message_dict):
                    delta = chunk.choices[0].delta
                    content = getattr(delta, "content", None)

                    if content:
                        buffer += content

                        # 🔍 Log nội dung nhận được
                        # logger.info(f"[DEBUG] GPT chunk delta: {delta}")
                        if intent not in ["sql_query", "product_query"]:
                            is_json_mode = False  # ✅ đảm bảo luôn stream với general_chat

                        # ✅ Chỉ bật JSON mode nếu là intent SQL
                        if intent in ["sql_query", "product_query"]:
                            if content.strip().startswith("{") or '"sql_query":' in content:
                                is_json_mode = True

                        if not is_json_mode:
                            # logger.info(f"[STREAM] Streaming chunk: {content}")
                            yield f"data: {json.dumps({'natural_text': content})}\n\n"
                            await asyncio.sleep(0.01)

            # --- Step 2: Trích xuất và từ vấn sức khỏe ---
            if step == "symptom_extract":
                symptoms, suggestion = extract_symptoms_gpt(msg.message, session_key)
                logger.info(f"✅ Triệu chứng trích được: {symptoms}")

                if not symptoms:
                    if stored_symptoms and looks_like_followup_with_gpt(msg.message):
                        symptoms = stored_symptoms

                        asked_ids = session_data.get("asked_followup_ids", [])
                        unasked_symptoms = [s for s in stored_symptoms if s['id'] not in asked_ids]

                        if not unasked_symptoms:
                            logger.info("✅ Tất cả triệu chứng đã hỏi follow-up. Không hỏi lại.")

                            session_symptoms = await get_symptoms_from_session(session_key)
                            if session_symptoms:
                                symptoms = session_symptoms

                            if await should_trigger_diagnosis(msg.message, symptoms):
                                logger.info("⚡ GPT xác định đã đủ điều kiện chẩn đoán → chuyển sang final_diagnosis")

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
                        logger.info(f"🤖 GPT gợi ý phản hồi khi không có triệu chứng: {suggestion}")
                        yield f"data: {json.dumps({'natural_text': suggestion})}\n\n"
                        await asyncio.sleep(0.01)  # ✅ flush nhẹ
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
                    logger.info(f"🧾 Đã lưu tạm triệu chứng: {updated}")
                else:
                    logger.info("ℹ️ Không có triệu chứng mới, giữ nguyên danh sách cũ")
                    symptoms = stored_symptoms

                # Kiểm tra các triệu chứng chưa follow-up
                asked_ids = session_data.get("asked_followup_ids", [])
                unasked_symptoms = [s for s in symptoms if s['id'] not in asked_ids]

                if not unasked_symptoms:
                    logger.info("✅ Tất cả triệu chứng đã hỏi follow-up. Không hỏi lại.")

                    if await should_trigger_diagnosis(msg.message, symptoms):
                        logger.info("⚡ GPT xác định đã đủ điều kiện chẩn đoán → chuyển sang final_diagnosis")

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

                yield f"data: {json.dumps({'natural_text': followup_question})}\n\n"
                yield "data: [DONE]\n\n"
                return
        
            # --- Step 3: Nếu cần xử lý SQL riêng biệt ---
            elif step == "sql":
                try:
                    logger.info(f"[DEBUG] Nội dung buffer để parse SQL: {buffer.strip()}")

                    # Kiểm tra và parse JSON
                    buffer_clean = buffer.strip()
                    if not buffer_clean.startswith("{") or not buffer_clean.endswith("}"):
                        raise ValueError("Dữ liệu không phải JSON hợp lệ")
                    
                    parsed = json.loads(buffer_clean)
                    sql_query = parsed.get("sql_query")
                    natural_text = parsed.get("natural_text", "").strip()

                except Exception as e:
                    sql_query = None
                    logger.warning(f"Lỗi phân tích JSON: {e}")
                    yield f"data: {json.dumps({'natural_text': '⚠️ Không thể xử lý câu hỏi SQL từ tin nhắn vừa rồi.'})}\n\n"
                    yield "data: [DONE]\n\n"
                    return

                # ✅ Nếu có natural_text (gợi mở đầu) → stream trước
                if natural_text:
                    yield f"data: {json.dumps({'natural_text': natural_text})}\n\n"

                # ✅ Chạy truy vấn nếu có SQL
                if sql_query:
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
                        error_msg = result.get("error", "Lỗi không xác định.")
                        yield f"data: {json.dumps({'natural_text': f'⚠️ Lỗi SQL: {error_msg}'})}\n\n"

                yield "data: [DONE]\n\n"

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




