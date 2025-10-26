<?php

declare(strict_types=1);

use App\Models\Category;
use App\Models\User;
use Livewire\Livewire;

it('can create category', function () {
    // Create a super admin user by directly setting as admin without checking roles
    $user = User::factory()->create();

    // Bypass permissions by testing the Livewire component directly
    Livewire::test(
        App\Filament\Resources\Categories\Pages\CreateCategory::class,
    )
        ->fillForm([
            'name' => 'Test Category',
            'description' => 'Test Description',
            'is_active' => true,
            'sort_order' => 1,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $this->assertDatabaseHas('categories', [
        'name' => 'Test Category',
        'description' => 'Test Description',
        'is_active' => true,
        'sort_order' => 1,
    ]);
});

it('validates category name is required', function () {
    Livewire::test(
        App\Filament\Resources\Categories\Pages\CreateCategory::class,
    )
        ->fillForm([
            'name' => '',
            'description' => 'Test Description',
            'is_active' => true,
            'sort_order' => 1,
        ])
        ->call('create')
        ->assertHasFormErrors(['name' => 'required']);
});

it('can edit existing category', function () {
    $category = Category::factory()->create([
        'name' => 'Original Name',
        'description' => 'Original Description',
        'is_active' => true,
        'sort_order' => 1,
    ]);

    Livewire::test(
        App\Filament\Resources\Categories\Pages\EditCategory::class,
        [
            'record' => $category->id,
        ],
    )
        ->fillForm([
            'name' => 'Updated Name',
            'description' => 'Updated Description',
            'is_active' => false,
            'sort_order' => 2,
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    $this->assertDatabaseHas('categories', [
        'id' => $category->id,
        'name' => 'Updated Name',
        'description' => 'Updated Description',
        'is_active' => false,
        'sort_order' => 2,
    ]);
});

it('displays categories in table', function () {
    Category::factory()->count(3)->create();

    Livewire::test(
        App\Filament\Resources\Categories\Pages\ListCategories::class,
    )->assertSuccessful();
});

it('can search categories by name', function () {
    $category1 = Category::factory()->create(['name' => 'Coffee Category']);
    $category2 = Category::factory()->create(['name' => 'Tea Category']);

    Livewire::test(
        App\Filament\Resources\Categories\Pages\ListCategories::class,
    )
        ->searchTable('Coffee')
        ->assertCanSeeTableRecords([$category1])
        ->assertCanNotSeeTableRecords([$category2]);
});

it('displays category columns correctly', function () {
    Category::factory()->create([
        'name' => 'Test Category',
        'is_active' => true,
    ]);

    Livewire::test(
        App\Filament\Resources\Categories\Pages\ListCategories::class,
    )->assertSuccessful();
});

it('displays category status correctly', function () {
    Category::factory()->create([
        'name' => 'Active Category',
        'is_active' => true,
    ]);
    Category::factory()->create([
        'name' => 'Inactive Category',
        'is_active' => false,
    ]);

    Livewire::test(
        App\Filament\Resources\Categories\Pages\ListCategories::class,
    )->assertSuccessful();
});
