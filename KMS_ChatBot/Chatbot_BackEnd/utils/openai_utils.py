from config import MODEL
import sys
import os

sys.path.append(os.path.abspath(os.path.join(os.path.dirname(__file__), '..')))

from .openai_client import chat_completion, chat_stream

def chat(message, history, system_message_dict):
    messages = [system_message_dict] + history + [{"role": "user", "content": message}]
    response = chat_completion(model=MODEL, messages=messages)
    return response.choices[0].message.content

def stream_chat(message, history, system_message_dict):
    messages = [system_message_dict] + history + [{"role": "user", "content": message}]
    return chat_stream(model=MODEL, messages=messages)
