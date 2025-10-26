<?php

declare(strict_types=1);

use App\Enums\Currency;
use App\Services\CurrencyConverter;

it('can create currency enum values', function (): void {
    expect(Currency::USD->value)->toBe('USD');
    expect(Currency::EUR->value)->toBe('EUR');
    expect(Currency::GBP->value)->toBe('GBP');
});

it('can get currency names', function (): void {
    expect(Currency::USD->getName())->toBe('US Dollar');
    expect(Currency::EUR->getName())->toBe('Euro');
    expect(Currency::GBP->getName())->toBe('British Pound');
    expect(Currency::JPY->getName())->toBe('Japanese Yen');
});

it('can get currency symbols', function (): void {
    expect(Currency::USD->getSymbol())->toBe('$');
    expect(Currency::EUR->getSymbol())->toBe('€');
    expect(Currency::GBP->getSymbol())->toBe('£');
    expect(Currency::JPY->getSymbol())->toBe('¥');
    expect(Currency::AUD->getSymbol())->toBe('A$');
    expect(Currency::CAD->getSymbol())->toBe('C$');
});

it('can get currency decimal places', function (): void {
    expect(Currency::USD->getDecimals())->toBe(2);
    expect(Currency::EUR->getDecimals())->toBe(2);
    expect(Currency::JPY->getDecimals())->toBe(0);
    expect(Currency::KRW->getDecimals())->toBe(0);
    expect(Currency::VND->getDecimals())->toBe(0);
});

it('can format currency amounts', function (): void {
    expect(Currency::USD->formatAmount(123.45))->toBe('$123.45');
    expect(Currency::EUR->formatAmount(123.45))->toBe('123.45€');
    expect(Currency::GBP->formatAmount(123.45))->toBe('£123.45');
    expect(Currency::JPY->formatAmount(123))->toBe('¥123');
    expect(Currency::INR->formatAmount(123.45))->toBe('₹123.45');
    expect(Currency::BRL->formatAmount(123.45))->toBe('R$ 123.45');
});

it('can get select options', function (): void {
    $options = Currency::getSelectOptions();

    expect($options)->toBeArray();
    expect($options)->toHaveKey('USD');
    expect($options['USD'])->toBe('US Dollar (USD)');
    expect($options['EUR'])->toBe('Euro (EUR)');
});

it('can get common currencies', function (): void {
    $common = Currency::getCommon();

    expect($common)->toBeArray();
    expect($common)->toHaveCount(8);
    expect($common)->toContain(Currency::USD);
    expect($common)->toContain(Currency::EUR);
    expect($common)->toContain(Currency::GBP);
    expect($common)->toContain(Currency::JPY);
});

it('can validate currency codes', function (): void {
    expect(Currency::isValid('USD'))->toBeTrue();
    expect(Currency::isValid('EUR'))->toBeTrue();
    expect(Currency::isValid('INVALID'))->toBeFalse();
    expect(Currency::isValid('XYZ'))->toBeFalse();
});

it('can create currency converter', function (): void {
    $converter = new CurrencyConverter();
    expect($converter)->toBeInstanceOf(CurrencyConverter::class);
});

it('returns same amount when converting to same currency', function (): void {
    $converter = new CurrencyConverter();

    $amount = 100.5;
    $result = $converter->convert($amount, Currency::USD, Currency::USD);

    expect($result)->toBe($amount);
});

it('returns rate 1.0 for same currency', function (): void {
    $converter = new CurrencyConverter();

    $rate = $converter->getExchangeRate(Currency::EUR, Currency::EUR);

    expect($rate)->toBe(1.0);
});

it('can convert and format with target currency', function (): void {
    $converter = new class extends CurrencyConverter
    {
        public function convert(
            float $amount,
            Currency $from,
            Currency $to,
        ): float {
            return match ([$from->value, $to->value]) {
                ['USD', 'EUR'] => 92.0,
                ['USD', 'GBP'] => 79.0,
                default => $amount,
            };
        }
    };

    $result = $converter->convertAndFormat(100.0, Currency::USD, Currency::EUR);

    expect($result)->toBe('92.00€');
});

it('can convert to multiple currencies', function (): void {
    $converter = new class extends CurrencyConverter
    {
        public function convert(
            float $amount,
            Currency $from,
            Currency $to,
        ): float {
            return match ([$from->value, $to->value]) {
                ['USD', 'EUR'] => 92.0,
                ['USD', 'GBP'] => 79.0,
                ['USD', 'JPY'] => 14950,
                default => $amount,
            };
        }
    };

    $results = $converter->convertToMultiple(100.0, Currency::USD, [
        Currency::EUR,
        Currency::GBP,
        Currency::JPY,
    ]);

    expect($results)->toHaveKey('EUR');
    expect($results)->toHaveKey('GBP');
    expect($results)->toHaveKey('JPY');
    expect($results['EUR']['amount'])->toBe(92.0);
    expect($results['EUR']['formatted'])->toBe('92.00€');
    expect($results['GBP']['amount'])->toBe(79.0);
    expect($results['JPY']['amount'])->toBe(14950.0);
});

it('can clear cache', function (): void {
    $converter = new CurrencyConverter();

    expect($converter->clearCache(...))->not->toThrow(Exception::class);
});

it('handles zero decimal currencies correctly', function (): void {
    expect(Currency::JPY->formatAmount(123))->toBe('¥123');
    expect(Currency::KRW->formatAmount(50000))->toBe('₩50000');
    expect(Currency::VND->formatAmount(1000000))->toBe('₫1000000');
});

it(
    'provides accurate currency information for all supported currencies',
    function (): void {
        $currencies = Currency::cases();

        foreach ($currencies as $currency) {
            expect($currency->getName())->toBeString();
            expect($currency->getName())->not->toBeEmpty();

            expect($currency->getSymbol())->toBeString();
            expect($currency->getSymbol())->not->toBeEmpty();

            expect($currency->getDecimals())->toBeIn([0, 2]);

            $formatted = $currency->formatAmount(123.45);
            expect($formatted)->toBeString();
            expect($formatted)->not->toBeEmpty();
        }
    },
);
