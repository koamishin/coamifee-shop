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
     */
    public function canConvert(UnitType $fromUnit, UnitType $toUnit): bool
    {
        $weightUnits = [UnitType::GRAMS, UnitType::KILOGRAMS];
        $volumeUnits = [UnitType::MILLILITERS, UnitType::LITERS];

        // Same unit
        if ($fromUnit === $toUnit) {
            return true;
        }

        // Both are weight units
        if (in_array($fromUnit, $weightUnits, true) && in_array($toUnit, $weightUnits, true)) {
            return true;
        }

        // Both are volume units
        if (in_array($fromUnit, $volumeUnits, true) && in_array($toUnit, $volumeUnits, true)) {
            return true;
        }

        // Cannot convert between different measurement types or pieces
        return false;
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
     * Convert quantity to base unit (grams for weight, milliliters for volume).
     */
    private function toBaseUnit(float $quantity, UnitType $unit): float
    {
        return match ($unit) {
            UnitType::GRAMS => $quantity,
            UnitType::KILOGRAMS => $quantity * 1000,
            UnitType::MILLILITERS => $quantity,
            UnitType::LITERS => $quantity * 1000,
            UnitType::PIECES => $quantity,
        };
    }

    /**
     * Convert quantity from base unit to target unit.
     */
    private function fromBaseUnit(float $baseQuantity, UnitType $unit): float
    {
        return match ($unit) {
            UnitType::GRAMS => $baseQuantity,
            UnitType::KILOGRAMS => $baseQuantity / 1000,
            UnitType::MILLILITERS => $baseQuantity,
            UnitType::LITERS => $baseQuantity / 1000,
            UnitType::PIECES => $baseQuantity,
        };
    }
}
