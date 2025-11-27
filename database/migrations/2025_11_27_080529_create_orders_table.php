<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Based on Selcom Checkout API documentation:
     * https://developers.selcommobile.com/#checkout-api
     * 
     * This table stores checkout orders following Selcom's payment flow.
     */
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            
            // Merchant/vendor identifier (e.g., "SHOP203", "VENDORTILL")
            // In real Selcom, this identifies which merchant/shop the order belongs to
            $table->string('vendor', 50)->index();
            
            // Unique order identifier from merchant's system
            // Must be unique per vendor to prevent duplicate payments
            $table->string('order_id', 100)->index();
            $table->unique(['vendor', 'order_id']); // Composite unique constraint
            
            // Buyer information (required by Selcom)
            $table->string('buyer_email');
            $table->string('buyer_name');
            $table->string('buyer_phone', 20);
            $table->string('buyer_userid')->nullable(); // Empty for guest checkout
            
            // Payment amount in minor units (cents/paisa)
            // Example: 5000 = 50.00 TZS
            // Why integer? Prevents floating-point precision errors in financial calculations
            $table->unsignedInteger('amount');
            
            // Currency (TZS, USD, etc.) - ISO 4217 standard
            $table->string('currency', 3)->default('TZS');
            
            // Payment status tracking
            // Matches Selcom's payment_status values
            $table->enum('payment_status', [
                'PENDING',      // Order created, payment not started
                'COMPLETED',    // Payment successful
                'CANCELLED',    // Cancelled by merchant
                'USERCANCELLED',// Cancelled by customer
                'REJECTED',     // Payment rejected
                'INPROGRESS'    // Payment being processed
            ])->default('PENDING')->index();
            
            // Gateway-generated fields (returned after order creation)
            $table->string('gateway_buyer_uuid')->nullable(); // For stored card feature
            $table->string('payment_token')->nullable();       // Till number/payment code
            $table->text('payment_gateway_url')->nullable();   // URL to redirect customer
            $table->text('qr_code')->nullable();              // QR code for payment
            
            // Transaction details (populated after payment)
            $table->string('transid')->nullable();     // Unique transaction ID from payment channel
            $table->string('channel')->nullable();      // E.g., "AIRTELMONEY", "MPESA-TZ"
            $table->string('reference')->nullable();    // Selcom gateway reference
            
            // Webhook and redirect URLs (base64 encoded as per Selcom spec)
            $table->text('webhook_url')->nullable();
            $table->text('redirect_url')->nullable();
            $table->text('cancel_url')->nullable();
            
            // Additional information
            $table->text('buyer_remarks')->nullable();
            $table->text('merchant_remarks')->nullable();
            $table->unsignedInteger('no_of_items')->default(1);
            
            // Expiry time for the order (default 60 minutes per Selcom)
            $table->timestamp('expires_at')->nullable();
            
            // Standard timestamps
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
