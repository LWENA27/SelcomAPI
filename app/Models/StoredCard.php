<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class StoredCard extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'vendor',
        'buyer_userid',
        'gateway_buyer_uuid',
        'card_token',
        'card_brand',
        'last4_digits',
        'expiry_month',
        'expiry_year',
        'is_default',
    ];

    protected $casts = [
        'is_default' => 'boolean',
        'expiry_month' => 'integer',
        'expiry_year' => 'integer',
    ];

    public function toSelcomResponse(): array
    {
        return [
            'card_id' => $this->id,
            'card_token' => $this->card_token,
            'card_brand' => $this->card_brand,
            'last4' => $this->last4_digits,
            'expiry' => sprintf('%02d/%d', $this->expiry_month, $this->expiry_year),
            'is_default' => $this->is_default,
        ];
    }
}
