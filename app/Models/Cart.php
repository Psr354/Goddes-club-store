<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Cart extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'session_id',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function items()
    {
        return $this->hasMany(CartItem::class);
    }

    public function subtotal(): float
    {
        return (float) $this->items->sum(
            fn (CartItem $item) => (float) $item->variant?->price * $item->quantity
        );
    }

    public function count(): int
    {
        return (int) $this->items->sum('quantity');
    }
}
