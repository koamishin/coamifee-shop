<?php

declare(strict_types=1);

namespace App\Enums;

enum TableNumber: string
{
    case TABLE_1 = 'table_1';
    case TABLE_2 = 'table_2';
    case TABLE_3 = 'table_3';
    case TABLE_4 = 'table_4';
    case TABLE_5 = 'table_5';

    /**
     * Get all table number options for forms
     */
    public static function getOptions(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn ($case) => [$case->value => $case->getLabel()])
            ->toArray();
    }

    /**
     * Get table number from value
     */
    public static function fromValue(string $value): ?self
    {
        return self::tryFrom($value);
    }

    /**
     * Get the display label for the table
     */
    public function getLabel(): string
    {
        return match ($this) {
            self::TABLE_1 => 'Table 1',
            self::TABLE_2 => 'Table 2',
            self::TABLE_3 => 'Table 3',
            self::TABLE_4 => 'Table 4',
            self::TABLE_5 => 'Table 5',
        };
    }
}
