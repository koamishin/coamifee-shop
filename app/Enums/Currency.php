<?php

declare(strict_types=1);

namespace App\Enums;

enum Currency: string
{
    case USD = 'USD';
    case EUR = 'EUR';
    case GBP = 'GBP';
    case JPY = 'JPY';
    case AUD = 'AUD';
    case CAD = 'CAD';
    case CHF = 'CHF';
    case CNY = 'CNY';
    case INR = 'INR';
    case BRL = 'BRL';
    case MXN = 'MXN';
    case SEK = 'SEK';
    case NOK = 'NOK';
    case DKK = 'DKK';
    case PLN = 'PLN';
    case CZK = 'CZK';
    case HUF = 'HUF';
    case RON = 'RON';
    case BGN = 'BGN';
    case HRK = 'HRK';
    case RUB = 'RUB';
    case TRY = 'TRY';
    case ZAR = 'ZAR';
    case SGD = 'SGD';
    case HKD = 'HKD';
    case NZD = 'NZD';
    case KRW = 'KRW';
    case THB = 'THB';
    case MYR = 'MYR';
    case PHP = 'PHP';
    case IDR = 'IDR';
    case VND = 'VND';

    /**
     * Get all currencies as an array for select options
     */
    public static function getSelectOptions(): array
    {
        $options = [];
        foreach (self::cases() as $currency) {
            $options[$currency->value] =
                $currency->getName().' ('.$currency->value.')';
        }

        return $options;
    }

    /**
     * Get commonly used currencies
     */
    public static function getCommon(): array
    {
        return [
            self::USD,
            self::EUR,
            self::GBP,
            self::JPY,
            self::AUD,
            self::CAD,
            self::CHF,
            self::CNY,
        ];
    }

    /**
     * Check if the currency is valid
     */
    public static function isValid(string $currency): bool
    {
        return in_array($currency, array_column(self::cases(), 'value'));
    }

    /**
     * Get the full name of the currency
     */
    public function getName(): string
    {
        return match ($this) {
            self::USD => 'US Dollar',
            self::EUR => 'Euro',
            self::GBP => 'British Pound',
            self::JPY => 'Japanese Yen',
            self::AUD => 'Australian Dollar',
            self::CAD => 'Canadian Dollar',
            self::CHF => 'Swiss Franc',
            self::CNY => 'Chinese Yuan',
            self::INR => 'Indian Rupee',
            self::BRL => 'Brazilian Real',
            self::MXN => 'Mexican Peso',
            self::SEK => 'Swedish Krona',
            self::NOK => 'Norwegian Krone',
            self::DKK => 'Danish Krone',
            self::PLN => 'Polish Zloty',
            self::CZK => 'Czech Koruna',
            self::HUF => 'Hungarian Forint',
            self::RON => 'Romanian Leu',
            self::BGN => 'Bulgarian Lev',
            self::HRK => 'Croatian Kuna',
            self::RUB => 'Russian Ruble',
            self::TRY => 'Turkish Lira',
            self::ZAR => 'South African Rand',
            self::SGD => 'Singapore Dollar',
            self::HKD => 'Hong Kong Dollar',
            self::NZD => 'New Zealand Dollar',
            self::KRW => 'South Korean Won',
            self::THB => 'Thai Baht',
            self::MYR => 'Malaysian Ringgit',
            self::PHP => 'Philippine Peso',
            self::IDR => 'Indonesian Rupiah',
            self::VND => 'Vietnamese Dong',
        };
    }

    /**
     * Get the currency symbol
     */
    public function getSymbol(): string
    {
        return match ($this) {
            self::USD => '$',
            self::EUR => '€',
            self::GBP => '£',
            self::JPY => '¥',
            self::AUD => 'A$',
            self::CAD => 'C$',
            self::CHF => 'CHF',
            self::CNY => '¥',
            self::INR => '₹',
            self::BRL => 'R$',
            self::MXN => '$',
            self::SEK => 'kr',
            self::NOK => 'kr',
            self::DKK => 'kr',
            self::PLN => 'zł',
            self::CZK => 'Kč',
            self::HUF => 'Ft',
            self::RON => 'lei',
            self::BGN => 'лв',
            self::HRK => 'kn',
            self::RUB => '₽',
            self::TRY => '₺',
            self::ZAR => 'R',
            self::SGD => 'S$',
            self::HKD => 'HK$',
            self::NZD => 'NZ$',
            self::KRW => '₩',
            self::THB => '฿',
            self::MYR => 'RM',
            self::PHP => '₱',
            self::IDR => 'Rp',
            self::VND => '₫',
        };
    }

    /**
     * Get the number of decimal places for the currency
     */
    public function getDecimals(): int
    {
        return match ($this) {
            self::JPY, self::KRW, self::VND => 0,
            default => 2,
        };
    }

    /**
     * Format an amount with the currency symbol
     */
    public function formatAmount(float $amount): string
    {
        $decimals = $this->getDecimals();
        $formattedAmount = number_format($amount, $decimals, '.', '');

        return match ($this) {
            self::USD,
            self::AUD,
            self::CAD,
            self::MXN,
            self::NZD,
            self::HKD,
            self::SGD => $this->getSymbol().$formattedAmount,
            self::EUR => $formattedAmount.$this->getSymbol(),
            self::GBP => $this->getSymbol().$formattedAmount,
            self::JPY, self::CNY, self::KRW => $this->getSymbol().
                $formattedAmount,
            self::INR => $this->getSymbol().$formattedAmount,
            self::BRL => $this->getSymbol().' '.$formattedAmount,
            self::VND => $this->getSymbol().$formattedAmount,
            default => $formattedAmount.' '.$this->getSymbol(),
        };
    }
}
