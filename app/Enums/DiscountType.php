<?php

declare(strict_types=1);

namespace App\Enums;

enum DiscountType: string
{
    case STUDENT = 'student';
    case PWD = 'pwd';
    case SENIOR = 'senior';
    case EMPLOYEE = 'employee';
    case CUSTOM = 'custom';

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
            self::STUDENT => 'Student',
            self::PWD => 'PWD (Person with Disability)',
            self::SENIOR => 'Senior Citizen',
            self::EMPLOYEE => 'Employee',
            self::CUSTOM => 'Custom Discount',
        };
    }

    /**
     * Get the discount percentage for the type
     */
    public function getPercentage(): ?float
    {
        return match ($this) {
            self::STUDENT => 10.0,
            self::PWD => 15.0,
            self::SENIOR => 20.0,
            self::EMPLOYEE => 25.0,
            self::CUSTOM => null, // Custom requires user input
        };
    }

    /**
     * Check if this discount type requires custom value input
     */
    public function requiresCustomValue(): bool
    {
        return $this === self::CUSTOM;
    }

    /**
     * Get discount description with percentage
     */
    public function getDescription(): string
    {
        $percentage = $this->getPercentage();

        if ($percentage === null) {
            return 'Enter custom amount or percentage';
        }

        return "{$percentage}% discount";
    }
}
