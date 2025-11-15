<?php

declare(strict_types=1);

use App\Enums\BeverageVariant;

test('beverage variant enum has correct cases', function () {
    $cases = BeverageVariant::cases();

    expect($cases)->toHaveCount(2);
    expect(BeverageVariant::HOT->value)->toBe('Hot');
    expect(BeverageVariant::COLD->value)->toBe('Cold');
});

test('beverage variant enum returns correct labels', function () {
    expect(BeverageVariant::HOT->getLabel())->toBe('Hot');
    expect(BeverageVariant::COLD->getLabel())->toBe('Cold');
});

test('beverage variant enum returns correct icons', function () {
    expect(BeverageVariant::HOT->getIcon())->toBe('heroicon-o-fire');
    expect(BeverageVariant::COLD->getIcon())->toBe('heroicon-o-cube');
});

test('beverage variant enum returns correct price modifiers', function () {
    expect(BeverageVariant::HOT->getPriceModifier())->toBe(0.0);
    expect(BeverageVariant::COLD->getPriceModifier())->toBe(10.0);
});

test('beverage variant enum returns options array', function () {
    $options = BeverageVariant::getOptions();

    expect($options)->toBeArray();
    expect($options)->toHaveKey('Hot');
    expect($options)->toHaveKey('Cold');
    expect($options['Hot'])->toBe('Hot');
    expect($options['Cold'])->toBe('Cold');
});
