import os

def load_schema(schema_name):
    base_dir = os.path.dirname(__file__)  # thư mục hiện tại: .../prompts/db_schema
    filepath = os.path.join(base_dir, f"{schema_name}.txt")
    with open(filepath, 'r', encoding='utf-8') as f:
        return f.read()

# load file schema chứa thông tin table
# file schema chứa users và roles
user_core_schema = load_schema('user_core')

# Các schema khác chỉ load khi cần
schema_modules = {
    # USER SYSTEM
    # file schema chứa users_info, user_addresses, guest_users
    'user_profile': load_schema('user_profile'),
    
    # file schema chứa notifications, user_notifications
    'notifications': load_schema('notifications_module'),

    # Y TẾ
    # file schema chứa medical_categories, diseases, symptoms, disease_symptoms, user_symptom_history
    'medical_history': load_schema('medical_history_module'),

    # file schema chứa clinics, specialties, doctors, doctor_schedules 
    'doctor_clinic': load_schema('doctor_clinic_module'),

    # file schema chứa appointments, medical_records, prescriptions
    'appointments': load_schema('appointments_module'),

    # file schema chứa health_records, chat_logs, health_predictions, prediction_diseases, chatbot_knowledge_base
    'ai_prediction': load_schema('ai_prediction_module'),

    'ai_prediction_result': load_schema('ai_prediction_module'),

    # SẢN PHẨM & ĐƠN HÀNG
    # file schema chứa products, product_categories, medicines, prescription_products, product_reviews
    'products': load_schema('products_module'),
    'suggest_product': load_schema('products_module'),
    
    # file schema chứa orders, order_items, payments
    'orders': load_schema('ecommerce_orders_module'),

    # DỊCH VỤ Y TẾ
    # file schema chứa service_categories, services, service_features, service_packages, package_features
    'services': load_schema('service_module'),
}