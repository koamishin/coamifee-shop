<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class OrderItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'product_id',
        'quantity',
        'price',
        'notes',
        'is_served',
    ];

    protected $casts = [
        'order_id' => 'integer',
        'product_id' => 'integer',
        'quantity' => 'integer',
        'price' => 'decimal:2',
        'is_served' => 'boolean',
    ];

    protected $appends = [
        'total_quantity',
        'total_revenue',
        'subtotal',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function getTotalQuantityAttribute(): int
    {
        return (int) $this->quantity;
    }

    public function getTotalRevenueAttribute(): float
    {
        return (float) ($this->quantity * $this->price);
    }

    public function getSubtotalAttribute(): float
    {
        return (float) ($this->quantity * $this->price);
    }
}
