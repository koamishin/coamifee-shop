<?php

declare(strict_types=1);

use App\Enums\UnitType;
use App\Services\UnitConversionService;

beforeEach(function () {
    $this->service = new UnitConversionService();
});

describe('Unit Conversion Service', function () {
    describe('Same Unit Conversions', function () {
        it('returns same value when units are identical', function () {
            expect($this->service->convert(100, UnitType::GRAMS, UnitType::GRAMS))
                ->toBe(100.0);

            expect($this->service->convert(5.5, UnitType::MILLILITERS, UnitType::MILLILITERS))
                ->toBe(5.5);

            expect($this->service->convert(10, UnitType::PIECES, UnitType::PIECES))
                ->toBe(10.0);
        });
    });

    describe('Base Units Only', function () {
        it('only accepts base units for grams', function () {
            expect($this->service->convert(1000, UnitType::GRAMS, UnitType::GRAMS))
                ->toBe(1000.0);
        });

        it('only accepts base units for milliliters', function () {
            expect($this->service->convert(1000, UnitType::MILLILITERS, UnitType::MILLILITERS))
                ->toBe(1000.0);
        });

        it('only accepts base units for pieces', function () {
            expect($this->service->convert(5, UnitType::PIECES, UnitType::PIECES))
                ->toBe(5.0);
        });
    });

    describe('Invalid Conversions', function () {
        it('throws exception when converting between weight and volume', function () {
            expect(fn () => $this->service->convert(100, UnitType::GRAMS, UnitType::MILLILITERS))
                ->toThrow(InvalidArgumentException::class);
        });

        it('throws exception when converting volume to weight', function () {
            expect(fn () => $this->service->convert(100, UnitType::MILLILITERS, UnitType::GRAMS))
                ->toThrow(InvalidArgumentException::class);
        });

        it('throws exception when converting to/from pieces', function () {
            expect(fn () => $this->service->convert(100, UnitType::PIECES, UnitType::GRAMS))
                ->toThrow(InvalidArgumentException::class);

            expect(fn () => $this->service->convert(100, UnitType::MILLILITERS, UnitType::PIECES))
                ->toThrow(InvalidArgumentException::class);
        });
    });

    describe('Can Convert Check', function () {
        it('returns true for same unit types', function () {
            expect($this->service->canConvert(UnitType::GRAMS, UnitType::GRAMS))->toBeTrue();
            expect($this->service->canConvert(UnitType::MILLILITERS, UnitType::MILLILITERS))->toBeTrue();
            expect($this->service->canConvert(UnitType::PIECES, UnitType::PIECES))->toBeTrue();
        });

        it('returns false for different unit types', function () {
            expect($this->service->canConvert(UnitType::GRAMS, UnitType::MILLILITERS))->toBeFalse();
            expect($this->service->canConvert(UnitType::PIECES, UnitType::GRAMS))->toBeFalse();
            expect($this->service->canConvert(UnitType::MILLILITERS, UnitType::PIECES))->toBeFalse();
        });
    });

    describe('Normalize to Inventory Unit', function () {
        it('normalizes when units are the same', function () {
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
