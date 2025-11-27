<?php



namespace App\Http\Controllers\Api;



use App\Http\Controllers\Controller;namespace App\Http\Controllers\Api;namespace App\Http\Controllers\Api;

use App\Models\Order;

use App\Models\StoredCard;

use App\Models\TillAlias;

use Illuminate\Http\Request;use App\Http\Controllers\Controller;use App\Http\Controllers\Controller;

use Illuminate\Support\Facades\Log;

use Illuminate\Support\Facades\Validator;use App\Models\Order;use App\Models\Order;

use Illuminate\Support\Str;

use Illuminate\Http\Request;use Illuminate\Http\Request;

class CheckoutController extends Controller

{use Illuminate\Support\Facades\Log;use Illuminate\Support\Facades\Log;

    public function createOrder(Request $request)

    {use Illuminate\Support\Facades\Validator;use Illuminate\Support\Facades\Validator;

        $validator = Validator::make($request->all(), [

            'vendor' => 'required|string|max:50',use Illuminate\Support\Str;use Illuminate\Support\Str;

            'order_id' => 'required|string|max:100',

            'buyer_email' => 'required|email|max:255',

            'buyer_name' => 'required|string|max:255',

            'buyer_phone' => 'required|string|max:20',class CheckoutController extends Controllerclass CheckoutController extends Controller

            'amount' => 'required|integer|min:100',

            'currency' => 'nullable|string|size:3|in:TZS,USD',{{

            'buyer_remarks' => 'nullable|string|max:500',

            'merchant_remarks' => 'nullable|string|max:500',    public function createOrder(Request $request)    public function createOrder(Request $request)

            'no_of_items' => 'nullable|integer|min:1',

            'expiry' => 'nullable|integer|min:1|max:1440',    {    {

        ]);

        $validator = Validator::make($request->all(), [        $validator = Validator::make($request->all(), [

        if ($validator->fails()) {

            return $this->errorResponse(            'vendor' => 'required|string|max:50',            'vendor' => 'required|string|max:50',

                'Validation failed',

                '400',            'order_id' => 'required|string|max:100',            'order_id' => 'required|string|max:100',

                $validator->errors()->first()

            );            'buyer_email' => 'required|email|max:255',            'buyer_email' => 'required|email|max:255',

        }

            'buyer_name' => 'required|string|max:255',            'buyer_name' => 'required|string|max:255',

        $validated = $validator->validated();

            'buyer_phone' => 'required|string|max:20',            'buyer_phone' => 'required|string|max:20',

        $existingOrder = Order::where('vendor', $validated['vendor'])

            ->where('order_id', $validated['order_id'])            'amount' => 'required|integer|min:100',            'amount' => 'required|integer|min:100',

            ->first();

            'currency' => 'nullable|string|size:3|in:TZS,USD',            'currency' => 'nullable|string|size:3|in:TZS,USD',

        if ($existingOrder) {

            Log::info('Duplicate order attempt', [            'buyer_remarks' => 'nullable|string|max:500',            'buyer_remarks' => 'nullable|string|max:500',

                'vendor' => $validated['vendor'],

                'order_id' => $validated['order_id'],            'merchant_remarks' => 'nullable|string|max:500',            'merchant_remarks' => 'nullable|string|max:500',

            ]);

            'no_of_items' => 'nullable|integer|min:1',            'no_of_items' => 'nullable|integer|min:1',

            return response()->json($existingOrder->toSelcomResponse(

                'SUCCESS',            'expiry' => 'nullable|integer|min:1|max:1440',            'expiry' => 'nullable|integer|min:1|max:1440',

                'Order already exists'

            ));        ]);        ]);

        }



        try {

            $paymentToken = $this->generatePaymentToken();        if ($validator->fails()) {        if ($validator->fails()) {

            $gatewayUrl = $this->generateGatewayUrl($validated['order_id']);

            $qrCode = $this->generateQRCode($paymentToken);            return $this->errorResponse(            return $this->errorResponse(



            $expiryMinutes = $validated['expiry'] ?? 60;                'Validation failed',                'Validation failed',

            $expiresAt = now()->addMinutes($expiryMinutes);

                '400',                '400',

            $order = Order::create([

                'vendor' => $validated['vendor'],                $validator->errors()->first()                $validator->errors()->first()

                'order_id' => $validated['order_id'],

                'buyer_email' => $validated['buyer_email'],            );            );

                'buyer_name' => $validated['buyer_name'],

                'buyer_phone' => $validated['buyer_phone'],        }        }

                'buyer_userid' => $request->input('buyer_userid'),

                'amount' => $validated['amount'],

                'currency' => $validated['currency'] ?? 'TZS',

                'payment_status' => Order::STATUS_PENDING,        $validated = $validator->validated();        $validated = $validator->validated();

                'payment_token' => $paymentToken,

                'payment_gateway_url' => $gatewayUrl,

                'qr_code' => $qrCode,

                'gateway_buyer_uuid' => Str::uuid()->toString(),        $existingOrder = Order::where('vendor', $validated['vendor'])        // Check for duplicate order

                'buyer_remarks' => $validated['buyer_remarks'] ?? null,

                'merchant_remarks' => $validated['merchant_remarks'] ?? null,            ->where('order_id', $validated['order_id'])        $existingOrder = Order::where('vendor', $validated['vendor'])

                'no_of_items' => $validated['no_of_items'] ?? 1,

                'expires_at' => $expiresAt,            ->first();            ->where('order_id', $validated['order_id'])

            ]);

            ->first();

            Log::info('Order created successfully', [

                'vendor' => $order->vendor,        if ($existingOrder) {

                'order_id' => $order->order_id,

                'amount' => $order->amount,            Log::info('Duplicate order attempt', [        if ($existingOrder) {

                'currency' => $order->currency,

            ]);                'vendor' => $validated['vendor'],            Log::info('Duplicate order attempt', [



            return response()->json($order->toSelcomResponse(                'order_id' => $validated['order_id'],                'vendor' => $validated['vendor'],

                'SUCCESS',

                'Order created successfully'            ]);                'order_id' => $validated['order_id'],

            ), 201);

            ]);

        } catch (\Exception $e) {

            Log::error('Order creation failed', [            return $this->errorResponse(

                'error' => $e->getMessage(),

                'vendor' => $validated['vendor'],                'Order already exists',            // Return existing order details (idempotent response)

                'order_id' => $validated['order_id'],

            ]);                '409',            return response()->json($existingOrder->toSelcomResponse(



            return $this->errorResponse(                'An order with this ID already exists'                'SUCCESS',

                'Order creation failed',

                '500',            );                'Order already exists'

                'Internal server error. Please try again later.'

            );        }            ));

        }

    }        }



    public function createOrderFull(Request $request)        try {

    {

        $validator = Validator::make($request->all(), [            $paymentToken = $this->generatePaymentToken();        try {

            'vendor' => 'required|string|max:50',

            'order_id' => 'required|string|max:100',            $gatewayUrl = $this->generateGatewayUrl($validated['order_id']);            // Generate payment gateway artifacts

            'buyer_email' => 'required|email|max:255',

            'buyer_name' => 'required|string|max:255',            $qrCode = $this->generateQRCode($paymentToken);            $paymentToken = $this->generatePaymentToken();

            'buyer_phone' => 'required|string|max:20',

            'buyer_userid' => 'nullable|string|max:100',                        $gatewayUrl = $this->generateGatewayUrl($validated['order_id']);

            'amount' => 'required|integer|min:100',

            'currency' => 'nullable|string|size:3|in:TZS,USD',            $expiresAt = now()->addMinutes($validated['expiry'] ?? 60);            $qrCode = $this->generateQRCode($paymentToken);

            'buyer_remarks' => 'nullable|string|max:500',

            'merchant_remarks' => 'nullable|string|max:500',            

            'no_of_items' => 'nullable|integer|min:1',

            'expiry' => 'nullable|integer|min:1|max:1440',            $order = Order::create([            // Calculate expiry time (default 60 minutes)

            'webhook_url' => 'nullable|url|max:500',

            'redirect_url' => 'nullable|url|max:500',                'vendor' => $validated['vendor'],            $expiryMinutes = $validated['expiry'] ?? 60;

            'cancel_url' => 'nullable|url|max:500',

        ]);                'order_id' => $validated['order_id'],            $expiresAt = now()->addMinutes($expiryMinutes);



        if ($validator->fails()) {                'buyer_email' => $validated['buyer_email'],

            return $this->errorResponse('Validation failed', '400', $validator->errors()->first());

        }                'buyer_name' => $validated['buyer_name'],            // Create order



        $validated = $validator->validated();                'buyer_phone' => $validated['buyer_phone'],            $order = Order::create([



        $existingOrder = Order::where('vendor', $validated['vendor'])                'amount' => $validated['amount'],                'vendor' => $validated['vendor'],

            ->where('order_id', $validated['order_id'])

            ->first();                'currency' => $validated['currency'] ?? 'TZS',                'order_id' => $validated['order_id'],



        if ($existingOrder) {                'payment_status' => Order::STATUS_PENDING,                'buyer_email' => $validated['buyer_email'],

            return response()->json($existingOrder->toSelcomResponse('SUCCESS', 'Order already exists'));

        }                'payment_token' => $paymentToken,                'buyer_name' => $validated['buyer_name'],



        try {                'payment_gateway_url' => $gatewayUrl,                'buyer_phone' => $validated['buyer_phone'],

            $order = Order::create([

                'vendor' => $validated['vendor'],                'qr_code' => $qrCode,                'buyer_userid' => $request->input('buyer_userid'),

                'order_id' => $validated['order_id'],

                'buyer_email' => $validated['buyer_email'],                'gateway_buyer_uuid' => Str::uuid()->toString(),                'amount' => $validated['amount'],

                'buyer_name' => $validated['buyer_name'],

                'buyer_phone' => $validated['buyer_phone'],                'buyer_remarks' => $validated['buyer_remarks'] ?? null,                'currency' => $validated['currency'] ?? 'TZS',

                'buyer_userid' => $validated['buyer_userid'] ?? null,

                'amount' => $validated['amount'],                'merchant_remarks' => $validated['merchant_remarks'] ?? null,                'payment_status' => Order::STATUS_PENDING,

                'currency' => $validated['currency'] ?? 'TZS',

                'payment_status' => Order::STATUS_PENDING,                'no_of_items' => $validated['no_of_items'] ?? 1,                'payment_token' => $paymentToken,

                'payment_token' => $this->generatePaymentToken(),

                'payment_gateway_url' => $this->generateGatewayUrl($validated['order_id']),                'expires_at' => $expiresAt,                'payment_gateway_url' => $gatewayUrl,

                'qr_code' => $this->generateQRCode($this->generatePaymentToken()),

                'gateway_buyer_uuid' => Str::uuid()->toString(),            ]);                'qr_code' => $qrCode,

                'buyer_remarks' => $validated['buyer_remarks'] ?? null,

                'merchant_remarks' => $validated['merchant_remarks'] ?? null,                'gateway_buyer_uuid' => Str::uuid()->toString(), // For stored cards

                'no_of_items' => $validated['no_of_items'] ?? 1,

                'webhook_url' => isset($validated['webhook_url']) ? base64_encode($validated['webhook_url']) : null,            Log::info('Order created', [                'buyer_remarks' => $validated['buyer_remarks'] ?? null,

                'redirect_url' => isset($validated['redirect_url']) ? base64_encode($validated['redirect_url']) : null,

                'cancel_url' => isset($validated['cancel_url']) ? base64_encode($validated['cancel_url']) : null,                'vendor' => $order->vendor,                'merchant_remarks' => $validated['merchant_remarks'] ?? null,

                'expires_at' => now()->addMinutes($validated['expiry'] ?? 60),

            ]);                'order_id' => $order->order_id,                'no_of_items' => $validated['no_of_items'] ?? 1,



            Log::info('Full order created', ['vendor' => $order->vendor, 'order_id' => $order->order_id]);                'amount' => $order->amount,                'expires_at' => $expiresAt,



            return response()->json($order->toSelcomResponse('SUCCESS', 'Order created successfully'), 201);            ]);            ]);



        } catch (\Exception $e) {

            Log::error('Full order creation failed', ['error' => $e->getMessage()]);

            return $this->errorResponse('Order creation failed', '500', 'Internal server error');            return response()->json($order->toSelcomResponse(            Log::info('Order created successfully', [

        }

    }                'SUCCESS',                'vendor' => $order->vendor,



    public function getOrderStatus(Request $request)                'Order created successfully'                'order_id' => $order->order_id,

    {

        $validator = Validator::make($request->all(), [            ), 201);                'amount' => $order->amount,

            'order_id' => 'required|string',

        ]);                'currency' => $order->currency,



        if ($validator->fails()) {        } catch (\Exception $e) {            ]);

            return $this->errorResponse('Validation failed', '400', 'order_id is required');

        }            Log::error('Order creation failed', [



        $orderId = $request->input('order_id');                'error' => $e->getMessage(),            return response()->json($order->toSelcomResponse(

        $order = Order::where('order_id', $orderId)->first();

                'vendor' => $validated['vendor'] ?? null,                'SUCCESS',

        if (!$order) {

            return $this->errorResponse('Order not found', '404', "Order with ID {$orderId} does not exist");                'order_id' => $validated['order_id'] ?? null,                'Order created successfully'

        }

            ]);            ), 201);

        Log::info('Order status queried', ['order_id' => $orderId, 'status' => $order->payment_status]);



        $responseData = [

            'order_id' => $order->order_id,            return $this->errorResponse(        } catch (\Exception $e) {

            'creation_date' => $order->created_at->format('Y-m-d H:i:s'),

            'amount' => $order->amount,                'Order creation failed',            Log::error('Order creation failed', [

            'currency' => $order->currency,

            'payment_status' => $order->payment_status,                '500',                'error' => $e->getMessage(),

            'transid' => $order->transid,

            'channel' => $order->channel,                'Internal server error'                'vendor' => $validated['vendor'],

            'reference' => $order->reference,

            'phone' => $order->payment_status === Order::STATUS_COMPLETED ? $order->buyer_phone : null,            );                'order_id' => $validated['order_id'],

        ];

        }            ]);

        return response()->json([

            'reference' => $order->reference ?? $order->order_id,    }

            'resultcode' => '000',

            'result' => 'SUCCESS',            return $this->errorResponse(

            'message' => 'Order fetched successfully',

            'data' => [$responseData],    public function getOrderStatus(Request $request)                'Order creation failed',

        ]);

    }    {                '500',



    public function paymentCallback(Request $request)        $validator = Validator::make($request->all(), [                'Internal server error. Please try again later.'

    {

        $validator = Validator::make($request->all(), [            'order_id' => 'required|string',            );

            'order_id' => 'required|string',

            'transid' => 'required|string',        ]);        }

            'channel' => 'required|string|in:MPESA-TZ,AIRTELMONEY,TIGOPESATZ,HALOPESATZ,CARD',

            'reference' => 'required|string',    }

            'result' => 'required|string|in:SUCCESS,FAIL',

        ]);        if ($validator->fails()) {



        if ($validator->fails()) {            return $this->errorResponse(    /**

            return $this->errorResponse('Validation failed', '400', $validator->errors()->first());

        }                'Validation failed',     * Get order status



        $orderId = $request->input('order_id');                '400',     * 

        $order = Order::where('order_id', $orderId)->first();

                'order_id is required'     * GET /api/v1/checkout/order-status?order_id={order_id}

        if (!$order) {

            return $this->errorResponse('Order not found', '404', "Order {$orderId} not found");            );     * 

        }

        }     * Returns current status of an order.

        if (!$order->canBePaid()) {

            return $this->errorResponse(     * Used by merchants to check payment completion.

                'Invalid order state',

                '400',        $orderId = $request->input('order_id');     */

                "Order is {$order->payment_status} and cannot be paid"

            );        $order = Order::where('order_id', $orderId)->first();    public function getOrderStatus(Request $request)

        }

    {

        $result = $request->input('result');

        if (!$order) {        $validator = Validator::make($request->all(), [

        if ($result === 'SUCCESS') {

            $order->markAsCompleted(            return $this->errorResponse(            'order_id' => 'required|string',

                $request->input('transid'),

                $request->input('channel'),                'Order not found',        ]);

                $request->input('reference')

            );                '404',



            Log::info('Payment completed', [                "Order with ID {$orderId} does not exist"        if ($validator->fails()) {

                'order_id' => $orderId,

                'transid' => $request->input('transid'),            );            return $this->errorResponse(

                'channel' => $request->input('channel'),

                'amount' => $order->amount,        }                'Validation failed',

            ]);

                '400',

            return response()->json([

                'reference' => $order->reference,        Log::info('Order status queried', [                'order_id is required'

                'resultcode' => '000',

                'result' => 'SUCCESS',            'order_id' => $orderId,            );

                'message' => 'Payment processed successfully',

                'data' => [],            'status' => $order->payment_status,        }

            ]);

        } else {        ]);

            $order->update(['payment_status' => Order::STATUS_REJECTED]);

        $orderId = $request->input('order_id');

            Log::warning('Payment failed', [

                'order_id' => $orderId,        $responseData = [

                'reason' => $request->input('message', 'Payment failed'),

            ]);            'order_id' => $order->order_id,        // Find order (search across all vendors since order_id should be globally unique in practice)



            return response()->json([            'creation_date' => $order->created_at->format('Y-m-d H:i:s'),        $order = Order::where('order_id', $orderId)->first();

                'reference' => $order->order_id,

                'resultcode' => '400',            'amount' => $order->amount,

                'result' => 'FAIL',

                'message' => 'Payment failed',            'currency' => $order->currency,        if (!$order) {

                'data' => [],

            ]);            'payment_status' => $order->payment_status,            return $this->errorResponse(

        }

    }            'transid' => $order->transid,                'Order not found',



    public function cancelOrder(Request $request)            'channel' => $order->channel,                '404',

    {

        $validator = Validator::make($request->all(), [            'reference' => $order->reference,                "Order with ID {$orderId} does not exist"

            'order_id' => 'required|string',

        ]);            'phone' => $order->payment_status === Order::STATUS_COMPLETED ? $order->buyer_phone : null,            );



        if ($validator->fails()) {        ];        }

            return $this->errorResponse('Validation failed', '400', 'order_id is required');

        }



        $orderId = $request->input('order_id');        return response()->json([        Log::info('Order status queried', [

        $order = Order::where('order_id', $orderId)->first();

            'reference' => $order->reference ?? $order->order_id,            'order_id' => $orderId,

        if (!$order) {

            return $this->errorResponse('Order not found', '404', "Order {$orderId} not found");            'resultcode' => '000',            'status' => $order->payment_status,

        }

            'result' => 'SUCCESS',        ]);

        if (!$order->cancel()) {

            return $this->errorResponse(            'message' => 'Order fetched successfully',

                'Cannot cancel order',

                '400',            'data' => [$responseData],        // Build response matching Selcom format

                "Order is {$order->payment_status} and cannot be cancelled"

            );        ]);        $responseData = [

        }

    }            'order_id' => $order->order_id,

        Log::info('Order cancelled', ['order_id' => $orderId]);

            'creation_date' => $order->created_at->format('Y-m-d H:i:s'),

        return response()->json([

            'reference' => $order->order_id,    public function paymentCallback(Request $request)            'amount' => $order->amount,

            'resultcode' => '000',

            'result' => 'SUCCESS',    {            'currency' => $order->currency,

            'message' => 'Order cancelled successfully',

            'data' => [],        $validator = Validator::make($request->all(), [            'payment_status' => $order->payment_status,

        ]);

    }            'order_id' => 'required|string',            'transid' => $order->transid,



    public function listOrders(Request $request)            'transid' => 'required|string',            'channel' => $order->channel,

    {

        try {            'channel' => 'required|string|in:MPESA-TZ,AIRTELMONEY,TIGOPESATZ,HALOPESATZ,CARD',            'reference' => $order->reference,

            $validator = Validator::make($request->all(), [

                'vendor' => 'required|string|max:50',            'reference' => 'required|string',            'phone' => $order->payment_status === Order::STATUS_COMPLETED ? $order->buyer_phone : null,

                'from_date' => 'nullable|date',

                'to_date' => 'nullable|date|after_or_equal:from_date',            'result' => 'required|string|in:SUCCESS,FAIL',        ];

            ]);

        ]);

            if ($validator->fails()) {

                return $this->errorResponse('Validation failed', '400', $validator->errors()->first());        return response()->json([

            }

        if ($validator->fails()) {            'reference' => $order->reference ?? $order->order_id,

            $vendor = $request->input('vendor');

            $fromDate = $request->input('from_date');            return $this->errorResponse(            'resultcode' => '000',

            $toDate = $request->input('to_date');

                'Validation failed',            'result' => 'SUCCESS',

            Log::info('Listing orders', ['vendor' => $vendor, 'from_date' => $fromDate, 'to_date' => $toDate]);

                '400',            'message' => 'Order fetched successfully',

            $query = Order::where('vendor', $vendor);

                $validator->errors()->first()            'data' => [$responseData],

            if ($fromDate) {

                $query->whereDate('created_at', '>=', $fromDate);            );        ]);

            }

        }    }

            if ($toDate) {

                $query->whereDate('created_at', '<=', $toDate);

            }

        $orderId = $request->input('order_id');    /**

            $orders = $query->orderBy('created_at', 'desc')->get();

        $order = Order::where('order_id', $orderId)->first();     * Simulate payment callback webhook

            return response()->json([

                'reference' => Str::uuid()->toString(),     * 

                'resultcode' => '200',

                'result' => 'SUCCESS',        if (!$order) {     * POST /api/v1/checkout/payment-callback

                'message' => 'Orders retrieved successfully',

                'data' => [            return $this->errorResponse(     * 

                    'orders' => $orders->map(function ($order) {

                        return $order->toSelcomResponse();                'Order not found',     * In real Selcom, this would be called by their payment gateway

                    }),

                    'total' => $orders->count(),                '404',     * when a customer completes payment via mobile money, card, etc.

                ],

            ]);                "Order {$orderId} not found"     * 



        } catch (\Exception $e) {            );     * For demo/testing purposes, we simulate this endpoint.

            Log::error('List orders failed', ['error' => $e->getMessage()]);

            return $this->errorResponse('Failed to retrieve orders', '500', 'Internal server error');        }     * 

        }

    }     * Interview Note: In production, this endpoint would:



    public function getStoredCards(Request $request)        if (!$order->canBePaid()) {     * 1. Be called by Selcom's servers (not customer's browser)

    {

        $validator = Validator::make($request->all(), [            return $this->errorResponse(     * 2. Verify the request came from Selcom (signature check)

            'vendor' => 'required|string|max:50',

            'buyer_userid' => 'required|string|max:100',                'Invalid order state',     * 3. Update order status atomically

        ]);

                '400',     * 4. Trigger merchant's webhook if configured

        if ($validator->fails()) {

            return $this->errorResponse('Validation failed', '400', $validator->errors()->first());                "Order is {$order->payment_status} and cannot be paid"     */

        }

            );    public function paymentCallback(Request $request)

        $cards = StoredCard::where('vendor', $request->input('vendor'))

            ->where('buyer_userid', $request->input('buyer_userid'))        }    {

            ->get();

        $validator = Validator::make($request->all(), [

        return response()->json([

            'reference' => Str::uuid()->toString(),        $result = $request->input('result');            'order_id' => 'required|string',

            'resultcode' => '000',

            'result' => 'SUCCESS',            'transid' => 'required|string',

            'message' => 'Stored cards retrieved successfully',

            'data' => [        if ($result === 'SUCCESS') {            'channel' => 'required|string|in:MPESA-TZ,AIRTELMONEY,TIGOPESATZ,HALOPESATZ,CARD',

                'cards' => $cards->map(fn($card) => $card->toSelcomResponse()),

                'total' => $cards->count(),            $order->markAsCompleted(            'reference' => 'required|string',

            ],

        ]);                $request->input('transid'),            'result' => 'required|string|in:SUCCESS,FAIL',

    }

                $request->input('channel'),        ]);

    public function deleteCard(Request $request)

    {                $request->input('reference')

        $validator = Validator::make($request->all(), [

            'vendor' => 'required|string|max:50',            );        if ($validator->fails()) {

            'card_id' => 'required|integer|exists:stored_cards,id',

        ]);            return $this->errorResponse(



        if ($validator->fails()) {            Log::info('Payment completed', [                'Validation failed',

            return $this->errorResponse('Validation failed', '400', $validator->errors()->first());

        }                'order_id' => $orderId,                '400',



        $card = StoredCard::where('vendor', $request->input('vendor'))                'transid' => $request->input('transid'),                $validator->errors()->first()

            ->where('id', $request->input('card_id'))

            ->first();            ]);            );



        if (!$card) {        }

            return $this->errorResponse('Card not found', '404', 'Card does not exist');

        }            return response()->json([



        $card->delete();                'reference' => $request->input('reference'),        $orderId = $request->input('order_id');



        Log::info('Card deleted', ['card_id' => $card->id, 'vendor' => $request->input('vendor')]);                'resultcode' => '000',        $order = Order::where('order_id', $orderId)->first();



        return response()->json([                'result' => 'SUCCESS',

            'reference' => Str::uuid()->toString(),

            'resultcode' => '000',                'message' => 'Payment processed successfully',        if (!$order) {

            'result' => 'SUCCESS',

            'message' => 'Card deleted successfully',                'data' => [],            return $this->errorResponse(

            'data' => [],

        ]);            ]);                'Order not found',

    }

        } else {                '404',

    public function cardPayment(Request $request)

    {            $order->update(['payment_status' => Order::STATUS_FAILED]);                "Order {$orderId} not found"

        $validator = Validator::make($request->all(), [

            'vendor' => 'required|string|max:50',            );

            'order_id' => 'required|string|max:100',

            'card_token' => 'required|string|max:255',            Log::warning('Payment failed', [        }

            'save_card' => 'nullable|boolean',

        ]);                'order_id' => $orderId,



        if ($validator->fails()) {                'transid' => $request->input('transid'),        // Check if order can be paid

            return $this->errorResponse('Validation failed', '400', $validator->errors()->first());

        }            ]);        if (!$order->canBePaid()) {



        $order = Order::where('vendor', $request->input('vendor'))            return $this->errorResponse(

            ->where('order_id', $request->input('order_id'))

            ->first();            return $this->errorResponse(                'Invalid order state',



        if (!$order) {                'Payment failed',                '400',

            return $this->errorResponse('Order not found', '404', 'Order does not exist');

        }                '400',                "Order is {$order->payment_status} and cannot be paid"



        if (!$order->canBePaid()) {                'Payment could not be processed'            );

            return $this->errorResponse('Cannot process payment', '400', 'Order cannot be paid');

        }            );        }



        if ($request->input('save_card')) {        }

            StoredCard::firstOrCreate(

                [    }        $result = $request->input('result');

                    'vendor' => $request->input('vendor'),

                    'buyer_userid' => $order->buyer_userid,

                    'card_token' => $request->input('card_token'),

                ],    public function cancelOrder(Request $request)        if ($result === 'SUCCESS') {

                [

                    'gateway_buyer_uuid' => $order->gateway_buyer_uuid,    {            // Mark as completed

                    'card_brand' => $request->input('card_brand', 'VISA'),

                    'last4_digits' => $request->input('last4', '****'),        $validator = Validator::make($request->all(), [            $order->markAsCompleted(

                    'expiry_month' => $request->input('expiry_month'),

                    'expiry_year' => $request->input('expiry_year'),            'order_id' => 'required|string',                $request->input('transid'),

                ]

            );            'cancel_by_user' => 'nullable|boolean',                $request->input('channel'),

        }

        ]);                $request->input('reference')

        $transid = 'TXN' . strtoupper(Str::random(10));

        $order->markAsCompleted($transid, 'CARD', 'CARD-' . time());            );



        Log::info('Card payment processed', ['order_id' => $order->order_id, 'transid' => $transid]);        if ($validator->fails()) {



        return response()->json([            return $this->errorResponse(            Log::info('Payment completed', [

            'reference' => $order->reference,

            'resultcode' => '000',                'Validation failed',                'order_id' => $orderId,

            'result' => 'SUCCESS',

            'message' => 'Payment processed successfully',                '400',                'transid' => $request->input('transid'),

            'data' => ['transid' => $transid],

        ]);                'order_id is required'                'channel' => $request->input('channel'),

    }

            );                'amount' => $order->amount,

    public function walletPayment(Request $request)

    {        }            ]);

        $validator = Validator::make($request->all(), [

            'vendor' => 'required|string|max:50',

            'order_id' => 'required|string|max:100',

            'channel' => 'required|string|in:MPESA-TZ,AIRTELMONEY,TIGOPESATZ,HALOPESATZ',        $orderId = $request->input('order_id');            return response()->json([

            'phone' => 'required|string|regex:/^[0-9]{10,15}$/',

        ]);        $order = Order::where('order_id', $orderId)->first();                'reference' => $order->reference,



        if ($validator->fails()) {                'resultcode' => '000',

            return $this->errorResponse('Validation failed', '400', $validator->errors()->first());

        }        if (!$order) {                'result' => 'SUCCESS',



        $order = Order::where('vendor', $request->input('vendor'))            return $this->errorResponse(                'message' => 'Payment processed successfully',

            ->where('order_id', $request->input('order_id'))

            ->first();                'Order not found',                'data' => [],



        if (!$order) {                '404',            ]);

            return $this->errorResponse('Order not found', '404', 'Order does not exist');

        }                "Order {$orderId} not found"        } else {



        if (!$order->canBePaid()) {            );            // Mark as failed/rejected

            return $this->errorResponse('Cannot process payment', '400', 'Order cannot be paid');

        }        }            $order->update(['payment_status' => Order::STATUS_REJECTED]);



        $transid = 'TXN' . strtoupper(Str::random(10));

        $order->markAsCompleted($transid, $request->input('channel'), strtoupper($request->input('channel')) . '-' . time());

        $cancelByUser = $request->input('cancel_by_user', false);            Log::warning('Payment failed', [

        Log::info('Wallet payment processed', [

            'order_id' => $order->order_id,                'order_id' => $orderId,

            'channel' => $request->input('channel'),

            'transid' => $transid,        if ($order->cancel($cancelByUser)) {                'reason' => $request->input('message', 'Payment failed'),

        ]);

            Log::info('Order cancelled', [            ]);

        return response()->json([

            'reference' => $order->reference,                'order_id' => $orderId,

            'resultcode' => '000',

            'result' => 'SUCCESS',                'by_user' => $cancelByUser,            return response()->json([

            'message' => 'Payment initiated successfully',

            'data' => ['transid' => $transid],            ]);                'reference' => $order->order_id,

        ]);

    }                'resultcode' => '400',



    public function selcomPesaPayment(Request $request)            return response()->json([                'result' => 'FAIL',

    {

        $validator = Validator::make($request->all(), [                'reference' => $order->order_id,                'message' => 'Payment failed',

            'vendor' => 'required|string|max:50',

            'order_id' => 'required|string|max:100',                'resultcode' => '000',                'data' => [],

            'phone' => 'required|string|regex:/^[0-9]{10,15}$/',

        ]);                'result' => 'SUCCESS',            ]);



        if ($validator->fails()) {                'message' => 'Order cancelled successfully',        }

            return $this->errorResponse('Validation failed', '400', $validator->errors()->first());

        }                'data' => [],    }



        $order = Order::where('vendor', $request->input('vendor'))            ]);

            ->where('order_id', $request->input('order_id'))

            ->first();        } else {    /**



        if (!$order) {            return $this->errorResponse(     * Cancel an order (before payment completion)

            return $this->errorResponse('Order not found', '404', 'Order does not exist');

        }                'Cannot cancel order',     * 



        if (!$order->canBePaid()) {                '400',     * DELETE /api/v1/checkout/cancel-order?order_id={order_id}

            return $this->errorResponse('Cannot process payment', '400', 'Order cannot be paid');

        }                "Order is {$order->payment_status} and cannot be cancelled"     */



        $transid = 'SPESA' . strtoupper(Str::random(8));            );    public function cancelOrder(Request $request)

        $order->markAsCompleted($transid, 'SELCOMPESA', 'SPESA-' . time());

        }    {

        Log::info('SelcomPesa payment processed', ['order_id' => $order->order_id, 'transid' => $transid]);

    }        $validator = Validator::make($request->all(), [

        return response()->json([

            'reference' => $order->reference,            'order_id' => 'required|string',

            'resultcode' => '000',

            'result' => 'SUCCESS',    public function listOrders(Request $request)        ]);

            'message' => 'SelcomPesa payment initiated successfully',

            'data' => ['transid' => $transid],    {

        ]);

    }        try {        if ($validator->fails()) {



    public function createTillAlias(Request $request)            $validator = Validator::make($request->all(), [            return $this->errorResponse('Validation failed', '400', 'order_id is required');

    {

        $validator = Validator::make($request->all(), [                'vendor' => 'required|string|max:50',        }

            'vendor' => 'required|string|max:50',

            'alias_name' => 'required|string|max:100|unique:till_aliases,alias_name',                'from_date' => 'nullable|date',

            'till_number' => 'required|string|max:20',

        ]);                'to_date' => 'nullable|date|after_or_equal:from_date',        $orderId = $request->input('order_id');



        if ($validator->fails()) {            ]);        $order = Order::where('order_id', $orderId)->first();

            return $this->errorResponse('Validation failed', '400', $validator->errors()->first());

        }



        try {            if ($validator->fails()) {        if (!$order) {

            $alias = TillAlias::create([

                'vendor' => $request->input('vendor'),                return $this->errorResponse(            return $this->errorResponse('Order not found', '404', "Order {$orderId} not found");

                'alias_name' => $request->input('alias_name'),

                'till_number' => $request->input('till_number'),                    'Validation failed',        }

                'status' => 'ACTIVE',

            ]);                    '400',



            Log::info('Till alias created', ['alias_name' => $alias->alias_name, 'vendor' => $alias->vendor]);                    $validator->errors()->first()        if (!$order->cancel()) {



            return response()->json([                );            return $this->errorResponse(

                'reference' => Str::uuid()->toString(),

                'resultcode' => '000',            }                'Cannot cancel order',

                'result' => 'SUCCESS',

                'message' => 'Till alias created successfully',                '400',

                'data' => $alias->toSelcomResponse(),

            ], 201);            $vendor = $request->input('vendor');                "Order is {$order->payment_status} and cannot be cancelled"



        } catch (\Exception $e) {            $fromDate = $request->input('from_date');            );

            Log::error('Till alias creation failed', ['error' => $e->getMessage()]);

            return $this->errorResponse('Failed to create till alias', '500', 'Internal server error');            $toDate = $request->input('to_date');        }

        }

    }



    private function generatePaymentToken(): string            Log::info('Listing orders', [        Log::info('Order cancelled', ['order_id' => $orderId]);

    {

        return strval(random_int(10000000, 99999999));                'vendor' => $vendor,

    }

                'from_date' => $fromDate,        return response()->json([

    private function generateGatewayUrl(string $orderId): string

    {                'to_date' => $toDate,            'reference' => $order->order_id,

        return url("/payment-gateway/{$orderId}");

    }            ]);            'resultcode' => '000',



    private function generateQRCode(string $paymentToken): string            'result' => 'SUCCESS',

    {

        return base64_encode("SELCOM-PAY-{$paymentToken}");            $query = Order::where('vendor', $vendor);            'message' => 'Order cancelled successfully',

    }

            'data' => [],

    private function errorResponse(string $message, string $resultCode, string $details): \Illuminate\Http\JsonResponse

    {            if ($fromDate) {        ]);

        return response()->json([

            'reference' => null,                $query->whereDate('created_at', '>=', $fromDate);    }

            'resultcode' => $resultCode,

            'result' => 'FAIL',            }

            'message' => $message,

            'details' => $details,    // Helper methods

            'data' => [],

        ], $resultCode === '404' ? 404 : ($resultCode === '500' ? 500 : 400));            if ($toDate) {

    }

}                $query->whereDate('created_at', '<=', $toDate);    /**


            }     * List orders for a vendor

     * 

            $orders = $query->orderBy('created_at', 'desc')->get();     * GET /api/v1/checkout/list-orders?vendor={vendor}&from_date={date}&to_date={date}

     * 

            return response()->json([     * Used for reconciliation and reporting

                'reference' => Str::uuid()->toString(),     */

                'resultcode' => '200',    public function listOrders(Request $request)

                'result' => 'SUCCESS',    {

                'message' => 'Orders retrieved successfully',        try {

                'data' => [            $validator = Validator::make($request->all(), [

                    'orders' => $orders->map(function ($order) {                'vendor' => 'required|string|max:50',

                        return $order->toSelcomResponse();                'from_date' => 'nullable|date',

                    }),                'to_date' => 'nullable|date|after_or_equal:from_date',

                    'total' => $orders->count(),            ]);

                ],

            ]);            if ($validator->fails()) {

                return $this->errorResponse(

        } catch (\Exception $e) {                    'Validation failed',

            Log::error('List orders failed', [                    '400',

                'error' => $e->getMessage(),                    $validator->errors()->first()

            ]);                );

            }

            return $this->errorResponse(

                'Failed to retrieve orders',            $vendor = $request->input('vendor');

                '500',            $fromDate = $request->input('from_date');

                'Internal server error'            $toDate = $request->input('to_date');

            );

        }            Log::info('Listing orders', [

    }                'vendor' => $vendor,

                'from_date' => $fromDate,

    private function generatePaymentToken(): string                'to_date' => $toDate,

    {            ]);

        return strval(random_int(10000000, 99999999));

    }            // Build query

            $query = Order::where('vendor', $vendor);

    private function generateGatewayUrl(string $orderId): string

    {            if ($fromDate) {

        return url("/payment-gateway/{$orderId}");                $query->whereDate('created_at', '>=', $fromDate);

    }            }



    private function generateQRCode(string $paymentToken): string            if ($toDate) {

    {                $query->whereDate('created_at', '<=', $toDate);

        return base64_encode("SELCOM-PAY-{$paymentToken}");            }

    }

            $orders = $query->orderBy('created_at', 'desc')->get();

    private function errorResponse(string $message, string $resultCode, string $details): \Illuminate\Http\JsonResponse

    {            return response()->json([

        return response()->json([                'reference' => Str::uuid()->toString(),

            'reference' => null,                'resultcode' => '200',

            'resultcode' => $resultCode,                'result' => 'SUCCESS',

            'result' => 'FAIL',                'message' => 'Orders retrieved successfully',

            'message' => $message,                'data' => [

            'details' => $details,                    'orders' => $orders->map(function ($order) {

            'data' => [],                        return $order->toSelcomResponse();

        ], $resultCode === '404' ? 404 : ($resultCode === '500' ? 500 : 400));                    }),

    }                    'total' => $orders->count(),

}                ],

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
