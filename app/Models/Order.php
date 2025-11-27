<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Order Model - Selcom Checkout API Clone
 * 
 * Represents a checkout order following Selcom's payment gateway structure.
 * Reference: https://developers.selcommobile.com/#checkout-api
 * 
 * Interview Points:
 * - Follows real payment gateway patterns (Selcom, Stripe, PayPal)
 * - Demonstrates understanding of payment lifecycle states
 * - Shows knowledge of financial data handling (cents, not floats)
 * - Implements idempotency (vendor + order_id uniqueness)
 */
class Order extends Model
{
    use SoftDeletes;

    // Payment status constants matching Selcom API spec
    public const STATUS_PENDING = 'PENDING';
    public const STATUS_COMPLETED = 'COMPLETED';
    public const STATUS_CANCELLED = 'CANCELLED';
    public const STATUS_USERCANCELLED = 'USERCANCELLED';
    public const STATUS_REJECTED = 'REJECTED';
    public const STATUS_INPROGRESS = 'INPROGRESS';

    protected $fillable = [
        'vendor',
        'order_id',
        'buyer_email',
        'buyer_name',
        'buyer_phone',
        'buyer_userid',
        'amount',
        'currency',
        'payment_status',
        'gateway_buyer_uuid',
        'payment_token',
        'payment_gateway_url',
        'qr_code',
        'transid',
        'channel',
        'reference',
        'webhook_url',
        'redirect_url',
        'cancel_url',
        'buyer_remarks',
        'merchant_remarks',
        'no_of_items',
        'expires_at',
    ];

    protected $casts = [
        'amount' => 'integer',
        'no_of_items' => 'integer',
        'expires_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Generate Selcom-style response format
     * 
     * Interview note: Payment gateways have standard response structures
     * for consistency across their API
     */
    public function toSelcomResponse(string $result = 'SUCCESS', string $message = 'Order created successfully'): array
    {
        return [
            'reference' => $this->reference ?? $this->order_id,
            'resultcode' => $result === 'SUCCESS' ? '000' : '400',
            'result' => $result,
            'message' => $message,
            'data' => [[
                'order_id' => $this->order_id,
                'gateway_buyer_uuid' => $this->gateway_buyer_uuid,
                'payment_token' => $this->payment_token,
                'qr' => $this->qr_code,
                'payment_gateway_url' => $this->payment_gateway_url,
                'amount' => $this->amount,
                'currency' => $this->currency,
                'payment_status' => $this->payment_status,
                'creation_date' => $this->created_at->format('Y-m-d H:i:s'),
            ]]
        ];
    }

    /**
     * Check if order can be paid
     * 
     * Business logic: Only pending orders can receive payments
     */
    public function canBePaid(): bool
    {
        return $this->payment_status === self::STATUS_PENDING 
            && ($this->expires_at === null || $this->expires_at->isFuture());
    }

    /**
     * Mark order as completed with payment details
     */
    public function markAsCompleted(string $transid, string $channel, string $reference): bool
    {
        if (!$this->canBePaid()) {
            return false;
        }

        $this->update([
            'payment_status' => self::STATUS_COMPLETED,
            'transid' => $transid,
            'channel' => $channel,
            'reference' => $reference,
        ]);

        return true;
    }

    /**
     * Cancel the order
     */
    public function cancel(bool $byUser = false): bool
    {
        if ($this->payment_status === self::STATUS_COMPLETED) {
            return false; // Cannot cancel completed orders
        }

        $this->update([
            'payment_status' => $byUser ? self::STATUS_USERCANCELLED : self::STATUS_CANCELLED
        ]);

        return true;
    }

    /**
     * Check if order has expired
     */
    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    /**
     * Get amount in major currency units (e.g., TZS instead of cents)
     */
    public function getAmountInMajorUnits(): float
    {
        return $this->amount / 100;
    }

    // Query scopes for common filters
    public function scopePending($query)
    {
        return $query->where('payment_status', self::STATUS_PENDING);
    }

    public function scopeCompleted($query)
    {
        return $query->where('payment_status', self::STATUS_COMPLETED);
    }

    public function scopeForVendor($query, string $vendor)
    {
        return $query->where('vendor', $vendor);
    }
}
