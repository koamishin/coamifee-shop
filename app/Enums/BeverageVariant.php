<?php

declare(strict_types=1);

namespace App\Enums;

enum BeverageVariant: string
{
    case HOT = 'Hot';
    case COLD = 'Cold';

    /**
     * Get all options for filament select field
     */
    public static function getOptions(): array
    {
        return collect(self::cases())->mapWithKeys(function (BeverageVariant $variant) {
            return [$variant->value => $variant->getLabel()];
        })->toArray();
    }

    /**
     * Get display label for beverage variant
     */
    public function getLabel(): string
    {
        return match ($this) {
            self::HOT => 'Hot',
            self::COLD => 'Cold',
        };
    }

    /**
     * Get icon for beverage variant
     */
    public function getIcon(): string
    {
        return match ($this) {
            self::HOT => 'heroicon-o-fire',
            self::COLD => 'heroicon-o-cube',
        };
    }

    /**
     * Get default price modifier (can be used for automatic pricing)
     */
    public function getPriceModifier(): float
    {
        return match ($this) {
            self::HOT => 0.0, // Base price
            self::COLD => 10.0, // Cold drinks typically cost more
        };
    }
}
