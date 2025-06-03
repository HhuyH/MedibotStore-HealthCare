from fastapi import APIRouter, Query
from fastapi.responses import StreamingResponse
from models import Message, ChatRequest
from utils.openai_utils import chat, stream_chat
from utils.limit_history import limit_history_by_tokens
from prompts import system_message
import re
import requests
import json

router = APIRouter()

system_message_dict = {
    "role": "system",
    "content": system_message
}

def extract_sql(text):
    # 1. Ưu tiên tìm code block có dán ```sql ... ```
    code_block = re.search(r"```sql\s+(.*?)```", text, re.IGNORECASE | re.DOTALL)
    if code_block:
        return code_block.group(1).strip()

    # 2. Nếu không có code block thì tìm câu bắt đầu bằng SELECT ... ;
    select_stmt = re.search(r"(SELECT\s+.+?;)", text, re.IGNORECASE | re.DOTALL)
    if select_stmt:
        return select_stmt.group(1).strip()

    return None

@router.post("/chat")
async def chat_endpoint(msg: Message):
    limited_history = limit_history_by_tokens(system_message_dict, msg.history)
    reply = chat(msg.message, limited_history)
    print("Raw reply:", reply)  

    # Try parsing structured JSON response
    try:
        parsed = json.loads(reply)
        natural_text = parsed.get("natural_text", "")
        sql_query = parsed.get("sql_query", None)
    except json.JSONDecodeError:
        return {"reply": reply}  # fallback if not JSON

    if sql_query:
        try:
            response = requests.post("http://localhost/kms/chatbot_agent/query.php", data={"sql": sql_query})
            result = response.json()

            if "data" in result:
                rows = result["data"]
                result_text = "\n\ud83d\udcca K\u1ebft qu\u1ea3:\n"
                for row in rows:
                    result_text += "- " + ", ".join([f"{k}: {v}" for k, v in row.items()]) + "\n"
                natural_text += result_text
            elif "error" in result:
                natural_text += f"\n\u26a0\ufe0f L\u1ed7i t\u1eeb PHP: {result['error']}"
        except Exception as e:
            natural_text += f"\n\u26a0\ufe0f L\u1ed7i khi th\u1ef1c thi SQL: {str(e)}"

    return {"reply": natural_text}




@router.post("/chat/stream")
def chat_stream(msg: Message):
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

            # Detect if this is JSON (starts with { or [), else treat as plain text
            if not is_json_started and buffer.strip().startswith("{"):
                is_json_started = True

            if is_json_started:
                # JSON streaming
                try:
                    parsed = json.loads(buffer)
                    natural_text = parsed.get("natural_text", "")
                    new_text = natural_text[len(last_sent_text):]
                    if new_text:
                        data = {"text": new_text}
                        yield f"data: {json.dumps(data)}\n\n"
                        last_sent_text = natural_text
                except json.JSONDecodeError:
                    continue  # Wait until full JSON is received
            else:
                # Plain text streaming
                data = {"text": content}
                yield f"data: {json.dumps(data)}\n\n"

        # Handle SQL if JSON
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
                        # Lấy các cột từ keys của dict đầu tiên
                        headers = rows[0].keys()
                        
                        # Tạo header bảng Markdown
                        header_row = "| " + " | ".join(headers) + " |"
                        separator_row = "| " + " | ".join(["---"] * len(headers)) + " |"
                        
                        # Tạo các dòng dữ liệu
                        data_rows = []
                        for row in rows:
                            data_rows.append("| " + " | ".join(str(row[h]) for h in headers) + " |")
                        
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


