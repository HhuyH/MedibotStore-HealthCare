from utils.openai_utils import stream_gpt_tokens
from utils.openai_utils import chat_completion
from prompts.db_schema.load_schema import user_core_schema, schema_modules, load_schema

import logging
logger = logging.getLogger(__name__)
import re
import json

def extract_json(text: str) -> str:
    """Trích JSON đầu tiên trong text."""
    match = re.search(r"\{.*?\}", text, re.DOTALL)
    if match:
        return match.group(0)
    raise ValueError("Không tìm thấy JSON hợp lệ trong phản hồi.")


async def suggest_product(
    suggest_type: str,
    suggest_product_target: list[str],
    recent_messages: str,
):
    target_list = "\n".join(f"- {t}" for t in suggest_product_target)

    schema_text = load_schema("products_module")
    # 👉 Làm sạch một chút cho GPT dễ hiểu hơn
    cleaned_schema = "\n".join([
        line for line in schema_text.splitlines()
        if line.strip() and not line.strip().startswith(tuple("0123456789"))  # bỏ số thứ tự 25. 26. 27.
    ])
    prompt = f"""
        You are a smart assistant that helps generate SQL queries to retrieve medical product information from the database.

        💾 Database schema:
        {cleaned_schema}

        Context:
        - User’s product suggestion targets:
        {target_list}

        - Recent chat messages between user and assistant:
        {recent_messages}

        Your task:
        👉 Based on the above context, generate a JSON object with:
        1. "natural_text": a short friendly Vietnamese sentence that introduces the result to the user
        2. "sql_query": an appropriate SQL query to fetch product data

        SQL requirements:
        - Query only from either `products` or `medicines` table
        - Select: product_id, name, price, stock, description
        - Use reasonable WHERE conditions (e.g., match name, description, or category)
        - Always include `LIMIT 5`

        - Always use `AS` to rename columns with Vietnamese display names:
            product_id AS 'Mã sản phẩm',
            name AS 'Tên sản phẩm',
            price AS 'Giá',
            stock AS 'Số lượng',
            description AS 'Mô tả'

        🔍 SQL matching guidance:
        - Expand each product target into 2–4 short Vietnamese keywords commonly found in product descriptions.
        - Use keywords that are distinctive and avoid overly generic ones.

        → Good examples:
        - "Dưỡng ẩm da" → dưỡng ẩm, giữ ẩm, da khô, kem dưỡng
        - "Ngủ ngon hơn" → ngủ ngon, dễ ngủ, thư giãn, giấc ngủ
        - "Giảm đau họng" → đau họng, rát họng, dịu cổ họng

        ⚠️ Filtering rules:
        - Avoid selecting unrelated products (e.g., thuốc cảm, sốt, viêm) unless directly relevant
        - Only include items where name or description clearly matches at least one keyword
        - DO NOT include generic fever or flu meds unless context clearly matches

        📌 Format WHERE clause like:
            WHERE LOWER(name) LIKE '%keyword1%' OR LOWER(description) LIKE '%keyword1%' OR ...


        Return JSON exactly in the following structure, but generate your own content:

        ```json
        {{
             "natural_text": "📦 ...",
             "sql_query": "SELECT ... FROM products WHERE ... LIMIT 5"
        }}
 
    ⚠️ Do not explain anything. Only return valid JSON in the above format.
    """.strip()

    try:
        response = chat_completion(
            messages=[
                {"role": "system", "content": "Bạn là một trợ lý AI sinh câu lệnh SQL từ yêu cầu người dùng."},
                {"role": "user", "content": prompt}
            ],
            temperature=0.4,
            max_tokens=500,
        )

        raw_text = response.choices[0].message.content 
        json_text = extract_json(raw_text)
        return json.loads(json_text)

    except Exception as e:
        logger.warning("⚠️ Lỗi khi xử lý phản hồi GPT: %s", str(e))
        return {
            "natural_text": "Mình chưa xác định được sản phẩm phù hợp lúc này.",
            "sql_query": None
        }
    
