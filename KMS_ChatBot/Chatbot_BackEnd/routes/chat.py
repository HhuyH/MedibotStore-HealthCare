from fastapi import APIRouter, Query
from fastapi.responses import StreamingResponse
from models import Message, ChatRequest
from utils.openai_utils import chat, stream_chat
from utils.limit_history import limit_history_by_tokens
from prompts import system_message

import asyncio
import json

router = APIRouter()

system_message_dict = {
    "role": "system",
    "content": system_message
}

@router.post("/chat")
async def chat_endpoint(msg: Message):
    limited_history = limit_history_by_tokens(system_message_dict, msg.history)

    reply = chat(msg.message, limited_history)
    return {"reply": reply}


@router.post("/chat/stream")
def chat_stream(msg: Message):
    limited_history = limit_history_by_tokens(system_message_dict, msg.history)
    def event_generator():
        response = stream_chat(msg.message, limited_history)  # sync iterator

        for chunk in response:
            delta = chunk.choices[0].delta
            content = getattr(delta, "content", None)
            if content:
                data = {"text": content}
                yield f"data: {json.dumps(data)}\n\n"
        yield "data: [DONE]\n\n"

    return StreamingResponse(event_generator(), media_type="text/event-stream")

