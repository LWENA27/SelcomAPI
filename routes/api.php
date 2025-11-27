<?php

use App\Http\Controllers\Api\CheckoutController;
use Illuminate\Support\Facades\Route;

/**
 * Selcom Checkout API Routes
 * 
 * All routes are prefixed with /api/v1 and protected with signature verification.
 * 
 * Authentication: Two-layer security
 * 1. API Key via Authorization: Bearer {API_KEY}
 * 2. HMAC-SHA256 signature via X-SIGNATURE header
 * 
 * Interview Points:
 * - API versioning (/v1) for backward compatibility
 * - RESTful naming conventions
 * - Proper HTTP verbs (POST for creation, GET for retrieval, DELETE for cancellation)
 * - Middleware protection on sensitive endpoints
 */

Route::prefix('v1')->group(function () {
    
    // Public health check (no auth required)
    Route::get('/health', function () {
        return response()->json([
            'status' => 'ok',
            'service' => 'Selcom Checkout API',
            'version' => '1.0.0',
            'timestamp' => now()->toIso8601String(),
        ]);
    });

    // Protected checkout endpoints (require signature verification)
    Route::middleware(['verify.signature'])->prefix('checkout')->group(function () {
        
        /**
         * Create new checkout order
         * POST /api/v1/checkout/create-order-minimal
         * 
         * Creates a minimal order for mobile wallet payments.
         * Returns payment token, QR code, and gateway URL.
         */
        Route::post('/create-order-minimal', [CheckoutController::class, 'createOrder']);
        
        /**
         * Get order status
         * GET /api/v1/checkout/order-status?order_id={order_id}
         * 
         * Query the current status of an order.
         * Used by merchants to check if payment completed.
         */
        Route::get('/order-status', [CheckoutController::class, 'getOrderStatus']);
        
        /**
         * Cancel order
         * DELETE /api/v1/checkout/cancel-order?order_id={order_id}
         * 
         * Cancel a pending order before payment.
         * Cannot cancel completed orders.
         */
        Route::delete('/cancel-order', [CheckoutController::class, 'cancelOrder']);
        
        /**
         * List orders for a vendor
         * GET /api/v1/checkout/list-orders?vendor={vendor}&from_date={date}&to_date={date}
         * 
         * Retrieve all orders for reconciliation and reporting.
         */
        Route::get('/list-orders', [CheckoutController::class, 'listOrders']);
        
        /**
         * Payment callback webhook (simulation)
         * POST /api/v1/checkout/payment-callback
         * 
         * Simulates payment gateway callback.
         * In production, this would be called by Selcom's servers.
         * 
         * Interview Note: Real implementation would:
         * - Verify callback came from Selcom (IP whitelist + signature)
         * - Use database transactions for atomicity
         * - Trigger merchant's webhook asynchronously (queue)
         */
        Route::post('/payment-callback', [CheckoutController::class, 'paymentCallback']);
    });
});
