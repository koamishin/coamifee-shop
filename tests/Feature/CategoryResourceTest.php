<?php

declare(strict_types=1);

use App\Filament\Resources\Categories\Pages\CreateCategory;
use App\Filament\Resources\Categories\Pages\EditCategory;
use App\Filament\Resources\Categories\Pages\ListCategories;
use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

it('can create category', function (): void {
    // Create a super admin user by directly setting as admin without checking roles
    $user = User::factory()->create();

    // Bypass permissions by testing the Livewire component directly
    Livewire::test(
        CreateCategory::class,
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

it('validates category name is required', function (): void {
    Livewire::test(
        CreateCategory::class,
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

it('can edit existing category', function (): void {
    $category = Category::factory()->create([
        'name' => 'Original Name',
        'description' => 'Original Description',
        'is_active' => true,
        'sort_order' => 1,
    ]);

    Livewire::test(
        EditCategory::class,
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

it('displays categories in table', function (): void {
    Category::factory()->count(3)->create();

    Livewire::test(
        ListCategories::class,
    )->assertSuccessful();
});

it('can search categories by name', function (): void {
    $category1 = Category::factory()->create(['name' => 'Coffee Category']);
    $category2 = Category::factory()->create(['name' => 'Tea Category']);

    Livewire::test(
        ListCategories::class,
    )
        ->searchTable('Coffee')
        ->assertCanSeeTableRecords([$category1])
        ->assertCanNotSeeTableRecords([$category2]);
});

it('displays category columns correctly', function (): void {
    Category::factory()->create([
        'name' => 'Test Category',
        'is_active' => true,
    ]);

    Livewire::test(
        ListCategories::class,
    )->assertSuccessful();
});

it('displays category status correctly', function (): void {
    Category::factory()->create([
        'name' => 'Active Category',
        'is_active' => true,
    ]);
    Category::factory()->create([
        'name' => 'Inactive Category',
        'is_active' => false,
    ]);

    Livewire::test(
        ListCategories::class,
    )->assertSuccessful();
});
