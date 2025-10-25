<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'price',
        'category_id',
        'image_url',
        'is_active',
        'sku',
        'preparation_time',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'category_id' => 'integer',
        'is_active' => 'boolean',
        'preparation_time' => 'integer',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function ingredients(): HasMany
    {
        return $this->hasMany(ProductIngredient::class);
    }

    public function metrics(): HasMany
    {
        return $this->hasMany(ProductMetric::class);
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }
}
