from config.config import MODEL
from .openai_client import chat_completion, chat_stream

def chat(message, history, system_message_dict):
    messages = [system_message_dict] + history + [{"role": "user", "content": message}]
    response = chat_completion(messages=messages)
    return response.choices[0].message.content

async def stream_chat(message, history, system_message_dict):
    messages = [system_message_dict] + history + [{"role": "user", "content": message}]
    stream = await chat_stream(model=MODEL, messages=messages)

    async for chunk in stream:
        yield chunk
