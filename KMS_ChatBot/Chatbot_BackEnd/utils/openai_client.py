from openai import AsyncOpenAI
from config.config import OPENAI_API_KEY, MODEL

client = AsyncOpenAI(api_key=OPENAI_API_KEY)

async def chat_stream(*, model=None, messages, **kwargs):
    if model is None:
        model = MODEL

    stream = await client.chat.completions.create(
        model=model,
        messages=messages,
        stream=True,
        **kwargs
    )
    return stream

def chat_completion(messages, **kwargs):
    # Nếu bạn cần version sync thì tạo thêm 1 client sync
    from openai import OpenAI
    sync_client = OpenAI(api_key=OPENAI_API_KEY)
    return sync_client.chat.completions.create(model=MODEL, messages=messages, **kwargs)

async def chat_stream_health(*, model=None, messages, **kwargs):
    if model is None:
        model = MODEL

    stream = await client.chat.completions.create(
        model=model,
        messages=messages,
        stream=True,
        **kwargs
    )

    async for chunk in stream:
        delta = chunk.choices[0].delta
        if "content" in delta:
            yield delta["content"]
