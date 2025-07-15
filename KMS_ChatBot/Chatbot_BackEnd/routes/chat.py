from fastapi import APIRouter, Body
from fastapi.responses import StreamingResponse
import json
import asyncio
import logging
logger = logging.getLogger(__name__)
from datetime import datetime
from redis import asyncio as aioredis

# Tạo client nếu cần
redis_client = aioredis.from_url("redis://localhost")

from models import Message,ResetRequest
from config.intents import INTENT_PIPELINES

from utils.limit_history import limit_history_by_tokens, refresh_system_context
from utils.auth_utils import enforce_permission, get_pipeline, log_intent_handling, normalize_role

from utils.session_store import (
    resolve_session_key,
    get_session_data, 
    save_session_data, 
    get_symptoms_from_session, 
    clear_followup_asked_all_keys, 
    clear_symptoms_all_keys,
    update_chat_history_in_session,
    reset_related_symptom_flag,
    clear_all_sessions_in_redis
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
from utils.product_suggester import suggest_product, summarize_products
from utils.patient_summary import (
    generate_patient_summary,
    gpt_decide_patient_summary_action,
    patient_summary_action,
    extract_date_from_text,
    resolve_user_id_from_message
)
import pymysql
from utils.booking import booking_appointment

from config.config import DB_CONFIG

router = APIRouter()

symptom_list = get_symptom_list()

@router.post("/chat/stream")
async def chat_stream(msg: Message = Body(...)):
    role = normalize_role(msg.role)
    # logger.info(f"ID: {msg.user_id} User: ({msg.username}) Session:({msg.session_id}) với vai trò {role} gửi: {msg.message}")
    logger.info(f"📨 Nhận tin User: {msg.user_id} || Role: {role} || msg: {msg.message}")

    # ✅ Load session data trước
    session_data = await get_session_data(user_id=msg.user_id, session_id=msg.session_id)

    session_data = await ensure_active_date_fresh(msg, session_data)

    # Cập nhật active_date
    today = datetime.now().strftime("%Y-%m-%d")
    is_same_day = session_data.get("active_date") == today

    diagnosed_today = has_diagnosis_today(user_id=msg.user_id) if msg.user_id else False
    
    # Sau khi bot xử lý xong và đã có câu trả lời cuối cùng:

    recent_messages = session_data.get("recent_messages", [])
    recent_user_messages = session_data.get("recent_user_messages", [])
    recent_assistant_messages = session_data.get("recent_assistant_messages", [])

    # ➕ Thêm câu mới nhất (user vừa gửi)
    recent_user_messages.append(msg.message)
    recent_messages.append(f"👤 {msg.message}")

    stored_symptoms = [s["name"] for s in session_data.get("stored_symptoms", []) if "name" in s]

    # 🔁 Phát hiện intent
    last_intent = session_data.get("last_intent", None)

    should_suggest_product = session_data.get("should_suggest_product", False)

    intent = await detect_intent(
        last_intent=last_intent,
        recent_user_messages=recent_user_messages,
        recent_assistant_messages=recent_assistant_messages,
        diagnosed_today=diagnosed_today,
        stored_symptoms=stored_symptoms,
        should_suggest_product=should_suggest_product
    )

    session_data["last_intent"] = intent

    # Xác định mục tiêu người dùng để lấy chức năng phù hợp
    intent = intent.replace("intent:", "").strip()

    # Áp quyền
    original_intent = intent
    intent = enforce_permission(role, intent)

    # Ghi log
    log_intent_handling(
        user_id=msg.user_id,
        username=msg.username,
        role=role,
        original_intent=original_intent,
        final_intent=intent
    )

    # Lấy pipeline tương ứng
    pipeline = INTENT_PIPELINES.get(intent, [])
    logger.debug(f"[PIPELINE] Pipeline for intent '{intent}': {pipeline}")

    updated_session_data = None  # Sẽ lưu lại nếu cần
    symptoms = []
    suggestion = None

    async def event_generator():
        buffer = ""
        is_json_mode = True
        final_bot_message = ""
        chat_id = None
        nonlocal symptoms, suggestion, updated_session_data, session_data
        sql_query = None
        natural_text = ""

        stored_symptoms = await get_symptoms_from_session(session_id=msg.session_id, user_id=msg.user_id)


        for step in pipeline:
            # --- Step 1: Chat tự nhiên ---
            if step == "chat":
                # Hệ thống hiện có sử dụng hàm `limit_history_by_tokens()` để giới hạn số token 
                # của lịch sử hội thoại khi gửi lên GPT (mặc định 1000 token). 
                # Tuy nhiên, việc giới hạn này hiện **chỉ áp dụng ở một số chức năng cụ thể** như `chat`,
                # chứ chưa áp dụng thống nhất cho toàn bộ các bước xử lý (e.g., health_talk, suggest_product).
                # Có thể mở rộng trong tương lai nếu cần đảm bảo ổn định cho các prompt dài.

                limited_history, _ = refresh_system_context(intent, stored_symptoms, msg.history)
                symptoms = [s['name'] for s in stored_symptoms] if stored_symptoms else []
                system_message_dict = build_system_message(
                    intent,
                    symptoms,
                    recent_user_messages=recent_user_messages,
                    recent_assistant_messages=recent_assistant_messages,
                    fallback_reason="insufficient_permission" if original_intent != intent else None
                )
                limited_history.clear()
                limited_history.extend(limit_history_by_tokens(system_message_dict, msg.history))

                async for chunk in stream_chat(msg.message, limited_history, system_message_dict):
                    delta = chunk.choices[0].delta
                    content = getattr(delta, "content", None)

                    if content:
                        # logger.info(f"[STREAM] 🌊 Đang stream ra: {repr(content)}") 
                        buffer += content

                        if intent not in ["sql_query", "product_query"]:
                            is_json_mode = False
                        if intent in ["sql_query", "product_query"]:
                            if content.strip().startswith("{") or '"sql_query":' in content:
                                is_json_mode = True

                        if not is_json_mode:
                            yield f"data: {json.dumps({'natural_text': content})}\n\n"
                            await asyncio.sleep(0.01)
                final_bot_message = buffer.strip()

                # ✅ Reload session sau khi health_talk đã cập nhật bằng mark_followup_asked, update_note, v.v.
                session_data = await get_session_data(user_id=msg.user_id, session_id=msg.session_id)
                updated_session_data = session_data

                await update_chat_history_in_session(msg.user_id, session_data, msg.session_id, msg.message, final_bot_message)

                # ✅ Lưu log hội thoại
                save_chat_log(user_id=msg.user_id, guest_id=None, intent=intent, message=msg.message, sender='user')
                chat_id = save_chat_log(user_id=msg.user_id, guest_id=None, intent=intent, message=final_bot_message, sender='bot')
   
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
                    chat_id=chat_id,
                    session_context={
                        "is_same_day": is_same_day,
                        "diagnosed_today": diagnosed_today
                    }
                ):
                    chunks.append(chunk)
                    yield f"data: {json.dumps({'natural_text': chunk}, ensure_ascii=False)}\n\n"

                full_message = "".join(chunks).strip()

                final_message = full_message
                final_bot_message = final_message

                # ✅ Reload session sau khi health_talk đã cập nhật bằng mark_followup_asked, update_note, v.v.
                session_data = await get_session_data(user_id=msg.user_id, session_id=msg.session_id)
                updated_session_data = session_data

                await update_chat_history_in_session(msg.user_id, session_data, msg.session_id, msg.message, final_bot_message)

                # ✅ Lưu log hội thoại
                save_chat_log(user_id=msg.user_id, guest_id=None, intent=intent, message=msg.message, sender='user')
                chat_id = save_chat_log(user_id=msg.user_id, guest_id=None, intent=intent, message=final_bot_message, sender='bot')

                # ✅ Lưu message cuối của bot
                session_data["last_bot_message"] = final_message

                yield "data: [DONE]\n\n"
                return

            # --- Step 2.1: GPT điều phối tư vấn sức khỏe thông thường ---
            elif step == "health_advice":
                result = await health_advice(msg.message, recent_messages)

                # Stream message
                for chunk in stream_gpt_tokens(result["natural_text"]):
                    yield f"data: {json.dumps({'natural_text': chunk}, ensure_ascii=False)}\n\n"
                    await asyncio.sleep(0.065)

                # Lưu flag
                session_data["should_suggest_product"] = result.get("should_suggest_product", False)

                logger.info("💡 [health_advice] Gợi ý sản phẩm:\n%s", json.dumps({
                    "should_suggest_product": session_data["should_suggest_product"],
                }, ensure_ascii=False))

                await save_session_data(user_id=msg.user_id, session_id=msg.session_id, data=session_data)

                # Lưu lịch sử hội thoại
                final_bot_message = result["natural_text"].strip()
                await update_chat_history_in_session(msg.user_id, session_data, msg.session_id, msg.message, final_bot_message)
                save_chat_log(user_id=msg.user_id, guest_id=None, intent=intent, message=msg.message, sender='user')
                save_chat_log(user_id=msg.user_id, guest_id=None, intent=intent, message=final_bot_message, sender='bot')

                yield "data: [DONE]\n\n"
                return

            # --- Step 2.2: GPT điều phối xem tổng quất triệu chứng và phỏng đoán từ AI cho bác sĩ ---
            elif step == "patient_summary":
                session_data = await get_session_data(user_id=msg.user_id, session_id=msg.session_id)
                updated_session_data = session_data
                user_id_for_summary = session_data.get("current_summary_user_id")

                # 1️⃣ Nếu chưa có user_id thì cố gắng extract từ câu hỏi
                if not user_id_for_summary:
                    info = resolve_user_id_from_message(msg.message)
                    if info and info.get("user_id"):
                        user_id_for_summary = info["user_id"]
                        session_data["current_summary_user_id"] = user_id_for_summary
                        await save_session_data(user_id=msg.user_id, session_id=msg.session_id, data=session_data)
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

                result = generate_patient_summary(user_id_for_summary, for_date=for_date)
                markdown = result["markdown"]
                summary_data = result["summary_data"]

                # Nếu không có ngày cụ thể, GPT quyết định có cần hỏi không
                if not for_date:
                    gpt_result = patient_summary_action(msg.message, summary_data)
                    action = gpt_result.get("action")
                    message = gpt_result.get("message", "Mình sẽ hiển thị thông tin gần nhất nha.")

                    if action == "ask_for_date" or action == "ask_for_user_info":
                        # ✅ Lưu lại cả message hỏi ngày/user info
                        await update_chat_history_in_session(msg.user_id, session_data, msg.session_id, msg.message, message)
                        save_chat_log(user_id=msg.user_id, guest_id=None, intent=intent, message=msg.message, sender='user')
                        save_chat_log(user_id=msg.user_id, guest_id=None, intent=intent, message=message, sender='bot')

                        for chunk in stream_gpt_tokens(message):
                            yield f"data: {json.dumps({'natural_text': chunk})}\n\n"
                            await asyncio.sleep(0.03)
                        yield "data: [DONE]\n\n"
                        return
                                # 3️⃣ Gọi hàm sinh tổng hợp hồ sơ

                final_bot_message = markdown

                await update_chat_history_in_session(msg.user_id, session_data, msg.session_id, msg.message, final_bot_message)
                
                # ✅ Lưu log hội thoại
                save_chat_log(user_id=msg.user_id, guest_id=None, intent=intent, message=msg.message, sender='user')
                chat_id = save_chat_log(user_id=msg.user_id, guest_id=None, intent=intent, message=final_bot_message, sender='bot')

                # 4️⃣ Hiển thị toàn bộ markdown
                yield f"data: {json.dumps({'natural_text': markdown})}\n\n"
                yield "data: [DONE]\n\n"
                return

            # --- Step 2.3: GPT điều phối gợi ý sản phẩm ---
            elif step == "suggest_product":
                gpt_result = await suggest_product(recent_messages=recent_messages)
                logger.info(f"[DEBUG] GPT result:\n{json.dumps(gpt_result, indent=2, ensure_ascii=False)}")

                sql = gpt_result.get("sql_query")
                suggest_type = gpt_result.get("suggest_type", "general")

                if not sql:
                    logger.warning("⚠️ GPT không trả về SQL query nào.")
                    yield f"data: 📋 Không tìm thấy sản phẩm phù hợp.\n\n"
                    yield "data: [DONE]\n\n"
                    return

                sql_result = run_sql_query(sql)
                logger.info(f"[SQL DEBUG] Kết quả truy vấn:\n{json.dumps(sql_result, ensure_ascii=False, indent=2, default=str)}")
                rows = sql_result.get("data", []) if sql_result.get("status") == "success" else []

                if not rows:
                    yield f"data: 📋 Không tìm thấy sản phẩm phù hợp.\n\n"
                    yield "data: [DONE]\n\n"
                    return

                # 👉 CHỈ GỌI summarize_products 1 LẦN với tất cả sản phẩm
                full_message = ""
                chunks = []

                async for chunk in summarize_products(
                    suggest_type=suggest_type,
                    products=rows,
                    recent_messages=recent_messages
                ):
                    chunks.append(chunk)
                    yield f"data: {json.dumps({'natural_text': chunk})}\n\n"
                    await asyncio.sleep(0.01)
                    full_message += chunk

                # ✅ Lưu lịch sử sau khi stream xong
                await update_chat_history_in_session(
                    msg.user_id, session_data, msg.session_id, msg.message, full_message
                )
                save_chat_log(user_id=msg.user_id, guest_id=None, intent=intent, message=msg.message, sender='user')
                save_chat_log(user_id=msg.user_id, guest_id=None, intent=intent, message=full_message, sender='bot')

                yield "data: [DONE]\n\n"

            # --- Step 2.4: GPT điều phối hỗ trợ đặt lịch ---
            elif step == "booking":
                
                chunks = []
                async for chunk in booking_appointment(
                    user_message=msg.message,
                    recent_messages=recent_messages,
                    recent_user_messages=recent_user_messages,
                    recent_assistant_messages=recent_assistant_messages,
                    session_id=msg.session_id,
                    user_id=msg.user_id
                ):
                        
                    if isinstance(chunk, dict):
                        msg_text = chunk.get("message", "")
                    else:
                        msg_text = str(chunk)
                    
                    chunks.append(msg_text)
                    yield f"data: {json.dumps({'natural_text': msg_text}, ensure_ascii=False)}\n\n"

                full_message = "".join(chunks).strip()


                final_message = full_message
                final_bot_message = final_message

                # ✅ Reload session sau khi health_talk đã cập nhật bằng mark_followup_asked, update_note, v.v.
                session_data = await get_session_data(user_id=msg.user_id, session_id=msg.session_id)
                updated_session_data = session_data

                await update_chat_history_in_session(msg.user_id, session_data, msg.session_id, msg.message, final_bot_message)

                # ✅ Lưu log hội thoại
                save_chat_log(user_id=msg.user_id, guest_id=None, intent=intent, message=msg.message, sender='user')
                chat_id = save_chat_log(user_id=msg.user_id, guest_id=None, intent=intent, message=final_bot_message, sender='bot')

                # ✅ Lưu message cuối của bot
                session_data["last_bot_message"] = final_message

                yield "data: [DONE]\n\n"
                return
            
            # --- Step 3: Xử lý SQL query nếu có ---
            elif step == "sql":
                logger.debug(f"🧪 Step 'sql' nhận buffer:\n{buffer}")
                if not buffer:
                    yield "data: ⚠️ Không có dữ liệu để xử lý SQL.\n\n"
                    yield "data: [DONE]\n\n"
                    return
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

                            final_bot_message = natural_text

                            await update_chat_history_in_session(
                                msg.user_id, session_data, msg.session_id, msg.message, final_bot_message
                            )
                            save_chat_log(user_id=msg.user_id, guest_id=None, intent=intent, message=msg.message, sender='user')
                            save_chat_log(user_id=msg.user_id, guest_id=None, intent=intent, message=final_bot_message, sender='bot')
                        else:
                            result_text = "📋 Không có dữ liệu phù hợp."

                        yield f"data: {json.dumps({'natural_text': result_text, 'table': rows})}\n\n"
                        payload = {'natural_text': result_text, 'table': rows}
                        logger.debug(f"[DEBUG] Payload gửi về frontend: {json.dumps(payload, ensure_ascii=False, indent=2)}")
                    else:
                        error_msg = result.get("error", "Lỗi không xác định.")
                        yield f"data: {json.dumps({'natural_text': f'⚠️ Lỗi SQL: {error_msg}'})}\n\n"

                # ✅ Lưu lịch sử nếu có bảng kết quả và natural_text
   


                yield "data: [DONE]\n\n"

        # ✅ Lưu session nếu có cập nhật
        # if updated_session_data:
        #     await save_session_data(user_id=msg.user_id, session_id=msg.session_id, data=updated_session_data)

        yield "data: [DONE]\n\n"
    return StreamingResponse(event_generator(), media_type="text/event-stream; charset=utf-8")

@router.post("/chat/reset")
async def reset_session(data: ResetRequest):
    session_id = data.session_id
    user_id = data.user_id

    # 🔁 Reset toàn bộ session RAM (session_store)
    await save_session_data(
        user_id=user_id,
        session_id=session_id,
        data={
            "last_intent": None,
            "recent_messages": [],
            "recent_user_messages": [],
            "recent_assistant_messages": [],
            "symptoms": [],
            "followup_asked": [],
            "symptom_notes_list": [],
            "booking_info": [],
            "extracted_info": [],
            "related_symptom_asked": False
        }
    )

    # Reset toan bo session
    await clear_all_sessions_in_redis()

    # 🧹 Reset luôn bộ nhớ symptom riêng nếu có
    await clear_symptoms_all_keys(user_id=user_id, session_id=session_id)
    await clear_followup_asked_all_keys(user_id=user_id, session_id=session_id)
    await reset_related_symptom_flag(session_id=session_id, user_id=user_id)

    await redis_client.delete(resolve_session_key(user_id, session_id))


    # logger.info(f"✅ Đã reset session cho user_id={user_id}, session_id={session_id}")
    logger.debug(await get_session_data(user_id, session_id))  # Log lại để xác nhận

    return {"status": "success", "message": "Đã reset session!"}

@router.get("/chat/history")
async def get_chat_history(session_id: str, user_id: int = None):
    session = await get_session_data(session_id=session_id, user_id=user_id)
    return {
        "recent_messages": session.get("recent_messages", [])
    }

def get_today_str():
    return datetime.now().strftime("%Y-%m-%d")

async def ensure_active_date_fresh(msg, session_data):
    today = get_today_str()
    last_active_date = session_data.get("active_date")

    # Nếu chưa có active_date → gán luôn
    if not last_active_date:
        logger.debug("📅 Lần đầu ghi nhận active_date → gán hôm nay")
        session_data["active_date"] = today
        return session_data

    # Nếu đã qua ngày → reset
    if last_active_date != today:
        logger.info(f"🔄 Reset session vì đã qua ngày: {last_active_date} → {today}")
        await reset_session(data=ResetRequest(session_id=msg.session_id, user_id=msg.user_id or None))
        session_data = await get_session_data(user_id=msg.user_id, session_id=msg.session_id)
        session_data["active_date"] = today
    else:
        # ✅ Cập nhật lại nếu cùng ngày (đảm bảo đồng bộ)
        session_data["active_date"] = today

    return session_data


@router.get("/chat/logs")
def get_chat_logs(session_id: str = None, user_id: int = None, guest_id: int = None, limit: int = 30):
    import pymysql
    from config.config import DB_CONFIG

    conn = pymysql.connect(**DB_CONFIG)
    try:
        with conn.cursor() as cursor:
            if user_id:
                cursor.execute("""
                    SELECT message, sender, sent_at
                    FROM chat_logs
                    WHERE user_id = %s
                    ORDER BY sent_at DESC
                    LIMIT %s
                """, (user_id, limit))
            elif guest_id:
                cursor.execute("""
                    SELECT message, sender, sent_at
                    FROM chat_logs
                    WHERE guest_id = %s
                    ORDER BY sent_at DESC
                    LIMIT %s
                """, (guest_id, limit))
            else:
                return []

            rows = list(cursor.fetchall())   # ✅ Chuyển thành list
            rows.reverse()                   # ✅ Đảo chiều để hiện từ cũ → mới

            return [{"message": m, "sender": s, "time": str(t)} for m, s, t in rows]
    finally:
        conn.close()


def save_chat_log(user_id=None, guest_id=None, intent=None, message=None, sender='user'):
    conn = pymysql.connect(**DB_CONFIG)
    try:
        with conn.cursor() as cursor:
            cursor.execute("""
                INSERT INTO chat_logs (user_id, guest_id, intent, message, sender)
                VALUES (%s, %s, %s, %s, %s)
            """, (user_id, guest_id, intent, message, sender))
            conn.commit()
            return cursor.lastrowid  # 👉 trả về chat_id vừa insert
    finally:
        conn.close()


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