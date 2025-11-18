<?php

declare(strict_types=1);

namespace App\Enums;

enum UnitType: string
{
    case GRAMS = 'grams';
    case MILLILITERS = 'ml';
    case PIECES = 'pieces';

    /**
     * Get all options for filament select field
     */
    public static function getOptions(): array
    {
        return collect(self::cases())->mapWithKeys(function (UnitType $unit) {
            return [$unit->value => $unit->getLabel()];
        })->toArray();
    }

    /**
     * Get filament select field configuration
     */
    public static function getSelectFieldConfig(): array
    {
        return collect(self::cases())->map(function (UnitType $unit) {
            return [
                'value' => $unit->value,
                'label' => $unit->getLabel(),
                'icon' => $unit->getIcon(),
                'description' => $unit->getDescription(),
                'color' => $unit->getColor(),
            ];
        })->toArray();
    }

    /**
     * Get display label for unit type
     */
    public function getLabel(): string
    {
        return match ($this) {
            self::GRAMS => 'Grams (g)',
            self::MILLILITERS => 'Milliliters (ml)',
            self::PIECES => 'Pieces',
        };
    }

    /**
     * Get heroicon for unit type
     */
    public function getIcon(): string
    {
        return match ($this) {
            self::GRAMS => 'heroicon-o-scale',
            self::MILLILITERS => 'heroicon-o-beaker',
            self::PIECES => 'heroicon-o-cube',
        };
    }

    /**
     * Get filament color for unit type
     */
    public function getColor(): string
    {
        return match ($this) {
            self::GRAMS => 'warning',
            self::MILLILITERS => 'info',
            self::PIECES => 'success',
        };
    }

    /**
     * Get full description for unit type
     */
    public function getDescription(): string
    {
        return match ($this) {
            self::GRAMS => 'Weight measurement in grams (enter kg as grams: 1kg = 1000g)',
            self::MILLILITERS => 'Volume measurement in milliliters (enter L as ml: 1L = 1000ml)',
            self::PIECES => 'Count measurement for individual items',
        };
    }
}
