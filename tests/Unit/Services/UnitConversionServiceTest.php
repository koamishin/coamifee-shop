<?php

declare(strict_types=1);

use App\Enums\UnitType;
use App\Services\UnitConversionService;

beforeEach(function (): void {
    $this->service = new UnitConversionService();
});

describe('Unit Conversion Service', function (): void {
    describe('Same Unit Conversions', function (): void {
        it('returns same value when units are identical', function (): void {
            expect($this->service->convert(100, UnitType::GRAMS, UnitType::GRAMS))
                ->toBe(100.0);

            expect($this->service->convert(5.5, UnitType::MILLILITERS, UnitType::MILLILITERS))
                ->toBe(5.5);

            expect($this->service->convert(10, UnitType::PIECES, UnitType::PIECES))
                ->toBe(10.0);
        });
    });

    describe('Base Units Only', function (): void {
        it('only accepts base units for grams', function (): void {
            expect($this->service->convert(1000, UnitType::GRAMS, UnitType::GRAMS))
                ->toBe(1000.0);
        });

        it('only accepts base units for milliliters', function (): void {
            expect($this->service->convert(1000, UnitType::MILLILITERS, UnitType::MILLILITERS))
                ->toBe(1000.0);
        });

        it('only accepts base units for pieces', function (): void {
            expect($this->service->convert(5, UnitType::PIECES, UnitType::PIECES))
                ->toBe(5.0);
        });
    });

    describe('Invalid Conversions', function (): void {
        it('throws exception when converting between weight and volume', function (): void {
            expect(fn () => $this->service->convert(100, UnitType::GRAMS, UnitType::MILLILITERS))
                ->toThrow(InvalidArgumentException::class);
        });

        it('throws exception when converting volume to weight', function (): void {
            expect(fn () => $this->service->convert(100, UnitType::MILLILITERS, UnitType::GRAMS))
                ->toThrow(InvalidArgumentException::class);
        });

        it('throws exception when converting to/from pieces', function (): void {
            expect(fn () => $this->service->convert(100, UnitType::PIECES, UnitType::GRAMS))
                ->toThrow(InvalidArgumentException::class);

            expect(fn () => $this->service->convert(100, UnitType::MILLILITERS, UnitType::PIECES))
                ->toThrow(InvalidArgumentException::class);
        });
    });

    describe('Can Convert Check', function (): void {
        it('returns true for same unit types', function (): void {
            expect($this->service->canConvert(UnitType::GRAMS, UnitType::GRAMS))->toBeTrue();
            expect($this->service->canConvert(UnitType::MILLILITERS, UnitType::MILLILITERS))->toBeTrue();
            expect($this->service->canConvert(UnitType::PIECES, UnitType::PIECES))->toBeTrue();
        });

        it('returns false for different unit types', function (): void {
            expect($this->service->canConvert(UnitType::GRAMS, UnitType::MILLILITERS))->toBeFalse();
            expect($this->service->canConvert(UnitType::PIECES, UnitType::GRAMS))->toBeFalse();
            expect($this->service->canConvert(UnitType::MILLILITERS, UnitType::PIECES))->toBeFalse();
        });
    });

    describe('Normalize to Inventory Unit', function (): void {
        it('normalizes when units are the same', function (): void {
            // All units are base units now
            expect($this->service->normalizeToInventoryUnit(250, UnitType::MILLILITERS, UnitType::MILLILITERS))
                ->toBe(250.0);

            expect($this->service->normalizeToInventoryUnit(500, UnitType::GRAMS, UnitType::GRAMS))
                ->toBe(500.0);

            expect($this->service->normalizeToInventoryUnit(5, UnitType::PIECES, UnitType::PIECES))
                ->toBe(5.0);
        });
    });
});
