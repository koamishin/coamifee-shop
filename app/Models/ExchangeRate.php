<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\Currency;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use ValueError;

/**
 * @property int $id
 * @property string $base_currency
 * @property string $target_currency
 * @property float $rate
 * @property array|null $rates_data
 * @property Carbon $fetched_at
 * @property Carbon $expires_at
 * @property string $source
 * @property bool $is_active
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
final class ExchangeRate extends Model
{
    use HasFactory;

    protected $fillable = [
        'base_currency',
        'target_currency',
        'rate',
        'rates_data',
        'fetched_at',
        'expires_at',
        'source',
        'is_active',
    ];

    protected $casts = [
        'rate' => 'decimal:8',
        'rates_data' => 'array',
        'fetched_at' => 'datetime',
        'expires_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    /**
     * Get latest rate for a currency pair
     */
    public static function getLatestRate(
        Currency $base,
        Currency $target,
    ): ?self {
        return self::active()
            ->notExpired()
            ->forPair($base, $target)
            ->latest('fetched_at')
            ->first();
    }

    /**
     * Store new exchange rate
     */
    public static function storeRate(
        Currency $base,
        Currency $target,
        float $rate,
        string $source = 'api',
        ?array $allRates = null,
        int $hoursToExpire = 24,
    ): self {
        return self::create([
            'base_currency' => $base->value,
            'target_currency' => $target->value,
            'rate' => $rate,
            'rates_data' => $allRates,
            'fetched_at' => now(),
            'expires_at' => now()->addHours($hoursToExpire),
            'source' => $source,
            'is_active' => true,
        ]);
    }

    /**
     * Store bulk rates from a base currency
     */
    public static function storeBulkRates(
        Currency $base,
        array $rates,
        string $source = 'api',
        int $hoursToExpire = 24,
    ): void {
        $now = now();
        $expiresAt = $now->copy()->addHours($hoursToExpire);

        foreach ($rates as $targetCurrency => $rate) {
            if (is_numeric($rate) && $targetCurrency !== $base->value) {
                try {
                    $targetCurrencyEnum = Currency::from($targetCurrency);

                    self::create([
                        'base_currency' => $base->value,
                        'target_currency' => $targetCurrencyEnum->value,
                        'rate' => (float) $rate,
                        'rates_data' => $rates,
                        'fetched_at' => $now,
                        'expires_at' => $expiresAt,
                        'source' => $source,
                        'is_active' => true,
                    ]);
                } catch (ValueError $e) {
                    // Skip unsupported currency codes
                    continue;
                }
            }
        }
    }

    /**
     * Get stored rates for a base currency
     */
    public static function getStoredRates(Currency $base): array
    {
        return self::active()
            ->notExpired()
            ->where('base_currency', $base->value)
            ->latest('fetched_at')
            ->get()
            ->keyBy('target_currency')
            ->map(fn ($rate) => (float) $rate->rate)
            ->toArray();
    }

    /**
     * Cleanup old expired rates
     */
    public static function cleanupExpired(): int
    {
        return self::where('expires_at', '<', now())->delete();
    }

    /**
     * Deactivate all rates for a base currency
     */
    public static function deactivateBase(Currency $base): int
    {
        return self::where('base_currency', $base->value)->update([
            'is_active' => false,
        ]);
    }

    /**
     * Check if we have fresh rates for a base currency
     */
    public static function hasFreshRates(
        Currency $base,
        int $maxAgeHours = 6,
    ): bool {
        return self::active()
            ->where('base_currency', $base->value)
            ->where('fetched_at', '>', now()->subHours($maxAgeHours))
            ->exists();
    }

    /**
     * Scope for active rates
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for non-expired rates
     */
    public function scopeNotExpired($query)
    {
        return $query->where('expires_at', '>', now());
    }

    /**
     * Scope for a specific currency pair
     */
    public function scopeForPair($query, Currency $base, Currency $target)
    {
        return $query
            ->where('base_currency', $base->value)
            ->where('target_currency', $target->value);
    }

    /**
     * Get rates data with full currency information
     */
    public function getFormattedRate(): string
    {
        try {
            $base = Currency::from($this->base_currency);
            $target = Currency::from($this->target_currency);

            return "1 {$base->value} = {$this->rate} {$target->value}";
        } catch (ValueError $e) {
            return "1 {$this->base_currency} = {$this->rate} {$this->target_currency}";
        }
    }

    /**
     * Check if rate is still valid
     */
    public function isValid(): bool
    {
        return $this->is_active && $this->expires_at->isFuture();
    }

    /**
     * Get age of the rate in hours
     */
    public function getAgeInHours(): float
    {
        return $this->fetched_at->diffInHours(now());
    }
}
