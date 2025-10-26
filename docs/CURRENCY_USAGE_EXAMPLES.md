# Currency Enum & CurrencyConverter Usage Examples

This document provides examples of how to use the Currency enum and CurrencyConverter service in your Laravel application.

## Currency Enum Usage

### Basic Currency Information

```php
use App\Enums\Currency;

// Get basic currency information
$usd = Currency::USD;
echo $usd->value;           // Output: USD
echo $usd->getName();       // Output: US Dollar
echo $usd->getSymbol();     // Output: $
echo $usd->getDecimals();   // Output: 2

// Format amounts
echo $usd->formatAmount(123.45);  // Output: $123.45
echo Currency::EUR->formatAmount(123.45);  // Output: 123.45€
echo Currency::JPY->formatAmount(123);     // Output: ¥123 (no decimals)
```

### Working with Select Options

```php
// Get all currencies as select options
$options = Currency::getSelectOptions();
// Result: ['USD' => 'US Dollar (USD)', 'EUR' => 'Euro (EUR)', ...]

// Get only common currencies
$common = Currency::getCommon();
// Result: [Currency::USD, Currency::EUR, Currency::GBP, ...]

// Validate currency codes
if (Currency::isValid('USD')) {
    echo "Valid currency code";
}
```

## CurrencyConverter Service Usage

### Basic Currency Conversion

```php
use App\Services\CurrencyConverter;
use App\Enums\Currency;

$converter = new CurrencyConverter();

// Convert between currencies
$amount = 100.00;
$converted = $converter->convert($amount, Currency::USD, Currency::EUR);
echo $converted;  // ~92.00 (depending on current rates)

// Convert and format in one call
$formatted = $converter->convertAndFormat(100.00, Currency::USD, Currency::EUR);
echo $formatted;  // ~92.00€
```

### Getting Exchange Rates

```php
// Get specific exchange rate
$rate = $converter->getExchangeRate(Currency::USD, Currency::EUR);
echo $rate;  // ~0.92

// Get all rates for a base currency
$rates = $converter->getExchangeRates(Currency::USD);
// Result: ['EUR' => 0.92, 'GBP' => 0.79, 'JPY' => 149.50, ...]
```

### Multiple Conversions

```php
// Convert to multiple currencies at once
$amount = 100.00;
$currencies = [Currency::EUR, Currency::GBP, Currency::JPY];
$conversions = $converter->convertToMultiple($amount, Currency::USD, $currencies);

foreach ($conversions as $code => $data) {
    echo "{$code}: {$data['formatted']}\n";
}
// Output:
// EUR: 92.00€
// GBP: 79.00£
// JPY: 14950¥
```

### Cache Management

```php
// Clear exchange rate cache
$converter->clearCache();

// Check if rates are available for a currency
if ($converter->areRatesAvailable(Currency::EUR)) {
    echo "Rates available for EUR";
}

// Get last updated timestamp
$lastUpdated = $converter->getLastUpdated();
```

## Real-World Examples

### E-commerce Product Pricing

```php
class ProductController extends Controller
{
    public function show(Product $product, CurrencyConverter $converter, Request $request)
    {
        $userCurrency = Currency::tryFrom($request->get('currency', 'USD')) 
            ?? Currency::USD;
        
        $convertedPrice = $converter->convert(
            $product->price, 
            Currency::USD, 
            $userCurrency
        );
        
        $formattedPrice = $userCurrency->formatAmount($convertedPrice);
        
        return view('product.show', [
            'product' => $product,
            'price' => $formattedPrice,
            'currency' => $userCurrency,
        ]);
    }
}
```

### Service Class for Multi-Currency Operations

```php
class PricingService
{
    public function __construct(
        private CurrencyConverter $converter
    ) {}
    
    public function convertOrderTotal(Order $order, Currency $targetCurrency): array
    {
        $originalCurrency = Currency::from($order->currency);
        $total = $order->total_amount;
        
        return [
            'original_amount' => $originalCurrency->formatAmount($total),
            'converted_amount' => $this->converter->convertAndFormat(
                $total, 
                $originalCurrency, 
                $targetCurrency
            ),
            'exchange_rate' => $this->converter->getExchangeRate(
                $originalCurrency, 
                $targetCurrency
            ),
        ];
    }
    
    public function getAvailableCurrencies(): array
    {
        return [
            'common' => Currency::getCommon(),
            'all' => Currency::cases(),
        ];
    }
}
```

### Livewire Component for Currency Switcher

```php
use Livewire\Component;

class CurrencySwitcher extends Component
{
    public string $selectedCurrency = 'USD';
    public float $amount = 100.00;
    public array $conversions = [];
    
    public function updatedSelectedCurrency()
    {
        $this->loadConversions();
    }
    
    public function mount()
    {
        $this->loadConversions();
    }
    
    private function loadConversions()
    {
        $converter = app(CurrencyConverter::class);
        $from = Currency::from($this->selectedCurrency);
        
        $this->conversions = $converter->convertToMultiple(
            $this->amount,
            $from,
            Currency::getCommon()
        );
    }
    
    public function render()
    {
        return view('livewire.currency-switcher', [
            'currencies' => Currency::getSelectOptions(),
        ]);
    }
}
```

## Configuration

### Environment Variables

Add these to your `.env` file to configure exchange rate API:

```env
# Exchange Rate API (optional - fallback APIs will be used if not configured)
EXCHANGE_RATE_API_KEY=your_api_key_here
EXCHANGE_RATE_API_URL=https://api.exchangerate-api.com/v4/latest/
```

### API Integration

The CurrencyConverter automatically falls back through multiple sources:

1. Primary: ExchangeRate-API (requires API key)
2. Fallback: European Central Bank API (free)
3. Emergency: Static rates (for basic currencies)

## Testing

The package includes comprehensive tests. Run them with:

```bash
php artisan test tests/Feature/CurrencyConverterTest.php
```

## Best Practices

1. **Always validate currency codes** before using them:
   ```php
   $currency = Currency::isValid($input) ? Currency::from($input) : Currency::USD;
   ```

2. **Cache currency conversions** for frequently used amounts:
   ```php
   Cache::remember("conversion_{$from}_{$to}_{$amount}", 3600, function() {
       return $converter->convert($amount, $from, $to);
   });
   ```

3. **Handle API failures gracefully**:
   ```php
   try {
       $rate = $converter->getExchangeRate($from, $to);
   } catch (\Exception $e) {
       // Log error and use fallback rate
       $rate = 1.0;
   }
   ```

4. **Use proper formatting for display**:
   ```php
   // For forms/inputs
   $rawAmount = $converter->convert($amount, $from, $to);
   
   // For display
   $formattedAmount = $converter->convertAndFormat($amount, $from, $to);
   ```
