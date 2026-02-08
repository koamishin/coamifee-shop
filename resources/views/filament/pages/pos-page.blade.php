<x-filament-panels::page class="h-full filament-pos-page">
    {{-- Custom CSS for hiding scrollbars but allowing scroll --}}
    <style>
        .no-scrollbar::-webkit-scrollbar {
            display: none;
        }
        .no-scrollbar {
            -ms-overflow-style: none;
            scrollbar-width: none;
        }
    </style>

    {{-- Main Layout: Full Screen Flex Container --}}
    <div class="fixed inset-x-0 bottom-0 top-[4rem] flex flex-row h-[calc(100vh-4rem)] bg-gray-100 dark:bg-gray-950 overflow-hidden font-sans">
        
        {{-- LEFT COLUMN: Navigation & Menu (65-70%) --}}
        <div class="flex-1 flex flex-col min-w-0 border-r border-gray-200 dark:border-gray-800">
            
            {{-- Top Header: Search & Context --}}
            <header class="flex-none h-20 px-6 flex items-center justify-between bg-white dark:bg-gray-900 border-b border-gray-200 dark:border-gray-800 z-20">
                {{-- Search Bar --}}
                <div class="flex-1 max-w-xl relative group">
                    <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                        <x-filament::icon icon="heroicon-o-magnifying-glass" class="h-6 w-6 text-gray-400 group-focus-within:text-primary-500 transition-colors" />
                    </div>
                    <input
                        type="text"
                        wire:model.live.debounce.300ms="search"
                        placeholder="Search for items..."
                        class="block w-full pl-12 pr-4 py-3 bg-gray-50 dark:bg-gray-800 border-none rounded-2xl text-lg text-gray-900 dark:text-white placeholder-gray-400 focus:ring-2 focus:ring-primary-500/50 focus:bg-white dark:focus:bg-gray-900 transition-all shadow-sm group-focus-within:shadow-md"
                    />
                </div>

                {{-- Status Indicators --}}
                <div class="flex items-center gap-6">
                    <div class="hidden lg:flex items-center gap-2 px-4 py-2 bg-green-50 dark:bg-green-900/20 rounded-full border border-green-100 dark:border-green-800">
                        <div class="w-2.5 h-2.5 rounded-full bg-green-500 animate-pulse"></div>
                        <span class="text-sm font-bold text-green-700 dark:text-green-400 uppercase tracking-wide">System Online</span>
                    </div>
                    <div class="text-right">
                        <div class="text-xs font-bold text-gray-400 uppercase tracking-wider">Server</div>
                        <div class="text-sm font-bold text-gray-900 dark:text-white">{{ auth()->user()->name ?? 'Staff' }}</div>
                    </div>
                </div>
            </header>

            {{-- Main Content Area: Categories & Grid --}}
            <div class="flex-1 flex overflow-hidden">
                {{-- Categories Rail (Left Side) --}}
                <nav class="flex-none w-28 bg-white dark:bg-gray-900 overflow-y-auto no-scrollbar py-6 flex flex-col gap-4 items-center border-r border-gray-100 dark:border-gray-800">
                    <button
                        wire:click="selectCategory(null)"
                        class="w-20 h-20 rounded-2xl flex flex-col items-center justify-center gap-2 transition-all duration-300 {{ $selectedCategoryId === null ? 'bg-gray-900 dark:bg-white text-white dark:text-gray-900 shadow-xl scale-105' : 'bg-gray-50 dark:bg-gray-800 text-gray-500 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700' }}"
                    >
                        <x-filament::icon icon="heroicon-o-squares-2x2" class="w-7 h-7" />
                        <span class="text-[10px] font-bold uppercase tracking-wide">All</span>
                    </button>

                    <div class="w-12 h-px bg-gray-200 dark:bg-gray-800 my-1"></div>

                    @foreach($categories as $category)
                        <button
                            wire:click="selectCategory({{ $category->id }})"
                            wire:key="cat-{{ $category->id }}"
                            class="w-20 h-20 rounded-2xl flex flex-col items-center justify-center gap-1 transition-all duration-300 relative group {{ $selectedCategoryId === $category->id ? 'bg-primary-600 text-white shadow-lg shadow-primary-500/30 scale-105 ring-4 ring-primary-50 dark:ring-primary-900/20' : 'bg-white dark:bg-gray-800 border border-gray-100 dark:border-gray-700 text-gray-500 dark:text-gray-400 hover:border-primary-200 hover:text-primary-600' }}"
                        >
                            @if($category->icon)
                                <x-filament::icon icon="{{ $category->icon }}" class="w-7 h-7 {{ $selectedCategoryId === $category->id ? 'text-white' : 'text-gray-400 group-hover:text-primary-500' }}" />
                            @else
                                <span class="text-2xl font-bold {{ $selectedCategoryId === $category->id ? 'text-white' : 'text-gray-300' }}">{{ mb_substr($category->name, 0, 1) }}</span>
                            @endif
                            <span class="text-[10px] font-bold text-center leading-tight px-1 line-clamp-1 w-full max-w-[90%]">{{ $category->name }}</span>
                        </button>
                    @endforeach
                </nav>

                {{-- Product Grid --}}
                <div class="flex-1 bg-gray-100 dark:bg-gray-950 p-6 overflow-y-auto no-scrollbar">
                    {{-- Breadcrumb / Count --}}
                    <div class="flex items-center justify-between mb-6 sticky top-0 z-10 py-2 bg-gray-100/95 dark:bg-gray-950/95 backdrop-blur-sm">
                        <h2 class="text-2xl font-black text-gray-800 dark:text-white tracking-tight">
                            {{ $selectedCategoryId ? $categories->firstWhere('id', $selectedCategoryId)?->name : 'Menu' }}
                        </h2>
                        <span class="px-4 py-1.5 bg-white dark:bg-gray-800 text-gray-500 dark:text-gray-400 text-xs font-bold uppercase tracking-wider rounded-full shadow-sm border border-gray-200 dark:border-gray-700">
                            {{ $products->count() }} Products
                        </span>
                    </div>

                    {{-- Grid --}}
                    @if($products->count() > 0)
                        <div class="grid grid-cols-2 md:grid-cols-3 xl:grid-cols-4 2xl:grid-cols-5 gap-5 pb-24">
                            @foreach($products as $product)
                                @php
                                    $availability = $productAvailability[$product->id] ?? null;
                                    $stockStatus = $availability['stock_status'] ?? 'in_stock';
                                    $qtyInCart = collect($this->cartItems)->where('product_id', $product->id)->sum('quantity');
                                    $isInCart = $qtyInCart > 0;
                                    $isOut = $stockStatus === 'out_of_stock';
                                @endphp

                                <div
                                    wire:click="addToCart({{ $product->id }})"
                                    wire:key="prod-{{ $product->id }}"
                                    class="group relative flex flex-col bg-white dark:bg-gray-900 rounded-3xl overflow-hidden shadow-sm hover:shadow-xl transition-all duration-300 active:scale-95 text-left h-64 cursor-pointer
                                    {{ $isInCart ? 'ring-2 ring-primary-500 dark:ring-primary-400' : 'hover:ring-2 hover:ring-primary-500/20' }}
                                    {{ $isOut ? 'opacity-50 grayscale cursor-not-allowed pointer-events-none' : '' }}"
                                >
                                    {{-- Image Half --}}
                                    <div class="h-36 w-full bg-gray-100 dark:bg-gray-800 relative overflow-hidden">
                                        @if($product->image_url)
                                            <img src="{{ $this->getProductImageUrl($product->image_url) }}" class="w-full h-full object-cover transition-transform duration-700 group-hover:scale-110">
                                        @else
                                            <div class="w-full h-full flex items-center justify-center text-gray-300 dark:text-gray-600">
                                                <x-filament::icon icon="heroicon-o-photo" class="w-12 h-12" />
                                            </div>
                                        @endif

                                        {{-- Overlays --}}
                                        @if($isInCart)
                                            <div class="absolute top-3 right-3 bg-primary-600 text-white text-sm font-bold w-8 h-8 flex items-center justify-center rounded-full shadow-lg">
                                                {{ $qtyInCart }}
                                            </div>
                                        @endif
                                        
                                        <div class="absolute inset-0 bg-gradient-to-t from-black/60 to-transparent opacity-60"></div>
                                        
                                        <div class="absolute bottom-3 left-3 right-3 text-white">
                                            <div class="font-bold text-lg leading-tight shadow-black drop-shadow-md">{{ $this->formatCurrency($product->price) }}</div>
                                        </div>
                                    </div>

                                    {{-- Info Half --}}
                                    <div class="flex-1 p-4 flex flex-col justify-between relative bg-white dark:bg-gray-900">
                                        <h3 class="font-bold text-gray-900 dark:text-white text-base leading-snug line-clamp-2">
                                            {{ $product->name }}
                                        </h3>
                                        
                                        <div class="flex items-end justify-between mt-2">
                                            <span class="text-xs font-medium text-gray-400 uppercase tracking-wide truncate pr-2">{{ $categories->firstWhere('id', $product->category_id)?->name ?? 'Item' }}</span>
                                            
                                            @if($isInCart)
                                                <div class="flex items-center gap-1 bg-primary-50 dark:bg-primary-900/30 rounded-full p-1 border border-primary-100 dark:border-primary-800 shadow-sm">
                                                    <div 
                                                        wire:click.stop="removeOneFromCart({{ $product->id }})"
                                                        class="w-7 h-7 rounded-full bg-white dark:bg-gray-800 text-red-500 flex items-center justify-center shadow-sm hover:bg-red-50 dark:hover:bg-red-900/50 transition-colors cursor-pointer"
                                                    >
                                                        <x-filament::icon icon="heroicon-m-minus" class="w-4 h-4" />
                                                    </div>
                                                    
                                                    <span class="text-xs font-black text-primary-700 dark:text-primary-400 w-5 text-center">{{ $qtyInCart }}</span>

                                                    <div 
                                                        wire:click.stop="addToCart({{ $product->id }})"
                                                        class="w-7 h-7 rounded-full bg-primary-600 text-white flex items-center justify-center shadow-sm hover:bg-primary-700 transition-colors cursor-pointer"
                                                    >
                                                        <x-filament::icon icon="heroicon-m-plus" class="w-4 h-4" />
                                                    </div>
                                                </div>
                                            @else
                                                <div class="w-8 h-8 rounded-full bg-gray-100 dark:bg-gray-800 text-gray-500 dark:text-gray-400 flex items-center justify-center group-hover:bg-primary-600 group-hover:text-white transition-colors duration-300 shadow-sm">
                                                    <x-filament::icon icon="heroicon-m-plus" class="w-5 h-5" />
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="h-96 flex flex-col items-center justify-center text-gray-400">
                            <div class="w-24 h-24 bg-gray-200 dark:bg-gray-800 rounded-full flex items-center justify-center mb-6">
                                <x-filament::icon icon="heroicon-o-magnifying-glass" class="w-12 h-12 opacity-50" />
                            </div>
                            <h3 class="text-xl font-bold text-gray-900 dark:text-white">No items found</h3>
                            <p class="text-gray-500 mt-2">Try searching for something else</p>
                            <button wire:click="refreshProducts" class="mt-6 px-6 py-2 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-full font-bold text-primary-600 shadow-sm hover:shadow-md transition-all">
                                Show All Items
                            </button>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- RIGHT COLUMN: Cart & Checkout (30-35%) --}}
        <div class="w-[420px] flex-none flex flex-col bg-white dark:bg-gray-900 border-l border-gray-200 dark:border-gray-800 shadow-2xl z-30 relative">
            {{-- Order Context Header --}}
            <div class="flex-none p-5 bg-white dark:bg-gray-900 border-b border-gray-100 dark:border-gray-800 z-20">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-xl font-black text-gray-900 dark:text-white flex items-center gap-2">
                        <span>Current Order</span>
                        @if($currentOrderId)
                            <span class="px-2 py-0.5 bg-gray-100 dark:bg-gray-800 text-gray-500 text-xs rounded-md">#{{ $currentOrderId }}</span>
                        @endif
                    </h2>
                    <button wire:click="resetOrder" class="p-2 text-gray-400 hover:text-red-500 transition-colors" title="Clear Order">
                        <x-filament::icon icon="heroicon-m-trash" class="w-5 h-5" />
                    </button>
                </div>

                {{-- Order Type Switcher --}}
                <div class="grid grid-cols-3 gap-1 p-1.5 bg-gray-100 dark:bg-gray-800 rounded-2xl">
                    @foreach(['dine-in', 'takeout', 'delivery'] as $type)
                        <button
                            wire:click="setOrderType('{{ $type }}')"
                            class="py-2.5 rounded-xl text-xs font-bold uppercase tracking-wide transition-all {{ $orderType === $type ? 'bg-white dark:bg-gray-700 text-primary-600 shadow-sm ring-1 ring-black/5 dark:ring-white/10' : 'text-gray-500 hover:text-gray-700 dark:hover:text-gray-300' }}"
                        >
                            {{ Str::title(str_replace('-', ' ', $type)) }}
                        </button>
                    @endforeach
                </div>

                {{-- Table/Customer Context --}}
                <div class="mt-4 flex items-center gap-3">
                    @if($orderType === 'dine-in')
                        <button
                            wire:click="openTableSelector"
                            class="flex-1 h-12 flex items-center justify-center gap-2 rounded-xl border-2 {{ $tableNumber ? 'border-primary-500 bg-primary-50 dark:bg-primary-900/10 text-primary-700 dark:text-primary-400' : 'border-dashed border-gray-300 dark:border-gray-700 text-gray-400 hover:border-gray-400' }} font-bold transition-all"
                        >
                            @if($tableNumber)
                                <x-filament::icon icon="heroicon-m-table-cells" class="w-5 h-5" />
                                <span>{{ $tableNumber }}</span>
                            @else
                                <span>Select Table</span>
                            @endif
                        </button>
                    @endif
                    
                    <button class="h-12 w-12 flex items-center justify-center rounded-xl bg-gray-50 dark:bg-gray-800 text-gray-500 hover:bg-gray-100 dark:hover:bg-gray-700 border border-gray-100 dark:border-gray-700 transition-colors">
                        <x-filament::icon icon="heroicon-o-user" class="w-6 h-6" />
                    </button>
                </div>
            </div>

            {{-- Cart Items (Scrollable) --}}
            <div class="flex-1 overflow-y-auto p-4 space-y-3 no-scrollbar relative bg-gray-50/50 dark:bg-gray-900">
                @if(empty($cartItems))
                    <div class="absolute inset-0 flex flex-col items-center justify-center text-center p-8 opacity-40">
                        <div class="w-32 h-32 bg-gray-200 dark:bg-gray-800 rounded-full flex items-center justify-center mb-6">
                            <x-filament::icon icon="heroicon-o-shopping-bag" class="w-16 h-16 text-gray-400" />
                        </div>
                        <h4 class="text-xl font-bold text-gray-900 dark:text-white">Empty Order</h4>
                        <p class="text-sm text-gray-500 mt-2">Items you add will appear here</p>
                    </div>
                @else
                    @foreach($cartItems as $index => $item)
                        <div wire:key="cart-item-{{ $index }}" class="group relative bg-white dark:bg-gray-800 p-3 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 flex gap-3 transition-all hover:border-primary-300 dark:hover:border-primary-700">
                            {{-- Qty --}}
                            <div class="flex flex-col items-center justify-between w-10 bg-gray-50 dark:bg-gray-900 rounded-xl py-1">
                                <button wire:click="updateQuantity({{ $index }}, {{ $item['quantity'] + 1 }})" class="p-1 hover:text-primary-600 transition-colors"><x-filament::icon icon="heroicon-m-chevron-up" class="w-4 h-4" /></button>
                                <span class="font-bold text-sm">{{ $item['quantity'] }}</span>
                                <button wire:click="updateQuantity({{ $index }}, {{ $item['quantity'] - 1 }})" class="p-1 hover:text-red-500 transition-colors"><x-filament::icon icon="heroicon-m-chevron-down" class="w-4 h-4" /></button>
                            </div>

                            {{-- Details --}}
                            <div class="flex-1 min-w-0 flex flex-col justify-center">
                                <div class="flex justify-between items-start">
                                    <h4 class="font-bold text-gray-900 dark:text-white text-sm leading-tight pr-2">
                                        {{ $item['name'] }}
                                    </h4>
                                    <span class="font-bold text-gray-900 dark:text-white text-sm">
                                        {{ $this->formatCurrency($item['subtotal']) }}
                                    </span>
                                </div>
                                @if(!empty($item['variant_name']))
                                    <div class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">{{ $item['variant_name'] }}</div>
                                @endif
                                <div class="text-xs text-gray-400 mt-1">{{ $this->formatCurrency($item['price']) }}</div>
                            </div>

                            {{-- Remove --}}
                            <button wire:click="removeFromCart({{ $index }})" class="absolute -right-2 -top-2 bg-red-500 text-white rounded-full p-1 shadow-md opacity-0 group-hover:opacity-100 transition-opacity scale-90 hover:scale-110">
                                <x-filament::icon icon="heroicon-m-x-mark" class="w-3 h-3" />
                            </button>
                        </div>
                    @endforeach
                @endif
            </div>

            {{-- Footer: Totals & Action --}}
            <div class="flex-none bg-white dark:bg-gray-900 border-t border-gray-200 dark:border-gray-800 p-5 shadow-[0_-10px_40px_rgba(0,0,0,0.05)] z-30">
                <div class="space-y-3 mb-5">
                    <div class="flex justify-between text-sm text-gray-500 dark:text-gray-400">
                        <span>Subtotal</span>
                        <span class="font-medium text-gray-900 dark:text-white">{{ $this->formatCurrency(collect($cartItems)->sum('subtotal')) }}</span>
                    </div>
                    @if($discountValue > 0)
                        <div class="flex justify-between text-sm text-green-600 font-medium">
                            <span>Discount</span>
                            <span>-{{ $this->formatCurrency($discountValue) }}</span>
                        </div>
                    @endif
                    <div class="flex justify-between items-end pt-3 border-t border-dashed border-gray-200 dark:border-gray-700">
                        <span class="text-lg font-bold text-gray-900 dark:text-white">Total</span>
                        <span class="text-3xl font-black text-gray-900 dark:text-white tracking-tight leading-none">
                            {{ $this->formatCurrency($totalAmount) }}
                        </span>
                    </div>
                </div>

                {{-- Checkout Button --}}
                <button
                    wire:click="mountAction('placeOrder')"
                    @disabled(empty($cartItems) || ($orderType === 'dine-in' && !$tableNumber))
                    class="w-full h-16 rounded-2xl bg-gray-900 dark:bg-white text-white dark:text-gray-900 font-black text-lg shadow-xl shadow-gray-200 dark:shadow-none flex items-center justify-between px-8 hover:scale-[1.02] active:scale-[0.98] transition-all disabled:opacity-50 disabled:cursor-not-allowed"
                >
                    <span>Checkout</span>
                    <x-filament::icon icon="heroicon-m-arrow-right" class="w-6 h-6" />
                </button>
            </div>
        </div>
    </div>

    {{-- Overlay: Variant Selection --}}
    @if($selectedProductForVariant)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-gray-900/60 backdrop-blur-sm transition-all" wire:click="closeVariantSelection">
            <div class="w-full max-w-lg bg-white dark:bg-gray-900 rounded-3xl shadow-2xl overflow-hidden scale-100 transition-all animate-in zoom-in-95 duration-200" wire:click.stop>
                <div class="p-6 border-b border-gray-100 dark:border-gray-800 bg-gray-50/50 dark:bg-gray-800/50 flex items-center gap-4">
                    @php $selectedProduct = $products->firstWhere('id', $selectedProductForVariant); @endphp
                    <div class="h-16 w-16 bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden flex-none">
                        @if($selectedProduct?->image_url)
                            <img src="{{ $this->getProductImageUrl($selectedProduct->image_url) }}" class="w-full h-full object-cover">
                        @endif
                    </div>
                    <div>
                        <h3 class="text-xl font-bold text-gray-900 dark:text-white">{{ $selectedProduct?->name }}</h3>
                        <p class="text-sm text-gray-500">Select an option</p>
                    </div>
                </div>
                <div class="p-4 grid grid-cols-1 sm:grid-cols-2 gap-3 max-h-[60vh] overflow-y-auto">
                    @if($selectedProduct)
                        @foreach($selectedProduct->activeVariants as $variant)
                            <button
                                wire:click="selectVariant({{ $variant->id }})"
                                class="flex items-center justify-between p-4 rounded-2xl border-2 border-gray-100 dark:border-gray-800 bg-white dark:bg-gray-800 hover:border-primary-500 hover:bg-primary-50 dark:hover:bg-primary-900/10 transition-all group"
                            >
                                <span class="font-bold text-gray-700 dark:text-gray-200 group-hover:text-primary-700">{{ $variant->name }}</span>
                                <span class="font-extrabold text-gray-900 dark:text-white">{{ $this->formatCurrency($variant->price) }}</span>
                            </button>
                        @endforeach
                    @endif
                </div>
                <div class="p-4 bg-gray-50 dark:bg-gray-950">
                    <button wire:click="closeVariantSelection" class="w-full py-3 rounded-xl font-bold text-gray-500 hover:bg-gray-200 dark:hover:bg-gray-800 transition-colors">Cancel</button>
                </div>
            </div>
        </div>
    @endif

    {{-- Overlay: Table Selection --}}
    @if($isTableSelectorOpen)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-gray-900/60 backdrop-blur-sm transition-all" wire:click="closeTableSelector">
            <div class="w-full max-w-4xl bg-white dark:bg-gray-900 rounded-3xl shadow-2xl overflow-hidden scale-100 transition-all animate-in zoom-in-95 duration-200" wire:click.stop>
                <div class="p-6 border-b border-gray-100 dark:border-gray-800 flex items-center justify-between">
                    <h3 class="text-2xl font-black text-gray-900 dark:text-white">Select Table</h3>
                    <button wire:click="closeTableSelector" class="p-2 bg-gray-100 dark:bg-gray-800 rounded-full hover:bg-gray-200 dark:hover:bg-gray-700 transition-colors">
                        <x-filament::icon icon="heroicon-m-x-mark" class="w-6 h-6 text-gray-500" />
                    </button>
                </div>
                <div class="p-6 grid grid-cols-4 sm:grid-cols-5 md:grid-cols-6 gap-4 max-h-[60vh] overflow-y-auto bg-gray-50 dark:bg-gray-950">
                    @foreach(\App\Enums\TableNumber::cases() as $table)
                        <button
                            wire:click="selectTable('{{ $table->value }}')"
                            class="aspect-square flex flex-col items-center justify-center gap-2 rounded-2xl border-2 transition-all {{ $tableNumber === $table->value ? 'border-primary-600 bg-primary-600 text-white shadow-xl scale-105' : 'border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-600 dark:text-gray-300 hover:border-primary-400 hover:text-primary-600 hover:shadow-md' }}"
                        >
                            <span class="text-2xl font-black">{{ str_replace('Table ', '', $table->getLabel()) }}</span>
                            <span class="text-[10px] font-bold uppercase tracking-wider opacity-80">Table</span>
                        </button>
                    @endforeach
                </div>
            </div>
        </div>
    @endif

    <x-filament-actions::modals />
</x-filament-panels::page>
