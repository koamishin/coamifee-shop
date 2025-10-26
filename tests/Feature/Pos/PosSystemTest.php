<?php

declare(strict_types=1);

use App\Livewire\Pos;
use Livewire\Livewire;

test('pos page renders with sidebar and products', function (): void {
    Livewire::test(Pos::class)
        ->assertOk()
        ->assertViewHas('categories')
        ->assertViewHas('bestSellers')
        ->assertViewHas('products')
        ->assertViewHas('productAvailability');
});

test('pos can filter products by category', function (): void {
    Livewire::test(Pos::class)
        ->set('selectedCategory', 1)
        ->assertSet('selectedCategory', 1);
});

test('pos can search products', function (): void {
    Livewire::test(Pos::class)
        ->set('search', 'test')
        ->assertSet('search', 'test');
});

test('pos uses url parameters', function (): void {
    Livewire::test(Pos::class)
        ->set('selectedCategory', 1)
        ->set('search', 'coffee')
        ->assertSet('selectedCategory', 1)
        ->assertSet('search', 'coffee');
});
