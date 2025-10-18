<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class Inventory extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'quantity',
        'minimum_stock',
        'maximum_stock',
        'unit_cost',
        'location',
        'notes',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'minimum_stock' => 'integer',
        'maximum_stock' => 'integer',
        'unit_cost' => 'decimal:2',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function isLowStock(): bool
    {
        return $this->quantity <= $this->minimum_stock;
    }

    public function isOverstock(): bool
    {
        return $this->maximum_stock && $this->quantity >= $this->maximum_stock;
    }

    public function isOptimalStock(): bool
    {
        return ! $this->isLowStock() && ! $this->isOverstock();
    }

    public function getStockStatusAttribute(): string
    {
        if ($this->quantity === 0) {
            return 'out_of_stock';
        }

        if ($this->isLowStock()) {
            return 'low_stock';
        }

        if ($this->isOverstock()) {
            return 'overstock';
        }

        return 'in_stock';
    }

    public function getStockStatusColorAttribute(): string
    {
        return match ($this->stock_status) {
            'out_of_stock' => 'red',
            'low_stock' => 'yellow',
            'overstock' => 'blue',
            'in_stock' => 'green',
            default => 'gray',
        };
    }

    public function getTotalValueAttribute(): float
    {
        return $this->quantity * $this->unit_cost;
    }

    public function canFulfillOrder(int $quantity): bool
    {
        return $this->quantity >= $quantity;
    }

    public function deductStock(int $quantity): bool
    {
        if (! $this->canFulfillOrder($quantity)) {
            return false;
        }

        $this->quantity -= $quantity;
        $this->save();

        return true;
    }

    public function addStock(int $quantity): void
    {
        $this->quantity += $quantity;
        $this->save();
    }

    public function scopeLowStock($query)
    {
        return $query->whereRaw('quantity <= minimum_stock');
    }

    public function scopeOutOfStock($query)
    {
        return $query->where('quantity', 0);
    }

    public function scopeOverstock($query)
    {
        return $query->whereRaw('quantity >= maximum_stock')
            ->whereNotNull('maximum_stock');
    }
}
