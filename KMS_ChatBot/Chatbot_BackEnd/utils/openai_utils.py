from config import OPENAI_API_KEY, MODEL
import sys
import os

sys.path.append(os.path.abspath(os.path.join(os.path.dirname(__file__), '..')))

from .openai_client import chat_completion, chat_stream
from prompts.prompts import system_message

def chat(message, history, system_message=system_message):
    messages = [{"role": "system", "content": system_message}] + history + [{"role": "user", "content": message}]
    response = chat_completion(model=MODEL, messages=messages)
    return response.choices[0].message.content

def stream_chat(message, history, system_message=system_message):
    messages = [{"role": "system", "content": system_message}] + history + [{"role": "user", "content": message}]
    return chat_stream(model=MODEL, messages=messages)
