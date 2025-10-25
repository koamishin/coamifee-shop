<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class Ingredient extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'unit_type',
        'is_trackable',
        'current_stock',
        'unit_cost',
        'supplier',
    ];

    protected $casts = [
        'is_trackable' => 'boolean',
        'current_stock' => 'decimal:3',
        'unit_cost' => 'decimal:2',
    ];

    public function productIngredients(): HasMany
    {
        return $this->hasMany(ProductIngredient::class);
    }

    public function inventory(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(IngredientInventory::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(InventoryTransaction::class);
    }

    public function usage(): HasMany
    {
        return $this->hasMany(IngredientUsage::class);
    }
}
