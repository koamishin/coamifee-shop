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
        'product_name',
        'unit_price',
        'quantity',
        'subtotal',
        'customizations',
        'notes',
        'status',
    ];

    protected $casts = [
        'unit_price' => 'decimal:2',
        'subtotal' => 'decimal:2',
        'customizations' => 'array',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function getFormattedUnitPriceAttribute(): string
    {
        return '$'.number_format($this->unit_price, 2);
    }

    public function getFormattedSubtotalAttribute(): string
    {
        return '$'.number_format($this->subtotal, 2);
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

    public function getCustomizationSummaryAttribute(): string
    {
        if (! $this->customizations || empty($this->customizations)) {
            return 'None';
        }

        $summary = [];

        foreach ($this->customizations as $key => $value) {
            if (is_array($value)) {
                $summary[] = ucfirst($key).': '.implode(', ', $value);
            } else {
                $summary[] = ucfirst($key).': '.$value;
            }
        }

        return implode(', ', $summary);
    }

    public function hasCustomizations(): bool
    {
        return ! empty($this->customizations);
    }

    public function canBeModified(): bool
    {
        return in_array($this->status, ['pending', 'confirmed']);
    }

    public function canBeCancelled(): bool
    {
        return in_array($this->status, ['pending', 'confirmed', 'preparing']);
    }

    public function updateStatus(string $status): void
    {
        $this->status = $status;
        $this->save();
    }

    public function calculateSubtotal(): void
    {
        $this->subtotal = $this->unit_price * $this->quantity;
        $this->save();
    }

    public function updateQuantity(int $quantity): void
    {
        if ($quantity <= 0) {
            return;
        }

        $this->quantity = $quantity;
        $this->calculateSubtotal();
    }

    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    public function scopeActive($query)
    {
        return $query->whereIn('status', ['pending', 'confirmed', 'preparing', 'ready']);
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeCancelled($query)
    {
        return $query->where('status', 'cancelled');
    }
}
