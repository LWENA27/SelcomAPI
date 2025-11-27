<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TillAlias extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'vendor',
        'alias_name',
        'till_number',
        'status',
    ];

    public function toSelcomResponse(): array
    {
        return [
            'alias_id' => $this->id,
            'alias_name' => $this->alias_name,
            'till_number' => $this->till_number,
            'status' => $this->status,
            'created_at' => $this->created_at->toIso8601String(),
        ];
    }
}
