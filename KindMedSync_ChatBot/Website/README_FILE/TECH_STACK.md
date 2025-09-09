# MediSync Technology Stack Documentation

## Overview

MediSync is a comprehensive healthcare management system that combines a PHP-based web application with a Python-powered AI chatbot. The system provides features for appointment booking, product management, medical services, and AI-powered health consultations.

## Frontend Technologies

### Core Technologies

- **PHP**: Server-side scripting language (Main application backend)
- **HTML5/CSS3**: Frontend markup and styling
- **JavaScript**: Client-side interactivity

### CSS Frameworks and Libraries

- **Bootstrap 5.3.0**: Main CSS framework for responsive design
- **Font Awesome 6.4.0**: Icon library

### JavaScript Libraries

- **jQuery**: DOM manipulation and AJAX requests
- **Bootstrap Bundle JS 5.3.0**: Bootstrap's JavaScript components

### Custom CSS Modules

- `cart-badge.css`: Shopping cart notification styling
- `header-mobile.css`: Mobile-responsive header styling
- `layout.css`: Main layout styling
- `style.css`: Global styles
- Various other module-specific CSS files

## Backend Technologies

### PHP Backend

- **PHP**: Server-side application logic
- **MySQL**: Primary database system
- **XAMPP**: Local development environment

### Python Chatbot Backend

- **FastAPI 0.115.12**: Modern web framework for building APIs
- **Uvicorn 0.34.3**: ASGI server implementation
- **OpenAI 1.86.0**: AI integration for chatbot functionality
- **PyMySQL 1.1.1**: MySQL database connector for Python
- **Pydantic 2.11.5**: Data validation using Python type annotations

### AI and Natural Language Processing

- **OpenAI GPT Models**: AI-powered chat responses
- **Tiktoken 0.9.0**: OpenAI's tokenizer
- **RapidFuzz 3.13.0**: String matching and comparison
- **Unidecode 1.4.0**: Unicode text normalization

### Environment and Configuration

- **Python-dotenv 1.1.0**: Environment variable management
- **Requests 2.32.4**: HTTP library for API requests

## Database Structure

The system uses a MySQL database with multiple modules:

- User Management
- Medical Services
- E-commerce (Products/Orders)
- Appointment System
- AI Chatbot Data
- Email System

## Key Features

### User Management

- Authentication and Authorization
- User Roles (Admin, Doctor, Patient)
- Profile Management

### Medical Services

- Appointment Booking
- Doctor Schedules
- Medical Service Categories

### E-commerce

- Product Management
- Shopping Cart
- Order Processing
- Payment Integration

### AI Chatbot

- Health Consultation
- Symptom Analysis
- Medical Advice
- Patient History Integration

### Email System

- Appointment Notifications
- Order Confirmations
- Password Reset
- System Notifications

## Development Tools and Utilities

### Logging System

- Custom PHP logging implementation
- Enhanced logging for debugging
- Activity tracking
- Error logging

### Security Features

- Password hashing
- Session management
- CSRF protection
- Input validation

### Development Environment

- XAMPP (Apache, MySQL, PHP)
- Python virtual environment
- Git version control

## File Structure Overview

```
htdocs/
├── admin/           # Administrative interface
├── api/             # API endpoints
├── assets/          # Static resources
├── Chat/            # Chat interface
├── Chatbot_BackEnd/ # Python chatbot backend
├── database/        # Database scripts
├── includes/        # Shared PHP components
└── README_FILE/     # Documentation
```

## Getting Started

1. Install XAMPP
2. Set up Python environment and install requirements
3. Configure database using provided SQL scripts
4. Set up environment variables
5. Initialize the application

## Configuration

- Database configuration in `includes/config.php`
- Chatbot configuration in `Chatbot_BackEnd/config/config.py`
- Environment variables in `.env`

## Security Notes

- API keys should be stored in environment variables
- Database credentials should be properly secured
- Regular security updates should be maintained
- Input validation and sanitization implemented

## Maintenance

- Regular database backups
- Log rotation
- Performance monitoring
- Security updates

---

_This documentation is maintained as part of the MediSync Healthcare Management System._
