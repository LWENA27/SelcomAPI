# 🚀 Selcom Checkout API Clone<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>



A production-grade Laravel implementation of Selcom's Checkout API for payment gateway integration. Built for technical interview demonstration with focus on security, scalability, and best practices.<p align="center">

<a href="https://github.com/laravel/framework/actions"><img src="https://github.com/laravel/framework/workflows/tests/badge.svg" alt="Build Status"></a>

[![Laravel](https://img.shields.io/badge/Laravel-12.x-red.svg)](https://laravel.com)<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/dt/laravel/framework" alt="Total Downloads"></a>

[![PHP](https://img.shields.io/badge/PHP-8.1+-blue.svg)](https://php.net)<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version"></a>

[![License](https://img.shields.io/badge/License-MIT-green.svg)](LICENSE)<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>

</p>

## 📋 Table of Contents

## About Laravel

- [Features](#-features)

- [Tech Stack](#-tech-stack)Laravel is a web application framework with expressive, elegant syntax. We believe development must be an enjoyable and creative experience to be truly fulfilling. Laravel takes the pain out of development by easing common tasks used in many web projects, such as:

- [Installation](#-installation)

- [API Documentation](#-api-documentation)- [Simple, fast routing engine](https://laravel.com/docs/routing).

- [Testing](#-testing)- [Powerful dependency injection container](https://laravel.com/docs/container).

- [Security](#-security)- Multiple back-ends for [session](https://laravel.com/docs/session) and [cache](https://laravel.com/docs/cache) storage.

- [Interview Highlights](#-interview-highlights)- Expressive, intuitive [database ORM](https://laravel.com/docs/eloquent).

- Database agnostic [schema migrations](https://laravel.com/docs/migrations).

## ✨ Features- [Robust background job processing](https://laravel.com/docs/queues).

- [Real-time event broadcasting](https://laravel.com/docs/broadcasting).

### Core Functionality

- ✅ **Order Creation** - Minimal and full checkout order creationLaravel is accessible, powerful, and provides tools required for large, robust applications.

- ✅ **Payment Callbacks** - Webhook handling for payment notifications

- ✅ **Order Status** - Real-time order tracking and queries## Learning Laravel

- ✅ **Order Cancellation** - Safe cancellation with state validation

- ✅ **Order Listing** - Date-range filtered order retrievalLaravel has the most extensive and thorough [documentation](https://laravel.com/docs) and video tutorial library of all modern web application frameworks, making it a breeze to get started with the framework. You can also check out [Laravel Learn](https://laravel.com/learn), where you will be guided through building a modern Laravel application.



### Security & AuthenticationIf you don't feel like reading, [Laracasts](https://laracasts.com) can help. Laracasts contains thousands of video tutorials on a range of topics including Laravel, modern PHP, unit testing, and JavaScript. Boost your skills by digging into our comprehensive video library.

- 🔐 **Two-Layer Security**

  - API Key authentication (Bearer token)## Laravel Sponsors

  - HMAC-SHA256 signature verification

- 🛡️ **Request Validation** - Laravel Form Requests with strict rulesWe would like to extend our thanks to the following sponsors for funding Laravel development. If you are interested in becoming a sponsor, please visit the [Laravel Partners program](https://partners.laravel.com).

- 📝 **Audit Logging** - Comprehensive logging of all operations

- ⏱️ **Order Expiry** - Time-limited checkout sessions### Premium Partners



### Production-Ready Features- **[Vehikl](https://vehikl.com)**

- 💰 **Financial Accuracy** - Integer-based amount handling (no float errors)- **[Tighten Co.](https://tighten.co)**

- 🔄 **Idempotency** - Composite unique keys prevent duplicate payments- **[Kirschbaum Development Group](https://kirschbaumdevelopment.com)**

- 📊 **Soft Deletes** - Data retention for auditing and compliance- **[64 Robots](https://64robots.com)**

- 🎯 **RESTful API** - Standard HTTP methods and status codes- **[Curotec](https://www.curotec.com/services/technologies/laravel)**

- 📦 **JSON Responses** - Consistent Selcom-style response format- **[DevSquad](https://devsquad.com/hire-laravel-developers)**

- **[Redberry](https://redberry.international/laravel-development)**

## 🛠️ Tech Stack- **[Active Logic](https://activelogic.com)**



- **Framework:** Laravel 12.x## Contributing

- **Database:** MySQL 8.0

- **PHP Version:** 8.1+Thank you for considering contributing to the Laravel framework! The contribution guide can be found in the [Laravel documentation](https://laravel.com/docs/contributions).

- **Authentication:** Custom HMAC middleware

- **API Style:** RESTful JSON API## Code of Conduct



## 📥 InstallationIn order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).



### Prerequisites## Security Vulnerabilities

```bash

- PHP >= 8.1If you discover a security vulnerability within Laravel, please send an e-mail to Taylor Otwell via [taylor@laravel.com](mailto:taylor@laravel.com). All security vulnerabilities will be promptly addressed.

- Composer

- MySQL >= 8.0## License

- Git

```The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).


### Setup Steps

1. **Clone the repository**
```bash
git clone https://github.com/LWENA27/SelcomAPI.git
cd SelcomAPI
```

2. **Install dependencies**
```bash
composer install
```

3. **Configure environment**
```bash
cp .env.example .env
php artisan key:generate
```

4. **Update `.env` file**
```env
DB_DATABASE=selcom_checkout
DB_USERNAME=root
DB_PASSWORD=your_password

# API Security Keys
API_KEY=sk_live_selcom_test_key_12345678
API_SECRET=whsec_hmac_secret_key_987654321
```

5. **Create database**
```bash
mysql -u root -p -e "CREATE DATABASE selcom_checkout;"
```

6. **Run migrations**
```bash
php artisan migrate
```

7. **Start the server**
```bash
php artisan serve
```

API will be available at: `http://127.0.0.1:8000`

## 📚 API Documentation

Complete API documentation is available in [`docs/API.md`](docs/API.md)

### Quick Start

**Health Check**
```bash
curl http://127.0.0.1:8000/api/v1/health
```

**Create Order (with signature)**
```bash
# Generate signature
PAYLOAD='{"vendor":"SHOP203","order_id":"ORD-123","buyer_email":"test@example.com","buyer_name":"John Doe","buyer_phone":"255712345678","amount":50000,"currency":"TZS","no_of_items":1}'
SECRET="whsec_hmac_secret_key_987654321"
SIGNATURE=$(echo -n "$PAYLOAD" | openssl dgst -sha256 -hmac "$SECRET" | cut -d' ' -f2)

# Make request
curl -X POST http://127.0.0.1:8000/api/v1/checkout/create-order-minimal \
  -H "Authorization: Bearer sk_live_selcom_test_key_12345678" \
  -H "X-SIGNATURE: $SIGNATURE" \
  -H "Content-Type: application/json" \
  -d "$PAYLOAD"
```

### Base URL
```
http://127.0.0.1:8000/api/v1
```

### Authentication Headers
```
Authorization: Bearer {API_KEY}
X-SIGNATURE: {HMAC_SHA256_SIGNATURE}
```

### Endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/health` | API health check |
| POST | `/checkout/create-order-minimal` | Create minimal order |
| GET | `/checkout/order-status` | Query order status |
| POST | `/checkout/payment-callback` | Payment notification webhook |
| DELETE | `/checkout/cancel-order` | Cancel pending order |
| GET | `/checkout/list-orders` | List orders by date range |

## 🧪 Testing

### Postman Collection

Import the collection from `docs/Selcom_Checkout_API.postman_collection.json`

**Features:**
- ✅ Pre-request scripts for automatic signature generation
- ✅ Tests for response validation
- ✅ Environment variables for easy configuration
- ✅ Complete API workflow demonstration

### Manual Testing Flow

1. **Create Order** → Returns `payment_gateway_url`
2. **Check Status** → Verify order is `PENDING`
3. **Simulate Payment** → Send callback with payment details
4. **Check Status** → Verify order is `COMPLETED`

### Run Laravel Tests (Optional)
```bash
php artisan test
```

## 🔒 Security

### HMAC Signature Verification

**How it works:**
1. Client creates request body/query string
2. Client computes: `HMAC-SHA256(payload, SECRET)`
3. Client sends signature in `X-SIGNATURE` header
4. Server recomputes signature and compares

**Why HMAC?**
- ✅ Verifies request integrity (not tampered)
- ✅ Proves authenticity (only holder of secret can create valid signature)
- ✅ Protects against replay attacks (when combined with timestamp)

### Security Best Practices Implemented

1. **No Floats for Money** - All amounts stored as integers (cents)
2. **Mass Assignment Protection** - Only whitelisted fields fillable
3. **SQL Injection Prevention** - Eloquent ORM with prepared statements
4. **Constant-Time Comparison** - `hash_equals()` prevents timing attacks
5. **Input Validation** - Laravel Form Requests with strict rules
6. **Rate Limiting** - Configurable throttling on routes
7. **HTTPS Required** - Production must use encrypted connections

## 💼 Interview Highlights

### Key Technical Concepts Demonstrated

#### 1. **Payment Gateway Architecture**
- Order lifecycle management (PENDING → COMPLETED)
- Webhook callback handling
- Idempotent operations (vendor + order_id uniqueness)

#### 2. **Security Implementation**
- Two-factor authentication (API Key + HMAC)
- Request integrity verification
- Timing attack prevention

#### 3. **Financial Systems Knowledge**
- Integer-based amount storage (avoiding float precision errors)
- Currency handling (minor units - cents/paisa)
- Transaction state machines

#### 4. **Laravel Best Practices**
- Form Request validation
- Eloquent relationships and scopes
- Custom middleware
- RESTful routing
- Service layer pattern

#### 5. **Database Design**
- Composite unique constraints
- Soft deletes for audit trails
- Indexed columns for performance
- Enum types for state management

### Interview Questions to Prepare

**Q: "Why use HMAC instead of just API keys?"**
> A: Defense in depth. API keys authenticate WHO you are, HMAC verifies the request hasn't been TAMPERED WITH. Even if someone steals the API key, they can't forge valid signatures without the secret.

**Q: "Why store amounts as integers instead of floats?"**
> A: Floating-point arithmetic has precision errors (0.1 + 0.2 ≠ 0.3). For financial systems, we store in minor currency units (cents) as integers to ensure accuracy. 50000 cents = 500.00 TZS exactly.

**Q: "How do you prevent duplicate payments?"**
> A: Composite unique constraint on (vendor, order_id). If a merchant retries the same order_id, the database rejects it. This is idempotency - same request produces same result.

**Q: "What happens if the payment callback fails?"**
> A: We log the failure and return appropriate error code. Payment gateways have retry mechanisms. Order remains PENDING until confirmed. Manual reconciliation can resolve edge cases.

**Q: "How would you scale this to millions of transactions?"**
> A: 
> - Database indexing on frequently queried columns
> - Queue-based callback processing
> - Redis caching for order lookups
> - Database read replicas for queries
> - Horizontal scaling with load balancers

## 📝 Project Structure

```
selcom/
├── app/
│   ├── Http/
│   │   ├── Controllers/Api/
│   │   │   └── CheckoutController.php    # Main API logic
│   │   ├── Middleware/
│   │   │   └── VerifySignature.php       # HMAC verification
│   │   └── Requests/
│   │       ├── CreateOrderRequest.php    # Validation rules
│   │       └── PaymentCallbackRequest.php
│   └── Models/
│       └── Order.php                      # Order Eloquent model
├── database/
│   └── migrations/
│       └── *_create_orders_table.php      # Database schema
├── docs/
│   ├── API.md                             # Complete API docs
│   └── Selcom_Checkout_API.postman_collection.json
├── routes/
│   └── api.php                            # API routes
└── README.md                              # This file
```

## 🎯 Real-World Selcom API Reference

This implementation is based on the official Selcom Checkout API:
- Documentation: https://developers.selcommobile.com/#checkout-api
- Key differences: This is a learning/demo implementation, not connected to real payment processors

## 📄 License

MIT License - feel free to use for learning and interviews

## 👨‍💻 Author

**LWENA**
- GitHub: [@LWENA27](https://github.com/LWENA27)
- Project: [SelcomAPI](https://github.com/LWENA27/SelcomAPI)

---

## 🎓 Learning Resources

- [Selcom Official Docs](https://developers.selcommobile.com)
- [Laravel Documentation](https://laravel.com/docs)
- [HMAC Authentication](https://www.okta.com/identity-101/hmac/)
- [Payment Gateway Design Patterns](https://stripe.com/docs/api)

---

**⭐ Star this repo if it helped you prepare for your interview!**
