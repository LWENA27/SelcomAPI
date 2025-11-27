# Selcom Checkout API - Documentation

## Overview

This is a **Mini Checkout API System** modeled after [Selcom's Checkout API](https://developers.selcommobile.com/#checkout-api). It provides a secure payment gateway integration for e-commerce platforms to accept payments via mobile wallets, cards, and other channels in Tanzania.

### Key Features

✅ **Two-Layer Security**
- API Key authentication (Bearer token)
- HMAC-SHA256 signature verification

✅ **Payment Lifecycle Management**
- Order creation with unique identifiers
- Real-time status tracking
- Payment callback handling

✅ **Production-Ready Design**
- Input validation and sanitization
- Comprehensive error handling
- Audit logging
- Idempotency support

---

## Authentication

All API requests (except health check) require **two-layer authentication**:

### 1. API Key (Bearer Token)

```http
Authorization: Bearer sk_live_selcom_test_key_12345678
```

**Purpose**: Identifies the client making the request.

### 2. HMAC-SHA256 Signature

```http
X-SIGNATURE: <computed_signature>
```

**Purpose**: Verifies request integrity (prevents tampering).

**How to compute:**
```php
$payload = json_encode($requestBody);
$signature = hash_hmac('sha256', $payload, $apiSecret);
```

### Interview Discussion Point

> **Q: Why two authentication layers?**
>
> **A**: Defense in depth! Even if someone steals the API key, they can't forge valid signatures without the secret. This is the gold standard used by Stripe, PayPal, and other payment gateways.

---

## Base URL

```
http://your-domain.com/api/v1
```

---

## Standard Response Format

All endpoints follow Selcom's response structure:

### Success Response
```json
{
  "reference": "ORD-123456",
  "resultcode": "000",
  "result": "SUCCESS",
  "message": "Operation successful",
  "data": [...]
}
```

### Error Response
```json
{
  "reference": null,
  "resultcode": "400",
  "result": "FAIL",
  "message": "Validation failed",
  "details": "amount is required",
  "data": []
}
```

### Result Codes

| Code | Meaning |
|------|---------|
| `000` | Success |
| `400` | Bad Request / Validation Error |
| `404` | Resource Not Found |
| `500` | Internal Server Error |

---

## Endpoints

### 1. Health Check

**No authentication required**

```http
GET /api/v1/health
```

**Response:**
```json
{
  "status": "ok",
  "service": "Selcom Checkout API",
  "version": "1.0.0",
  "timestamp": "2025-11-27T10:30:45+00:00"
}
```

---

### 2. Create Order (Minimal)

Create a new checkout order for payment processing.

```http
POST /api/v1/checkout/create-order-minimal
```

#### Headers
```http
Content-Type: application/json
Authorization: Bearer {API_KEY}
X-SIGNATURE: {HMAC_SHA256_SIGNATURE}
```

#### Request Body

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `vendor` | string | Yes | Merchant/shop identifier (e.g., "SHOP203") |
| `order_id` | string | Yes | Unique order ID from your system |
| `buyer_email` | string | Yes | Customer email address |
| `buyer_name` | string | Yes | Customer full name |
| `buyer_phone` | string | Yes | Customer phone number (international format) |
| `amount` | integer | Yes | Amount in cents (e.g., 50000 = 500.00 TZS) |
| `currency` | string | No | Currency code (TZS, USD). Default: TZS |
| `buyer_remarks` | string | No | Customer's note/description |
| `merchant_remarks` | string | No | Merchant's note/description |
| `no_of_items` | integer | No | Number of items. Default: 1 |
| `expiry` | integer | No | Order expiry in minutes. Default: 60 |

#### Example Request
```json
{
  "vendor": "SHOP203",
  "order_id": "ORD-20251127-ABC123",
  "buyer_email": "john.doe@example.com",
  "buyer_name": "John Doe",
  "buyer_phone": "255712345678",
  "amount": 50000,
  "currency": "TZS",
  "buyer_remarks": "Payment for invoice #12345",
  "no_of_items": 3,
  "expiry": 120
}
```

#### Success Response (201 Created)
```json
{
  "reference": "ORD-20251127-ABC123",
  "resultcode": "000",
  "result": "SUCCESS",
  "message": "Order created successfully",
  "data": [
    {
      "order_id": "ORD-20251127-ABC123",
      "gateway_buyer_uuid": "550e8400-e29b-41d4-a716-446655440000",
      "payment_token": "12345678",
      "qr": "U0VMQ09NLVBBWS0xMjM0NTY3OA==",
      "payment_gateway_url": "http://your-domain.com/payment-gateway/ORD-20251127-ABC123",
      "amount": 50000,
      "currency": "TZS",
      "payment_status": "PENDING",
      "creation_date": "2025-11-27 10:30:45"
    }
  ]
}
```

#### Interview Points

1. **Idempotency**: If you call this endpoint twice with the same `vendor` + `order_id`, you'll get the same order back. This prevents double-charging customers.

2. **Amount in Cents**: We use integers (cents) instead of floats to avoid precision errors:
   ```
   0.1 + 0.2 = 0.30000000000000004 (float)
   10 + 20 = 30 (integer)
   ```

3. **Payment Token**: Like a till number or payment code customers can use at agents or USSD.

---

### 3. Get Order Status

Query the current status of an order.

```http
GET /api/v1/checkout/order-status?order_id={order_id}
```

#### Headers
```http
Authorization: Bearer {API_KEY}
X-SIGNATURE: {HMAC_SHA256_SIGNATURE}
```

#### Query Parameters

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `order_id` | string | Yes | The order ID to query |

#### Example Request
```http
GET /api/v1/checkout/order-status?order_id=ORD-20251127-ABC123
```

#### Success Response (200 OK)
```json
{
  "reference": "ORD-20251127-ABC123",
  "resultcode": "000",
  "result": "SUCCESS",
  "message": "Order fetched successfully",
  "data": [
    {
      "order_id": "ORD-20251127-ABC123",
      "creation_date": "2025-11-27 10:30:45",
      "amount": 50000,
      "currency": "TZS",
      "payment_status": "COMPLETED",
      "transid": "TXN1732704645",
      "channel": "MPESA-TZ",
      "reference": "REF1732704645",
      "phone": "255712345678"
    }
  ]
}
```

#### Payment Status Values

| Status | Description |
|--------|-------------|
| `PENDING` | Order created, awaiting payment |
| `INPROGRESS` | Payment being processed |
| `COMPLETED` | Payment successful |
| `CANCELLED` | Cancelled by merchant |
| `USERCANCELLED` | Cancelled by customer |
| `REJECTED` | Payment rejected/failed |

---

### 4. Payment Callback (Webhook Simulation)

Simulate payment completion from payment gateway.

**Note**: In production, this would be called by Selcom's servers, not by the merchant.

```http
POST /api/v1/checkout/payment-callback
```

#### Headers
```http
Content-Type: application/json
Authorization: Bearer {API_KEY}
X-SIGNATURE: {HMAC_SHA256_SIGNATURE}
```

#### Request Body

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `order_id` | string | Yes | Order ID to update |
| `transid` | string | Yes | Unique transaction ID from payment channel |
| `channel` | string | Yes | Payment channel (MPESA-TZ, AIRTELMONEY, etc.) |
| `reference` | string | Yes | Payment gateway reference |
| `result` | string | Yes | Payment result (SUCCESS or FAIL) |

#### Example Request
```json
{
  "order_id": "ORD-20251127-ABC123",
  "transid": "TXN1732704645",
  "channel": "MPESA-TZ",
  "reference": "REF1732704645",
  "result": "SUCCESS"
}
```

#### Success Response (200 OK)
```json
{
  "reference": "REF1732704645",
  "resultcode": "000",
  "result": "SUCCESS",
  "message": "Payment processed successfully",
  "data": []
}
```

---

### 5. Cancel Order

Cancel a pending order before payment completion.

```http
DELETE /api/v1/checkout/cancel-order?order_id={order_id}
```

#### Headers
```http
Authorization: Bearer {API_KEY}
X-SIGNATURE: {HMAC_SHA256_SIGNATURE}
```

#### Query Parameters

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `order_id` | string | Yes | Order ID to cancel |

#### Example Request
```http
DELETE /api/v1/checkout/cancel-order?order_id=ORD-20251127-ABC123
```

#### Success Response (200 OK)
```json
{
  "reference": "ORD-20251127-ABC123",
  "resultcode": "000",
  "result": "SUCCESS",
  "message": "Order cancelled successfully",
  "data": []
}
```

#### Error Response (Cannot Cancel)
```json
{
  "reference": null,
  "resultcode": "400",
  "result": "FAIL",
  "message": "Cannot cancel order",
  "details": "Order is COMPLETED and cannot be cancelled",
  "data": []
}
```

---

## Complete Payment Flow

### 1. Merchant creates order
```
POST /api/v1/checkout/create-order-minimal
→ Returns payment_token and payment_gateway_url
```

### 2. Customer pays
```
Customer visits payment_gateway_url or uses payment_token
Enters mobile money PIN or card details
```

### 3. Payment gateway callback
```
POST /api/v1/checkout/payment-callback
→ Updates order status to COMPLETED
```

### 4. Merchant checks status
```
GET /api/v1/checkout/order-status
→ Confirms payment completion
```

---

## Code Examples

### PHP - Create Order with HMAC Signature

```php
<?php

$apiKey = 'sk_live_selcom_test_key_12345678';
$apiSecret = 'whsec_hmac_secret_key_987654321';
$baseUrl = 'http://your-domain.com/api/v1';

// Order data
$orderData = [
    'vendor' => 'SHOP203',
    'order_id' => 'ORD-' . time(),
    'buyer_email' => 'john.doe@example.com',
    'buyer_name' => 'John Doe',
    'buyer_phone' => '255712345678',
    'amount' => 50000,
    'currency' => 'TZS',
];

// Convert to JSON
$payload = json_encode($orderData);

// Compute HMAC signature
$signature = hash_hmac('sha256', $payload, $apiSecret);

// Make request
$ch = curl_init("{$baseUrl}/checkout/create-order-minimal");
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => $payload,
    CURLOPT_HTTPHEADER => [
        'Content-Type: application/json',
        "Authorization: Bearer {$apiKey}",
        "X-SIGNATURE: {$signature}",
    ],
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

$result = json_decode($response, true);
print_r($result);
```

### Python - Create Order

```python
import hmac
import hashlib
import json
import requests

api_key = 'sk_live_selcom_test_key_12345678'
api_secret = 'whsec_hmac_secret_key_987654321'
base_url = 'http://your-domain.com/api/v1'

# Order data
order_data = {
    'vendor': 'SHOP203',
    'order_id': f'ORD-{int(time.time())}',
    'buyer_email': 'john.doe@example.com',
    'buyer_name': 'John Doe',
    'buyer_phone': '255712345678',
    'amount': 50000,
    'currency': 'TZS'
}

# Convert to JSON
payload = json.dumps(order_data)

# Compute HMAC signature
signature = hmac.new(
    api_secret.encode('utf-8'),
    payload.encode('utf-8'),
    hashlib.sha256
).hexdigest()

# Make request
headers = {
    'Content-Type': 'application/json',
    'Authorization': f'Bearer {api_key}',
    'X-SIGNATURE': signature
}

response = requests.post(
    f'{base_url}/checkout/create-order-minimal',
    data=payload,
    headers=headers
)

print(response.json())
```

---

## Interview Discussion Topics

### 1. Security Architecture

**Q: Why do you use HMAC instead of just API keys?**

**A**: HMAC provides **integrity verification**. An API key only proves "who you are," but HMAC proves "the message wasn't tampered with." Even if someone intercepts the request, they can't modify it without knowing the secret.

### 2. Database Design

**Q: Why store amounts as integers instead of decimals?**

**A**: Floating-point precision errors. `0.1 + 0.2 !== 0.3` in most programming languages. Financial systems use:
- **Integers** (cents/paisa): Simple, no precision loss
- **Decimal types**: Fixed precision (e.g., DECIMAL(10,2))
- **Never floats**: Too risky for money!

### 3. Idempotency

**Q: How do you prevent duplicate payments if a user clicks "Pay" twice?**

**A**: Composite unique constraint on `vendor + order_id`. If the same order_id is submitted twice, we return the existing order instead of creating a duplicate. This is called **idempotent API design**.

### 4. Status Management

**Q: Why can't you cancel a COMPLETED order?**

**A**: Business logic: Once money changes hands, cancellation becomes a **refund** operation, which requires different workflows (accounting, compliance, reconciliation).

### 5. Logging

**Q: What do you log and why?**

**A**: 
- **Order creation**: Audit trail, fraud detection
- **Payment callbacks**: Reconciliation, debugging
- **Failed requests**: Security monitoring, troubleshooting
- **But NOT**: Sensitive data (PINs, full card numbers)

---

## Error Handling

### Common Errors

#### 1. Missing Signature
```json
{
  "reference": null,
  "resultcode": "401",
  "result": "FAIL",
  "message": "Invalid signature",
  "data": []
}
```

#### 2. Duplicate Order
```json
{
  "reference": "ORD-123",
  "resultcode": "000",
  "result": "SUCCESS",
  "message": "Order already exists",
  "data": [...]
}
```

#### 3. Order Not Found
```json
{
  "reference": null,
  "resultcode": "404",
  "result": "FAIL",
  "message": "Order not found",
  "details": "Order with ID ORD-123 does not exist",
  "data": []
}
```

---

## Testing

### Manual Testing Steps

1. **Start server**: `php artisan serve`
2. **Create order**: Use Postman or curl
3. **Check status**: Verify PENDING
4. **Simulate payment**: Call payment-callback
5. **Verify completion**: Check status is COMPLETED

### Using Postman

Import the Postman collection: `docs/Selcom-API.postman_collection.json`

The collection includes:
- Pre-configured environment variables
- Automatic HMAC signature generation
- Complete test flow examples

---

## Production Deployment Checklist

- [ ] Change API keys from test to production values
- [ ] Enable HTTPS (TLS/SSL)
- [ ] Set up rate limiting
- [ ] Configure proper logging (ELK stack, CloudWatch, etc.)
- [ ] Implement webhook retry logic
- [ ] Add monitoring and alerts
- [ ] Set up database backups
- [ ] Configure CORS policies
- [ ] Implement IP whitelisting for callbacks
- [ ] Add request/response logging
- [ ] Set up error tracking (Sentry, Bugsnag)

---

## Support & Contact

For questions or issues:
- Email: support@yourcompany.com
- Documentation: https://docs.yourapi.com
- API Status: https://status.yourapi.com

---

**Built with Laravel 12** | **Inspired by Selcom Checkout API** | **Version 1.0.0**
