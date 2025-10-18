<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

final class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_number',
        'customer_id',
        'status',
        'order_type',
        'subtotal',
        'tax_amount',
        'discount_amount',
        'total_amount',
        'notes',
        'table_number',
        'order_date',
        'estimated_ready_time',
        'completed_at',
        'created_by',
    ];

    protected $casts = [
        'subtotal' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'order_date' => 'datetime',
        'estimated_ready_time' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function payment(): HasOne
    {
        return $this->hasOne(Payment::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function getFormattedSubtotalAttribute(): string
    {
        return '$'.number_format($this->subtotal, 2);
    }

    public function getFormattedTaxAmountAttribute(): string
    {
        return '$'.number_format($this->tax_amount, 2);
    }

    public function getFormattedDiscountAmountAttribute(): string
    {
        return '$'.number_format($this->discount_amount, 2);
    }

    public function getFormattedTotalAmountAttribute(): string
    {
        return '$'.number_format($this->total_amount, 2);
    }

    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'pending' => 'gray',
            'confirmed' => 'blue',
            'preparing' => 'yellow',
            'ready' => 'green',
            'completed' => 'emerald',
            'cancelled' => 'red',
            default => 'gray',
        };
    }

    public function getOrderTypeLabelAttribute(): string
    {
        return match ($this->order_type) {
            'dine_in' => 'Dine In',
            'takeaway' => 'Takeaway',
            'delivery' => 'Delivery',
            default => ucfirst($this->order_type),
        };
    }

    public function canBeModified(): bool
    {
        return in_array($this->status, ['pending', 'confirmed']);
    }

    public function canBeCancelled(): bool
    {
        return in_array($this->status, ['pending', 'confirmed', 'preparing']);
    }

    public function isPaid(): bool
    {
        return $this->payment && $this->payment->status === 'completed';
    }

    public function getPreparationTimeAttribute(): ?int
    {
        if (! $this->estimated_ready_time || ! $this->order_date) {
            return null;
        }

        return $this->order_date->diffInMinutes($this->estimated_ready_time);
    }

    public function getActualPreparationTimeAttribute(): ?int
    {
        if (! $this->completed_at || ! $this->order_date) {
            return null;
        }

        return $this->order_date->diffInMinutes($this->completed_at);
    }

    public function updateStatus(string $status): void
    {
        $this->status = $status;

        if ($status === 'completed') {
            $this->completed_at = now();
        }

        $this->save();
    }

    public function calculateTotals(): void
    {
        $this->subtotal = $this->orderItems->sum('subtotal');
        $this->tax_amount = $this->subtotal * 0.08; // 8% tax
        $this->total_amount = $this->subtotal + $this->tax_amount - $this->discount_amount;
        $this->save();
    }

    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    public function scopeByOrderType($query, string $orderType)
    {
        return $query->where('order_type', $orderType);
    }

    public function scopeByCustomer($query, int $customerId)
    {
        return $query->where('customer_id', $customerId);
    }

    public function scopeActive($query)
    {
        return $query->whereIn('status', ['pending', 'confirmed', 'preparing', 'ready']);
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeToday($query)
    {
        return $query->whereDate('order_date', today());
    }

    public function scopeThisWeek($query)
    {
        return $query->whereBetween('order_date', [now()->startOfWeek(), now()->endOfWeek()]);
    }

    public function scopeThisMonth($query)
    {
        return $query->whereMonth('order_date', now()->month)
            ->whereYear('order_date', now()->year);
    }
}
