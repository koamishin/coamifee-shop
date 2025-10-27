<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class IngredientInventory extends Model
{
    use HasFactory;

    protected $fillable = [
        'ingredient_id',
        'current_stock',
        'min_stock_level',
        'max_stock_level',
        'reorder_level',
        'unit_cost',
        'location',
        'supplier_info',
        'last_restocked_at',
    ];

    protected $casts = [
        'current_stock' => 'decimal:3',
        'min_stock_level' => 'decimal:3',
        'max_stock_level' => 'decimal:3',
        'reorder_level' => 'decimal:3',
        'unit_cost' => 'decimal:2',
        'last_restocked_at' => 'datetime',
    ];

    public function ingredient(): BelongsTo
    {
        return $this->belongsTo(Ingredient::class);
    }
}
