# Laravel API Sanctum Boilerplate

A comprehensive Laravel API boilerplate with Sanctum authentication, featuring user management, post CRUD operations, weather API integration, and email functionality. Built with Laravel 12, this project provides a solid foundation for building modern RESTful APIs with proper authentication, validation, and testing coverage.

## 🚀 Features

### 🔐 **Authentication & Authorization**
- **Laravel Sanctum Integration**: Token-based authentication for SPA and mobile applications
- **User Registration & Login**: Complete user authentication flow with validation
- **Token Management**: Secure token creation, validation, and revocation
- **Protected Routes**: Middleware-protected endpoints with proper authorization

### 👥 **User Management**
- **Complete CRUD Operations**: Create, read, update, and delete users
- **User Profiles**: Extended user model with phone and address fields
- **Data Validation**: Comprehensive request validation with custom error messages
- **Resource Filtering**: Secure user data filtering to hide sensitive information

### 📝 **Post Management**
- **Post CRUD Operations**: Full post management with user ownership
- **Post Status System**: Draft and published post states
- **User Relationships**: Posts linked to users with proper authorization
- **Query Filtering**: Filter posts by status and user ownership
- **Date Management**: Proper date handling with custom formatting

### 🌤️ **Weather API Integration**
- **External API Integration**: WeatherAPI.com integration for real-time weather data
- **Caching System**: Redis-based caching for improved performance
- **Location Support**: Support for multiple location formats (city names, coordinates)
- **Error Handling**: Graceful handling of API failures and network issues
- **Cache Management**: Manual cache clearing for specific locations

### 📧 **Email System**
- **Welcome Emails**: Automated welcome emails for new user registrations
- **Queue Integration**: Background email processing for better performance
- **Gmail SMTP Support**: Pre-configured for Gmail SMTP with app passwords
- **Email Templates**: Clean, responsive email templates
- **Queue Management**: Reliable email delivery with retry mechanisms

### 🧪 **Comprehensive Testing**
- **PHPUnit Integration**: Complete test suite with 94+ tests
- **Unit Tests**: Service layer and component testing
- **Feature Tests**: End-to-end API endpoint testing
- **98.6% Pass Rate**: Robust test coverage ensuring reliability
- **Automated Validation**: Request validation and error handling tests

### 🛠️ **Development Tools**
- **API Documentation**: Complete Postman collection for all endpoints
- **Database Seeding**: Sample data for development and testing
- **Environment Configuration**: Flexible configuration for different environments
- **Error Logging**: Comprehensive error logging and debugging tools
- **Code Standards**: PSR-4 autoloading and Laravel best practices

## 📋 Requirements

- **PHP**: >= 8.2
- **Laravel**: 12.x
- **Database**: MySQL 8.0+ or PostgreSQL 13+
- **Redis**: For caching and queues
- **Composer**: For dependency management
- **Node.js**: For asset compilation (optional)

## 🛠️ Installation & Setup

### 1. Clone the Repository
```bash
git clone https://github.com/gusdeyw/laravel-api-sanctum-boilerplate.git
cd laravel-api-sanctum-boilerplate
```

### 2. Install Dependencies
```bash
# Install PHP dependencies
composer install

# Install JavaScript dependencies (optional)
npm install
```

### 3. Environment Configuration
```bash
# Copy environment file
cp .env.example .env

# Generate application key
php artisan key:generate
```

### 4. Database Setup
Configure your database in `.env` file:
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=laravel_sanctum_api
DB_USERNAME=your_username
DB_PASSWORD=your_password
```

Run migrations and seed the database:
```bash
# Run migrations
php artisan migrate

# Seed with sample data (optional)
php artisan db:seed
```

### 5. Cache & Queue Configuration
Configure Redis for caching and queues:
```env
CACHE_DRIVER=redis
QUEUE_CONNECTION=redis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379
```

### 6. Weather API Setup
Get your free API key from [WeatherAPI.com](https://www.weatherapi.com/) and add to `.env`:
```env
WEATHER_API_KEY=your_weather_api_key
WEATHER_DEFAULT_LOCATION="Perth, Australia"
```

### 7. Email Configuration
Configure email settings for Gmail SMTP:
```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your_email@gmail.com
MAIL_PASSWORD=your_app_password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=your_email@gmail.com
MAIL_FROM_NAME="${APP_NAME}"
```

### 8. Start the Application
```bash
# Start the development server
php artisan serve

# Start the queue worker (in separate terminal)
php artisan queue:work

# Clear caches (if needed)
php artisan cache:clear
php artisan config:clear
```

## 🚀 How to Use

### Default Laravel Setup
This project follows Laravel's standard structure and conventions:

1. **Routes**: API routes defined in `routes/api.php`
2. **Controllers**: Located in `app/Http/Controllers/Api/`
3. **Models**: Located in `app/Models/`
4. **Requests**: Form validation in `app/Http/Requests/`
5. **Resources**: API resources in `app/Http/Resources/`
6. **Services**: Business logic in `app/Services/`
7. **Migrations**: Database migrations in `database/migrations/`
8. **Tests**: PHPUnit tests in `tests/` directory

### API Endpoints Overview
```
Authentication:
POST   /api/register      - User registration
POST   /api/login         - User login
GET    /api/user          - Get current user
POST   /api/logout        - User logout

User Management:
GET    /api/users         - List all users (paginated)
POST   /api/users         - Create new user
GET    /api/users/{id}    - Get specific user
PUT    /api/users/{id}    - Update user
DELETE /api/users/{id}    - Delete user

Post Management:
GET    /api/posts         - List all posts
POST   /api/posts         - Create new post
GET    /api/posts/{id}    - Get specific post
PUT    /api/posts/{id}    - Update post
DELETE /api/posts/{id}    - Delete post

Weather API:
GET    /api/weather                - Get weather (default location)
GET    /api/weather?location=City  - Get weather for specific location
DELETE /api/weather/cache          - Clear weather cache
```

### Testing the API
```bash
# Run all tests
php artisan test

# Run specific test suites
php artisan test --testsuite=Feature
php artisan test --testsuite=Unit

# Run with coverage (requires Xdebug)
php artisan test --coverage
```

## 🔧 Custom Artisan Commands

This project includes several custom Artisan commands to help with development, testing, and maintenance:

### **Queue Management**
```bash
# Check queue jobs status
php artisan queue:status

# Clear all pending jobs
php artisan queue:status --clear

# Process one job manually
php artisan queue:status --process

# Watch queue status in real-time
php artisan queue:status --watch
```
**Function**: Monitor and manage queue jobs, including pending, processing, and failed jobs. Provides a dashboard view of queue health and allows manual job processing.

### **Email Testing**
```bash
# Test welcome email to specific address
php artisan email:test-welcome user@example.com

# Create new test user and send welcome email
php artisan email:test-welcome --create-user

# Send test email with custom user data
php artisan email:test-welcome --create-user --name="John Doe" --phone="+1234567890"

# Skip confirmation prompts
php artisan email:test-welcome user@example.com --force
```
**Function**: Send test welcome emails to verify email configuration and templates. Can create test users with custom data for comprehensive email testing.

### **Gmail SMTP Testing**
```bash
# Test Gmail SMTP connection (dry run)
php artisan email:test-connection

# Test connection and send actual test email
php artisan email:test-connection --send-test --to=your-email@gmail.com
```
**Function**: Verify Gmail SMTP configuration, test connection credentials, and validate email delivery setup. Displays current mail configuration and connection status.

### **Weather API Testing**
```bash
# Test weather API with default location
php artisan test:weather

# Test weather API for specific location
php artisan test:weather "London, UK"

# Test with cache clearing
php artisan test:weather "New York" --clear-cache
```
**Function**: Test WeatherAPI.com integration, verify API key configuration, and check weather data retrieval. Includes cache management for testing different scenarios.

### **Weather Endpoint Testing**
```bash
# Test weather API endpoint via HTTP
php artisan test:weather-endpoint

# Test specific location via endpoint
php artisan test:weather-endpoint "Tokyo, Japan"

# Test with specific user authentication
php artisan test:weather-endpoint --user=1
```
**Function**: Test the complete weather API endpoint including authentication, HTTP requests, and response formatting. Simulates real API usage with token authentication.

### **Command Usage Examples**

#### Queue Monitoring Workflow
```bash
# 1. Check current queue status
php artisan queue:status

# 2. Start queue worker in background
php artisan queue:work &

# 3. Monitor in real-time
php artisan queue:status --watch
```

#### Email Setup Verification
```bash
# 1. Test SMTP connection
php artisan email:test-connection

# 2. Send test welcome email
php artisan email:test-welcome test@example.com

# 3. Check queue for email processing
php artisan queue:status
```

#### Weather API Validation
```bash
# 1. Test weather service directly
php artisan test:weather

# 2. Test complete HTTP endpoint
php artisan test:weather-endpoint

# 3. Test with authentication
php artisan test:weather-endpoint --user=1
```

## 📚 Documentation

### API Documentation
- **Postman Collection**: [Laravel_Sanctum_API_Complete_Collection.json](./Laravel_Sanctum_API_Complete_Collection.json)
- **Import into Postman**: Contains all 27 endpoints with tests and examples
- **Environment Variables**: Pre-configured with authentication token management
- **Test Scripts**: Automated testing for each endpoint

### Key Files & Directories
```
├── app/
│   ├── Http/Controllers/Api/     # API Controllers
│   ├── Http/Requests/           # Form Request Validation
│   ├── Http/Resources/          # API Resources
│   ├── Mail/                    # Email Classes
│   ├── Models/                  # Eloquent Models
│   └── Services/                # Business Logic Services
├── config/
│   ├── auth.php                 # Authentication Configuration
│   ├── mail.php                 # Email Configuration
│   └── weather.php              # Weather API Configuration
├── database/
│   ├── migrations/              # Database Migrations
│   ├── factories/               # Model Factories
│   └── seeders/                 # Database Seeders
├── routes/
│   └── api.php                  # API Routes
├── tests/
│   ├── Feature/                 # Feature Tests
│   └── Unit/                    # Unit Tests
├── Laravel_Sanctum_API_Complete_Collection.json  # Postman Collection
└── GMAIL_TESTING_GUIDE.md       # Email Setup Guide
```

### Authentication Flow
1. **Register/Login**: Get authentication token
2. **Include Token**: Add `Authorization: Bearer {token}` header
3. **Access Protected Routes**: Use token for all authenticated endpoints
4. **Logout**: Revoke current token

### Data Validation
All endpoints include comprehensive validation:
- **Registration**: Email uniqueness, password confirmation, field lengths
- **Posts**: Required fields, date format validation, status validation
- **Weather**: Location parameter validation
- **User Management**: Email uniqueness, optional field handling

### Error Handling
Standardized error responses:
- **422**: Validation errors with detailed field messages
- **401**: Authentication errors
- **403**: Authorization errors
- **404**: Resource not found
- **500**: Server errors with logging

## 🧪 Testing

### Test Coverage
- **Total Tests**: 94 tests with 457 assertions
- **Success Rate**: 98.6% (92 passing, 2 skipped)
- **Unit Tests**: 19/19 passing (100%)
- **Feature Tests**: 75/75 passing (100%)

### Test Categories
- **Authentication Tests**: Registration, login, logout, profile access
- **User CRUD Tests**: Complete user management operations
- **Post CRUD Tests**: Post creation, reading, updating, deletion
- **Weather API Tests**: External API integration and caching
- **Email Tests**: Welcome email functionality and queuing
- **Validation Tests**: Input validation and error handling
- **Authorization Tests**: Access control and permissions

## 📝 License

This project is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).

---

**Built with ❤️ using Laravel 12 and modern PHP practices**