@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="max-w-4xl mx-auto">
        <nav class="mb-8">
            <ol class="flex items-center space-x-2 text-sm text-gray-600">
                <li><a href="{{ route('products.index') }}" class="hover:text-blue-600">Products</a></li>
                <li>/</li>
                <li class="text-gray-900">{{ $product->name }}</li>
            </ol>
        </nav>

        <div class="bg-white rounded-lg shadow-lg overflow-hidden">
            <div class="md:flex">
                @if($product->image_url)
                    <div class="md:w-1/2">
                        <img src="{{ $product->image_url }}" alt="{{ $product->name }}" class="w-full h-full object-cover">
                    </div>
                @endif
                <div class="md:w-1/2 p-8">
                    <div class="mb-4">
                        @if($product->is_featured)
                            <span class="inline-block px-3 py-1 text-xs font-semibold text-yellow-800 bg-yellow-100 rounded-full mb-2">Featured</span>
                        @endif
                        <h1 class="text-3xl font-bold text-gray-900">{{ $product->name }}</h1>
                        <p class="text-lg text-gray-600 mt-2">{{ $product->category->name }}</p>
                    </div>

                    <div class="mb-6">
                        <p class="text-4xl font-bold text-green-600">{{ $product->formatted_price }}</p>
                        @if($product->cost)
                            <p class="text-sm text-gray-500">Cost: {{ $product->formatted_cost }}</p>
                            <p class="text-sm text-gray-500">Margin: {{ number_format($product->profit_margin, 1) }}%</p>
                        @endif
                    </div>

                    @if($product->inventory && $product->inventory->quantity > 0)
                        <div class="mb-6">
                            <div class="flex items-center justify-between">
                                <span class="text-gray-700">Stock Level:</span>
                                <span class="font-semibold {{ $product->inventory->stock_status === 'in_stock' ? 'text-green-600' : 'text-yellow-600' }}">
                                    {{ $product->inventory->quantity }} units
                                </span>
                            </div>
                            <div class="w-full bg-gray-200 rounded-full h-2 mt-2">
                                <div class="bg-{{ $product->inventory->stock_status_color }}-500 h-2 rounded-full"
                                     style="width: {{ min(100, ($product->inventory->quantity / $product->inventory->maximum_stock) * 100) }}%"></div>
                            </div>
                        </div>
                    @else
                        <div class="mb-6">
                            <span class="text-red-600 font-semibold">Out of Stock</span>
                        </div>
                    @endif

                    @if($product->description)
                        <div class="mb-6">
                            <h3 class="text-lg font-semibold text-gray-900 mb-2">Description</h3>
                            <p class="text-gray-600">{{ $product->description }}</p>
                        </div>
                    @endif

                    @if($product->ingredients)
                        <div class="mb-6">
                            <h3 class="text-lg font-semibold text-gray-900 mb-2">Ingredients</h3>
                            <p class="text-gray-600">{{ $product->ingredients }}</p>
                        </div>
                    @endif

                    @if($product->preparation_time)
                        <div class="mb-6">
                            <h3 class="text-lg font-semibold text-gray-900 mb-2">Preparation Time</h3>
                            <p class="text-gray-600">{{ $product->preparation_time }} minutes</p>
                        </div>
                    @endif

                    @if($product->calories)
                        <div class="mb-6">
                            <h3 class="text-lg font-semibold text-gray-900 mb-2">Nutritional Information</h3>
                            <p class="text-gray-600">{{ $product->calories }} calories</p>
                        </div>
                    @endif

                    <div class="mb-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-2">Product Details</h3>
                        <dl class="grid grid-cols-2 gap-4 text-sm">
                            <div>
                                <dt class="text-gray-500">SKU:</dt>
                                <dd class="font-medium">{{ $product->sku }}</dd>
                            </div>
                            @if($product->barcode)
                                <div>
                                    <dt class="text-gray-500">Barcode:</dt>
                                    <dd class="font-medium">{{ $product->barcode }}</dd>
                                </div>
                            @endif
                            <div>
                                <dt class="text-gray-500">Status:</dt>
                                <dd class="font-medium">{{ $product->is_active ? 'Active' : 'Inactive' }}</dd>
                            </div>
                            <div>
                                <dt class="text-gray-500">Location:</dt>
                                <dd class="font-medium">{{ $product->inventory->location ?? 'Not specified' }}</dd>
                            </div>
                        </dl>
                    </div>

                    <div class="flex gap-4">
                        <a href="{{ route('products.index') }}"
                           class="inline-block bg-gray-500 text-white px-6 py-3 rounded-lg hover:bg-gray-600">
                            Back to Products
                        </a>
                        @if($product->inventory && $product->inventory->quantity > 0)
                            <button class="inline-block bg-blue-500 text-white px-6 py-3 rounded-lg hover:bg-blue-600">
                                Add to Cart
                            </button>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <div class="mt-8 bg-white rounded-lg shadow-lg p-6">
            <h2 class="text-2xl font-bold text-gray-900 mb-4">Related Products</h2>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                @php
                    $relatedProducts = \App\Models\Product::where('category_id', $product->category_id)
                        ->where('id', '!=', $product->id)
                        ->where('is_active', true)
                        ->limit(3)
                        ->get();
                @endphp
                @forelse($relatedProducts as $relatedProduct)
                    <div class="text-center">
                        @if($relatedProduct->image_url)
                            <img src="{{ $relatedProduct->image_url }}" alt="{{ $relatedProduct->name }}"
                                 class="w-full h-32 object-cover rounded-lg mb-2">
                        @endif
                        <h3 class="font-semibold text-gray-900">{{ $relatedProduct->name }}</h3>
                        <p class="text-green-600 font-bold">{{ $relatedProduct->formatted_price }}</p>
                        <a href="{{ route('products.show', $relatedProduct->slug) }}"
                           class="inline-block text-blue-600 hover:text-blue-800 mt-2">
                            View Details
                        </a>
                    </div>
                @empty
                    <div class="col-span-3 text-center text-gray-500">
                        No related products found.
                    </div>
                @endforelse
            </div>
        </div>
    </div>
</div>
@endsection
