# 🎯 Selcom Checkout API - Interview Preparation Guide

Complete guide for technical interview demonstration. Use this to confidently explain every aspect of the project.

---

## 📋 Quick Demo Script (5 minutes)

### Opening Statement
> "I built a production-grade payment gateway API clone based on Selcom's Checkout API. It demonstrates secure payment processing, webhook handling, and follows real-world payment industry standards."

### Live Demo Flow

1. **Show the Architecture** (30 seconds)
```
API Client → [API Key + HMAC Auth] → Laravel Routes → Controller → Model → MySQL
                                        ↓
                                  Validation
                                  Logging
```

2. **Demonstrate Security** (1 minute)
```bash
# Open terminal and show signature generation
PAYLOAD='{"vendor":"SHOP203","order_id":"ORD-DEMO-001",...}'
SIGNATURE=$(echo -n "$PAYLOAD" | openssl dgst -sha256 -hmac "secret" | cut -d' ' -f2)
echo "Generated Signature: $SIGNATURE"
```

3. **Show Postman Collection** (2 minutes)
- Import collection
- Run "Create Order" → Show automatic signature generation
- Run "Get Status" → Show PENDING status
- Run "Payment Callback" → Simulate payment
- Run "Get Status" again → Show COMPLETED status

4. **Highlight Code Quality** (1.5 minutes)
```php
// Open CheckoutController and point out:
- Form Request validation
- Try-catch error handling
- Logging at each step
- Selcom-style response formatting
- Business logic in Model methods
```

---

## 💡 Technical Concepts to Master

### 1. HMAC Authentication

**What it is:**
Hash-based Message Authentication Code using SHA256 algorithm.

**How it works:**
```
Client Side:
1. Create request: {"amount": 50000, "vendor": "SHOP203"}
2. Compute: HMAC-SHA256(request_body, API_SECRET)
3. Send: X-SIGNATURE: abc123def456...

Server Side:
1. Receive request
2. Recompute: HMAC-SHA256(request_body, API_SECRET)
3. Compare: client_signature === server_signature
4. If match → Proceed, else → Reject 401
```

**Interview Questions:**

**Q: Why not just encrypt the payload?**
> A: HMAC provides integrity and authenticity, not confidentiality. We use HTTPS for encryption in transit. HMAC is faster and perfect for verifying the request hasn't been tampered with.

**Q: What's hash_equals() and why use it?**
> A: It's a constant-time string comparison function. Regular `==` comparison can leak timing information through side-channel attacks, allowing attackers to guess signatures byte-by-byte. `hash_equals()` always takes the same time regardless of where strings differ.

**Q: How do you prevent replay attacks?**
> A: Include a timestamp in the payload and reject requests older than X minutes. Selcom uses ISO 8601 format timestamps in their signature computation.

---

### 2. Financial Data Handling

**Why Integer-Based Amounts?**

```php
// WRONG - Float precision errors
$price = 0.1 + 0.2;
echo $price; // 0.30000000000000004 ❌

// CORRECT - Integer cents
$price_cents = 10 + 20;
echo $price_cents; // 30 ✅
echo $price_cents / 100; // 0.30 (for display)
```

**Database Schema:**
```php
$table->unsignedInteger('amount'); // NOT decimal or float
```

**Interview Questions:**

**Q: Why not use DECIMAL(10,2)?**
> A: Both work, but integers are faster and eliminate any floating-point ambiguity. Industry standard (Stripe, PayPal, Selcom) is integer minor units.

**Q: How do you handle different currencies?**
> A: Store currency code (TZS, USD) separately. Minor unit division varies:
> - Most currencies: 100 (dollars → cents)
> - Japanese Yen: 1 (no decimal)
> - Bahraini Dinar: 1000 (three decimal places)

---

### 3. Idempotency

**What it is:**
Making the same API request multiple times produces the same result without side effects.

**Implementation:**
```php
// Database constraint
$table->unique(['vendor', 'order_id']);
```

**Example:**
```
Request 1: POST /create-order {vendor: "SHOP1", order_id: "ORD-123"}
Response: 201 Created ✅

Request 2: POST /create-order {vendor: "SHOP1", order_id: "ORD-123"}
Response: 409 Conflict ❌ (Order already exists)
```

**Interview Questions:**

**Q: Why is idempotency important in payment systems?**
> A: Network failures can cause clients to retry requests. Without idempotency, the customer could be charged twice. Composite unique keys prevent duplicate transactions.

**Q: How does this differ from ACID transactions?**
> A: ACID ensures database consistency within a single transaction. Idempotency ensures API-level consistency across multiple requests. Both are needed.

---

### 4. State Machines (Payment Status)

**Order Lifecycle:**
```
PENDING → INPROGRESS → COMPLETED
   ↓                        ↓
CANCELLED              (Final State)
   ↓
USERCANCELLED
```

**Business Rules:**
```php
// Only PENDING orders can be paid
public function canBePaid(): bool {
    return $this->payment_status === 'PENDING' 
        && !$this->isExpired();
}

// Cannot cancel COMPLETED orders
public function cancel(): bool {
    if ($this->payment_status === 'COMPLETED') {
        return false;
    }
    // ...
}
```

**Interview Questions:**

**Q: What if payment callback arrives after order expires?**
> A: Log the callback, return success to payment gateway (to stop retries), but mark order as REJECTED. Initiate refund process separately.

**Q: How do you handle concurrent requests?**
> A: Database-level locking with `FOR UPDATE`:
```php
$order = Order::where('id', $id)->lockForUpdate()->first();
```

---

### 5. Webhook Security

**Why webhooks need authentication:**
- Anyone can send HTTP POST to your callback URL
- Attacker could fake "payment successful" callbacks
- Must verify webhook is from legitimate payment gateway

**Selcom's Approach:**
1. They compute HMAC signature of callback payload
2. Send signature in header
3. We recompute and verify

**Interview Questions:**

**Q: What if webhook fails?**
> A: Payment gateways retry with exponential backoff:
> - Attempt 1: Immediate
> - Attempt 2: 5 minutes later
> - Attempt 3: 1 hour later
> - Continue up to 24 hours
> We log all attempts and have manual reconciliation process.

**Q: How do you ensure webhook order?**
> A: Include sequence number or timestamp. Process out-of-order webhooks correctly by checking current state before updating.

---

## 🗣️ Common Interview Questions

### Architecture & Design

**Q: Walk me through the request flow for creating an order.**

> A: 
> 1. Client generates HMAC signature of request body
> 2. Sends POST with Authorization + X-SIGNATURE headers
> 3. Request hits Laravel routes (api.php)
> 4. VerifySignature middleware validates both API key and HMAC
> 5. CreateOrderRequest validates input data
> 6. CheckoutController creates Order model
> 7. Generates payment_token and payment_gateway_url
> 8. Logs operation to laravel.log
> 9. Returns Selcom-formatted JSON response
> 10. Client redirects user to payment_gateway_url

**Q: How would you scale this to handle Black Friday traffic?**

> A:
> - **Database:** Read replicas for GET requests, write to primary
> - **Caching:** Redis for order lookups (cache-aside pattern)
> - **Queues:** Move webhook processing to background jobs (Laravel Queue)
> - **Load Balancer:** Nginx/AWS ALB distributing traffic across instances
> - **CDN:** Cache static content (docs, images)
> - **Database Indexing:** Already indexed on vendor, order_id, payment_status
> - **Monitoring:** APM tools (New Relic, DataDog) to identify bottlenecks

**Q: How do you handle database failures?**

> A:
> - Retry logic with exponential backoff
> - Circuit breaker pattern to fail fast
> - Dead letter queue for failed operations
> - Health check endpoint (`/api/v1/health`) for load balancer
> - Database connection pooling
> - Graceful degradation (return cached data if DB down)

---

### Security

**Q: What security vulnerabilities did you consider?**

> A:
> 1. **SQL Injection:** Prevented by Eloquent ORM prepared statements
> 2. **Timing Attacks:** Using `hash_equals()` for signature comparison
> 3. **Mass Assignment:** `$fillable` whitelist in Model
> 4. **CSRF:** Not needed for API (stateless, no cookies)
> 5. **Rate Limiting:** Throttle middleware (60 requests/minute)
> 6. **Input Validation:** Laravel Form Requests with strict rules
> 7. **XSS:** JSON API not rendering HTML
> 8. **Sensitive Data:** API keys in .env, never committed to git

**Q: How would you implement rate limiting?**

```php
// routes/api.php
Route::middleware(['throttle:60,1'])->group(function () {
    // 60 requests per 1 minute per IP
});

// For authenticated endpoints, limit by user:
Route::middleware(['throttle:100,1,user'])->group(function () {
    // 100 requests per minute per authenticated user
});
```

**Q: What about PCI DSS compliance?**

> A: This demo doesn't handle card details directly. In production:
> - Never store CVV
> - Tokenize cards (use Stripe/Selcom tokens)
> - Encrypt PAN (Primary Account Number) at rest
> - TLS 1.2+ for all communications
> - Regular security audits and penetration testing
> - PCI DSS Level 1 compliance for large volumes

---

### Code Quality

**Q: Why did you use Form Requests instead of validating in controller?**

> A:
> - **Separation of Concerns:** Validation logic separate from business logic
> - **Reusability:** Same validation across multiple controllers
> - **Automatic Responses:** Returns 422 with validation errors automatically
> - **Type Hinting:** `CreateOrderRequest $request` shows dependencies clearly
> - **Testing:** Easier to unit test validation rules

```php
// BAD - Controller bloat
public function store(Request $request) {
    $validated = $request->validate([
        'vendor' => 'required|string|max:50',
        'order_id' => 'required|string|max:100',
        // 20 more rules...
    ]);
    // Business logic...
}

// GOOD - Clean controller
public function store(CreateOrderRequest $request) {
    // Business logic only
}
```

**Q: Why put business logic in Model methods?**

> A: "Fat Models, Skinny Controllers" principle:
> - **Single Responsibility:** Controller handles HTTP, Model handles business rules
> - **Testability:** Can unit test `Order::markAsCompleted()` without HTTP
> - **Reusability:** Use same logic from CLI commands, jobs, tests
> - **Readability:** `$order->canBePaid()` is self-documenting

---

### Laravel-Specific

**Q: Why use Eloquent instead of Query Builder?**

> A:
> - **Relationships:** Easy `$order->transactions()`
> - **Mutators/Accessors:** Automatic data transformation
> - **Events:** Model events for auditing (creating, created, updating, etc.)
> - **Soft Deletes:** Built-in with `SoftDeletes` trait
> - **Scopes:** Reusable query constraints `Order::pending()`
> - Still can use Query Builder when needed for performance

**Q: What are the downsides of Eloquent?**

> A:
> - **N+1 Query Problem:** Must use eager loading (`with()`)
> - **Memory Usage:** Loading large datasets as models
> - **Performance:** Slight overhead vs raw SQL
> - **Solutions:** Use `chunk()`, `cursor()`, or Query Builder for bulk operations

---

## 🎬 Demo Checklist

### Before Interview
- [ ] Start Laravel server: `php artisan serve`
- [ ] Open Postman with imported collection
- [ ] Have terminal ready with project open in VS Code
- [ ] Check database has test data: `mysql -u root -p selcom_checkout`
- [ ] Test one complete flow to ensure everything works

### During Demo
- [ ] Explain project overview (30 seconds)
- [ ] Show Postman collection workflow (2 minutes)
- [ ] Open 2-3 key files and explain (2 minutes)
- [ ] Answer technical questions confidently
- [ ] Mention scalability/production considerations

### Key Files to Show
1. `app/Http/Controllers/Api/CheckoutController.php` - Main logic
2. `app/Http/Middleware/VerifySignature.php` - Security
3. `app/Models/Order.php` - Business logic
4. `database/migrations/*_create_orders_table.php` - Database design
5. `docs/API.md` - Documentation quality

---

## 🚀 Bonus: Advanced Topics

### If asked about microservices:
> "This monolith could be split into:
> - **Order Service:** Create/manage orders
> - **Payment Service:** Process payments
> - **Notification Service:** Send webhooks to merchants
> - **Analytics Service:** Reporting and insights
> 
> Communication via REST APIs or message queues (RabbitMQ/Kafka).
> Eventual consistency instead of ACID transactions."

### If asked about testing:
> "Would implement:
> - **Unit Tests:** Model methods, helper functions
> - **Feature Tests:** Full API request/response cycle
> - **Integration Tests:** Database transactions
> - **Contract Tests:** Ensure API matches documentation
> - **Load Tests:** Apache JMeter or Locust for performance"

### If asked about monitoring:
> "Production monitoring stack:
> - **APM:** New Relic / DataDog for performance
> - **Logging:** ELK Stack (Elasticsearch, Logstash, Kibana)
> - **Metrics:** Prometheus + Grafana
> - **Alerts:** PagerDuty for critical issues
> - **Uptime:** Pingdom / StatusCake"

---

## ✅ Final Pre-Interview Checklist

- [ ] Can explain HMAC authentication clearly
- [ ] Can justify integer-based money storage
- [ ] Understand idempotency and why it matters
- [ ] Know the complete order lifecycle
- [ ] Can discuss scaling strategies
- [ ] Familiar with security best practices
- [ ] Can demo end-to-end payment flow
- [ ] Prepared for "What would you improve?" question

---

## 🎓 Suggested Answer: "What would you improve?"

> "Given more time, I would add:
> 
> 1. **Testing Suite:** PHPUnit tests for 80%+ code coverage
> 2. **Async Webhooks:** Move callback processing to queues (Laravel Horizon)
> 3. **Retry Mechanism:** Exponential backoff for failed webhook deliveries
> 4. **Admin Dashboard:** Monitor orders, revenues, failed payments
> 5. **API Versioning:** Support /v1/ and /v2/ simultaneously
> 6. **OpenAPI Spec:** Swagger documentation for auto-generated client SDKs
> 7. **Metrics:** Track success rates, response times, error rates
> 8. **Multi-tenancy:** Support multiple vendors with data isolation
> 9. **Refund API:** Handle payment reversals
> 10. **Fraud Detection:** ML models or rules engine for suspicious patterns"

---

**Good luck with your interview! 🎉**

Remember: It's not just about the code, it's about demonstrating your **thought process**, **problem-solving approach**, and **understanding of real-world systems**.
