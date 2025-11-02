<?php

declare(strict_types=1);

use App\Filament\Resources\Categories\Pages\CreateCategory;
use App\Filament\Resources\Categories\Pages\EditCategory;
use App\Filament\Resources\Categories\Pages\ListCategories;
use App\Models\Category;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertDatabaseHas;

uses(RefreshDatabase::class);

// Set up authentication for all tests
beforeEach(function () {
    $user = User::factory()->create();
    actingAs($user);

    // Set the current panel for Filament testing
    Filament::setCurrentPanel('admin');
});

it('can create category', function (): void {
    $newCategoryData = Category::factory()->make();

    Livewire::test(CreateCategory::class)
        ->assertOk()
        ->fillForm([
            'name' => $newCategoryData->name,
            'description' => $newCategoryData->description,
            'is_active' => $newCategoryData->is_active,
            'sort_order' => $newCategoryData->sort_order,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    assertDatabaseHas('categories', [
        'name' => $newCategoryData->name,
        'description' => $newCategoryData->description,
        'is_active' => $newCategoryData->is_active,
        'sort_order' => $newCategoryData->sort_order,
    ]);
});

it('validates category name is required', function (): void {
    Livewire::test(CreateCategory::class)
        ->assertOk()
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

    $newCategoryData = Category::factory()->make();

    Livewire::test(EditCategory::class, [
        'record' => $category->id,
    ])
        ->assertOk()
        ->fillForm([
            'name' => $newCategoryData->name,
            'description' => $newCategoryData->description,
            'is_active' => $newCategoryData->is_active,
            'sort_order' => $newCategoryData->sort_order,
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    assertDatabaseHas('categories', [
        'id' => $category->id,
        'name' => $newCategoryData->name,
        'description' => $newCategoryData->description,
        'is_active' => $newCategoryData->is_active,
        'sort_order' => $newCategoryData->sort_order,
    ]);
});

it('displays categories in table', function (): void {
    $categories = Category::factory()->count(3)->create();

    Livewire::test(ListCategories::class)
        ->assertOk()
        ->assertCanSeeTableRecords($categories);
});

it('can search categories by name', function (): void {
    $category1 = Category::factory()->create(['name' => 'Coffee Category']);
    $category2 = Category::factory()->create(['name' => 'Tea Category']);

    Livewire::test(ListCategories::class)
        ->assertOk()
        ->assertCanSeeTableRecords([$category1, $category2])
        ->searchTable('Coffee')
        ->assertCanSeeTableRecords([$category1])
        ->assertCanNotSeeTableRecords([$category2]);
});

it('displays category columns correctly', function (): void {
    Category::factory()->create([
        'name' => 'Test Category',
        'is_active' => true,
    ]);

    Livewire::test(ListCategories::class)
        ->assertOk();
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

    Livewire::test(ListCategories::class)
        ->assertOk();
});
