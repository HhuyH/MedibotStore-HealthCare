# File: utils/symptom_utils.py

import pymysql
import unidecode  # Cài bằng: pip install Unidecode
from rapidfuzz import fuzz, process
from utils.openai_client import chat_completion
import json

SYMPTOM_LIST = []  # Cache triệu chứng toàn cục

def normalize_text(text):
    return unidecode.unidecode(text).lower().strip()

def load_symptom_list():
    try:
        connection = pymysql.connect(
            host='localhost',
            user='chatbot_user',
            password='StrongPassword123',
            db='kms',
            charset='utf8mb4',
            cursorclass=pymysql.cursors.Cursor
        )
        with connection.cursor() as cursor:
            cursor.execute("SELECT symptom_id, name, alias FROM symptoms")
            results = cursor.fetchall()
            global SYMPTOM_LIST
            SYMPTOM_LIST = []
            for row in results:
                symptom_id = row[0]
                name = row[1]
                alias_raw = row[2] or ''
                alias_list = [normalize_text(name)] + [normalize_text(a) for a in alias_raw.split(',') if a.strip()]
                SYMPTOM_LIST.append({
                    "id": symptom_id,
                    "name": name,
                    "aliases": alias_list
                })
            print(f"✅ Loaded {len(SYMPTOM_LIST)} symptoms from database:")
    except Exception as e:
        print(f"❌ Error loading symptoms: {e}")
    finally:
        if connection:
            connection.close()

def extract_symptoms(text):
    text_norm = normalize_text(text)
    found = []
    seen_ids = set()
    for symptom in SYMPTOM_LIST:
        for keyword in symptom["aliases"]:
            if keyword in text_norm and symptom["id"] not in seen_ids:
                found.append({"id": symptom["id"], "name": symptom["name"]})
                seen_ids.add(symptom["id"])
                break
    return found

def extract_symptoms_gpt(text):
    prompt = f"""
I will send you a sentence in Vietnamese. Please read it and respond with a JSON list of possible health symptoms mentioned in the sentence.

- If there are no symptoms, return []
- If there are symptoms, return a list of symptom names as strings. For example:
["Buồn nôn", "Chóng mặt"]

Sentence: "{text}"

Answer:
"""

    try:
        reply = chat_completion([{"role": "user", "content": prompt}], temperature=0.4, max_tokens=150)
        content = reply.choices[0].message.content.strip()
        print("🧠 GPT raw reply:", repr(content))

        if content.startswith("```json"):
            content = content.replace("```json", "").replace("```", "").strip()

        names = json.loads(content)

        matched = []
        unmatched = []
        seen_ids = set()

        for name in names:
            norm_name = normalize_text(name)
            found_match = False
            for symptom in SYMPTOM_LIST:
                if any(norm_name == alias for alias in symptom["aliases"]):
                    if symptom["id"] not in seen_ids:
                        matched.append({"id": symptom["id"], "name": symptom["name"]})
                        seen_ids.add(symptom["id"])
                        found_match = True
                        break
            if not found_match:
                unmatched.append(name)

        suggestion = None

        # Nếu có triệu chứng không khớp exact, thử fuzzy match từng cái
        if unmatched:
            all_aliases = []
            alias_to_symptom = {}
            for symptom in SYMPTOM_LIST:
                for alias in symptom["aliases"]:
                    all_aliases.append(alias)
                    alias_to_symptom[alias] = symptom["name"]

            fuzzy_suggestions = set()
            for name in unmatched:
                norm_name = normalize_text(name)
                matches = process.extract(norm_name, all_aliases, scorer=fuzz.ratio, limit=2)
                for match_name, score, _ in matches:
                    if score >= 75:
                        fuzzy_suggestions.add(alias_to_symptom[match_name])
                        break

            if fuzzy_suggestions:
                joined = ' hoặc '.join(fuzzy_suggestions)
                suggestion = f"Ý bạn có phải là {joined} không?"
            elif unmatched:
                joined = ' hoặc '.join(unmatched)
                suggestion = f"Tôi chưa hiểu rõ. Bạn có đang đề cập đến: {joined} không?"

        # ✅ Fallback: nếu GPT trả [] → thử fuzzy toàn bộ input
        if not names:
            all_aliases = []
            alias_to_symptom = {}
            for symptom in SYMPTOM_LIST:
                for alias in symptom["aliases"]:
                    all_aliases.append(alias)
                    alias_to_symptom[alias] = symptom["name"]

            norm_text = normalize_text(text)
            matches = process.extract(norm_text, all_aliases, scorer=fuzz.partial_ratio, limit=3)
            fuzzy_suggestions = set()
            for match_name, score, _ in matches:
                if score >= 75:
                    fuzzy_suggestions.add(alias_to_symptom[match_name])

            if fuzzy_suggestions and len(text.split()) > 3:
                joined = ' hoặc '.join(fuzzy_suggestions)
                suggestion = f"Bạn đang muốn nói tới: {joined} phải không?"
                return [], suggestion
            else:
                return [], None


        return matched, suggestion

    except Exception as e:
        print("❌ GPT symptom extraction failed:", str(e))
        return [], None
