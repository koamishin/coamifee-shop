<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Livewire\Livewire;

/* NOTE: Do Not Remove
/ Livewire asset handling if using sub folder in domain
*/
Livewire::setUpdateRoute(function ($handle) {
    return Route::post(config('app.asset_prefix').'/livewire/update', $handle)->name('custom-livewire.update');
});

Livewire::setScriptRoute(function ($handle) {
    return Route::get(config('app.asset_prefix').'/livewire/livewire.js', $handle);
});
/*
/ END
*/

Route::get('/', function () {
    return view('welcome');
});

// Product routes
Route::get('/products', function () {
    $query = App\Models\Product::with(['category', 'inventory'])->where('is_active', true);

    // Search filter
    if (request('search')) {
        $query->where('name', 'like', '%'.request('search').'%')
            ->orWhere('description', 'like', '%'.request('search').'%');
    }

    // Category filter
    if (request('category')) {
        $query->where('category_id', request('category'));
    }

    // Stock filter
    if (request('stock')) {
        match (request('stock')) {
            'in_stock' => $query->whereHas('inventory', fn ($q) => $q->where('quantity', '>', 0)),
            'low_stock' => $query->whereHas('inventory', fn ($q) => $q->whereRaw('quantity <= minimum_stock')),
            'out_of_stock' => $query->whereHas('inventory', fn ($q) => $q->where('quantity', 0)),
            default => null,
        };
    }

    // Featured filter
    if (request('featured')) {
        $query->where('is_featured', true);
    }

    $products = $query->orderBy('name')->paginate(15);
    $categories = App\Models\Category::where('is_active', true)->orderBy('name')->get();

    return view('products.index', compact('products', 'categories'));
})->name('products.index');

Route::get('/products/{slug}', function ($slug) {
    $product = App\Models\Product::where('slug', $slug)->with(['category', 'inventory'])->firstOrFail();

    return view('products.show', compact('product'));
})->name('products.show');

// Admin product routes
Route::prefix('admin')->name('admin.')->group(function () {
    Route::get('/products', function () {
        return redirect('/admin');
    })->name('products.index');

    Route::post('/products', function () {
        // Simulate validation errors for testing
        $data = request()->all();
        $errors = [];

        if (empty($data['name'])) {
            $errors['name'] = 'Name is required';
        }
        if (empty($data['price'])) {
            $errors['price'] = 'Price is required';
        }
        if (empty($data['category_id'])) {
            $errors['category_id'] = 'Category is required';
        }
        if (isset($data['price']) && $data['price'] <= 0) {
            $errors['price'] = 'Price must be positive';
        }

        // Check for unique SKU if provided
        if (! empty($data['sku'])) {
            $exists = App\Models\Product::where('sku', $data['sku'])->exists();
            if ($exists) {
                $errors['sku'] = 'The SKU has already been taken.';
            }
        }

        if (! empty($errors)) {
            return redirect()->back()->withErrors($errors);
        }

        // Actually create the product
        App\Models\Product::create([
            'name' => $data['name'],
            'slug' => Illuminate\Support\Str::slug($data['name']),
            'description' => $data['description'] ?? null,
            'price' => $data['price'],
            'cost' => $data['cost'] ?? 0,
            'sku' => $data['sku'],
            'category_id' => $data['category_id'],
            'is_active' => true,
            'preparation_time' => $data['preparation_time'] ?? 5,
            'calories' => $data['calories'] ?? null,
        ]);

        return redirect('/admin/products');
    })->name('products.store');

    Route::put('/products/{product}', function (App\Models\Product $product) {
        $data = request()->all();

        $product->update([
            'name' => $data['name'] ?? $product->name,
            'description' => $data['description'] ?? $product->description,
            'price' => $data['price'] ?? $product->price,
        ]);

        return redirect('/admin/products');
    })->name('products.update');

    Route::delete('/products/{product}', function (App\Models\Product $product) {
        $product->delete();

        return redirect('/admin/products');
    })->name('products.destroy');
});
