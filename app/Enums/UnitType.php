<?php

declare(strict_types=1);

namespace App\Enums;

enum UnitType: string
{
    case GRAMS = 'grams';
    case KILOGRAMS = 'kilograms';
    case MILLILITERS = 'ml';
    case LITERS = 'liters';
    case PIECES = 'pieces';

    /**
     * Get all options for filament select field.
     *
     * @return array<string, string>
     */
    public static function getOptions(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (UnitType $unit): array => [$unit->value => $unit->getLabel()])
            ->all();
    }

    /**
     * Get filament select field configuration.
     *
     * @return array<int, array{value: string, label: string, icon: string, description: string, color: string}>
     */
    public static function getSelectFieldConfig(): array
    {
        return collect(self::cases())
            ->map(fn (UnitType $unit): array => [
                'value' => $unit->value,
                'label' => $unit->getLabel(),
                'icon' => $unit->getIcon(),
                'description' => $unit->getDescription(),
                'color' => $unit->getColor(),
            ])
            ->all();
    }

    public function getLabel(): string
    {
        return match ($this) {
            self::GRAMS => 'Grams',
            self::KILOGRAMS => 'Kilograms',
            self::MILLILITERS => 'Milliliters',
            self::LITERS => 'Liters',
            self::PIECES => 'Pieces',
        };
    }

    public function getIcon(): string
    {
        return match ($this) {
            self::GRAMS, self::KILOGRAMS => 'heroicon-o-scale',
            self::MILLILITERS, self::LITERS => 'heroicon-o-beaker',
            self::PIECES => 'heroicon-o-cube',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::GRAMS => 'warning',
            self::KILOGRAMS => 'danger',
            self::MILLILITERS => 'info',
            self::LITERS => 'primary',
            self::PIECES => 'success',
        };
    }

    public function getDescription(): string
    {
        return match ($this) {
            self::GRAMS => 'Weight measurement in grams (g)',
            self::KILOGRAMS => 'Weight measurement in kilograms (kg)',
            self::MILLILITERS => 'Volume measurement in milliliters (ml)',
            self::LITERS => 'Volume measurement in liters (L)',
            self::PIECES => 'Count measurement for individual items',
        };
    }
}
