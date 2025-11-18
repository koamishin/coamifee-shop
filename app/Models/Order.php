<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_name',
        'customer_id',
        'order_type',
        'payment_method',
        'payment_status',
        'subtotal',
        'discount_type',
        'discount_value',
        'discount_amount',
        'add_ons',
        'add_ons_total',
        'total',
        'status',
        'inventory_processed',
        'table_number',
        'notes',
    ];

    protected $casts = [
        'total' => 'decimal:2',
        'subtotal' => 'decimal:2',
        'discount_value' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'add_ons' => 'array',
        'add_ons_total' => 'decimal:2',
        'customer_id' => 'integer',
        'inventory_processed' => 'boolean',
    ];

    protected $appends = [
        'revenue',
        'orders',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function getRevenueAttribute(): float
    {
        return (float) $this->total;
    }

    public function getOrdersAttribute(): int
    {
        return $this->items()->count();
    }
}
