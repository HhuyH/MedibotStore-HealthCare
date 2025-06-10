from fastapi import APIRouter, Body
from fastapi.responses import StreamingResponse
from models import Message
from utils.openai_utils import chat, stream_chat
from utils.limit_history import limit_history_by_tokens
from prompts.intent_utils import detect_intent, build_system_message
from utils.auth_utils import log_and_validate_user, has_permission, normalize_role
from utils.symptom_utils import extract_symptoms, load_symptom_list, extract_symptoms_gpt
import re
import requests
import json
import asyncio

INTENT_PIPELINES = {
    "health_query": ["symptom_extract", "chat", "save_symptom"],
    "product_query": ["sql"],
    "general_chat": ["chat"]
}

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
    intent = intent.replace("intent:", "").strip()
    print("🎯 Intent phát hiện:", intent)
    pipeline = INTENT_PIPELINES.get(intent, [])

    symptoms = []
    suggestion = None
    if "symptom_extract" in pipeline:
        symptoms, suggestion = extract_symptoms_gpt(msg.message)
        print("✅ Triệu chứng trích được:", symptoms)

        if suggestion and symptoms:
            async def suggestion_stream():
                yield f"data: {json.dumps({'natural_text': suggestion})}\n\n"
                yield "data: [DONE]\n\n"
            return StreamingResponse(suggestion_stream(), media_type="text/event-stream")

    system_message_dict = build_system_message(intent, [s['name'] for s in symptoms])
    limited_history = limit_history_by_tokens(system_message_dict, msg.history)

    def event_generator():
        buffer = ""

        if "chat" in pipeline or "sql" in pipeline:
            response = stream_chat(msg.message, limited_history, system_message_dict)
            for chunk in response:
                delta = chunk.choices[0].delta
                content = getattr(delta, "content", None)
                if content:
                    buffer += content

        print("🟡 RAW BUFFER >>>", repr(buffer))

        cleaned_buffer = buffer.strip()
        if cleaned_buffer.startswith("{{") and cleaned_buffer.endswith("}}"):  # Clean double curly
            cleaned_buffer = cleaned_buffer[1:-1]

        # 👁️‍🗨️ Kiểm tra xem buffer có phải JSON không
        is_json_like = '"natural_text"' in cleaned_buffer and ('"sql_query"' in cleaned_buffer or "SELECT" in cleaned_buffer)

        sql_query = None
        natural_text = ""

        if is_json_like:
            try:
                parsed = json.loads(cleaned_buffer)
                natural_text = parsed.get("natural_text", "").strip()
                sql_query = parsed.get("sql_query")
            except Exception as e:
                print("⚠️ Không parse được JSON:", e)
                natural_text = cleaned_buffer
        else:
            natural_text = cleaned_buffer

        if natural_text:
            response_data = {'natural_text': natural_text}
            if symptoms:
                response_data['symptoms'] = symptoms
            yield f"data: {json.dumps(response_data)}\n\n"


        # 🧠 Lưu triệu chứng nếu có
        if "save_symptom" in pipeline and symptoms and msg.user_id:
            try:
                payload = {"user_id": msg.user_id, "symptoms": symptoms}
                res = requests.post("http://localhost/kms/chatbot_agent/save_symptoms.php", json=payload)
                result = res.json()
                if result.get("status") == "success":
                    yield f"data: {json.dumps({'natural_text': '✅ Triệu chứng đã được lưu vào hồ sơ của bạn.'})}\n\n"
                else:
                    yield f"data: {json.dumps({'natural_text': '⚠️ Không thể lưu triệu chứng. Vui lòng thử lại sau.'})}\n\n"
            except Exception as e:
                print("❌ Lỗi khi gọi API PHP:", str(e))
                yield f"data: {json.dumps({'natural_text': '❌ Lỗi khi lưu triệu chứng. Vui lòng liên hệ admin.'})}\n\n"

        # 🧪 Nếu có SQL query → chạy luôn (kể cả khi không có trong pipeline)
        if sql_query:
            print("🛠️ Phát hiện có SQL. Đang thực thi...")
            try:
                res = requests.post("http://localhost/kms/chatbot_agent/query.php", data={"sql": sql_query})
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
                        result_text = "\n📊 Kết quả:\n" + "\n".join([header_row, separator_row] + data_rows) + "\n"
                    else:
                        result_text = "\n📊 Kết quả: Không có dữ liệu.\n"
                    yield f"data: {json.dumps({'natural_text': result_text})}\n\n"
                elif "error" in result:
                    yield f"data: {json.dumps({'natural_text': f'⚠️ Lỗi từ PHP: {result['error']}'})}\n\n"
            except Exception as e:
                error_msg = f"⚠️ Lỗi khi thực thi SQL: {str(e)}"
                print("❌ SQL Execution Error:", error_msg)
                yield f"data: {json.dumps({'natural_text': error_msg})}\n\n"

        yield "data: [DONE]\n\n"

    return StreamingResponse(event_generator(), media_type="text/event-stream")

