<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\Currency;
use App\Models\ExchangeRate;
use DateTimeImmutable;
use Exception;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

final class CurrencyConverter
{
    private const EXCHANGE_RATE_CACHE_KEY = 'exchange_rates';

    private const CACHE_TTL = 3600; // 1 hour

    private const BACKUP_CACHE_TTL = 86400; // 24 hours for offline cache

    private const MAX_AGE_HOURS = 6;

    private readonly string $apiUrl;

    private bool $forceOffline = false;

    public function __construct()
    {
        $this->apiUrl =
            config(
                'services.exchange_rate_api.url',
                'https://api.exchangerate-api.com/v4/latest/',
            ) ?:
            'https://api.exchangerate-api.com/v4/latest/';
    }

    /**
     * Force offline mode for testing
     */
    public function forceOffline(bool $force = true): self
    {
        $this->forceOffline = $force;

        return $this;
    }

    /**
     * Convert amount from one currency to another
     */
    public function convert(float $amount, Currency $from, Currency $to): float
    {
        if ($from === $to) {
            return $amount;
        }

        $rate = $this->getExchangeRate($from, $to);

        return round($amount * $rate, $to->getDecimals());
    }

    /**
     * Get exchange rate between two currencies
     */
    public function getExchangeRate(Currency $from, Currency $to): float
    {
        if ($from === $to) {
            return 1.0;
        }

        // Try database first (fastest and works offline)
        $dbRate = $this->getExchangeRateFromDatabase($from, $to);
        if ($dbRate !== null) {
            return $dbRate;
        }

        // Try cache
        $rates = $this->getExchangeRates($from);

        return $rates[$to->value] ??
            $this->getStoredRateWithFallback($from, $to);
    }

    /**
     * Get all exchange rates for a base currency
     */
    public function getExchangeRates(Currency $base): array
    {
        // Check database first for fresh rates
        if (ExchangeRate::hasFreshRates($base, self::MAX_AGE_HOURS)) {
            $dbRates = ExchangeRate::getStoredRates($base);
            if ($dbRates !== []) {
                return $dbRates;
            }
        }

        // Check cache
        $cacheKey = self::EXCHANGE_RATE_CACHE_KEY.'_'.$base->value;
        $cachedRates = Cache::get($cacheKey);
        if ($cachedRates && is_array($cachedRates)) {
            return $cachedRates;
        }

        try {
            // Try to fetch from API
            $rates = $this->fetchExchangeRates($base);

            // Store in database for offline use
            if ($rates !== []) {
                $this->storeRatesInDatabase($base, $rates);
                Cache::put($cacheKey, $rates, self::CACHE_TTL);
            }

            return $rates;
        } catch (Exception $e) {
            Log::error('Failed to fetch exchange rates', [
                'base' => $base->value,
                'error' => $e->getMessage(),
                'using_fallback' => true,
            ]);

            // Fallback to database
            $dbRates = ExchangeRate::getStoredRates($base);
            if ($dbRates !== []) {
                Cache::put($cacheKey, $dbRates, self::BACKUP_CACHE_TTL);

                return $dbRates;
            }

            // Last resort: static rates
            return $this->getStaticRates($base);
        }
    }

    /**
     * Clear exchange rate cache
     */
    public function clearCache(): void
    {
        foreach (Currency::cases() as $currency) {
            $cacheKey = self::EXCHANGE_RATE_CACHE_KEY.'_'.$currency->value;
            Cache::forget($cacheKey);
        }
    }

    /**
     * Force refresh of exchange rates from API
     */
    public function refreshRates(Currency $base): bool
    {
        try {
            // Clear cache for this base currency
            $cacheKey = self::EXCHANGE_RATE_CACHE_KEY.'_'.$base->value;
            Cache::forget($cacheKey);

            // Fetch fresh rates
            $rates = $this->fetchExchangeRates($base);

            if ($rates !== []) {
                $this->storeRatesInDatabase($base, $rates);
                Cache::put($cacheKey, $rates, self::CACHE_TTL);

                return true;
            }

            return false;
        } catch (Exception $e) {
            Log::error('Failed to refresh rates', [
                'base' => $base->value,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Check if offline mode is active
     */
    public function isOffline(): bool
    {
        return $this->forceOffline || ! $this->areApisAvailable();
    }

    /**
     * Get statistics about stored rates
     */
    public function getStoredRatesStats(Currency $base): array
    {
        $latestRate = ExchangeRate::getLatestRate($base, Currency::USD); // Just to check
        $hasFreshRates = ExchangeRate::hasFreshRates(
            $base,
            self::MAX_AGE_HOURS,
        );
        $allRates = ExchangeRate::getStoredRates($base);

        return [
            'has_fresh_rates' => $hasFreshRates,
            'total_rates' => count($allRates),
            'last_updated' => $latestRate?->fetched_at?->format('Y-m-d H:i:s'),
            'is_offline_capable' => $allRates !== [],
        ];
    }

    /**
     * Manually import rates from backup data
     */
    public function importBackupRates(Currency $base, array $rates): bool
    {
        try {
            $this->storeRatesInDatabase($base, $rates);

            // Update cache
            $cacheKey = self::EXCHANGE_RATE_CACHE_KEY.'_'.$base->value;
            Cache::put($cacheKey, $rates, self::BACKUP_CACHE_TTL);

            Log::info('Manually imported backup rates', [
                'base' => $base->value,
                'count' => count($rates),
            ]);

            return true;
        } catch (Exception $e) {
            Log::error('Failed to import backup rates', [
                'base' => $base->value,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Export rates for backup
     */
    public function exportRatesForBackup(Currency $base): array
    {
        $rates = ExchangeRate::getStoredRates($base);

        return [
            'base_currency' => $base->value,
            'rates' => $rates,
            'exported_at' => now()->toISOString(),
            'expires_at' => now()->addHours(24)->toISOString(),
        ];
    }

    /**
     * Cleanup old rates
     */
    public function cleanup(): int
    {
        return ExchangeRate::cleanupExpired();
    }

    /**
     * Convert amount and format it with target currency symbol
     */
    public function convertAndFormat(
        float $amount,
        Currency $from,
        Currency $to,
    ): string {
        $convertedAmount = $this->convert($amount, $from, $to);

        return $to->formatAmount($convertedAmount);
    }

    /**
     * Get multiple currency conversions for the same amount
     */
    public function convertToMultiple(
        float $amount,
        Currency $from,
        array $toCurrencies,
    ): array {
        $conversions = [];

        foreach ($toCurrencies as $currency) {
            if ($currency instanceof Currency) {
                $conversions[$currency->value] = [
                    'amount' => $this->convert($amount, $from, $currency),
                    'formatted' => $this->convertAndFormat(
                        $amount,
                        $from,
                        $currency,
                    ),
                ];
            }
        }

        return $conversions;
    }

    /**
     * Check if exchange rates are available for a currency
     */
    public function areRatesAvailable(Currency $currency): bool
    {
        try {
            $rates = $this->getExchangeRates($currency);

            return $rates !== [];
        } catch (Exception) {
            return false;
        }
    }

    /**
     * Get the last updated timestamp for exchange rates
     */
    public function getLastUpdated(): ?DateTimeImmutable
    {
        $cacheKey = self::EXCHANGE_RATE_CACHE_KEY.'_last_updated';

        return Cache::get($cacheKey);
    }

    /**
     * Check if APIs are available
     */
    public function areApisAvailable(): bool
    {
        try {
            // Quick test with minimal request
            $response = Http::timeout(5)->head($this->apiUrl.'USD');
            $isAvailable = $response->successful();

            Log::info('API availability check', [
                'url' => $this->apiUrl,
                'available' => $isAvailable,
            ]);

            return $isAvailable;
        } catch (Exception $e) {
            Log::warning('API availability check failed', [
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Get rate from database
     */
    private function getExchangeRateFromDatabase(
        Currency $from,
        Currency $to,
    ): ?float {
        $rate = ExchangeRate::getLatestRate($from, $to);

        if ($rate && $rate->isValid()) {
            // If rate is fresh, return it
            if ($rate->getAgeInHours() < self::MAX_AGE_HOURS) {
                return (float) $rate->rate;
            }

            // If rate is old but API fails, still use it
            try {
                $this->fetchExchangeRates($from);

                return $this->getExchangeRateFromDatabase($from, $to);
            } catch (Exception $e) {
                Log::warning('API failed, using stale rate from database', [
                    'from' => $from->value,
                    'to' => $to->value,
                    'age_hours' => $rate->getAgeInHours(),
                    'error' => $e->getMessage(),
                ]);

                return (float) $rate->rate;
            }
        }

        return null;
    }

    /**
     * Fallback to stored rate when API fails
     */
    private function getStoredRateWithFallback(
        Currency $from,
        Currency $to,
    ): float {
        // Try to get the most recent stored rate
        $rate = ExchangeRate::forPair($from, $to)
            ->latest('fetched_at')
            ->first();

        if ($rate) {
            Log::warning('Using expired rate as fallback', [
                'from' => $from->value,
                'to' => $to->value,
                'rate' => $rate->rate,
                'age_hours' => $rate->getAgeInHours(),
            ]);

            return (float) $rate->rate;
        }

        // Last resort: static rates
        $staticRates = $this->getStaticRates($from);

        return $staticRates[$to->value] ?? 1.0;
    }

    /**
     * Store rates in database for offline use
     */
    private function storeRatesInDatabase(Currency $base, array $rates): void
    {
        try {
            // Deactivate old rates for this base currency
            ExchangeRate::deactivateBase($base);

            // Store new rates
            ExchangeRate::storeBulkRates($base, $rates, 'api', 24);

            Log::info('Stored exchange rates in database', [
                'base' => $base->value,
                'count' => count($rates),
            ]);
        } catch (Exception $e) {
            Log::error('Failed to store rates in database', [
                'base' => $base->value,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Fetch exchange rates from external API
     */
    private function fetchExchangeRates(Currency $base): array
    {
        throw_if($this->forceOffline, Exception::class, 'Forced offline mode - no API calls');

        try {
            // Try to get from free API first
            $response = Http::timeout(10)->get($this->apiUrl.$base->value);

            if ($response->successful()) {
                $data = $response->json();
                $rates = $data['rates'] ?? [];

                if (! empty($rates)) {
                    Log::info('Successfully fetched rates from primary API', [
                        'base' => $base->value,
                        'count' => count($rates),
                    ]);

                    return $rates;
                }
            }

            // Fallback to European Central Bank API
            return $this->fetchFromEcbApi($base);
        } catch (RequestException $e) {
            Log::warning('Primary API request failed', [
                'base' => $base->value,
                'error' => $e->getMessage(),
            ]);

            // Fallback to European Central Bank API on error
            return $this->fetchFromEcbApi($base);
        }
    }

    /**
     * Fallback to European Central Bank API
     */
    private function fetchFromEcbApi(Currency $base): array
    {
        throw_if($this->forceOffline, Exception::class, 'Forced offline mode - no API calls');

        try {
            $response = Http::timeout(10)->get(
                'https://data.ecb.europa.eu/api/statistics/v1/data/exrates/latest',
            );

            throw_unless($response->successful(), Exception::class, 'ECB API request failed');

            $data = $response->json();
            $rates = [];

            // Parse ECB response format
            if (isset($data['dataSets'][0]['series'])) {
                $seriesData = $data['dataSets'][0]['series'];
                $dimensions =
                    $data['structure']['dimensions']['series'][0]['values'];

                foreach ($dimensions as $index => $currencyInfo) {
                    $currencyCode = $currencyInfo['id'];
                    if (
                        $currencyCode !== $base->value &&
                        isset($seriesData['0:0:'.$index][0])
                    ) {
                        $rates[$currencyCode] = $seriesData['0:0:'.$index][0];
                    }
                }
            }

            if ($rates !== []) {
                Log::info('Successfully fetched rates from ECB API', [
                    'base' => $base->value,
                    'count' => count($rates),
                ]);
            }

            // If base is EUR, use ECB rates directly
            if ($base === Currency::EUR) {
                return $rates;
            }

            // If base is not EUR, we need to convert through EUR
            return $this->convertThroughEur($base, $rates);
        } catch (RequestException $e) {
            Log::error('ECB API request failed', [
                'base' => $base->value,
                'error' => $e->getMessage(),
            ]);
            throw new Exception('All APIs failed', $e->getCode(), $e);
        }
    }

    /**
     * Convert rates through EUR as base
     */
    private function convertThroughEur(Currency $base, array $eurRates): array
    {
        $rates = [];

        // Get EUR to base rate
        $eurToBaseRate = $eurRates[$base->value] ?? 1.0;

        if ($eurToBaseRate === 0) {
            return $this->getStaticRates($base);
        }

        // Convert all EUR rates to base currency rates
        foreach ($eurRates as $currency => $eurRate) {
            if ($currency !== $base->value && $eurRate > 0) {
                $rates[$currency] = $eurRate / $eurToBaseRate;
            }
        }

        return $rates;
    }

    /**
     * Fallback static rates for basic currencies
     */
    private function getStaticRates(Currency $base): array
    {
        // These are approximate rates and should only be used as fallback
        $staticRates = [
            'USD' => [
                'EUR' => 0.92,
                'GBP' => 0.79,
                'JPY' => 149.5,
                'AUD' => 1.53,
                'CAD' => 1.36,
                'CHF' => 0.87,
                'CNY' => 7.24,
                'INR' => 83.12,
            ],
            'EUR' => [
                'USD' => 1.09,
                'GBP' => 0.86,
                'JPY' => 162.5,
                'AUD' => 1.67,
                'CAD' => 1.48,
                'CHF' => 0.95,
                'CNY' => 7.87,
                'INR' => 90.35,
            ],
            'GBP' => [
                'USD' => 1.27,
                'EUR' => 1.16,
                'JPY' => 189.2,
                'AUD' => 1.94,
                'CAD' => 1.72,
                'CHF' => 1.1,
                'CNY' => 9.17,
                'INR' => 105.18,
            ],
        ];

        return $staticRates[$base->value] ?? [];
    }
}
