# Danh sách intent cho phép
VALID_INTENTS = [
    # --- Triệu chứng & chẩn  & chăm sóc sức khỏe ---
    "medical_history",
    "health_query",
    "health_advice",

    # --- Truy vấn hồ sơ bệnh nhân ---
    "patient_summary_request",

    # --- Truy vấn dữ liệu có cấu trúc (SQL) ---
    "user_profile",
    "ai_prediction",
    "ai_prediction_result",
    "appointments",
    "prescription_products",
    "order_items_details",
    "orders",

    # --- Dữ liệu danh mục & thương mại ---
    "products",
    "services",
    "notifications",

    # --- GỢI Ý SẢN PHẨM ---
    "suggest_product",

    # --- Intent truy vấn dạng danh sách (list_*) ---
    # "list_diseases",
    # "list_symptoms",
    # "list_appointments",

    # --- Chat tổng quát ---
    "general_chat",

    # --- Phân loại truy vấn sản phẩm (giữ để phân luồng) ---
    "product_query"
]

# Mapping từ client intent → pipeline key
INTENT_MAPPING = {

    # Xem thông tin phỏng đoán bệnh từ AI
    "patient_summary_request": "patient_summary_request",

    # Ý định cần trích xuất triệu chứng & hội thoại chẩn đoán
    "health_query":          "symptom_query",
    
    # Tư vấn sức khỏe
    "health_advice":         "health_advice",

    # gợi ý sản phẩm
    "suggest_product":       "suggest_product",
    
    # Truy vấn dữ liệu thương mại / dịch vụ
    "products":              "product_query",
    "order_items_details":   "product_query",
    "orders":                "product_query",
    "services":              "product_query",

    # Các truy vấn dữ liệu (cần SQL)
    "user_profile":          "sql_query",
    "ai_prediction":         "sql_query",
    "appointments":          "sql_query",
    "prescription_products": "sql_query",
    "list_diseases":         "sql_query",
    "list_symptoms":         "sql_query",
    "list_clinics":          "sql_query",
    "list_appointments":     "sql_query",
    "ai_prediction_result":   "sql_query",

    "notifications":         "general_chat",

    # medical_history: nếu dùng để hỏi triệu chứng quá khứ, giữ symptom_query
    # nếu dùng để xem lại dữ liệu trong DB thì nên map sang sql_query
    "medical_history":       "symptom_query"
    
}

# Pipeline xử lý cho từng intent
INTENT_PIPELINES = {
    # Toàn bộ các intent liên quan sức khỏe → dùng GPT lead
    "symptom_query": ["health_talk"],
    "health_advice": ["health_advice"],

    # Truy vấn hồ sơ bệnh nhân
    "patient_summary_request": ["patient_summary"],

    # Truy vấn dữ liệu gợi ý sản phẩm
    "suggest_product": ["suggest_product", "sql"],

    # Các intent truy vấn dữ liệu có cấu trúc → SQL
    "product_query": ["chat", "sql"],
    "sql_query":     ["chat", "sql"], 

    # Chat không chuyên sâu
    "general_chat":  ["chat"]
}
