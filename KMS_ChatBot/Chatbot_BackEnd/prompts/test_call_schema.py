from intent_utils import detect_intent, get_combined_schema_for_intent

def test_intent_and_schema():
    while True:
        question = input("Question (nhập 'exit' để thoát): ").strip()
        if question.lower() == 'exit':
            break

        intent = detect_intent(question)
        schemas_text = get_combined_schema_for_intent(intent)

        # Tách từng bảng schema để in số thứ tự + chi tiết
        schema_blocks = [block.strip() for block in schemas_text.split('\n\n') if block.strip()]

        print(f"\nQuestion: {question}")
        print(f"Detected intent: intent: {intent}")

        for i, block in enumerate(schema_blocks, 1):
            print(f"{i}. {block}")

        print("\n" + "="*50 + "\n")

# Gọi hàm test này trong file main hoặc script test của bạn
if __name__ == "__main__":
    test_intent_and_schema()

