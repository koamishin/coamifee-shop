<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\UnitType;
use InvalidArgumentException;

final class UnitConversionService
{
    /**
     * Convert quantity from one unit type to another.
     *
     * @param  float  $quantity  The quantity to convert
     * @param  UnitType  $fromUnit  The source unit type
     * @param  UnitType  $toUnit  The target unit type
     * @return float The converted quantity
     *
     * @throws InvalidArgumentException If conversion between units is not possible
     */
    public function convert(float $quantity, UnitType $fromUnit, UnitType $toUnit): float
    {
        // If units are the same, no conversion needed
        if ($fromUnit === $toUnit) {
            return $quantity;
        }

        // Check if conversion is possible (must be same measurement type)
        if (! $this->canConvert($fromUnit, $toUnit)) {
            throw new InvalidArgumentException(
                "Cannot convert from {$fromUnit->value} to {$toUnit->value}. Units must be of the same measurement type."
            );
        }

        // Convert to base unit first, then to target unit
        $baseQuantity = $this->toBaseUnit($quantity, $fromUnit);

        return $this->fromBaseUnit($baseQuantity, $toUnit);
    }

    /**
     * Check if conversion between two unit types is possible.
     * Since we only use base units now, only same-unit conversions are possible.
     */
    public function canConvert(UnitType $fromUnit, UnitType $toUnit): bool
    {
        // Same unit - always possible
        return $fromUnit === $toUnit;
    }

    /**
     * Normalize quantity to inventory unit.
     * Converts recipe quantity (e.g., 250ml) to match inventory unit (e.g., liters).
     *
     * @param  float  $recipeQuantity  Quantity required in recipe
     * @param  UnitType  $recipeUnit  Unit type in recipe
     * @param  UnitType  $inventoryUnit  Unit type in inventory
     * @return float Normalized quantity in inventory units
     */
    public function normalizeToInventoryUnit(
        float $recipeQuantity,
        UnitType $recipeUnit,
        UnitType $inventoryUnit
    ): float {
        return $this->convert($recipeQuantity, $recipeUnit, $inventoryUnit);
    }

    /**
     * Convert quantity to base unit.
     * Since we only use base units now, this just returns the quantity.
     */
    private function toBaseUnit(float $quantity, UnitType $unit): float
    {
        return match ($unit) {
            UnitType::GRAMS => $quantity,
            UnitType::MILLILITERS => $quantity,
            UnitType::PIECES => $quantity,
        };
    }

    /**
     * Convert quantity from base unit to target unit.
     * Since we only use base units now, this just returns the quantity.
     */
    private function fromBaseUnit(float $baseQuantity, UnitType $unit): float
    {
        return match ($unit) {
            UnitType::GRAMS => $baseQuantity,
            UnitType::MILLILITERS => $baseQuantity,
            UnitType::PIECES => $baseQuantity,
        };
    }
}
