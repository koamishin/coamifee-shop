<div>
    @if($loading)
        <div class="text-center py-4">
            <p>Searching...</p>
        </div>
    @endif

    <div class="mb-4">
        <p class="text-sm text-gray-600">{{ $productCount ?? 0 }} products found</p>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        @forelse($products ?? collect() as $product)
            <div class="bg-white rounded-lg shadow-md overflow-hidden">
                @if($product->image_url)
                    <img src="{{ $product->image_url }}" alt="{{ $product->name }}" class="w-full h-48 object-cover">
                @endif
                <div class="p-4">
                    <h3 class="text-lg font-semibold text-gray-900">{{ $product->name }}</h3>
                    <p class="text-gray-600 text-sm mb-2">{{ $product->category?->name }}</p>
                    <p class="text-2xl font-bold text-green-600">{{ $product->formatted_price }}</p>

                    @if($product->is_featured)
                        <span class="inline-block px-2 py-1 text-xs font-semibold text-yellow-800 bg-yellow-100 rounded-full">Featured</span>
                    @endif

                    <div class="mt-4">
                        <a href="{{ route('products.show', $product->slug) }}"
                           class="inline-block bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600">
                            View Details
                        </a>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-span-full text-center py-8">
                <p class="text-gray-500">No products found</p>
            </div>
        @endforelse
    </div>

    @if(isset($products) && $products->hasPages())
        <div class="mt-8">
            {{ $products->links() }}
        </div>
    @endif
</div>
