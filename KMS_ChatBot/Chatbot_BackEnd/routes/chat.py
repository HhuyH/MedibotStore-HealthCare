from fastapi import APIRouter, Body
from fastapi.responses import StreamingResponse, JSONResponse
from models import Message
from utils.openai_utils import chat, stream_chat
from utils.limit_history import limit_history_by_tokens
from prompts.intent_utils import detect_intent, build_system_message
from utils.auth_utils import log_and_validate_user, has_permission, normalize_role
import re
import requests
import json
import asyncio

router = APIRouter()

def extract_sql(text):
    code_block = re.search(r"```sql\s+(.*?)```", text, re.IGNORECASE | re.DOTALL)
    if code_block:
        return code_block.group(1).strip()
    select_stmt = re.search(r"(SELECT\s+.+?;)", text, re.IGNORECASE | re.DOTALL)
    if select_stmt:
        return select_stmt.group(1).strip()
    return None


@router.post("/chat")
async def chat_endpoint(msg: Message):

    if not log_and_validate_user(msg):
        return {"reply": "Bạn không có quyền thực hiện hành động này."}

    intent = detect_intent(msg.message)
    system_message_dict = build_system_message(intent)

    limited_history = limit_history_by_tokens(system_message_dict, msg.history)
    reply = chat(msg.message, limited_history)
    print("Raw reply:", reply)

    try:
        parsed = json.loads(reply)
        natural_text = parsed.get("natural_text", "")
        sql_query = parsed.get("sql_query", None)
    except json.JSONDecodeError:
        return {"reply": reply}

    if sql_query:
        try:
            response = requests.post("http://localhost/kms/chatbot_agent/query.php", data={"sql": sql_query})
            result = response.json()
            if "data" in result:
                rows = result["data"]
                result_text = "\n📊 Kết quả:\n"
                for row in rows:
                    result_text += "- " + ", ".join([f"{k}: {v}" for k, v in row.items()]) + "\n"
                natural_text += result_text
            elif "error" in result:
                natural_text += f"\n⚠️ Lỗi từ PHP: {result['error']}"
        except Exception as e:
            natural_text += f"\n⚠️ Lỗi khi thực thi SQL: {str(e)}"

    return {"reply": natural_text}

@router.post("/chat/stream")
async def chat_stream(msg: Message = Body(...)):
    role = normalize_role(msg.role)
    print(f"User {msg.user_id} ({msg.username}) với vai trò {role} gửi: {msg.message}")

    if not has_permission(role, "chat"):
        async def denied_stream():
            yield "data: ⚠️ Bạn không được phép thực hiện chức năng này.\n\n"
            await asyncio.sleep(1)
            yield "data: 😅 Vui lòng liên hệ admin để biết thêm chi tiết.\n\n"
        return StreamingResponse(denied_stream(), media_type="text/event-stream")

    intent = detect_intent(msg.message)
    system_message_dict = build_system_message(intent)
    limited_history = limit_history_by_tokens(system_message_dict, msg.history)

    def event_generator():
        buffer = ""
        response = stream_chat(msg.message, limited_history)

        for chunk in response:
            delta = chunk.choices[0].delta
            content = getattr(delta, "content", None)
            if content:
                buffer += content

        print("🟡 RAW BUFFER >>>", repr(buffer))  # Debug raw buffer

        # Clean JSON nếu bị bao bởi {{ ... }}
        cleaned_buffer = buffer.strip()
        if cleaned_buffer.startswith("{{") and cleaned_buffer.endswith("}}"):
            cleaned_buffer = cleaned_buffer[1:-1]  # Remove 1 { and }

        sql_query = None
        natural_text = ""

        try:
            parsed = json.loads(cleaned_buffer)
            natural_text = parsed.get("natural_text", "")
            sql_query = parsed.get("sql_query")
        except Exception as e:
            print("❌ Không thể parse JSON từ buffer:", repr(cleaned_buffer))
            natural_text = buffer  # fallback nếu không phải JSON

        # Gửi phần trả lời tự nhiên
        if natural_text.strip():
            yield f"data: {json.dumps({'natural_text': natural_text.strip()})}\n\n"

        # Nếu có SQL thì gọi backend PHP
        if sql_query:
            try:
                res = requests.post(
                    "http://localhost/kms/chatbot_agent/query.php",
                    data={"sql": sql_query},
                )
                result = res.json()
                if "data" in result:
                    rows = result["data"]
                    if rows:
                        headers = rows[0].keys()
                        header_row = "| " + " | ".join(headers) + " |"
                        separator_row = "| " + " | ".join(["---"] * len(headers)) + " |"
                        data_rows = [
                            "| " + " | ".join(str(row[h]) for h in headers) + " |"
                            for row in rows
                        ]
                        result_text = (
                            "\n📊 Kết quả:\n"
                            + "\n".join([header_row, separator_row] + data_rows)
                            + "\n"
                        )
                    else:
                        result_text = "\n📊 Kết quả: Không có dữ liệu.\n"

                    yield f"data: {json.dumps({'natural_text': result_text})}\n\n"

                elif "error" in result:
                    error_msg = f"⚠️ Lỗi từ PHP: {result['error']}"
                    yield f"data: {json.dumps({'natural_text': error_msg})}\n\n"

            except Exception as e:
                error_msg = f"⚠️ Lỗi khi thực thi SQL: {str(e)}"
                print("❌ SQL Execution Error:", error_msg)
                yield f"data: {json.dumps({'natural_text': error_msg})}\n\n"

        yield "data: [DONE]\n\n"

    return StreamingResponse(event_generator(), media_type="text/event-stream")

