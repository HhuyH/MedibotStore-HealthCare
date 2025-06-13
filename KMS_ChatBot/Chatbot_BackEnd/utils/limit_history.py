import tiktoken
from config import MODEL
from models import ChatHistoryItem
from utils.intent_utils import build_system_message 

def count_message_tokens(message, model_name: str = MODEL) -> int:
    encoding = tiktoken.encoding_for_model(model_name)
    # Kiểm tra nếu message là dict
    if isinstance(message, dict):
        role = message.get("role", "")
        content = message.get("content", "")
    else:
        # Giả định object có attr role và content
        role = getattr(message, "role", "")
        content = getattr(message, "content", "")

    tokens = len(encoding.encode(role + content)) + 4
    return tokens

def limit_history_by_tokens(system_message: dict, history: list, max_tokens=1000):
    total_tokens = count_message_tokens(system_message)
    limited_history = []

    for msg in reversed(history):
        tokens = count_message_tokens(msg)
        if total_tokens + tokens > max_tokens:
            break
        limited_history.insert(0, msg)
        total_tokens += tokens

    return limited_history

def refresh_system_context(intent: str, symptoms: list[dict], msg_history: list[dict]) -> tuple[list[dict], dict]:
    system_msg = build_system_message(intent, [s['name'] for s in symptoms])
    limited = limit_history_by_tokens(system_msg, msg_history)
    return limited, system_msg
