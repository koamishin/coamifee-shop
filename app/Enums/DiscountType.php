<?php

declare(strict_types=1);

namespace App\Enums;

enum DiscountType: string
{
    case PWD = 'pwd';
    case SENIOR = 'senior';

    /**
     * Get all discount type options for forms
     */
    public static function getOptions(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn ($case) => [$case->value => $case->getLabel()])
            ->toArray();
    }

    /**
     * Get the label for the discount type
     */
    public function getLabel(): string
    {
        return match ($this) {
            self::PWD => 'PWD (Person with Disability)',
            self::SENIOR => 'Senior Citizen',
        };
    }

    /**
     * Get the discount percentage for the type
     */
    public function getPercentage(): ?float
    {
        return match ($this) {
            self::PWD => 20.0,
            self::SENIOR => 20.0,
        };
    }

    /**
     * Check if this discount type requires custom value input
     */
    public function requiresCustomValue(): bool
    {
        return false; // No custom discounts available
    }

    /**
     * Get discount description with percentage
     */
    public function getDescription(): string
    {
        $percentage = $this->getPercentage();

        return "{$percentage}% discount";
    }
}
