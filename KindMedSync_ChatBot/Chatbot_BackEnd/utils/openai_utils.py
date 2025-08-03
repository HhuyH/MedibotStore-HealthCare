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
    "❤️", "✅", "🔄", "❌", "⚠️", "🌀","😵‍💫", "💧",
    "😴", "☕", "🌞","🍵"
])

def is_possible_emoji(token_id, enc):
    try:
        text = enc.decode([token_id])
        return any(char in COMMON_HEALTH_EMOJIS for char in text)
    except Exception:
        return False



def stream_gpt_tokens(text: str, model: str = "gpt-4o"):
    """
    Stream text giống GPT nhưng chống gãy icon, kể cả khi icon ở đầu chuỗi.
    Giữ lại 2 token trước emoji và 6 token sau emoji rồi mới decode.
    """
    enc = tiktoken.encoding_for_model(model)
    tokens = enc.encode(text)

    buffer = []
    pre_emoji_buffer = []
    hold_mode = False
    post_emoji_hold = 0

    i = 0
    while i < len(tokens):
        token = tokens[i]
        token_text = enc.decode([token])
        is_emoji = is_possible_emoji(token, enc)

        if hold_mode:
            buffer.append(token)
            post_emoji_hold -= 1

            if post_emoji_hold <= 0:
                try:
                    full = enc.decode(pre_emoji_buffer + buffer)
                    yield full
                except Exception:
                    yield "[⚠️ lỗi emoji]"
                buffer.clear()
                pre_emoji_buffer.clear()
                hold_mode = False
        else:
            if is_emoji:
                # Kích hoạt giữ token nếu thấy emoji
                hold_mode = True
                post_emoji_hold = 10  # giữ thêm token sau emoji

                # Lưu lại 2 token trước emoji
                pre_emoji_buffer = buffer[-2:] if len(buffer) >= 2 else buffer[:]
                buffer = buffer[:-2] if len(buffer) >= 2 else []

                buffer.append(token)  # emoji token
            else:
                buffer.append(token)
                if len(buffer) >= 3:
                    try:
                        chunk = enc.decode(buffer)
                        yield chunk
                    except Exception:
                        yield "[⚠️ lỗi decode]"
                    buffer.clear()
        i += 1

    # Flush phần còn lại
    if buffer or pre_emoji_buffer:
        try:
            chunk = enc.decode(pre_emoji_buffer + buffer)
            yield chunk
        except Exception:
            yield "[⚠️ lỗi cuối]"



