<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'provider',
        'provider_reference',
        'transaction_id',
        'payment_method',
        'payment_url',
        'amount',
        'status',
        'payload',
        'raw_response',
        'paid_at',
    ];

    protected $casts = [
        'payload' => 'array',
        'raw_response' => 'array',
        'paid_at' => 'datetime',
        'amount' => 'decimal:2',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}
