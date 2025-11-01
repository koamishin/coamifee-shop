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
        'total',
        'status',
        'table_number',
        'notes',
    ];

    protected $casts = [
        'total' => 'decimal:2',
        'customer_id' => 'integer',
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
