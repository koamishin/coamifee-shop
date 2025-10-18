<?php

declare(strict_types=1);

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Livewire\Livewire;

it('renders product list component', function () {
    $products = Product::factory(5)->create();

    Livewire::test('product-list')
        ->assertSee($products->first()->name)
        ->assertSee($products->last()->name);
});

it('can search products', function () {
    $product1 = Product::factory()->create(['name' => 'Espresso']);
    $product2 = Product::factory()->create(['name' => 'Cappuccino']);
    $product3 = Product::factory()->create(['name' => 'Latte']);

    Livewire::test('product-list')
        ->set('search', 'Espresso')
        ->assertSee($product1->name)
        ->assertDontSee($product2->name)
        ->assertDontSee($product3->name);
});

it('can filter by category', function () {
    $category1 = Category::factory()->create(['name' => 'Coffee']);
    $category2 = Category::factory()->create(['name' => 'Tea']);

    $coffeeProduct = Product::factory()->create(['category_id' => $category1->id]);
    $teaProduct = Product::factory()->create(['category_id' => $category2->id]);

    Livewire::test('product-list')
        ->set('selectedCategory', $category1->id)
        ->assertSee($coffeeProduct->name)
        ->assertDontSee($teaProduct->name);
});

it('can filter by stock status', function () {
    $inStockProduct = Product::factory()->create();
    $outOfStockProduct = Product::factory()->create();

    // Create inventory records
    App\Models\Inventory::factory()->create([
        'product_id' => $inStockProduct->id,
        'quantity' => 50,
        'minimum_stock' => 10,
    ]);

    App\Models\Inventory::factory()->create([
        'product_id' => $outOfStockProduct->id,
        'quantity' => 0,
        'minimum_stock' => 10,
    ]);

    Livewire::test('product-list')
        ->set('stockFilter', 'in_stock')
        ->assertSee($inStockProduct->name)
        ->assertDontSee($outOfStockProduct->name);
});

it('can toggle featured status', function () {
    $user = User::factory()->create();
    $product = Product::factory()->create(['is_featured' => false]);

    Livewire::actingAs($user)
        ->test('product-list')
        ->call('toggleFeatured', $product->id)
        ->assertDispatched('product-updated');

    $this->assertDatabaseHas('products', [
        'id' => $product->id,
        'is_featured' => true,
    ]);
});

it('can toggle active status', function () {
    $user = User::factory()->create();
    $product = Product::factory()->create(['is_active' => true]);

    Livewire::actingAs($user)
        ->test('product-list')
        ->call('toggleActive', $product->id)
        ->assertDispatched('product-updated');

    $this->assertDatabaseHas('products', [
        'id' => $product->id,
        'is_active' => false,
    ]);
});

it('can delete a product', function () {
    $user = User::factory()->create();
    $product = Product::factory()->create();

    Livewire::actingAs($user)
        ->test('product-list')
        ->call('deleteProduct', $product->id)
        ->assertDispatched('product-deleted');

    $this->assertSoftDeleted('products', [
        'id' => $product->id,
    ]);
});

it('resets search when category is cleared', function () {
    Livewire::test('product-list')
        ->set('search', 'test')
        ->set('selectedCategory', null)
        ->assertSet('search', '');

    Livewire::test('product-list')
        ->set('selectedCategory', 1)
        ->set('search', 'test')
        ->set('selectedCategory', null)
        ->assertSet('search', '');
});

it('loads products lazily', function () {
    $products = Product::factory(25)->create();

    Livewire::test('product-list')
        ->assertSet('loadMore', false)
        ->call('loadMoreProducts')
        ->assertSet('loadMore', true);
});

it('can sort products by different fields', function () {
    $product1 = Product::factory()->create(['name' => 'A Product', 'price' => 10.00]);
    $product2 = Product::factory()->create(['name' => 'B Product', 'price' => 5.00]);

    // Sort by name ascending
    Livewire::test('product-list')
        ->set('sortField', 'name')
        ->set('sortDirection', 'asc')
        ->assertSeeInOrder(['A Product', 'B Product']);

    // Sort by price ascending
    Livewire::test('product-list')
        ->set('sortField', 'price')
        ->set('sortDirection', 'asc')
        ->assertSeeInOrder(['B Product', 'A Product']);
});

it('shows loading state during search', function () {
    Livewire::test('product-list')
        ->set('search', 'test')
        ->assertSet('loading', true)
        ->assertSee('Searching...');
});

it('can export products to CSV', function () {
    $user = User::factory()->create();
    Product::factory(5)->create();

    Livewire::actingAs($user)
        ->test('product-list')
        ->call('exportProducts')
        ->assertDispatched('download-CSV');
});

it('handles pagination correctly', function () {
    Product::factory(25)->create();

    Livewire::test('product-list')
        ->assertSet('perPage', 15)
        ->call('setPerPage', 10)
        ->assertSet('perPage', 10);
});

it('can bulk update products', function () {
    $user = User::factory()->create();
    $products = Product::factory(3)->create();
    $category = Category::factory()->create();

    Livewire::actingAs($user)
        ->test('product-list')
        ->set('selectedProducts', $products->pluck('id'))
        ->set('bulkAction', 'update_category')
        ->set('bulkCategoryId', $category->id)
        ->call('applyBulkAction')
        ->assertDispatched('bulk-update-completed');

    foreach ($products as $product) {
        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'category_id' => $category->id,
        ]);
    }
});

it('validates bulk actions', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test('product-list')
        ->set('selectedProducts', [])
        ->set('bulkAction', 'delete')
        ->call('applyBulkAction')
        ->assertHasErrors(['selectedProducts']);
});

it('shows correct product count', function () {
    Product::factory(15)->create();

    Livewire::test('product-list')
        ->assertSee('15 products')
        ->assertSet('productCount', 15);
});

it('handles empty search results', function () {
    Product::factory(5)->create(['name' => 'Coffee']);

    Livewire::test('product-list')
        ->set('search', 'Tea')
        ->assertSee('No products found')
        ->assertSet('productCount', 0);
});

it('can duplicate a product', function () {
    $user = User::factory()->create();
    $product = Product::factory()->create([
        'name' => 'Original Coffee',
        'price' => 4.50,
    ]);

    Livewire::actingAs($user)
        ->test('product-list')
        ->call('duplicateProduct', $product->id)
        ->assertDispatched('product-duplicated');

    $this->assertDatabaseHas('products', [
        'name' => 'Original Coffee (Copy)',
        'price' => 4.50,
        'category_id' => $product->category_id,
    ]);
});
