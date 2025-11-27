#!/bin/bash

# ========================================
# Selcom Checkout API - Quick Test Script
# ========================================

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Configuration
BASE_URL="http://127.0.0.1:8000/api/v1"
API_KEY="sk_live_selcom_test_key_12345678"
API_SECRET="whsec_hmac_secret_key_987654321"
VENDOR="SHOP203"
ORDER_ID="ORD-TEST-$(date +%s)"

echo -e "${BLUE}=====================================${NC}"
echo -e "${BLUE}  Selcom Checkout API Test Suite${NC}"
echo -e "${BLUE}=====================================${NC}"
echo ""

# Function to generate HMAC signature
generate_signature() {
    local payload="$1"
    echo -n "$payload" | openssl dgst -sha256 -hmac "$API_SECRET" | awk '{print $2}'
}

# Function to make API request
api_request() {
    local method="$1"
    local endpoint="$2"
    local payload="$3"
    
    if [ -n "$payload" ]; then
        # POST/PUT/PATCH requests with signature
        local signature=$(generate_signature "$payload")
        
        curl -s -X "$method" \
            -H "Authorization: Bearer $API_KEY" \
            -H "Content-Type: application/json" \
            -H "X-SIGNATURE: $signature" \
            -d "$payload" \
            "$BASE_URL$endpoint"
    else
        # GET/DELETE requests without signature (no body)
        curl -s -X "$method" \
            -H "Authorization: Bearer $API_KEY" \
            "$BASE_URL$endpoint"
    fi
}

# Test 1: Health Check
echo -e "${YELLOW}[1/6] Testing Health Check...${NC}"
RESPONSE=$(curl -s "$BASE_URL/health")
STATUS=$(echo "$RESPONSE" | grep -o '"status":"ok"' | wc -l)

if [ $STATUS -eq 1 ]; then
    echo -e "${GREEN}✓ Health check passed${NC}"
else
    echo -e "${RED}✗ Health check failed${NC}"
    echo "$RESPONSE"
    exit 1
fi
echo ""

# Test 2: Create Order
echo -e "${YELLOW}[2/6] Creating test order...${NC}"
PAYLOAD=$(cat <<EOF
{
    "vendor": "$VENDOR",
    "order_id": "$ORDER_ID",
    "buyer_email": "test@example.com",
    "buyer_name": "Test User",
    "buyer_phone": "+255712345678",
    "amount": 50000,
    "currency": "TZS",
    "webhook_url": "https://example.com/webhook"
}
EOF
)

RESPONSE=$(api_request "POST" "/checkout/create-order-minimal" "$PAYLOAD")
PAYMENT_TOKEN=$(echo "$RESPONSE" | grep -o '"payment_token":"[^"]*"' | cut -d'"' -f4)

if [ -n "$PAYMENT_TOKEN" ]; then
    echo -e "${GREEN}✓ Order created successfully${NC}"
    echo -e "  Order ID: ${BLUE}$ORDER_ID${NC}"
    echo -e "  Payment Token: ${BLUE}$PAYMENT_TOKEN${NC}"
else
    echo -e "${RED}✗ Order creation failed${NC}"
    echo "$RESPONSE"
    exit 1
fi
echo ""

# Test 3: Get Order Status (Should be PENDING)
echo -e "${YELLOW}[3/6] Checking order status (should be PENDING)...${NC}"
RESPONSE=$(api_request "GET" "/checkout/order-status?vendor=$VENDOR&order_id=$ORDER_ID")
STATUS=$(echo "$RESPONSE" | grep -o '"payment_status":"PENDING"' | wc -l)

if [ $STATUS -eq 1 ]; then
    echo -e "${GREEN}✓ Order status is PENDING${NC}"
else
    echo -e "${RED}✗ Expected PENDING status${NC}"
    echo "$RESPONSE"
fi
echo ""

# Test 4: Simulate Payment Callback
echo -e "${YELLOW}[4/6] Simulating payment callback...${NC}"
TRANSID="TRANS-$(date +%s)"
CALLBACK_PAYLOAD=$(cat <<EOF
{
    "vendor": "$VENDOR",
    "order_id": "$ORDER_ID",
    "transid": "$TRANSID",
    "channel": "MPESA-TZ",
    "reference": "REF-$TRANSID",
    "result": "SUCCESS"
}
EOF
)

RESPONSE=$(api_request "POST" "/checkout/payment-callback" "$CALLBACK_PAYLOAD")
RESULT=$(echo "$RESPONSE" | grep -o '"result":"SUCCESS"' | wc -l)

if [ $RESULT -eq 1 ]; then
    echo -e "${GREEN}✓ Payment callback processed${NC}"
    echo -e "  Transaction ID: ${BLUE}$TRANSID${NC}"
else
    echo -e "${RED}✗ Payment callback failed${NC}"
    echo "$RESPONSE"
fi
echo ""

# Test 5: Get Order Status Again (Should be COMPLETED)
echo -e "${YELLOW}[5/6] Checking order status (should be COMPLETED)...${NC}"
sleep 1
RESPONSE=$(api_request "GET" "/checkout/order-status?vendor=$VENDOR&order_id=$ORDER_ID")
STATUS=$(echo "$RESPONSE" | grep -o '"payment_status":"COMPLETED"' | wc -l)

if [ $STATUS -eq 1 ]; then
    echo -e "${GREEN}✓ Order status is COMPLETED${NC}"
else
    echo -e "${RED}✗ Expected COMPLETED status${NC}"
    echo "$RESPONSE"
fi
echo ""

# Test 6: List Orders
echo -e "${YELLOW}[6/6] Listing vendor orders...${NC}"
RESPONSE=$(api_request "GET" "/checkout/list-orders?vendor=$VENDOR")
ORDER_COUNT=$(echo "$RESPONSE" | grep -o '"order_id"' | wc -l)

if [ $ORDER_COUNT -gt 0 ]; then
    echo -e "${GREEN}✓ Found $ORDER_COUNT orders for vendor $VENDOR${NC}"
else
    echo -e "${RED}✗ No orders found${NC}"
    echo "$RESPONSE"
fi
echo ""

# Security Tests
echo -e "${BLUE}------------------------------------${NC}"
echo -e "${BLUE}  Security Tests${NC}"
echo -e "${BLUE}------------------------------------${NC}"
echo ""

# Test 7: Invalid API Key
echo -e "${YELLOW}[7/8] Testing invalid API key...${NC}"
RESPONSE=$(curl -s -X GET \
    -H "Authorization: Bearer INVALID_KEY" \
    "$BASE_URL/checkout/order-status?vendor=$VENDOR&order_id=$ORDER_ID")
ERROR=$(echo "$RESPONSE" | grep -o '"message":"Invalid API key"' | wc -l)

if [ $ERROR -eq 1 ]; then
    echo -e "${GREEN}✓ Invalid API key rejected${NC}"
else
    echo -e "${RED}✗ Invalid API key should be rejected${NC}"
    echo "$RESPONSE"
fi
echo ""

# Test 8: Invalid Signature
echo -e "${YELLOW}[8/8] Testing invalid signature...${NC}"
RESPONSE=$(curl -s -X POST \
    -H "Authorization: Bearer $API_KEY" \
    -H "Content-Type: application/json" \
    -H "X-SIGNATURE: invalid_signature_12345" \
    -d '{"vendor":"TEST","order_id":"TEST123"}' \
    "$BASE_URL/checkout/create-order-minimal")
ERROR=$(echo "$RESPONSE" | grep -o '"message":"Invalid signature"' | wc -l)

if [ $ERROR -eq 1 ]; then
    echo -e "${GREEN}✓ Invalid signature rejected${NC}"
else
    echo -e "${RED}✗ Invalid signature should be rejected${NC}"
    echo "$RESPONSE"
fi
echo ""

# Summary
echo -e "${BLUE}=====================================${NC}"
echo -e "${GREEN}  All Tests Completed!${NC}"
echo -e "${BLUE}=====================================${NC}"
echo ""
echo -e "Test order created: ${BLUE}$ORDER_ID${NC}"
echo -e "View logs: ${YELLOW}tail -f storage/logs/laravel.log${NC}"
echo -e "API docs: ${YELLOW}docs/API.md${NC}"
echo ""
