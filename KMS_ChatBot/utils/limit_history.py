import tiktoken

def count_tokens(text: str, model_name: str = "gpt-3.5-turbo") -> int:
    # Lấy encoder phù hợp với model
    encoding = tiktoken.encoding_for_model(model_name)
    # Mã hóa đoạn text ra tokens (mã số)
    tokens = encoding.encode(text)
    # Trả về số lượng token
    return len(tokens)


MAX_TOKENS = 1000

def limit_history_by_tokens(system_message: dict, history: list, max_tokens=MAX_TOKENS):
    """
    Giữ nguyên system_message (1 phần), và cắt bớt history để tổng token <= max_tokens.
    """
    # Đếm token của system_message (lấy content từ dict)
    total_tokens = count_tokens(system_message['content'])
    limited_history = []

    # Duyệt lịch sử từ mới nhất đến cũ nhất để giữ context mới nhất
    for msg in reversed(history):
        tokens = count_tokens(msg['content'])
        if total_tokens + tokens > max_tokens:
            break
        limited_history.insert(0, msg)
        total_tokens += tokens

    return limited_history
