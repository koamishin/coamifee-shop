<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\UnitType;
use BinaryCats\Sku\HasSku;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

final class Ingredient extends Model
{
    use HasFactory;
    use HasSku;

    protected $fillable = [
        'name',
        'sku',
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

    /**
     * Set the unit_type attribute with proper enum handling.
     */
    public function setUnitTypeAttribute($value): void
    {
        if (is_string($value) && $value === '') {
            $this->attributes['unit_type'] = null;

            return;
        }

        if (is_string($value)) {
            $unitType = UnitType::tryFrom($value);
            if ($unitType === null) {
                throw new \Illuminate\Validation\ValidationException(
                    validator()->make(['unit_type' => $value], [
                        'unit_type' => ['required', 'in:'.implode(',', array_column(UnitType::cases(), 'value'))],
                    ])
                );
            }
            $this->attributes['unit_type'] = $unitType->value;

            return;
        }

        if ($value instanceof UnitType) {
            $this->attributes['unit_type'] = $value->value;

            return;
        }

        $this->attributes['unit_type'] = $value;
    }

    /**
     * Get the unit_type attribute as enum.
     */
    public function getUnitTypeAttribute($value): ?UnitType
    {
        return $value ? UnitType::from($value) : null;
    }

    public function productIngredients(): HasMany
    {
        return $this->hasMany(ProductIngredient::class);
    }

    public function inventory(): HasOne
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

    /**
     * Boot the model and add validation.
     */
    protected static function booted(): void
    {
        self::saving(function (Ingredient $ingredient) {
            $validator = validator()->make($ingredient->getAttributes(), [
                'name' => 'required|string|max:255',
                'unit_type' => ['required', 'string', 'in:'.implode(',', array_column(UnitType::cases(), 'value'))],
            ]);

            if ($validator->fails()) {
                throw new \Illuminate\Validation\ValidationException($validator);
            }
        });
    }
}
