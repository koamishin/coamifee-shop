<div class="flex flex-col h-[calc(100vh-8rem)] -m-6 bg-gray-50 dark:bg-gray-900">
    <div class="flex-1 flex overflow-hidden">
        {{-- Left: Menu & Grid --}}
        <div class="flex-1 flex flex-col min-w-0 border-r border-gray-200 dark:border-gray-800">
            {{-- Header: Search & Categories --}}
            <div class="flex-none p-4 bg-white dark:bg-gray-900 border-b border-gray-200 dark:border-gray-800 space-y-4">
                {{-- Search --}}
                <div class="relative">
                    <x-filament::icon icon="heroicon-o-magnifying-glass" class="absolute left-4 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400" />
                    <input
                        type="text"
                        wire:model.live.debounce.300ms="search"
                        placeholder="Search menu..."
                        class="w-full pl-11 pr-4 py-3 bg-gray-100 dark:bg-gray-800 border-none rounded-xl text-lg focus:ring-2 focus:ring-primary-500/50 transition-shadow"
                    />
                </div>

                {{-- Categories --}}
                <div class="flex gap-2 overflow-x-auto pb-2 no-scrollbar">
                    <button
                        wire:click="$set('selectedCategoryId', null)"
                        class="px-4 py-2 rounded-lg font-bold text-sm whitespace-nowrap transition-all {{ $selectedCategoryId === null ? 'bg-gray-900 dark:bg-white text-white dark:text-gray-900 shadow-md' : 'bg-white dark:bg-gray-800 text-gray-600 dark:text-gray-400 border border-gray-200 dark:border-gray-700 hover:bg-gray-50' }}"
                    >
                        All Items
                    </button>
                    @foreach($this->getRawCategories() as $category)
                        <button
                            wire:click="$set('selectedCategoryId', {{ $category->id }})"
                            wire:key="cat-{{ $category->id }}"
                            class="px-4 py-2 rounded-lg font-bold text-sm whitespace-nowrap transition-all {{ $selectedCategoryId === $category->id ? 'bg-primary-600 text-white shadow-md' : 'bg-white dark:bg-gray-800 text-gray-600 dark:text-gray-400 border border-gray-200 dark:border-gray-700 hover:bg-gray-50' }}"
                        >
                            {{ $category->name }}
                        </button>
                    @endforeach
                </div>
            </div>

            {{-- Product Grid --}}
            <div class="flex-1 overflow-y-auto p-4 bg-gray-50 dark:bg-gray-950">
                <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
                    @forelse($this->getRawProducts() as $product)
                        @php
                            $inCartQty = collect($cartItems)->where('product_id', $product->id)->sum('quantity');
                        @endphp
                        <button
                            wire:click="addToCart({{ $product->id }}, '{{ $product->name }}', {{ $product->price }})"
                            class="group relative flex flex-col text-left bg-white dark:bg-gray-900 rounded-2xl shadow-sm border border-gray-200 dark:border-gray-800 overflow-hidden hover:shadow-md hover:border-primary-500 transition-all active:scale-95 h-48"
                        >
                            <div class="h-28 w-full bg-gray-100 dark:bg-gray-800 relative">
                                @if($product->image_url)
                                    <img src="{{ $this->getProductImageUrl($product->image_url) }}" class="w-full h-full object-cover">
                                @else
                                    <div class="w-full h-full flex items-center justify-center text-gray-300">
                                        <x-filament::icon icon="heroicon-o-photo" class="w-10 h-10" />
                                    </div>
                                @endif
                                
                                @if($inCartQty > 0)
                                    <div class="absolute top-2 right-2 bg-primary-600 text-white text-xs font-bold w-6 h-6 flex items-center justify-center rounded-full shadow-lg">
                                        {{ $inCartQty }}
                                    </div>
                                @endif
                            </div>
                            <div class="p-3 flex flex-col justify-between flex-1">
                                <span class="font-bold text-gray-900 dark:text-white leading-tight line-clamp-2">{{ $product->name }}</span>
                                <span class="text-sm font-medium text-gray-500">{{ $this->formatCurrency($product->price) }}</span>
                            </div>
                        </button>
                    @empty
                        <div class="col-span-full text-center py-12 text-gray-500">
                            No products found.
                        </div>
                    @endforelse
                </div>
            </div>
        </div>

        {{-- Right: Cart --}}
        <div class="w-96 flex flex-col bg-white dark:bg-gray-900 border-l border-gray-200 dark:border-gray-800 z-10 shadow-xl">
            <div class="flex-none p-4 border-b border-gray-200 dark:border-gray-800 flex items-center justify-between bg-gray-50 dark:bg-gray-900">
                <h3 class="font-bold text-lg text-gray-900 dark:text-white uppercase tracking-wide">Items to Add</h3>
                <span class="bg-primary-100 text-primary-700 px-2 py-1 rounded text-xs font-bold">{{ count($cartItems) }}</span>
            </div>

            <div class="flex-1 overflow-y-auto p-4 space-y-3">
                @forelse($cartItems as $index => $item)
                    <div class="flex items-center gap-3 p-3 bg-gray-50 dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700">
                        <div class="flex flex-col items-center gap-1">
                            <button wire:click="updateQuantity({{ $item['product_id'] }}, {{ $item['quantity'] + 1 }}, {{ $item['variant_id'] ?? 'null' }})" class="p-1 text-gray-500 hover:text-primary-600 bg-white dark:bg-gray-700 rounded shadow-sm">
                                <x-filament::icon icon="heroicon-m-plus" class="w-3 h-3" />
                            </button>
                            <span class="font-bold text-sm w-6 text-center">{{ $item['quantity'] }}</span>
                            <button wire:click="updateQuantity({{ $item['product_id'] }}, {{ $item['quantity'] - 1 }}, {{ $item['variant_id'] ?? 'null' }})" class="p-1 text-gray-500 hover:text-red-600 bg-white dark:bg-gray-700 rounded shadow-sm">
                                <x-filament::icon icon="heroicon-m-minus" class="w-3 h-3" />
                            </button>
                        </div>
                        <div class="flex-1 min-w-0">
                            <div class="font-bold text-gray-900 dark:text-white truncate">{{ $item['product_name'] }}</div>
                            @if(!empty($item['variant_name']))
                                <div class="text-xs text-gray-500">{{ $item['variant_name'] }}</div>
                            @endif
                            <div class="text-xs text-gray-500 mt-0.5">{{ $this->formatCurrency($item['price']) }}</div>
                        </div>
                        <div class="text-right">
                            <div class="font-bold">{{ $this->formatCurrency($item['price'] * $item['quantity']) }}</div>
                            <button wire:click="removeFromCart({{ $item['product_id'] }}, {{ $item['variant_id'] ?? 'null' }})" class="text-red-500 p-1 hover:bg-red-50 rounded mt-1">
                                <x-filament::icon icon="heroicon-m-trash" class="w-4 h-4" />
                            </button>
                        </div>
                    </div>
                @empty
                    <div class="text-center py-10 opacity-50">
                        <x-filament::icon icon="heroicon-o-shopping-cart" class="w-12 h-12 mx-auto text-gray-300 mb-2" />
                        <p class="text-gray-500 text-sm">Select items from the menu</p>
                    </div>
                @endforelse
            </div>

            <div class="flex-none p-4 bg-gray-50 dark:bg-gray-900 border-t border-gray-200 dark:border-gray-800">
                <div class="flex justify-between items-end mb-4">
                    <span class="text-gray-600 dark:text-gray-400 font-medium">Total Added</span>
                    @php
                        $total = collect($cartItems)->sum(fn($item) => $item['price'] * $item['quantity']);
                    @endphp
                    <span class="text-2xl font-black text-gray-900 dark:text-white">{{ $this->formatCurrency($total) }}</span>
                </div>
                {{-- Note: The submit button is handled by the Filament Action modal footer, but we'll style the container to look connected --}}
            </div>
        </div>
    </div>
</div>
