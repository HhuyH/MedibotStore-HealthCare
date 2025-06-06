import openai
from config import OPENAI_API_KEY, MODEL

openai.api_key = OPENAI_API_KEY

def chat_completion(messages, **kwargs):
    return openai.chat.completions.create(model=MODEL, messages=messages, **kwargs)

def chat_stream(*, model=None, messages, **kwargs):
    if model is None:
        model = MODEL  # default
    return openai.chat.completions.create(model=model, messages=messages, stream=True, **kwargs)
