<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

final class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'price',
        'cost',
        'sku',
        'barcode',
        'category_id',
        'image_url',
        'is_active',
        'is_featured',
        'variations',
        'ingredients',
        'preparation_time',
        'calories',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'cost' => 'decimal:2',
        'is_active' => 'boolean',
        'is_featured' => 'boolean',
        'variations' => 'array',
        'preparation_time' => 'integer',
        'calories' => 'decimal:2',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function inventory(): HasOne
    {
        return $this->hasOne(Inventory::class);
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function getFormattedPriceAttribute(): string
    {
        return '$'.number_format($this->price, 2);
    }

    public function getFormattedCostAttribute(): string
    {
        return '$'.number_format($this->cost, 2);
    }

    public function getProfitMarginAttribute(): float
    {
        if ($this->price <= 0) {
            return 0;
        }

        return (($this->price - $this->cost) / $this->price) * 100;
    }

    public function isInStock(): bool
    {
        return $this->inventory && $this->inventory->quantity > 0;
    }

    public function getStockLevelAttribute(): int
    {
        return $this->inventory?->quantity ?? 0;
    }

    public function getLowStockAttribute(): bool
    {
        return $this->inventory &&
               $this->inventory->quantity <= $this->inventory->minimum_stock;
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    public function scopeInStock($query)
    {
        return $query->whereHas('inventory', function ($q) {
            $q->where('quantity', '>', 0);
        });
    }

    public function scopeByCategory($query, $categoryId)
    {
        return $query->where('category_id', $categoryId);
    }
}
