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

    /**
     * Boot model and add validation.
     */
    protected static function booted(): void
    {
        self::saving(function (IngredientInventory $inventory) {
            $validator = validator()->make($inventory->getAttributes(), [
                'ingredient_id' => 'required|exists:ingredients,id',
                'current_stock' => 'required|numeric|min:0',
                'min_stock_level' => 'nullable|numeric|min:0',
                'max_stock_level' => 'nullable|numeric|min:0',
                'reorder_level' => 'nullable|numeric|min:0',
                'unit_cost' => 'nullable|numeric|min:0',
                'location' => 'nullable|string|max:255',
                'supplier_info' => 'nullable|string|max:500',
            ]);

            if ($validator->fails()) {
                throw new \Illuminate\Validation\ValidationException($validator);
            }
        });
    }
}
