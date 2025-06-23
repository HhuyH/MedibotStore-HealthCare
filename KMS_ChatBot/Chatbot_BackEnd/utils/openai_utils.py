from config.config import MODEL
from .openai_client import chat_completion, chat_stream
import tiktoken
import re
def chat(message, history, system_message_dict):
    messages = [system_message_dict] + history + [{"role": "user", "content": message}]
    response = chat_completion(messages=messages)
    return response.choices[0].message.content

async def stream_chat(message, history, system_message_dict):
    messages = [system_message_dict] + history + [{"role": "user", "content": message}]
    stream = await chat_stream(model=MODEL, messages=messages)

    async for chunk in stream:
        yield chunk


# Danh sách emoji phổ biến trong tư vấn sức khỏe
COMMON_HEALTH_EMOJIS = set([
    "🌿", "😌", "💭", "😴", "🤒", "🤕", "🤧", "😷",
    "🥴", "🤢", "🤮", "🧘‍♂️", "📌", "💦", "😮‍💨",
    "❤️", "✅", "🔄", "❌", "⚠️", "🌀","😵‍💫", ""
])

def is_possible_emoji(token_id, enc):
    """
    Kiểm tra xem token có khả năng là emoji phổ biến không.
    """
    try:
        text = enc.decode([token_id])
        return any(char in COMMON_HEALTH_EMOJIS for char in text)
    except Exception:
        return False

def stream_gpt_tokens(text: str, model: str = "gpt-4o", max_default: int = 1):
    """
    Stream text giống GPT, chia token thông minh để tránh lỗi khi gặp emoji.
    """
    enc = tiktoken.encoding_for_model(model)
    tokens = enc.encode(text)
    buffer = []
    i = 0
    while i < len(tokens):
        token = tokens[i]
        buffer.append(token)

        # Nếu token có thể là emoji → gom nhiều hơn
        is_emoji = is_possible_emoji(token, enc)

        # Nếu gom đủ rồi hoặc không phải emoji → thử decode
        if len(buffer) >= (4 if is_emoji else max_default):
            try:
                chunk_text = enc.decode(buffer)
                yield chunk_text
                buffer.clear()
            except Exception:
                if len(buffer) >= 6:
                    # fallback nếu quá nhiều token vẫn decode fail
                    yield "[⚠️ lỗi emoji]"
                    buffer.clear()
        i += 1

    # Còn sót lại
    if buffer:
        try:
            yield enc.decode(buffer)
        except:
            yield "[⚠️ lỗi đoạn cuối]"



