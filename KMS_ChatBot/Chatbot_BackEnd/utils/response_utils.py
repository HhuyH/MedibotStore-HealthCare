import random

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
