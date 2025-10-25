<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class IngredientUsage extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_item_id',
        'ingredient_id',
        'quantity_used',
        'recorded_at',
    ];

    protected $casts = [
        'quantity_used' => 'decimal:3',
        'recorded_at' => 'datetime',
    ];

    public function orderItem(): BelongsTo
    {
        return $this->belongsTo(OrderItem::class);
    }

    public function ingredient(): BelongsTo
    {
        return $this->belongsTo(Ingredient::class);
    }
}
