<?php

declare(strict_types=1);

use App\Models\Category;
use App\Models\Inventory;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('can view all products', function () {
    $products = Product::factory(5)->create();

    $response = $this->get('/products')
        ->assertStatus(200);

    foreach ($products as $product) {
        $response->assertSee($product->name);
        $response->assertSee($product->formatted_price);
    }
});

it('can view a single product', function () {
    $product = Product::factory()->create();

    $response = $this->get("/products/{$product->slug}")
        ->assertStatus(200)
        ->assertSee($product->name)
        ->assertSee($product->description)
        ->assertSee($product->formatted_price)
        ->assertSee($product->category->name);
});

it('can create a new product as admin', function () {
    $admin = User::factory()->create();
    $category = Category::factory()->create();

    $response = $this->actingAs($admin)
        ->post('/admin/products', [
            'name' => 'Test Coffee',
            'slug' => 'test-coffee',
            'description' => 'A delicious test coffee',
            'price' => 4.50,
            'cost' => 2.00,
            'sku' => 'TEST-001',
            'category_id' => $category->id,
            'is_active' => true,
            'preparation_time' => 5,
            'calories' => 150,
        ])
        ->assertRedirect('/admin/products');

    $this->assertDatabaseHas('products', [
        'name' => 'Test Coffee',
        'slug' => 'test-coffee',
        'price' => 4.50,
        'sku' => 'TEST-001',
        'category_id' => $category->id,
    ]);
});

it('can update a product as admin', function () {
    $admin = User::factory()->create();
    $product = Product::factory()->create();

    $response = $this->actingAs($admin)
        ->put("/admin/products/{$product->id}", [
            'name' => 'Updated Coffee',
            'price' => 5.50,
            'description' => 'Updated description',
        ])
        ->assertRedirect('/admin/products');

    $this->assertDatabaseHas('products', [
        'id' => $product->id,
        'name' => 'Updated Coffee',
        'price' => 5.50,
    ]);
});

it('can delete a product as admin', function () {
    $admin = User::factory()->create();
    $product = Product::factory()->create();

    $response = $this->actingAs($admin)
        ->delete("/admin/products/{$product->id}")
        ->assertRedirect('/admin/products');

    $this->assertSoftDeleted('products', [
        'id' => $product->id,
    ]);
});

it('cannot create product without required fields', function () {
    $admin = User::factory()->create();

    $response = $this->actingAs($admin)
        ->post('/admin/products', [])
        ->assertSessionHasErrors(['name', 'price', 'category_id']);
});

it('can filter products by category', function () {
    $category1 = Category::factory()->create(['name' => 'Coffee']);
    $category2 = Category::factory()->create(['name' => 'Tea']);

    $coffeeProducts = Product::factory(3)->create(['category_id' => $category1->id]);
    $teaProducts = Product::factory(2)->create(['category_id' => $category2->id]);

    $response = $this->get("/products?category={$category1->id}")
        ->assertStatus(200);

    foreach ($coffeeProducts as $product) {
        $response->assertSee($product->name);
    }

    foreach ($teaProducts as $product) {
        $response->assertDontSee($product->name);
    }
});

it('can search products by name', function () {
    Product::factory()->create(['name' => 'Espresso']);
    Product::factory()->create(['name' => 'Cappuccino']);
    Product::factory()->create(['name' => 'Latte']);

    $response = $this->get('/products?search=Espresso')
        ->assertStatus(200)
        ->assertSee('Espresso')
        ->assertDontSee('Cappuccino')
        ->assertDontSee('Latte');
});

it('displays only active products', function () {
    $activeProduct = Product::factory()->create(['is_active' => true]);
    $inactiveProduct = Product::factory()->create(['is_active' => false]);

    $response = $this->get('/products')
        ->assertStatus(200)
        ->assertSee($activeProduct->name)
        ->assertDontSee($inactiveProduct->name);
});

it('shows correct stock status', function () {
    $product = Product::factory()->create();

    // Out of stock
    Inventory::factory()->create([
        'product_id' => $product->id,
        'quantity' => 0,
        'minimum_stock' => 10,
    ]);

    expect($product->isInStock())->toBeFalse();
    expect($product->low_stock)->toBeTrue();
    expect($product->stock_level)->toBe(0);

    // Update to have stock
    $product->inventory->update(['quantity' => 50]);

    expect($product->fresh()->isInStock())->toBeTrue();
    expect($product->fresh()->low_stock)->toBeFalse();
    expect($product->fresh()->stock_level)->toBe(50);
});

it('calculates profit margin correctly', function () {
    $product = Product::factory()->create([
        'price' => 5.00,
        'cost' => 2.00,
    ]);

    expect($product->profit_margin)->toBe(60.0); // ((5-2)/5)*100 = 60%
});

it('generates unique slugs automatically', function () {
    $category = Category::factory()->create();

    Product::factory()->create([
        'name' => 'Espresso',
        'slug' => 'espresso',
        'category_id' => $category->id,
    ]);

    // Creating another product with the same name should generate a unique slug
    $response = $this->actingAs(User::factory()->create())
        ->post('/admin/products', [
            'name' => 'Espresso',
            'price' => 4.50,
            'category_id' => $category->id,
        ]);

    $response->assertSessionHasNoErrors();

    $this->assertDatabaseHas('products', [
        'name' => 'Espresso',
        'slug' => 'espresso', // The factory should generate a unique slug
    ]);
});

it('displays featured products correctly', function () {
    $featuredProduct = Product::factory()->create(['is_featured' => true]);
    $regularProduct = Product::factory()->create(['is_featured' => false]);

    $response = $this->get('/products?featured=1')
        ->assertStatus(200)
        ->assertSee($featuredProduct->name)
        ->assertDontSee($regularProduct->name);
});

it('validates product price is positive', function () {
    $admin = User::factory()->create();
    $category = Category::factory()->create();

    $response = $this->actingAs($admin)
        ->post('/admin/products', [
            'name' => 'Test Product',
            'price' => -5.00,
            'category_id' => $category->id,
        ])
        ->assertSessionHasErrors(['price']);
});

it('validates SKU is unique', function () {
    $admin = User::factory()->create();
    $category = Category::factory()->create();

    $existingProduct = Product::factory()->create(['sku' => 'UNIQUE-001']);

    $response = $this->actingAs($admin)
        ->post('/admin/products', [
            'name' => 'New Product',
            'sku' => 'UNIQUE-001', // Same SKU
            'price' => 4.50,
            'category_id' => $category->id,
        ])
        ->assertSessionHasErrors(['sku']);
});
