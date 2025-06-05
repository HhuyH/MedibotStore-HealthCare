from fastapi import APIRouter
from fastapi.responses import StreamingResponse
from models import Message
from utils.openai_utils import chat, stream_chat
from utils.limit_history import limit_history_by_tokens
from intent_utils import detect_intent, build_system_message
import re
import requests
import json

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
def chat_stream(msg: Message):
    intent = detect_intent(msg.message)
    system_message_dict = build_system_message(intent)

    print("Received chat request:", msg.message)
    limited_history = limit_history_by_tokens(system_message_dict, msg.history)

    def event_generator():
        response = stream_chat(msg.message, limited_history)
        buffer = ""
        last_sent_text = ""
        is_json_started = False

        for chunk in response:
            delta = chunk.choices[0].delta
            content = getattr(delta, "content", None)
            if not content:
                continue

            buffer += content
            if not is_json_started and buffer.strip().startswith("{"):
                is_json_started = True

            if is_json_started:
                try:
                    parsed = json.loads(buffer)
                    natural_text = parsed.get("natural_text", "")
                    new_text = natural_text[len(last_sent_text):]
                    if new_text:
                        data = {"text": new_text}
                        yield f"data: {json.dumps(data)}\n\n"
                        last_sent_text = natural_text
                except json.JSONDecodeError:
                    continue
            else:
                data = {"text": content}
                yield f"data: {json.dumps(data)}\n\n"

        sql_query = None
        if is_json_started:
            try:
                parsed = json.loads(buffer)
                sql_query = parsed.get("sql_query")
            except:
                pass

        if sql_query:
            try:
                response = requests.post("http://localhost/kms/chatbot_agent/query.php", data={"sql": sql_query})
                result = response.json()
                if "data" in result:
                    rows = result["data"]
                    if rows:
                        headers = rows[0].keys()
                        header_row = "| " + " | ".join(headers) + " |"
                        separator_row = "| " + " | ".join(["---"] * len(headers)) + " |"
                        data_rows = [f"| {' | '.join(str(row[h]) for h in headers)} |" for row in rows]
                        result_text = "\n📊 Kết quả:\n" + "\n".join([header_row, separator_row] + data_rows) + "\n"
                    else:
                        result_text = "\n📊 Kết quả: Không có dữ liệu.\n"
                    yield f"data: {json.dumps({'text': result_text})}\n\n"
            except Exception as e:
                error_msg = f"\n⚠️ Lỗi khi thực thi SQL: {str(e)}"
                yield f"data: {json.dumps({'text': error_msg})}\n\n"

        print("DEBUG BUFFER >>>", buffer)
        yield "data: [DONE]\n\n"

    return StreamingResponse(event_generator(), media_type="text/event-stream")
