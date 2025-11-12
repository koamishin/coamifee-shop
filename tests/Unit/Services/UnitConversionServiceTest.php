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

            expect($this->service->convert(5.5, UnitType::LITERS, UnitType::LITERS))
                ->toBe(5.5);
        });
    });

    describe('Volume Conversions', function () {
        it('converts milliliters to liters correctly', function () {
            expect($this->service->convert(1000, UnitType::MILLILITERS, UnitType::LITERS))
                ->toBe(1.0);

            expect($this->service->convert(250, UnitType::MILLILITERS, UnitType::LITERS))
                ->toBe(0.25);

            expect($this->service->convert(500, UnitType::MILLILITERS, UnitType::LITERS))
                ->toBe(0.5);
        });

        it('converts liters to milliliters correctly', function () {
            expect($this->service->convert(1, UnitType::LITERS, UnitType::MILLILITERS))
                ->toBe(1000.0);

            expect($this->service->convert(0.25, UnitType::LITERS, UnitType::MILLILITERS))
                ->toBe(250.0);

            expect($this->service->convert(6, UnitType::LITERS, UnitType::MILLILITERS))
                ->toBe(6000.0);
        });
    });

    describe('Weight Conversions', function () {
        it('converts grams to kilograms correctly', function () {
            expect($this->service->convert(1000, UnitType::GRAMS, UnitType::KILOGRAMS))
                ->toBe(1.0);

            expect($this->service->convert(500, UnitType::GRAMS, UnitType::KILOGRAMS))
                ->toBe(0.5);

            expect($this->service->convert(250, UnitType::GRAMS, UnitType::KILOGRAMS))
                ->toBe(0.25);
        });

        it('converts kilograms to grams correctly', function () {
            expect($this->service->convert(1, UnitType::KILOGRAMS, UnitType::GRAMS))
                ->toBe(1000.0);

            expect($this->service->convert(0.5, UnitType::KILOGRAMS, UnitType::GRAMS))
                ->toBe(500.0);

            expect($this->service->convert(2.5, UnitType::KILOGRAMS, UnitType::GRAMS))
                ->toBe(2500.0);
        });
    });

    describe('Invalid Conversions', function () {
        it('throws exception when converting between weight and volume', function () {
            expect(fn () => $this->service->convert(100, UnitType::GRAMS, UnitType::LITERS))
                ->toThrow(InvalidArgumentException::class);
        });

        it('throws exception when converting volume to weight', function () {
            expect(fn () => $this->service->convert(100, UnitType::MILLILITERS, UnitType::KILOGRAMS))
                ->toThrow(InvalidArgumentException::class);
        });

        it('throws exception when converting to/from pieces', function () {
            expect(fn () => $this->service->convert(100, UnitType::PIECES, UnitType::GRAMS))
                ->toThrow(InvalidArgumentException::class);

            expect(fn () => $this->service->convert(100, UnitType::LITERS, UnitType::PIECES))
                ->toThrow(InvalidArgumentException::class);
        });
    });

    describe('Can Convert Check', function () {
        it('returns true for same unit types', function () {
            expect($this->service->canConvert(UnitType::GRAMS, UnitType::GRAMS))->toBeTrue();
            expect($this->service->canConvert(UnitType::LITERS, UnitType::LITERS))->toBeTrue();
        });

        it('returns true for convertible weight units', function () {
            expect($this->service->canConvert(UnitType::GRAMS, UnitType::KILOGRAMS))->toBeTrue();
            expect($this->service->canConvert(UnitType::KILOGRAMS, UnitType::GRAMS))->toBeTrue();
        });

        it('returns true for convertible volume units', function () {
            expect($this->service->canConvert(UnitType::MILLILITERS, UnitType::LITERS))->toBeTrue();
            expect($this->service->canConvert(UnitType::LITERS, UnitType::MILLILITERS))->toBeTrue();
        });

        it('returns false for incompatible unit types', function () {
            expect($this->service->canConvert(UnitType::GRAMS, UnitType::LITERS))->toBeFalse();
            expect($this->service->canConvert(UnitType::PIECES, UnitType::GRAMS))->toBeFalse();
        });
    });

    describe('Normalize to Inventory Unit', function () {
        it('normalizes recipe quantity to inventory unit', function () {
            // 250ml recipe using 6L inventory
            expect($this->service->normalizeToInventoryUnit(250, UnitType::MILLILITERS, UnitType::LITERS))
                ->toBe(0.25);

            // 500g recipe using 2kg inventory
            expect($this->service->normalizeToInventoryUnit(500, UnitType::GRAMS, UnitType::KILOGRAMS))
                ->toBe(0.5);
        });
    });
});
