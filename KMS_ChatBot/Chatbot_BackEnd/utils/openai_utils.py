import openai
from config import OPENAI_API_KEY, MODEL
from prompts import system_message

openai.api_key = OPENAI_API_KEY

def chat(message, history, system_message=system_message):
    messages = [{"role": "system", "content": system_message}] + history + [{"role": "user", "content": message}]
    response = openai.chat.completions.create(model=MODEL, messages=messages)
    return response.choices[0].message.content

def stream_chat(message, history, system_message=system_message):
    messages = [{"role": "system", "content": system_message}] + history + [{"role": "user", "content": message}]
    return openai.chat.completions.create(model=MODEL, messages=messages, stream=True)

