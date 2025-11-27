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
         */
        Route::post('/payment-callback', [CheckoutController::class, 'paymentCallback']);
        
        /**
         * Create full order with all optional fields
         * POST /api/v1/checkout/create-order
         */
        Route::post('/create-order', [CheckoutController::class, 'createOrderFull']);
        
        /**
         * Get stored cards for a buyer
         * GET /api/v1/checkout/stored-cards?vendor={vendor}&buyer_userid={user_id}
         */
        Route::get('/stored-cards', [CheckoutController::class, 'getStoredCards']);
        
        /**
         * Delete stored card
         * DELETE /api/v1/checkout/delete-card?vendor={vendor}&card_id={card_id}
         */
        Route::delete('/delete-card', [CheckoutController::class, 'deleteCard']);
        
        /**
         * Process card payment
         * POST /api/v1/checkout/card-payment
         */
        Route::post('/card-payment', [CheckoutController::class, 'cardPayment']);
        
        /**
         * Process mobile wallet payment
         * POST /api/v1/checkout/wallet-payment
         */
        Route::post('/wallet-payment', [CheckoutController::class, 'walletPayment']);
        
        /**
         * Process SelcomPesa payment
         * POST /api/v1/checkout/selcompesa-payment
         */
        Route::post('/selcompesa-payment', [CheckoutController::class, 'selcomPesaPayment']);
        
        /**
         * Create till alias
         * POST /api/v1/checkout/create-till-alias
         */
        Route::post('/create-till-alias', [CheckoutController::class, 'createTillAlias']);
    });
});
