<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

/**
 * CheckoutController - Selcom Checkout API Clone
 * 
 * Implements three main endpoints following Selcom's specification:
 * 1. POST /checkout/create-order-minimal - Create new order
 * 2. GET /checkout/order-status - Query order status
 * 3. POST /checkout/webhook-callback - Receive payment notifications (simulated)
 * 
 * Interview Points:
 * - RESTful API design
 * - Input validation and sanitization
 * - Idempotency handling (duplicate order prevention)
 * - Proper error handling with meaningful messages
 * - Logging for audit trail
 * - Response format standardization
 */
class CheckoutController extends Controller
{
    /**
     * Create a new minimal checkout order
     * 
     * POST /api/v1/checkout/create-order-minimal
     * 
     * Minimal version requires less fields than full order creation,
     * suitable for mobile wallet payments (no card billing info needed).
     * 
     * Interview Discussion:
     * - Why minimal vs full? Different payment methods have different requirements
     * - Card payments need billing info, mobile wallets don't
     * - This flexibility improves UX
     */
    public function createOrder(Request $request)
    {
        // Input validation
        $validator = Validator::make($request->all(), [
            'vendor' => 'required|string|max:50',
            'order_id' => 'required|string|max:100',
            'buyer_email' => 'required|email|max:255',
            'buyer_name' => 'required|string|max:255',
            'buyer_phone' => 'required|string|max:20',
            'amount' => 'required|integer|min:100', // Minimum 1.00 TZS (100 cents)
            'currency' => 'nullable|string|size:3|in:TZS,USD',
            'buyer_remarks' => 'nullable|string|max:500',
            'merchant_remarks' => 'nullable|string|max:500',
            'no_of_items' => 'nullable|integer|min:1',
            'expiry' => 'nullable|integer|min:1|max:1440', // Max 24 hours
        ]);

        if ($validator->fails()) {
            return $this->errorResponse(
                'Validation failed',
                '400',
                $validator->errors()->first()
            );
        }

        $validated = $validator->validated();

        // Check for duplicate order (idempotency)
        // Interview Gold: This prevents charging customers twice if they retry
        $existingOrder = Order::where('vendor', $validated['vendor'])
            ->where('order_id', $validated['order_id'])
            ->first();

        if ($existingOrder) {
            Log::info('Duplicate order attempt', [
                'vendor' => $validated['vendor'],
                'order_id' => $validated['order_id'],
            ]);

            // Return existing order details (idempotent response)
            return response()->json($existingOrder->toSelcomResponse(
                'SUCCESS',
                'Order already exists'
            ));
        }

        try {
            // Generate payment gateway artifacts
            $paymentToken = $this->generatePaymentToken();
            $gatewayUrl = $this->generateGatewayUrl($validated['order_id']);
            $qrCode = $this->generateQRCode($paymentToken);
            
            // Calculate expiry time (default 60 minutes)
            $expiryMinutes = $validated['expiry'] ?? 60;
            $expiresAt = now()->addMinutes($expiryMinutes);

            // Create order
            $order = Order::create([
                'vendor' => $validated['vendor'],
                'order_id' => $validated['order_id'],
                'buyer_email' => $validated['buyer_email'],
                'buyer_name' => $validated['buyer_name'],
                'buyer_phone' => $validated['buyer_phone'],
                'buyer_userid' => $request->input('buyer_userid'),
                'amount' => $validated['amount'],
                'currency' => $validated['currency'] ?? 'TZS',
                'payment_status' => Order::STATUS_PENDING,
                'payment_token' => $paymentToken,
                'payment_gateway_url' => $gatewayUrl,
                'qr_code' => $qrCode,
                'gateway_buyer_uuid' => Str::uuid()->toString(), // For stored cards
                'buyer_remarks' => $validated['buyer_remarks'] ?? null,
                'merchant_remarks' => $validated['merchant_remarks'] ?? null,
                'no_of_items' => $validated['no_of_items'] ?? 1,
                'expires_at' => $expiresAt,
            ]);

            Log::info('Order created successfully', [
                'vendor' => $order->vendor,
                'order_id' => $order->order_id,
                'amount' => $order->amount,
                'currency' => $order->currency,
            ]);

            return response()->json($order->toSelcomResponse(
                'SUCCESS',
                'Order created successfully'
            ), 201);

        } catch (\Exception $e) {
            Log::error('Order creation failed', [
                'error' => $e->getMessage(),
                'vendor' => $validated['vendor'],
                'order_id' => $validated['order_id'],
            ]);

            return $this->errorResponse(
                'Order creation failed',
                '500',
                'Internal server error. Please try again later.'
            );
        }
    }

    /**
     * Get order status
     * 
     * GET /api/v1/checkout/order-status?order_id={order_id}
     * 
     * Returns current status of an order.
     * Used by merchants to check payment completion.
     */
    public function getOrderStatus(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'order_id' => 'required|string',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse(
                'Validation failed',
                '400',
                'order_id is required'
            );
        }

        $orderId = $request->input('order_id');

        // Find order (search across all vendors since order_id should be globally unique in practice)
        $order = Order::where('order_id', $orderId)->first();

        if (!$order) {
            return $this->errorResponse(
                'Order not found',
                '404',
                "Order with ID {$orderId} does not exist"
            );
        }

        Log::info('Order status queried', [
            'order_id' => $orderId,
            'status' => $order->payment_status,
        ]);

        // Build response matching Selcom format
        $responseData = [
            'order_id' => $order->order_id,
            'creation_date' => $order->created_at->format('Y-m-d H:i:s'),
            'amount' => $order->amount,
            'currency' => $order->currency,
            'payment_status' => $order->payment_status,
            'transid' => $order->transid,
            'channel' => $order->channel,
            'reference' => $order->reference,
            'phone' => $order->payment_status === Order::STATUS_COMPLETED ? $order->buyer_phone : null,
        ];

        return response()->json([
            'reference' => $order->reference ?? $order->order_id,
            'resultcode' => '000',
            'result' => 'SUCCESS',
            'message' => 'Order fetched successfully',
            'data' => [$responseData],
        ]);
    }

    /**
     * Simulate payment callback webhook
     * 
     * POST /api/v1/checkout/payment-callback
     * 
     * In real Selcom, this would be called by their payment gateway
     * when a customer completes payment via mobile money, card, etc.
     * 
     * For demo/testing purposes, we simulate this endpoint.
     * 
     * Interview Note: In production, this endpoint would:
     * 1. Be called by Selcom's servers (not customer's browser)
     * 2. Verify the request came from Selcom (signature check)
     * 3. Update order status atomically
     * 4. Trigger merchant's webhook if configured
     */
    public function paymentCallback(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'order_id' => 'required|string',
            'transid' => 'required|string',
            'channel' => 'required|string|in:MPESA-TZ,AIRTELMONEY,TIGOPESATZ,HALOPESATZ,CARD',
            'reference' => 'required|string',
            'result' => 'required|string|in:SUCCESS,FAIL',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse(
                'Validation failed',
                '400',
                $validator->errors()->first()
            );
        }

        $orderId = $request->input('order_id');
        $order = Order::where('order_id', $orderId)->first();

        if (!$order) {
            return $this->errorResponse(
                'Order not found',
                '404',
                "Order {$orderId} not found"
            );
        }

        // Check if order can be paid
        if (!$order->canBePaid()) {
            return $this->errorResponse(
                'Invalid order state',
                '400',
                "Order is {$order->payment_status} and cannot be paid"
            );
        }

        $result = $request->input('result');

        if ($result === 'SUCCESS') {
            // Mark as completed
            $order->markAsCompleted(
                $request->input('transid'),
                $request->input('channel'),
                $request->input('reference')
            );

            Log::info('Payment completed', [
                'order_id' => $orderId,
                'transid' => $request->input('transid'),
                'channel' => $request->input('channel'),
                'amount' => $order->amount,
            ]);

            return response()->json([
                'reference' => $order->reference,
                'resultcode' => '000',
                'result' => 'SUCCESS',
                'message' => 'Payment processed successfully',
                'data' => [],
            ]);
        } else {
            // Mark as failed/rejected
            $order->update(['payment_status' => Order::STATUS_REJECTED]);

            Log::warning('Payment failed', [
                'order_id' => $orderId,
                'reason' => $request->input('message', 'Payment failed'),
            ]);

            return response()->json([
                'reference' => $order->order_id,
                'resultcode' => '400',
                'result' => 'FAIL',
                'message' => 'Payment failed',
                'data' => [],
            ]);
        }
    }

    /**
     * Cancel an order (before payment completion)
     * 
     * DELETE /api/v1/checkout/cancel-order?order_id={order_id}
     */
    public function cancelOrder(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'order_id' => 'required|string',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validation failed', '400', 'order_id is required');
        }

        $orderId = $request->input('order_id');
        $order = Order::where('order_id', $orderId)->first();

        if (!$order) {
            return $this->errorResponse('Order not found', '404', "Order {$orderId} not found");
        }

        if (!$order->cancel()) {
            return $this->errorResponse(
                'Cannot cancel order',
                '400',
                "Order is {$order->payment_status} and cannot be cancelled"
            );
        }

        Log::info('Order cancelled', ['order_id' => $orderId]);

        return response()->json([
            'reference' => $order->order_id,
            'resultcode' => '000',
            'result' => 'SUCCESS',
            'message' => 'Order cancelled successfully',
            'data' => [],
        ]);
    }

    // Helper methods

    /**
     * List orders for a vendor
     * 
     * GET /api/v1/checkout/list-orders?vendor={vendor}&from_date={date}&to_date={date}
     * 
     * Used for reconciliation and reporting
     */
    public function listOrders(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'vendor' => 'required|string|max:50',
                'from_date' => 'nullable|date',
                'to_date' => 'nullable|date|after_or_equal:from_date',
            ]);

            if ($validator->fails()) {
                return $this->errorResponse(
                    'Validation failed',
                    '400',
                    $validator->errors()->first()
                );
            }

            $vendor = $request->input('vendor');
            $fromDate = $request->input('from_date');
            $toDate = $request->input('to_date');

            Log::info('Listing orders', [
                'vendor' => $vendor,
                'from_date' => $fromDate,
                'to_date' => $toDate,
            ]);

            // Build query
            $query = Order::where('vendor', $vendor);

            if ($fromDate) {
                $query->whereDate('created_at', '>=', $fromDate);
            }

            if ($toDate) {
                $query->whereDate('created_at', '<=', $toDate);
            }

            $orders = $query->orderBy('created_at', 'desc')->get();

            return response()->json([
                'reference' => Str::uuid()->toString(),
                'resultcode' => '200',
                'result' => 'SUCCESS',
                'message' => 'Orders retrieved successfully',
                'data' => [
                    'orders' => $orders->map(function ($order) {
                        return $order->toSelcomResponse();
                    }),
                    'total' => $orders->count(),
                ],
            ]);

        } catch (\Exception $e) {
            Log::error('List orders failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return $this->errorResponse(
                'Failed to retrieve orders',
                '500',
                config('app.debug') ? $e->getMessage() : 'Internal server error'
            );
        }
    }

    /**
     * Generate payment token (like Selcom's till number)
     * In real system, this would be a unique till number or payment code
     */
    private function generatePaymentToken(): string
    {
        return strval(random_int(10000000, 99999999));
    }

    /**
     * Generate payment gateway URL
     * In real Selcom, this redirects to their hosted payment page
     */
    private function generateGatewayUrl(string $orderId): string
    {
        return url("/payment-gateway/{$orderId}");
    }

    /**
     * Generate QR code data
     * In real system, this would be a valid QR code for TanQR payments
     */
    private function generateQRCode(string $paymentToken): string
    {
        return base64_encode("SELCOM-PAY-{$paymentToken}");
    }

    /**
     * Standard error response format
     */
    private function errorResponse(string $message, string $resultCode, string $details): \Illuminate\Http\JsonResponse
    {
        return response()->json([
            'reference' => null,
            'resultcode' => $resultCode,
            'result' => 'FAIL',
            'message' => $message,
            'details' => $details,
            'data' => [],
        ], $resultCode === '404' ? 404 : ($resultCode === '500' ? 500 : 400));
    }
}
