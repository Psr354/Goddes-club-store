<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductVariant extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'sku',
        'attributes',
        'price',
        'stock',
        'reserved_stock',
        'weight_gram',
    ];

    protected $casts = [
        'attributes' => 'array',
        'price' => 'decimal:2',
        'stock' => 'integer',
        'reserved_stock' => 'integer',
        'weight_gram' => 'integer',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function cartItems()
    {
        return $this->hasMany(CartItem::class);
    }

    public function orderItems()
    {
        return $this->hasMany(OrderItem::class);
    }

    /**
     * The size label carried in the JSON attributes column ("S", "M", ... "One Size").
     */
    public function size(): string
    {
        $attributes = $this->getAttribute('attributes');

        if (! is_array($attributes)) {
            return 'One Size';
        }

        return (string) ($attributes['size'] ?? 'One Size');
    }

    /**
     * Stock still available for sale (physical stock minus reserved stock).
     */
    public function availableStock(): int
    {
        return max(0, $this->stock - $this->reserved_stock);
    }
}
