import openai
from config import OPENAI_API_KEY, MODEL
from prompts import system_message

openai.api_key = OPENAI_API_KEY

response = openai.chat.completions.create(
    model="gpt-4o",
    messages=[{"role": "user", "content": "hello"}],
    stream=True
)

for chunk in response:
    print(chunk.choices[0].delta.content or "", end="")
