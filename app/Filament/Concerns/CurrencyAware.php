<?php

declare(strict_types=1);

namespace App\Filament\Concerns;

use App\Enums\Currency;
use App\Services\GeneralSettingsService;
use Illuminate\Support\HtmlString;

trait CurrencyAware
{
    /**
     * Get the current currency from GeneralSettingsService
     */
    protected static function getCurrentCurrency(): Currency
    {
        $currencyCode = app(GeneralSettingsService::class)->getCurrency();

        return Currency::isValid($currencyCode)
            ? Currency::from($currencyCode)
            : Currency::USD; // Fallback to USD
    }

    /**
     * Get the current currency symbol
     */
    protected static function getCurrencySymbol(): string
    {
        return self::getCurrentCurrency()->getSymbol();
    }

    /**
     * Get the current currency code
     */
    protected static function getCurrencyCode(): string
    {
        return self::getCurrentCurrency()->value;
    }

    /**
     * Format amount using current currency
     */
    protected static function formatCurrency(float $amount): string
    {
        return self::getCurrentCurrency()->formatAmount($amount);
    }

    /**
     * Format amount as HTML string with styling
     */
    protected static function formatCurrencyHtml(float $amount, string $color = 'primary'): HtmlString
    {
        $formatted = self::formatCurrency($amount);

        $colorMap = [
            'primary' => '#3b82f6',
            'success' => '#10b981',
            'warning' => '#f59e0b',
            'danger' => '#ef4444',
            'gray' => '#6b7280',
        ];

        $hexColor = $colorMap[$color] ?? $colorMap['primary'];

        return new HtmlString(
            "<span style='color: {$hexColor}; font-weight: 600;'>{$formatted}</span>"
        );
    }

    /**
     * Get currency prefix for form fields
     */
    protected static function getCurrencyPrefix(): string
    {
        $currency = self::getCurrentCurrency();

        // Determine if symbol should be prefix or suffix
        return match (true) {
            in_array($currency, [Currency::EUR, Currency::SEK, Currency::NOK, Currency::DKK, Currency::PLN, Currency::CZK, Currency::HUF, Currency::RON, Currency::BGN, Currency::HRK, Currency::RUB, Currency::TRY, Currency::ZAR, Currency::THB, Currency::MYR, Currency::PHP, Currency::IDR, Currency::VND]) => '',
            default => $currency->getSymbol(),
        };
    }

    /**
     * Get currency suffix for form fields
     */
    protected static function getCurrencySuffix(): string
    {
        $currency = self::getCurrentCurrency();

        // Determine if symbol should be prefix or suffix
        return match (true) {
            in_array($currency, [Currency::EUR, Currency::SEK, Currency::NOK, Currency::DKK, Currency::PLN, Currency::CZK, Currency::HUF, Currency::RON, Currency::BGN, Currency::HRK, Currency::RUB, Currency::TRY, Currency::ZAR, Currency::THB, Currency::MYR, Currency::PHP, Currency::IDR, Currency::VND]) => $currency->getSymbol(),
            default => '',
        };
    }

    /**
     * Get number of decimal places for current currency
     */
    protected static function getCurrencyDecimals(): int
    {
        return self::getCurrentCurrency()->getDecimals();
    }

    /**
     * Format currency for Filament table columns
     */
    protected static function formatTableCurrency(float $amount): string
    {
        return self::getCurrentCurrency()->formatAmount($amount);
    }

    /**
     * Get currency configuration for Filament money() method
     */
    protected static function getMoneyConfig(): string
    {
        return self::getCurrencyCode();
    }

    /**
     * Format cost calculation with currency
     */
    protected static function formatCostCalculation(float $quantity, float $unitCost, string $unit): HtmlString
    {
        $totalCost = $quantity * $unitCost;
        $symbol = self::getCurrencySymbol();

        return new HtmlString(
            "
            <div style='display: flex; align-items: center; gap: 12px;'>
                <span style='color: #374151; font-weight: 500;'>Cost per product:</span>
                <span style='color: #10b981; font-weight: bold; font-size: 1.1em;'>".
                self::formatCurrency($totalCost).
                "</span>
                <span style='color: #6b7280; font-size: 0.9em;'>({$quantity} {$unit} × ".
                self::formatCurrency($unitCost).
                "/{$unit})</span>
            </div>
            "
        );
    }

    /**
     * Format inventory value calculation
     */
    protected static function formatInventoryValue(float $quantity, float $unitCost): HtmlString
    {
        $totalValue = $quantity * $unitCost;

        return new HtmlString(
            "<span style='color: #10b981; font-weight: bold; font-size: 1.1em;'>".
            self::formatCurrency($totalValue).
            '</span>'
        );
    }

    /**
     * Format unit cost display
     */
    protected static function formatUnitCost(float $cost): HtmlString
    {
        return new HtmlString(
            "<span style='color: #3b82f6;'>".
            self::formatCurrency($cost).
            '</span>'
        );
    }
}
