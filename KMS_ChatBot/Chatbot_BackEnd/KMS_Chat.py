from fastapi import FastAPI
from pydantic import BaseModel
import os
import openai
from dotenv import load_dotenv
import json
from fastapi.responses import StreamingResponse
import asyncio
import random
from typing import List, Dict

# load_dotenv(override=True)

openai_api_key = os.getenv('OPENAI_API_KEY')
if openai_api_key:
    print(f"OpenAI API Key exists and begins {openai_api_key[:8]}")
else:
    print("OpenAI API Key not set")

openai.api_key = openai_api_key

MODEL = "gpt-4o-mini"

system_message = "You are a friendly and caring virtual assistant for KMS Health Care. "# Thiết lập vai trò của chatbot là một trợ lý ảo trong lĩnh vực y tế, mang tính thân thiện và quan tâm — tạo cảm giác như đang nói chuyện với người thật, không phải máy móc lạnh lùng.
system_message += "Speak with warmth and professionalism, as if you are a supportive medical advisor. "# Định hướng giọng điệu trả lời — vừa thân thiện vừa chuyên nghiệp. Gợi ý rằng chatbot nên cư xử giống một bác sĩ hỗ trợ, chứ không xưng là bác sĩ thật.
system_message += "Provide clear, accurate information based on the user's symptoms or concerns. "# Nhấn mạnh yêu cầu cung cấp thông tin y tế chính xác, dễ hiểu và phù hợp với từng tình huống cụ thể do người dùng mô tả (ví dụ: triệu chứng, câu hỏi về sức khỏe).
system_message += "If needed, kindly suggest seeing a doctor, and offer to help book an appointment."# Cho phép chatbot **chủ động gợi ý khám bác sĩ khi thấy cần thiết**, và nếu hệ thống có chức năng đặt lịch, thì hỗ trợ luôn. Tạo sự kết nối giữa tư vấn và hành động thực tế.
system_message += "Do not make medical diagnoses; focus on general advice and care suggestions."# Rất quan trọng! Nhắc chatbot **không được đưa ra chẩn đoán y tế cụ thể**, chỉ nên gợi ý và chia sẻ thông tin tổng quát để đảm bảo an toàn và tránh rủi ro pháp lý.
system_message += "If the user describes severe or emergency symptoms, urge them to seek immediate medical attention."# Để chatbot luôn ưu tiên an toàn người dùng nếu triệu chứng nguy hiểm xuất hiện (ví dụ: khó thở, đau ngực, mất ý thức...).
system_message += "Reply everything with vietnamese."

# Các câu thân thiện theo từng loại phản hồi
friendly_responses = {
    "home_care": [
        "Bạn thử nghỉ ngơi và uống nhiều nước xem sao nhé, có thể cơ thể chỉ đang hơi mệt một chút thôi.",
        "Bạn có thể ăn nhẹ, tránh dầu mỡ và theo dõi xem tình trạng có cải thiện không nha."
    ],
    "follow_up": [
        "Nếu tình trạng không khá lên sau 1–2 ngày, bạn nên đi khám để yên tâm hơn nha.",
        "Mình khuyên bạn theo dõi thêm trong vòng 24–48 giờ nhé. Nếu có thêm triệu chứng, hãy liên hệ bác sĩ."
    ],
    "reassure": [
        "Nghe có vẻ không nghiêm trọng đâu, bạn đừng quá lo lắng nha. Cứ theo dõi thêm là được.",
        "Tình trạng này khá phổ biến và thường tự khỏi, mình ở đây nếu bạn cần thêm hỗ trợ nha."
    ],
    "suggest_exam": [
        "Để chắc chắn hơn, bạn nên ghé phòng khám kiểm tra nha. Mình có thể giúp bạn đặt lịch nếu cần.",
        "Bạn nên đến cơ sở y tế gần nhất để được kiểm tra kỹ hơn nhé, đừng chủ quan nhen."
    ],
    "emotional_support": [
        "Mình hiểu bạn đang lo lắng, nhưng cứ bình tĩnh nhé, mình sẽ hỗ trợ hết mức có thể.",
        "Bạn không đơn độc đâu, nếu cần tư vấn thêm mình luôn sẵn sàng hỗ trợ bạn."
    ]
}

# Hàm lấy câu phản hồi theo loại
def get_friendly_reply(response_type):
    if response_type in friendly_responses:
        return random.choice(friendly_responses[response_type])
    else:
        return "Bạn có thể chia sẻ thêm để mình hỗ trợ rõ hơn nhé."


def chat(message, history):
    messages = [{"role": "system", "content": system_message}] + history + [{"role": "user", "content": message}]
    response = openai.chat.completions.create(model=MODEL, messages=messages)
    return response.choices[0].message.content



app = FastAPI()

class Message(BaseModel):
    message: str
    history: List[Dict] = []  # Mặc định là danh sách rỗng





@app.post("/chat")
async def chat_endpoint(msg: Message):
    messages = [{"role": "system", "content": system_message}] + msg.history + [{"role": "user", "content": msg.message}]
    response = openai.chat.completions.create(model=MODEL, messages=messages)
    return {"reply": response.choices[0].message.content}





@app.post("/chat/stream")
async def chat_stream_endpoint(msg: Message):

    messages = [{"role": "system", "content": system_message}] + msg.history + [{"role": "user", "content": msg.message}]

    try:
        stream = openai.chat.completions.create(
            model=MODEL,
            messages=messages,
            stream=True
        )
    except Exception as e:
        return StreamingResponse(
            iter([f"data: {json.dumps({'error': str(e)})}\n\n"])
        )

    async def event_generator():
        try:
            for chunk in stream:
                content = chunk.choices[0].delta.get("content")
                if content:
                    # Dữ liệu SSE phải bắt đầu bằng "data: "
                    yield f"data: {json.dumps(content)}\n\n"
                await asyncio.sleep(0)  # Để không block event loop
        except Exception as e:
            yield f"data: {json.dumps({'error': str(e)})}\n\n"

    return StreamingResponse(event_generator(), media_type="text/event-stream")
