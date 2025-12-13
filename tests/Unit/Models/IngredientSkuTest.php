<?php

declare(strict_types=1);

use App\Models\Ingredient;
use BinaryCats\Sku\HasSku;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('Ingredient SKU Generation', function (): void {
    it('automatically generates SKU when ingredient is created', function (): void {
        $ingredient = Ingredient::factory()->create([
            'name' => 'Arabica Coffee Beans',
        ]);

        expect($ingredient->sku)
            ->not->toBeNull()
            ->toBeString();
    });

    it('generates unique SKUs for different ingredients', function (): void {
        $ingredient1 = Ingredient::factory()->create([
            'name' => 'Water',
        ]);

        $ingredient2 = Ingredient::factory()->create([
            'name' => 'Milk',
        ]);

        expect($ingredient1->sku)
            ->not->toBe($ingredient2->sku);
    });

    it('generates SKU based on ingredient name', function (): void {
        $ingredient = Ingredient::factory()->create([
            'name' => 'Coffee Beans',
        ]);

        // SKU should contain either COF or BEA from the name
        $containsExpectedChars = str_contains((string) $ingredient->sku, 'COF') ||
                                 str_contains((string) $ingredient->sku, 'BEA');

        expect($containsExpectedChars)->toBeTrue();
    });

    it('persists SKU to database', function (): void {
        $ingredient = Ingredient::factory()->create([
            'name' => 'Sugar',
        ]);

        $savedSku = $ingredient->sku;

        // Refresh from database
        $ingredient->refresh();

        expect($ingredient->sku)->toBe($savedSku);
    });

    it('regenerates SKU on name update', function (): void {
        $ingredient = Ingredient::factory()->create([
            'name' => 'Salt',
        ]);

        $originalSku = $ingredient->sku;

        // Update ingredient name - SKU will be regenerated
        $ingredient->update(['name' => 'Sea Salt']);

        // SKU should be different after name change
        expect($ingredient->sku)->not->toBe($originalSku);
    });

    it('has HasSku trait', function (): void {
        $ingredient = new Ingredient();

        expect(in_array(HasSku::class, class_uses_recursive($ingredient)))->toBeTrue();
    });
});
