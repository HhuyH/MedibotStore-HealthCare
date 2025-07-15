from utils.openai_utils import stream_gpt_tokens
from utils.openai_utils import chat_completion, stream_chat
from prompts.db_schema.load_schema import user_core_schema, schema_modules, load_schema
import asyncio
import logging
logger = logging.getLogger(__name__)
import re
import json
from typing import AsyncGenerator

def extract_json(text: str) -> str:
    """Cố gắng tách JSON object đầu tiên hợp lệ từ GPT content."""
    start = text.find("{")
    for end in range(len(text), start, -1):
        try:
            candidate = text[start:end]
            json.loads(candidate)
            return candidate
        except json.JSONDecodeError:
            continue
    raise ValueError("Không tìm thấy JSON hợp lệ.")


async def suggest_product(
    recent_messages: list[str],
) -> dict:
    recent_text = "\n".join(f"- {msg}" for msg in recent_messages[-5:])

    prompt = f"""
        You are a helpful assistant that generates SQL queries to retrieve health-related product suggestions from the database.

        🎯 Your job:
        1. Write an SQL query to retrieve a list of products (up to 5 items) from the `products` table, based on the user's soft health targets.
        2. Use the user's recent conversation to understand their wellness goals or health-related needs.
        3. If yes:
        - Set `"suggest_type"`: "relief_support" or "wellness" based on the user's intent
        - If the user's intent is not clear, set `"suggest_type"`: "general"
        - Generate a SQL query that retrieves relevant products (max 5) from the `products` table
        - You may LEFT JOIN `medicines` ON product_id to enrich data if needed.

        💬 Recent conversation:
        {recent_text}

        🛠️ SQL generation rules:
        - Use only the `products` table
        - You may LEFT JOIN the `medicines` table ON `product_id` to enrich the result

        ✅ Always SELECT the following fields from `products`:  
        `product_id`, `name`, `description`, `price`, `stock`, `is_medicine`, `image_url`

        ✅ If you JOIN `medicines`, also SELECT:
        `active_ingredient`, `dosage_form`, `unit`, `usage_instructions`, `medicine_type`, `side_effects`, `contraindications`

        ⚠️ Do not invent, rename, or shorten column names. Use only fields exactly as listed.
        - ✅ `usage_instructions`, not `usage`
        - ✅ `dosage_form`, not `dosage`
        - ✅ `product_id`, not `id`

        📌 WHERE clause:
        - Filter based on `name` and `description` only
        - Use Vietnamese keywords or phrases found in the user's message
        - Do NOT translate to English
        - Do NOT use structured symptom terms or clinical codes
        - This database contains Vietnamese product data

        📌 LIMIT:
        - Always LIMIT the result to 5 rows


        ✅ Output JSON exactly like:
        {{
            "sql_query": "SELECT ... FROM ... WHERE ... LIMIT 5"
            "suggest_type": "wellness" | "relief_support" | "general"
        }}

        ⚠️ Rules:
        - Output JSON only — no markdown, no explanation
    """.strip()

    try:
        response = chat_completion(
            messages=[
                {"role": "system", "content": "You are an assistant that generates SQL queries."},
                {"role": "user", "content": prompt}
            ],
            temperature=0.4,
            max_tokens=500,
        )
        raw_text = response.choices[0].message.content
        json_text = extract_json(raw_text)
        return json.loads(json_text)

    except Exception as e:
        logger.warning("⚠️ GPT lỗi khi sinh SQL: %s", str(e))
        return {
            "sql_query": None
        }

async def summarize_products(
    suggest_type: str,
    products: list[dict],
    recent_messages: list[str] = []
) -> AsyncGenerator[str, None]:


    prompt = f"""
        You are a warm and caring Vietnamese virtual health assistant.

        🎯 Task:
        The user is looking for suggestions to support their health or well-being. Based on the list of products and their recent message, write friendly and helpful recommendations in **Vietnamese** — one paragraph per product.

        📦 Product data:
        You will receive a JSON array named `products`. Each item includes:
        - name: product name
        - price: display price
        - description: internal info (⚠️ do not copy directly)
        - product_id: for linking
        - Other optional fields may exist — use what’s useful.

        💬 User’s recent message:
        "{recent_messages[-1] if recent_messages else ''}"

        🧠 Tone guide:
            The user may be asking for a suggestion in one of the following ways:

            → If {suggest_type} is `"wellness"`:
            - The user is looking to improve general well-being (e.g. skin, energy, sleep)
            - You act like a caring friend or lifestyle coach
            - Use emotional, inspiring language — something relatable
            - Recommend this product as a soft and uplifting tip
            - Start warm and human

            → If `suggest_type` is `"relief_support"`:
            - The user recently described symptoms or discomfort
            - You act like a soft-spoken nurse or health support
            - Recommend this product gently, as a way to feel better
            - Say when to use it, and mention anything to avoid (if applicable)
            - Stay human, not robotic or salesy

            → If `suggest_type` is missing or unclear:
            - The user may be asking directly about a product (by name or type)
            - You act like a helpful assistant confirming the product (or an alternative)
            - Clarify softly if it’s a match, or suggest it as a good option
            - Mention key benefits and what situation it’s useful for

        ✅ Output rules:
        - For each product: write a short paragraph in **Vietnamese** recommending it
        - Each paragraph must end with:
        👉 [Xem chi tiết tại đây](http://localhost/shop/details.php?id={{product_id}})

        - Do NOT repeat or rephrase the raw description
        - Output all paragraphs in order, no numbering, no formatting, no extra explanation
        - Output in Vietnamese only

        🧾 Here is the product list in JSON:
        ```json
        {json.dumps(products, ensure_ascii=False, indent=2)}

    """.strip()

    try:
        buffer = ""
        async for chunk in stream_chat(
            message=prompt,
            history=[],
            system_message_dict={"role": "system", "content": "Bạn là trợ lý sức khỏe dễ thương, tư vấn bằng tiếng Việt."}
        ):
            delta = chunk.choices[0].delta
            content = getattr(delta, "content", None)

            if content:
                logger.debug(f"[stream chunk] {content}")
                buffer += content
                yield content
                await asyncio.sleep(0.01)

    except Exception as e:
        logger.warning(f"[summarize_products] ⚠️ Fallback do lỗi: {e}")
        for p in products:
            fallback = f"🧴 *{p.get('name')}*\n{p.get('description', '')[:80]}...\n👉 [Xem chi tiết tại đây](https://demo.site.vn/products/{p.get('product_id')})"
            yield fallback + "\n\n"


