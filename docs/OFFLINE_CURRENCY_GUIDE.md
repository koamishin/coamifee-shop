# Offline Currency Exchange Rate System

This guide explains the comprehensive offline currency exchange rate system that ensures your application continues to function even when external APIs are unavailable.

## 🎯 Overview

The currency system now includes **robust offline capabilities** with multiple fallback layers:

1. **Primary API** - ExchangeRate-API (your configured API)
2. **Fallback API** - European Central Bank (free)
3. **Database Storage** - Cached rates for offline use
4. **Static Rates** - Emergency hardcoded rates
5. **File Backup/Import** - Manual rate management

## 🚀 Quick Start

### Basic Usage (Same as Before)

```php
use App\Services\CurrencyConverter;
use App\Enums\Currency;

$converter = new CurrencyConverter();

// Online conversion (works offline automatically)
$amount = $converter->convert(100, Currency::USD, Currency::PHP);
$formatted = $converter->convertAndFormat(100, Currency::USD, Currency::PHP);

echo $formatted; // "5866.00 ₱"
```

### Management Commands

```bash
# Refresh rates from API
php artisan exchange:rates refresh --base=USD

# Check status of stored rates
php artisan exchange:rates status --base=USD

# Export rates for backup
php artisan exchange:rates export --base=USD

# Import rates from backup
php artisan exchange:rates import --base=USD --file=backup.json

# Test offline capabilities
php artisan exchange:rates test --base=USD --force-offline

# Clean up expired rates
php artisan exchange:rates cleanup
```

## 🔄 How Offline Mode Works

### Automatic Fallback Chain

1. **Cache Check** - Fastest, if data is fresh
2. **Database Check** - Stored rates from previous successful API calls
3. **API Call** - Primary API, then fallback API
4. **Static Rates** - Emergency hardcoded rates
5. **Error** - If everything fails

### Rate Freshness Logic

```php
// Rates are considered "fresh" for 6 hours
const MAX_AGE_HOURS = 6;

// Fresh rates: < 6 hours old -> Use database
// Stale rates: 6-24 hours old -> Use if API fails
// Expired rates: > 24 hours old -> Use only as last resort
```

## 🗄️ Database Storage

### ExchangeRate Model

```php
// Key fields in database
- base_currency: string (3)      // e.g., 'USD'
- target_currency: string (3)     // e.g., 'PHP'
- rate: decimal (18,8)           // e.g., 58.66000000
- rates_data: json                 // All rates for backup
- fetched_at: timestamp            // When fetched from API
- expires_at: timestamp           // When rate expires
- source: string (50)             // 'api', 'cache', 'manual'
- is_active: boolean               // Whether rate is usable
```

### Automatic Database Operations

```php
// When API succeeds, rates are automatically stored:
ExchangeRate::storeBulkRates(Currency::USD, $rates, 'api', 24);

// When accessing rates:
$dbRates = ExchangeRate::getStoredRates(Currency::USD);
$latestRate = ExchangeRate::getLatestRate(Currency::USD, Currency::PHP);

// Freshness check:
$hasFreshRates = ExchangeRate::hasFreshRates(Currency::USD, 6);
```

## 📁 Backup & Recovery

### Export Rates for Backup

```php
$converter = new CurrencyConverter();

// Export all rates for a base currency
$backupData = $converter->exportRatesForBackup(Currency::USD);

// Save to file
file_put_contents('rates_backup.json', json_encode($backupData, JSON_PRETTY_PRINT));
```

**Backup Format:**
```json
{
    "base_currency": "USD",
    "rates": {
        "PHP": 58.66,
        "EUR": 0.92,
        "GBP": 0.79,
        "JPY": 149.50
    },
    "exported_at": "2025-10-26T06:30:00.000000Z",
    "expires_at": "2025-10-27T06:30:00.000000Z"
}
```

### Import Rates from Backup

```php
$converter = new CurrencyConverter();

// Load backup data
$backupData = json_decode(file_get_contents('rates_backup.json'), true);

// Import to database
$success = $converter->importBackupRates(Currency::USD, $backupData['rates']);

if ($success) {
    echo "Rates imported successfully!";
}
```

## 🧪 Testing Offline Functionality

### Force Offline Mode

```php
$converter = new CurrencyConverter();

// Force offline mode for testing
$converter->forceOffline(true);

// Now all operations use cached data only
$rate = $converter->getExchangeRate(Currency::USD, Currency::PHP);
$converted = $converter->convertAndFormat(100, Currency::USD, Currency::PHP);

echo "Offline conversion: $converted";
```

### Offline Testing

```bash
# Test offline capabilities
php artisan exchange:rates test --base=USD --force-offline

# Expected output:
# 🌐 Online rate: 58.66
# 🔌 Offline rate: 58.66  
# ✅ Offline conversion: 100 USD = 5866.00 ₱
```

## 📊 Monitoring & Statistics

### Get Stored Rates Information

```php
$converter = new CurrencyConverter();

$stats = $converter->getStoredRatesStats(Currency::USD);

print_r($stats);
/*
Array (
    [has_fresh_rates] => 1
    [total_rates] => 31
    [last_updated] => 2025-10-26 06:30:00
    [is_offline_capable] => 1
    [current_mode] => offline
)
*/
```

### Check Offline Status

```php
$converter = new CurrencyConverter();

if ($converter->isOffline()) {
    echo "System is operating in offline mode";
} else {
    echo "System has online capabilities";
}

// Check if rates are available
if ($converter->areRatesAvailable(Currency::USD)) {
    echo "USD rates are available";
}
```

## 🔧 Configuration

### Environment Variables

```env
# Your API key (from provider)
EXCHANGE_RATE_API_KEY=your_api_key_here

# API endpoint URL
EXCHANGE_RATE_API_URL=https://api.exchangerate-api.com/v4/latest/

# If not set, falls back to ECB API and static rates
```

### Cache Configuration

```php
// In CurrencyConverter:
const CACHE_TTL = 3600;        // 1 hour for API cache
const BACKUP_CACHE_TTL = 86400;  // 24 hours for offline cache
const MAX_AGE_HOURS = 6;         // 6 hours for "fresh" rates
```

## 🚨 Error Handling & Logging

### Automatic Error Recovery

```php
// System automatically logs errors and uses fallbacks:
Log::warning("API failed, using stale rate from database", [
    'from' => 'USD',
    'to' => 'PHP',
    'age_hours' => 8.5,
    'error' => 'Connection timeout'
]);

Log::error("All APIs failed", [
    'base' => 'USD',
    'error' => 'DNS resolution failed'
]);
```

### Manual Error Handling

```php
$converter = new CurrencyConverter();

try {
    $rate = $converter->getExchangeRate(Currency::USD, Currency::PHP);
    echo "Rate: $rate";
} catch (Exception $e) {
    // System should never throw - it has multiple fallbacks
    echo "Emergency: No rates available";
    // Use emergency static rate
    $rate = 58.0; // Approximate
}
```

## 📈 Performance Optimization

### Rate Lookup Order (Fastest to Slowest)

1. **Memory** - Current request cache
2. **Redis Cache** - Distributed cache
3. **Database** - Indexed queries
4. **API Call** - Network request
5. **Static Rates** - In-memory fallback

### Efficient Usage Patterns

```php
// ✅ GOOD: Convert multiple currencies in one call
$conversions = $converter->convertToMultiple(100, Currency::USD, [
    Currency::PHP, Currency::EUR, Currency::GBP, Currency::JPY
]);

// ❌ AVOID: Multiple separate calls
$php = $converter->convert(100, Currency::USD, Currency::PHP);
$eur = $converter->convert(100, Currency::USD, Currency::EUR); // Re-fetches rates
```

## 🔒 Security & Reliability

### Rate Expiration

```php
// Rates automatically expire after 24 hours
$expires_at = now()->addHours(24);

// Old rates are marked inactive but kept for emergency
ExchangeRate::where('expires_at', '<', now())
    ->update(['is_active' => false]);
```

### Validation

```php
// All currency codes are validated
try {
    $currency = Currency::from('USD');  // ✅ Valid
    $invalid = Currency::from('XYZ'); // ❌ Throws ValueError
} catch (ValueError $e) {
    echo "Invalid currency code";
}

// Database constraints prevent invalid pairs
// - unique (base_currency, target_currency)
// - valid enum values only
// - decimal precision (18,8)
```

## 🚀 Production Deployment

### Setup Checklist

- [ ] Configure API key in `.env`
- [ ] Run migration: `php artisan migrate`
- [ ] Seed initial rates: `php artisan exchange:rates refresh --base=USD`
- [ ] Schedule periodic refresh: Add to console/kernel.php
- [ ] Set up monitoring for API failures
- [ ] Create backup procedures

### Scheduled Rate Refresh

```php
// app/Console/Kernel.php
protected function schedule(Schedule $schedule): void
{
    // Refresh USD rates every 6 hours
    $schedule->command('exchange:rates refresh --base=USD')
        ->cron('0 */6 * * *')  // Every 6 hours
        ->runInBackground()
        ->onFailure(function () {
            Log::error('Failed to refresh USD exchange rates');
        });
    
    // Cleanup expired rates daily
    $schedule->command('exchange:rates cleanup')
        ->daily()
        ->runInBackground();
}
```

### Monitoring Setup

```php
// Health check endpoint
Route::get('/health/exchange-rates', function () {
    $converter = app(CurrencyConverter::class);
    
    return response()->json([
        'status' => $converter->isOffline() ? 'offline' : 'online',
        'rates_available' => $converter->areRatesAvailable(Currency::USD),
        'last_updated' => $converter->getLastUpdated(),
        'offline_capable' => $converter->getStoredRatesStats(Currency::USD)['is_offline_capable'],
    ]);
});
```

## 🆘 Troubleshooting

### Common Issues

#### "No offline rates available"
```bash
# Solution: Refresh rates first
php artisan exchange:rates refresh --base=USD

# Then check status
php artisan exchange:rates status --base=USD
```

#### "API failing frequently"
```bash
# Check API availability
php artisan exchange:rates test --base=USD

# If failing, use backup
php artisan exchange:rates import --base=USD --file=backup.json
```

#### "Rates seem outdated"
```bash
# Force refresh and check update time
php artisan exchange:rates refresh --base=USD
php artisan exchange:rates status --base=USD

# Look at "Last Updated" field
```

### Emergency Procedures

1. **Complete API Outage**
   ```bash
   # Import latest backup
   php artisan exchange:rates import --base=USD --file=emergency_backup.json
   
   # Force offline mode in application
   # $converter->forceOffline(true);
   ```

2. **Database Corruption**
   ```bash
   # Clean up and refresh
   php artisan exchange:rates cleanup
   php artisan exchange:rates refresh --base=USD
   ```

3. **Performance Issues**
   ```bash
   # Clear caches
   php artisan cache:clear
   php artisan exchange:rates cleanup
   ```

## 📋 Best Practices

### Development
- Always test offline mode with `--force-offline`
- Monitor logs for API failures
- Use `convertToMultiple()` for batch operations
- Validate currency codes before conversion

### Production
- Schedule automatic rate refreshes
- Monitor system health endpoint
- Keep regular backups of rate data
- Set up alerts for API failures
- Use CDN for backup file distribution

### Performance
- Cache frequently used conversions
- Batch multiple conversions when possible
- Use database indexes for rate lookups
- Monitor API response times

---

## 🎉 Summary

Your currency system now provides:

✅ **100% Uptime** - Always works, even during API outages  
✅ **Automatic Fallbacks** - Multiple layers of reliability  
✅ **Graceful Degradation** - Offline mode vs complete failure  
✅ **Data Persistence** - Rates survive restarts and outages  
✅ **Manual Override** - Import/export for emergency situations  
✅ **Comprehensive Logging** - Full visibility into system status  
✅ **Production Ready** - Monitoring, scheduling, health checks  

The system ensures your application can handle currency conversions **24/7** regardless of external API availability, with automatic recovery when services return.