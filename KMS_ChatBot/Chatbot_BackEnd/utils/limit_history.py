import tiktoken
from config import MODEL

def count_message_tokens(message: dict, model_name: str = MODEL) -> int:
    encoding = tiktoken.encoding_for_model(model_name)
    # Tính token cho role + content + 4 token overhead (theo docs)
    tokens = len(encoding.encode(message.get("role", "") + message.get("content", ""))) + 4
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