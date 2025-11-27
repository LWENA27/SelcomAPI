# Selcom Checkout API

[![Laravel](https://img.shields.io/badge/Laravel-12.x-red.svg)](https://laravel.com)
[![PHP](https://img.shields.io/badge/PHP-8.1+-blue.svg)](https://php.net)
[![License](https://img.shields.io/badge/License-MIT-green.svg)](LICENSE)

A Laravel-based REST API implementation for Selcom payment gateway integration. This API enables merchants to integrate Selcom's checkout services into their applications with support for multiple payment methods including card payments, mobile wallets (M-Pesa, Airtel Money, Tigo Pesa, Halo Pesa), and SelcomPesa.

## Overview

This API provides a complete integration layer for Selcom's payment services, allowing merchants to:
- Create and manage payment orders
- Process payments through multiple channels
- Manage stored payment cards for repeat customers
- Handle payment webhooks and callbacks
- Create and manage till aliases

## Features

### Payment Methods
- **Card Payments** - Visa, Mastercard with tokenization support
- **Mobile Wallets** - M-Pesa Tanzania, Airtel Money, Tigo Pesa, Halo Pesa
- **SelcomPesa** - Dedicated SelcomPesa payment channel
- **Stored Cards** - Save customer cards for future payments

### Security
- HMAC-SHA256 signature verification for all requests
- API key authentication
- PCI-compliant card tokenization
- Secure webhook callbacks

### Order Management
- Create orders with minimal or full details
- Real-time order status tracking
- Order cancellation
- Date-range order queries
- Payment callback handling

## Tech Stack

- **Framework:** Laravel 12.x
- **Database:** MySQL 8.0
- **PHP:** 8.1+
- **Authentication:** HMAC + API Keys

## Installation

### Prerequisites

```bash
- PHP >= 8.1
- Composer
- MySQL >= 8.0
- Git
```


### Setup

1. **Clone the repository**
```bash
git clone https://github.com/LWENA27/SelcomAPI.git
cd SelcomAPI
```

2. **Install dependencies**
```bash
composer install
```

3. **Environment configuration**
```bash
cp .env.example .env
php artisan key:generate
```

4. **Configure database**

Edit `.env` file:
```env
DB_DATABASE=selcom_checkout
DB_USERNAME=root
DB_PASSWORD=your_password
```

5. **Set API credentials**

Add your API keys to `.env`:
```env
API_KEY=your_selcom_api_key
API_SECRET=your_selcom_api_secret
```

6. **Database setup**
```bash
mysql -u root -p -e "CREATE DATABASE selcom_checkout;"
php artisan migrate
```

7. **Start the server**
```bash
php artisan serve
```

The API will be available at `http://127.0.0.1:8000`

## API Documentation

Complete API documentation is available in [`docs/API.md`](docs/API.md)

### Base URL
```
http://127.0.0.1:8000/api/v1
```

### Authentication

All API requests require two headers:
```
Authorization: Bearer {YOUR_API_KEY}
X-SIGNATURE: {HMAC_SHA256_SIGNATURE}
```

**Generating HMAC Signature:**
```bash
PAYLOAD='{"vendor":"VENDOR123","order_id":"ORD-001",...}'
SIGNATURE=$(echo -n "$PAYLOAD" | openssl dgst -sha256 -hmac "$API_SECRET" | cut -d' ' -f2)
```

### API Endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/health` | Health check |
| POST | `/checkout/create-order-minimal` | Create basic order |
| POST | `/checkout/create-order` | Create order with full details |
| GET | `/checkout/order-status` | Get order status |
| POST | `/checkout/payment-callback` | Payment webhook |
| POST | `/checkout/cancel-order` | Cancel order |
| GET | `/checkout/list-orders` | List orders |
| GET | `/checkout/stored-cards` | Get saved cards |
| DELETE | `/checkout/delete-card` | Delete saved card |
| POST | `/checkout/card-payment` | Pay with card |
| POST | `/checkout/wallet-payment` | Mobile wallet payment |
| POST | `/checkout/selcompesa-payment` | SelcomPesa payment |
| POST | `/checkout/create-till-alias` | Create till alias |

### Quick Example

```bash
# Create an order
curl -X POST http://127.0.0.1:8000/api/v1/checkout/create-order-minimal \
  -H "Authorization: Bearer YOUR_API_KEY" \
  -H "X-SIGNATURE: COMPUTED_SIGNATURE" \
  -H "Content-Type: application/json" \
  -d '{
    "vendor": "VENDOR123",
    "order_id": "ORD-001",
    "buyer_email": "customer@example.com",
    "buyer_name": "John Doe",
    "buyer_phone": "255712345678",
    "amount": 50000,
    "currency": "TZS",
    "order_desc": "Product purchase"
  }'
```

## Testing

### Using Postman

Import the collection from `docs/Selcom_Checkout_API.postman_collection.json`

The collection includes:
- Pre-configured requests for all endpoints
- Automatic HMAC signature generation
- Environment variables for easy configuration
- Sample test data

## Security

### HMAC Signature Verification

All POST/PUT/PATCH requests must include an HMAC-SHA256 signature to ensure request integrity and authenticity.

**Process:**
1. Serialize request payload (JSON for body, query string for GET)
2. Compute HMAC: `hash_hmac('sha256', $payload, $apiSecret)`
3. Include signature in `X-SIGNATURE` header
4. Server validates by recomputing and comparing

### Security Features

- API key authentication for merchant identification
- HMAC-SHA256 signatures prevent request tampering
- Integer-based financial calculations (no floating-point errors)
- SQL injection protection via Laravel ORM
- Input validation on all endpoints
- Idempotent operations prevent duplicate payments
- Secure webhook callbacks with signature verification

## Project Structure

```
selcom/
├── app/
│   ├── Http/
│   │   ├── Controllers/Api/
│   │   │   └── CheckoutController.php
│   │   ├── Middleware/
│   │   │   └── VerifySignature.php
│   │   └── Requests/
│   │       ├── CreateOrderRequest.php
│   │       └── PaymentCallbackRequest.php
│   └── Models/
│       ├── Order.php
│       ├── StoredCard.php
│       └── TillAlias.php
├── database/
│   └── migrations/
│       ├── *_create_orders_table.php
│       ├── *_create_stored_cards_table.php
│       └── *_create_till_aliases_table.php
├── docs/
│   ├── API.md
│   └── Selcom_Checkout_API.postman_collection.json
├── routes/
│   └── api.php
└── README.md
```

## Reference

This implementation follows the Selcom Checkout API specification:
- Official Documentation: https://developers.selcommobile.com/#checkout-api

## License

MIT License

## Author

**Adam Samson Lwena**
- GitHub: [@LWENA27](https://github.com/LWENA27)
- Email: lwena027@gmail.com
- Project: [SelcomAPI](https://github.com/LWENA27/SelcomAPI)
