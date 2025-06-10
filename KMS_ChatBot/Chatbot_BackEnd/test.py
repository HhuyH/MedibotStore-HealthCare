from utils.symptom_utils import load_symptom_list, extract_symptoms_gpt

# Tải danh sách triệu chứng vào bộ nhớ đệm toàn cục
load_symptom_list()

text = "em bị toc ngoc"

matched, suggestion = extract_symptoms_gpt(text)

print("🔍 Triệu chứng tìm thấy:")
for s in matched:
    print(f" - {s['id']}: {s['name']}")

if suggestion:
    print(suggestion)

