from fastapi import APIRouter, Body
from fastapi.responses import StreamingResponse
import json
import asyncio
import logging
logger = logging.getLogger(__name__)
from datetime import datetime

from models import Message,ResetRequest
from config.intents import INTENT_PIPELINES

from utils.limit_history import limit_history_by_tokens, refresh_system_context
from utils.auth_utils import has_permission, normalize_role
from utils.session_store import (
    get_session_data, 
    save_session_data, 
    get_symptoms_from_session, 
    clear_followup_asked_all_keys, 
    clear_symptoms_all_keys
)
from utils.intent_utils import detect_intent, build_system_message
from utils.symptom_utils import (
    get_symptom_list,
    has_diagnosis_today,
)
from utils.openai_utils import stream_chat
from utils.sql_executor import run_sql_query
from utils.health_care import (
    health_talk,
)
from utils.health_advice import health_advice
from utils.openai_utils import stream_gpt_tokens
from utils.patient_summary import (
    generate_patient_summary,
    gpt_decide_patient_summary_action,
    extract_date_from_text,
    resolve_user_id_from_message
)

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
        return StreamingResponse(denied_stream(), media_type="text/event-stream; charset=utf-8")

    # ✅ Load session data trước
    session_data = await get_session_data(user_id=msg.user_id, session_id=msg.session_id)

    # Ngày hôm nay
    today = datetime.now().strftime("%Y-%m-%d")

    # Lấy ngày hoạt động gần nhất
    last_active_date = session_data.get("active_date")

    # Nếu khác ngày → reset session (nhưng không trả lời ngay)
    if last_active_date and last_active_date != today:
        logger.info(f"🔄 Reset session vì đã qua ngày: {last_active_date} → {today}")
        
        # Gọi hàm reset
        await reset_session(data=ResetRequest(session_id=msg.session_id, user_id=msg.user_id or None))
        
        
        # Tải lại session sau khi reset
        session_data = await get_session_data(user_id=msg.user_id, session_id=msg.session_id)

    # Luôn cập nhật ngày hoạt động sau mỗi message
    session_data["active_date"] = today
    
    is_same_day = session_data.get("active_date") == today
    diagnosed_today = has_diagnosis_today(user_id=msg.user_id) if msg.user_id else False

    # Tạo recent_messages mới từ history
    # logger.info(f"🧪 msg.history type: {[type(m) for m in msg.history]}")
    # logger.info(f"🧪 msg.history raw: {msg.history}")


    recent_messages = [f"👤 {m.content}" if m.role == "user" else f"🤖 {m.content}" for m in msg.history]
    recent_messages = recent_messages[-6:]

    # Giữ lại tối đa 6 dòng gần nhất (3 cặp user-bot)
    recent_messages = recent_messages[-6:]

    # Tạo 2 danh sách riêng biệt
    recent_user_messages = [m.content for m in msg.history if m.role == "user"]
    recent_assistant_messages = [m.content for m in msg.history if m.role == "assistant"]

    # Lưu lại tối đa 6 dòng gần nhất (3 cặp user-bot)
    session_data["recent_messages"] = recent_messages[-6:]

    # Tối đa 3 dòng user gần nhất
    session_data["recent_user_messages"] = recent_user_messages[-3:]

    # Tối đa 3 dòng assistant gần nhất (cực kỳ quan trọng cho step 2 - avoid repeat)
    session_data["recent_assistant_messages"] = recent_assistant_messages[-3:]


    # logger.info("🧾 recent_user_messages:")
    # for i, user_msg in enumerate(session_data["recent_user_messages"], 1):
    #     logger.info(f"👤 [{i}] {user_msg}")

    # logger.info("📢 recent_assistant_messages:")
    # for i, assistant_msg in enumerate(session_data["recent_assistant_messages"], 1):
    #     logger.info(f"🤖 [{i}] {assistant_msg}")




    # 🔁 Phát hiện intent
    last_intent = session_data.get("last_intent", None)
    intent = await detect_intent(
        user_message=msg.message,
        session_id=msg.session_id,
        last_intent=last_intent,
        recent_messages=recent_messages,
        recent_user_messages=recent_user_messages,
        recent_assistant_messages=recent_assistant_messages
    )


    session_data["last_intent"] = intent
    save_session_data(msg.session_id, session_data)

    # Xác định mục tiêu người dùng để lấy chức năng phù hợp
    intent = intent.replace("intent:", "").strip()
    logger.info(f"🎯 Intent phát hiện: {intent}")

    # Xác định các bước xử lý
    pipeline = INTENT_PIPELINES.get(intent, [])
    logger.debug(f"[PIPELINE] Pipeline for intent '{intent}': {pipeline}")

    updated_session_data = None  # Sẽ lưu lại nếu cần
    symptoms = []
    suggestion = None

    async def event_generator():
        buffer = ""
        is_json_mode = True

        nonlocal symptoms, suggestion, updated_session_data, session_data
        sql_query = None
        natural_text = ""

        stored_symptoms = await get_symptoms_from_session(session_id=msg.session_id, user_id=msg.user_id)


        for step in pipeline:
            # --- Step 1: Chat tự nhiên ---
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
                        # logger.info(f"[STREAM] 🌊 Đang stream ra: {repr(content)}")  # 👈 Thêm dòng này để log từng mẩu
                        buffer += content

                        if intent not in ["sql_query", "product_query"]:
                            is_json_mode = False
                        if intent in ["sql_query", "product_query"]:
                            if content.strip().startswith("{") or '"sql_query":' in content:
                                is_json_mode = True

                        if not is_json_mode:
                            yield f"data: {json.dumps({'natural_text': content})}\n\n"
                            await asyncio.sleep(0.01)

            # --- Step 2: GPT điều phối health_talk ---
            elif step == "health_talk":
                chunks = []

                async for chunk in health_talk(
                    user_message=msg.message,
                    stored_symptoms=stored_symptoms,
                    recent_messages=recent_messages,
                    recent_user_messages=recent_user_messages,
                    recent_assistant_messages=recent_assistant_messages,
                    session_id=msg.session_id,
                    user_id=msg.user_id,
                    chat_id=getattr(msg, "chat_id", None),
                    session_context={
                        "is_same_day": is_same_day,
                        "diagnosed_today": diagnosed_today
                    }
                ):
                    chunks.append(chunk)
                    yield f"data: {json.dumps({'natural_text': chunk}, ensure_ascii=False)}\n\n"

                full_message = "".join(chunks).strip()

                final_message = full_message

                # ✅ Lưu message cuối của bot
                session_data["last_bot_message"] = final_message
                save_session_data(msg.session_id, session_data)

                yield "data: [DONE]\n\n"
                return

            # --- Step 2.1: GPT điều phối tư vấn sức khỏe thông thường ---
            elif step == "health_advice":

                chunks = []
                async for chunk in health_advice(msg.message, recent_messages):
                    delta = chunk.choices[0].delta
                    content = getattr(delta, "content", None)
                    if content:
                        chunks.append(content)
                        yield f"data: {json.dumps({'natural_text': content}, ensure_ascii=False)}\n\n"
                        await asyncio.sleep(0.02)

                final_message = "".join(chunks).strip()
                session_data["last_bot_message"] = final_message
                save_session_data(msg.session_id, session_data)

                yield "data: [DONE]\n\n"
                return

            # --- Step 2.2: GPT điều phối xem tổng quất triệu chứng và phỏng đoán từ AI cho bác sĩ ---
            elif step == "patient_summary":
                session_data = await get_session_data(user_id=msg.user_id, session_id=msg.session_id)
                user_id_for_summary = session_data.get("current_summary_user_id")

                # 1️⃣ Nếu chưa có user_id thì cố gắng extract từ câu hỏi
                if not user_id_for_summary:
                    info = resolve_user_id_from_message(msg.message)
                    if info and info.get("user_id"):
                        user_id_for_summary = info["user_id"]
                        session_data["current_summary_user_id"] = user_id_for_summary
                        save_session_data(msg.session_id, session_data)
                    else:
                        if info and info.get("ambiguous"):
                            match_type = info.get("matched_by")
                            if match_type == "phone_suffix":
                                message = "⚠️ Có nhiều người có số đuôi điện thoại giống nhau. Bạn có thể cho mình đầy đủ số điện thoại được không?"
                            else:
                                hint = {
                                    "name": "nhiều người trùng tên",
                                    "phone": "nhiều người có số giống nhau",
                                    "email": "nhiều người có email giống nhau"
                                }.get(match_type, "nhiều người trùng thông tin")
                                message = f"⚠️ Có {hint}. Bạn có thể cung cấp thêm email hoặc số điện thoại để xác định rõ hơn không?"
                        else:
                            message = "Bạn có thể cho mình biết thông tin người mà bạn muộn kiểm tra không?"

                        yield f"data: {json.dumps({'natural_text': message})}\n\n"
                        yield "data: [DONE]\n\n"
                        return

                # 2️⃣ Cố gắng trích ngày nếu có
                for_date = extract_date_from_text(msg.message)

                # 3️⃣ Gọi hàm sinh tổng hợp hồ sơ
                result = generate_patient_summary(user_id_for_summary, for_date=for_date)
                markdown = result["markdown"]
                summary_data = result["summary_data"]

                # Nếu không có ngày cụ thể, GPT quyết định có cần hỏi không
                if not for_date:
                    gpt_result = gpt_decide_patient_summary_action(msg.message, summary_data)
                    action = gpt_result.get("action")
                    message = gpt_result.get("message", "Mình sẽ hiển thị thông tin gần nhất nha.")

                    if action == "ask_for_date":
                        for chunk in stream_gpt_tokens(message):
                            yield f"data: {json.dumps({'natural_text': chunk})}\n\n"
                            await asyncio.sleep(0.03)
                        yield "data: [DONE]\n\n"
                        return
                    elif action == "ask_for_user_info":
                        yield f"data: {json.dumps({'natural_text': message})}\n\n"
                        yield "data: [DONE]\n\n"
                        return

                # 4️⃣ Hiển thị toàn bộ markdown
                yield f"data: {json.dumps({'natural_text': markdown})}\n\n"
                yield "data: [DONE]\n\n"
                return

            # --- Step 3: Xử lý SQL query nếu có ---
            elif step == "sql":
                try:
                    logger.info(f"[DEBUG] Nội dung buffer để parse SQL: {buffer.strip()}")

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

                if sql_query:
                    result = run_sql_query(sql_query)
                    if result.get("status") == "success":
                        rows = result.get("data", [])
                        if rows:
                            result_text = natural_text
                        else:
                            result_text = "📋 Không có dữ liệu phù hợp."

                        yield f"data: {json.dumps({'natural_text': result_text, 'table': rows})}\n\n"
                        payload = {'natural_text': result_text, 'table': rows}
                        logger.debug(f"[DEBUG] Payload gửi về frontend: {json.dumps(payload, ensure_ascii=False, indent=2)}")
                    else:
                        error_msg = result.get("error", "Lỗi không xác định.")
                        yield f"data: {json.dumps({'natural_text': f'⚠️ Lỗi SQL: {error_msg}'})}\n\n"

                yield "data: [DONE]\n\n"

        # ✅ Lưu session nếu có cập nhật
        if updated_session_data:
            save_session_data(msg.session_id, updated_session_data)

        yield "data: [DONE]\n\n"
    
    save_session_data(msg.session_id, session_data)
    return StreamingResponse(event_generator(), media_type="text/event-stream; charset=utf-8")


@router.post("/chat/reset")
async def reset_session(data: ResetRequest):
    session_id = data.session_id
    user_id = data.user_id

    # 🔁 Reset toàn bộ session RAM (session_store)
    save_session_data(session_id, {
        "last_intent": None,
        "recent_messages": [],
        "symptoms": [],
        "followup_asked": [],
        "symptom_notes": []
    })

    # 🧹 Reset luôn bộ nhớ symptom riêng nếu có
    await clear_symptoms_all_keys(user_id=user_id, session_id=session_id)
    await clear_followup_asked_all_keys(user_id=user_id, session_id=session_id)

    logger.info(f"✅ Đã reset session cho user_id={user_id}, session_id={session_id}")
    logger.debug(await get_session_data(user_id, session_id))  # Log lại để xác nhận

    return {"status": "success", "message": "Đã reset session!"}

async def not_use():
            # # --- Step 2: GPT điều phối health_talk ---
            # elif step == "health_talk":
            #     result = await gpt_health_talk(
            #         user_message=msg.message,
            #         stored_symptoms=stored_symptoms,
            #         recent_messages=recent_messages,
            #         session_key=msg.user_id or msg.session_id,
            #         user_id=msg.user_id,
            #         chat_id=getattr(msg, "chat_id", None)
            #     )

            #     if result.get("symptoms"):
            #         updated = save_symptoms_to_session(session_key, result["symptoms"])
            #         stored_symptoms = updated

            #     # ✅ Stream từng dòng nếu là message dài
            #     if result.get("trigger_diagnosis") or result.get("light_summary") or result.get("playful_reply"):
            #         async for line in stream_response_text(result["message"]):
            #             yield line
            #     elif result.get("followup_question"):
            #         yield f"data: {json.dumps({'natural_text': result['followup_question']})}\n\n"
            #     else:
            #         yield f"data: {json.dumps({'natural_text': result['message']})}\n\n"

            #     if result.get("end"):
            #         clear_symptoms_all_keys(user_id=msg.user_id, session_id=msg.session_id)

            #     yield "data: [DONE]\n\n"
            #     return

            # async def stream_response_text(text: str):
            #     for line in text.split("\n"):
            #         if line.strip():
            #             yield f"data: {json.dumps({'natural_text': line.strip()})}\n\n"
            #             await asyncio.sleep(0.01)
    return