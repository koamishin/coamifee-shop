<x-filament-panels::page>
    <div class="bg-gradient-to-br from-amber-50 via-orange-50 to-yellow-50 -m-6 h-[calc(100vh-4rem)] overflow-hidden">
        {{-- Status & Time Header --}}
        <div class="absolute top-3 right-16 z-50 flex items-center gap-3 px-4 py-1.5">
            <div class="flex items-center gap-1.5">
                <div class="w-2 h-2 rounded-full bg-green-500 animate-pulse"></div>
                <span class="text-xs font-semibold text-gray-700">Online</span>
            </div>
            <span class="text-gray-300">•</span>
            <div class="text-xs font-medium text-gray-600 flex items-center gap-2">
                <span id="manila-date" x-data="{ date: '' }" x-init="
                    const updateDateTime = () => {
                        const now = new Date();
                        const manilaTime = new Date(now.toLocaleString('en-US', { timeZone: 'Asia/Manila' }));
                        const options = { weekday: 'short', year: 'numeric', month: 'short', day: 'numeric' };
                        const dateStr = manilaTime.toLocaleDateString('en-US', options);
                        const hours = String(manilaTime.getHours()).padStart(2, '0');
                        const minutes = String(manilaTime.getMinutes()).padStart(2, '0');
                        const seconds = String(manilaTime.getSeconds()).padStart(2, '0');
                        document.getElementById('manila-date').innerText = dateStr;
                        document.getElementById('manila-clock').innerText = hours + ':' + minutes + ':' + seconds;
                    };
                    updateDateTime();
                    setInterval(updateDateTime, 1000);
                ">--</span>
                <span id="manila-clock">--:--:--</span>
            </div>
        </div>

        {{-- Loading overlay to prevent double-taps on tablets --}}
        <div
            wire:loading.flex
            wire:target="addToCart,updateQuantity,removeFromCart,clearCart,mountAction,createOrder"
            class="absolute inset-0 z-40 bg-black/10 backdrop-blur-[1px] items-center justify-center"
        >
            <div class="bg-white rounded-xl shadow-lg border border-gray-200 px-4 py-3 flex items-center gap-3">
                <x-filament::icon icon="heroicon-o-arrow-path" class="w-5 h-5 text-orange-600 animate-spin" />
                <div class="text-sm font-semibold text-gray-800">Processing…</div>
            </div>
        </div>

        <div class="h-full flex flex-col lg:flex-row gap-3 p-3 overflow-hidden">
            {{-- LEFT: Category + Products (tap-first) --}}
            <div class="flex-1 lg:w-[70%] flex flex-col gap-3 min-h-0">
                {{-- Category bar + optional search --}}
                <div class="bg-white rounded-xl shadow-sm border border-orange-100 p-3">
                    <div class="overflow-x-auto scrollbar-hide -mx-1 px-1">
                        <div class="flex gap-2 pb-1">
                            <button
                                wire:click="selectCategory(null)"
                                class="flex-shrink-0 px-4 py-3 rounded-xl font-semibold text-sm transition-all min-w-[96px] touch-manipulation {{ $selectedCategoryId === null ? 'bg-gradient-to-r from-amber-500 to-orange-600 text-white shadow-md' : 'bg-gray-100 text-gray-800 hover:bg-gray-200' }}"
                            >
                                <div class="flex items-center justify-center gap-2">
                                    <span class="text-base">☕</span>
                                    <span>All</span>
                                </div>
                            </button>

                            @if(isset($categories) && $categories->count() > 0)
                                @foreach($categories as $category)
                                    <button
                                        wire:click="selectCategory({{ $category->id }})"
                                        wire:key="category-{{ $category->id }}"
                                        class="flex-shrink-0 px-4 py-3 rounded-xl font-semibold text-sm transition-all min-w-[96px] touch-manipulation {{ $selectedCategoryId === $category->id ? 'bg-gradient-to-r from-amber-500 to-orange-600 text-white shadow-md' : 'bg-gray-100 text-gray-800 hover:bg-gray-200' }}"
                                    >
                                        <div class="flex items-center justify-center gap-2">
                                            @if($category->icon)
                                                <x-filament::icon icon="{{ $category->icon }}" class="w-4 h-4" />
                                            @endif
                                            <span class="whitespace-nowrap">{{ $category->name }}</span>
                                        </div>
                                    </button>
                                @endforeach
                            @endif
                        </div>
                    </div>

                    <div class="mt-3 flex items-center gap-2">
                        <div class="relative flex-1">
                            <x-filament::icon icon="heroicon-o-magnifying-glass" class="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400 pointer-events-none" />
                            <input
                                type="text"
                                wire:model.live.debounce.250ms="search"
                                placeholder="Search (optional)"
                                class="w-full pl-10 pr-12 py-3 text-base border border-gray-200 rounded-xl focus:ring-2 focus:ring-orange-200 focus:border-orange-400 transition-all bg-white"
                            />
                            <div wire:loading wire:target="search" class="absolute right-3 top-1/2 -translate-y-1/2">
                                <x-filament::icon icon="heroicon-o-arrow-path" class="w-5 h-5 text-orange-500 animate-spin" />
                            </div>
                            @if(!empty($search))
                                <button
                                    type="button"
                                    wire:click="search = ''"
                                    class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-500 hover:text-gray-700"
                                    title="Clear"
                                >
                                    <x-filament::icon icon="heroicon-o-x-mark" class="w-5 h-5" />
                                </button>
                            @endif
                        </div>
                    </div>
                </div>

                {{-- Products grid (bigger targets, no per-item stock queries) --}}
                <div class="flex-1 bg-white rounded-xl shadow-sm border border-orange-100 p-3 overflow-hidden flex flex-col min-h-0">
                    <div class="flex items-center justify-between mb-3">
                        <h2 class="font-bold text-base text-gray-900">Tap items to add</h2>
                        <span class="text-sm font-semibold text-gray-600 bg-gray-100 px-3 py-1 rounded-full">
                            {{ $products?->count() ?? 0 }}
                        </span>
                    </div>

                    @php
                        $cartQuantitiesByProduct = collect($this->cartItems)
                            ->groupBy('product_id')
                            ->map(fn ($items) => $items->sum('quantity'))
                            ->all();
                    @endphp

                    <div class="flex-1 overflow-y-auto scrollbar-thin scrollbar-thumb-orange-300 scrollbar-track-gray-100">
                        @if(isset($products) && $products->count() > 0)
                            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-3">
                                @foreach($products as $product)
                                    @php
                                        $availability = $productAvailability[$product->id] ?? null;
                                        $stockStatus = $availability['stock_status'] ?? 'in_stock';
                                        $qtyInCart = $cartQuantitiesByProduct[$product->id] ?? 0;
                                        $isInCart = $qtyInCart > 0;
                                        $isOut = $stockStatus === 'out_of_stock';
                                        $isLow = $stockStatus === 'low_stock';
                                    @endphp

                                    <button
                                        type="button"
                                        wire:click="addToCart({{ $product->id }})"
                                        wire:key="product-{{ $product->id }}"
                                        wire:loading.attr="disabled"
                                        wire:target="addToCart"
                                        {{ $isOut ? 'disabled' : '' }}
                                        class="group relative bg-white border-2 rounded-2xl p-3 transition-all touch-manipulation active:scale-[0.98] min-h-[150px] flex flex-col {{ $isInCart ? 'border-orange-500 bg-orange-50 shadow-md' : 'border-gray-200 hover:border-orange-300 hover:shadow-md' }} {{ $isOut ? 'opacity-50 cursor-not-allowed' : '' }}"
                                    >
                                        <div class="relative">
                                            <div class="aspect-square rounded-xl overflow-hidden bg-gradient-to-br from-gray-100 to-gray-200">
                                                @if($product->image_url)
                                                    <img
                                                        src="{{ $this->getProductImageUrl($product->image_url) }}"
                                                        alt="{{ $product->name }}"
                                                        class="w-full h-full object-cover"
                                                        loading="lazy"
                                                    />
                                                @else
                                                    <div class="w-full h-full flex items-center justify-center">
                                                        <x-filament::icon icon="heroicon-o-photo" class="w-10 h-10 text-gray-400" />
                                                    </div>
                                                @endif
                                            </div>

                                            @if($isOut)
                                                <div class="absolute inset-0 bg-black/60 flex items-center justify-center rounded-xl">
                                                    <span class="bg-red-500 text-white px-3 py-1 rounded-full text-sm font-bold">Out</span>
                                                </div>
                                            @endif

                                            @if($isLow && ! $isOut)
                                                <div class="absolute bottom-2 left-2">
                                                    <span class="bg-amber-500 text-white px-2 py-1 rounded-full text-xs font-bold">Low</span>
                                                </div>
                                            @endif

                                            @if($isInCart)
                                                <div class="absolute top-2 right-2 bg-orange-600 text-white px-2.5 py-1 rounded-full text-sm font-extrabold">
                                                    {{ $qtyInCart }}
                                                </div>
                                            @endif
                                        </div>

                                        <div class="mt-3 flex-1 flex flex-col">
                                            <h3 class="font-bold text-sm text-gray-900 line-clamp-2 text-left leading-snug">
                                                {{ $product->name }}
                                            </h3>
                                            <div class="mt-auto flex items-center justify-between">
                                                <div class="text-lg font-extrabold {{ $isInCart ? 'text-orange-700' : 'text-gray-900' }}">
                                                    {{ $this->formatCurrency($product->price) }}
                                                </div>
                                                <div class="text-xs font-semibold text-gray-500">Tap</div>
                                            </div>
                                        </div>
                                    </button>
                                @endforeach
                            </div>
                        @else
                            <div class="flex flex-col items-center justify-center h-full text-gray-400">
                                <x-filament::icon icon="heroicon-o-inbox" class="w-16 h-16 mb-3" />
                                <h3 class="text-base font-semibold text-gray-700">No products found</h3>
                                <p class="text-sm text-gray-500">Try clearing search or switching category.</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            {{-- RIGHT: Order setup + Cart (bigger controls) --}}
            <div class="lg:w-[30%] flex flex-col gap-3 min-h-0 overflow-hidden">
                {{-- Order type --}}
                <div class="bg-white rounded-xl shadow-sm border border-orange-100 p-3">
                    <div class="grid grid-cols-3 gap-2">
                        <button
                            type="button"
                            wire:click="setOrderType('dine_in')"
                            class="flex flex-col items-center justify-center p-3 rounded-xl border-2 transition-all touch-manipulation {{ $orderType === 'dine_in' ? 'border-orange-500 bg-orange-50 text-orange-700' : 'border-gray-200 hover:border-gray-300 text-gray-700' }}"
                        >
                            <span class="text-2xl">🍽️</span>
                            <span class="mt-1 text-sm font-bold">Dine In</span>
                        </button>
                        <button
                            type="button"
                            wire:click="setOrderType('takeaway')"
                            class="flex flex-col items-center justify-center p-3 rounded-xl border-2 transition-all touch-manipulation {{ $orderType === 'takeaway' ? 'border-orange-500 bg-orange-50 text-orange-700' : 'border-gray-200 hover:border-gray-300 text-gray-700' }}"
                        >
                            <span class="text-2xl">🥡</span>
                            <span class="mt-1 text-sm font-bold">Takeaway</span>
                        </button>
                        <button
                            type="button"
                            wire:click="setOrderType('delivery')"
                            class="flex flex-col items-center justify-center p-3 rounded-xl border-2 transition-all touch-manipulation {{ $orderType === 'delivery' ? 'border-orange-500 bg-orange-50 text-orange-700' : 'border-gray-200 hover:border-gray-300 text-gray-700' }}"
                        >
                            <span class="text-2xl">🚗</span>
                            <span class="mt-1 text-sm font-bold">Delivery</span>
                        </button>
                    </div>
                </div>

                {{-- Table selector (tap to select) --}}
                @if($orderType === 'dine_in')
                    <div class="bg-white rounded-xl shadow-sm border border-orange-100 p-3">
                        <div class="flex items-center justify-between mb-3">
                            <div class="text-sm font-bold text-gray-900">Table</div>
                            @php
                                $tableLabel = $tableNumber ? (\App\Enums\TableNumber::fromValue($tableNumber)?->getLabel() ?? $tableNumber) : 'Select';
                            @endphp
                            <div class="text-sm font-extrabold {{ $tableNumber ? 'text-orange-700' : 'text-gray-500' }}">{{ $tableLabel }}</div>
                        </div>

                        <div class="grid grid-cols-5 gap-2">
                            @foreach(\App\Enums\TableNumber::getOptions() as $value => $label)
                                @php
                                    $number = str_replace('Table ', '', $label);
                                @endphp
                                <button
                                    type="button"
                                    wire:click="selectTable('{{ $value }}')"
                                    class="h-12 rounded-xl border-2 font-extrabold text-base transition-all touch-manipulation {{ $tableNumber === $value ? 'border-orange-500 bg-orange-50 text-orange-700' : 'border-gray-200 text-gray-800 hover:bg-gray-50' }}"
                                >
                                    {{ $number }}
                                </button>
                            @endforeach
                        </div>
                    </div>
                @endif

                {{-- Cart --}}
                <div class="flex-1 bg-white rounded-xl shadow-sm border border-orange-100 p-3 flex flex-col min-h-0 overflow-hidden">
                    <div class="flex items-center justify-between mb-3">
                        <div class="flex items-center gap-2">
                            <x-filament::icon icon="heroicon-o-shopping-cart" class="w-5 h-5 text-orange-600" />
                            <h2 class="font-bold text-base text-gray-900">Cart</h2>
                            @if(!empty($this->cartItems))
                                <span class="bg-orange-600 text-white text-sm font-extrabold px-2.5 py-1 rounded-full">
                                    {{ array_sum(array_column($this->cartItems, 'quantity')) }}
                                </span>
                            @endif
                        </div>

                        <div class="flex items-center gap-2">
                            <button
                                type="button"
                                wire:click="resetOrder"
                                class="h-10 px-3 rounded-xl border border-gray-300 text-sm font-bold text-gray-800 hover:bg-gray-50"
                            >
                                New
                            </button>

                            @if(!empty($this->cartItems))
                                <button
                                    type="button"
                                    wire:click="clearCart"
                                    class="h-10 px-3 rounded-xl border border-red-200 bg-red-50 text-sm font-bold text-red-700 hover:bg-red-100"
                                >
                                    Clear
                                </button>
                            @endif
                        </div>
                    </div>

                    <div class="flex-1 overflow-y-auto scrollbar-thin scrollbar-thumb-orange-300 scrollbar-track-gray-100">
                        @if(!empty($this->cartItems))
                            <div class="space-y-3">
                                @foreach($this->cartItems as $index => $item)
                                    <div class="bg-gradient-to-r from-gray-50 to-orange-50/30 rounded-xl p-3 border border-gray-200">
                                        <div class="flex items-start justify-between gap-2">
                                            <div class="min-w-0 flex-1">
                                                <div class="font-extrabold text-sm text-gray-900 leading-snug">
                                                    {{ $item['name'] }}
                                                </div>
                                                <div class="text-sm font-semibold text-gray-600">
                                                    {{ $this->formatCurrency($item['price']) }}
                                                </div>
                                            </div>

                                            <button
                                                type="button"
                                                wire:click="removeFromCart({{ $index }})"
                                                class="h-10 w-10 rounded-xl bg-white border border-gray-300 flex items-center justify-center hover:bg-red-50 hover:border-red-300 transition-colors"
                                            >
                                                <x-filament::icon icon="heroicon-o-x-mark" class="w-5 h-5 text-red-600" />
                                            </button>
                                        </div>

                                        <div class="mt-3 flex items-center justify-between">
                                            <div class="flex items-center gap-2">
                                                <button
                                                    type="button"
                                                    wire:click="updateQuantity({{ $index }}, {{ $item['quantity'] - 1 }})"
                                                    class="h-12 w-12 rounded-xl bg-white border border-gray-300 flex items-center justify-center hover:bg-orange-50 hover:border-orange-300 transition-all touch-manipulation active:scale-[0.98]"
                                                >
                                                    <x-filament::icon icon="heroicon-o-minus" class="w-5 h-5 text-gray-800" />
                                                </button>

                                                <div class="w-12 text-center text-lg font-extrabold text-gray-900">
                                                    {{ $item['quantity'] }}
                                                </div>

                                                <button
                                                    type="button"
                                                    wire:click="updateQuantity({{ $index }}, {{ $item['quantity'] + 1 }})"
                                                    class="h-12 w-12 rounded-xl bg-gradient-to-r from-orange-500 to-orange-600 border border-orange-600 flex items-center justify-center hover:from-orange-600 hover:to-orange-700 transition-all touch-manipulation active:scale-[0.98]"
                                                >
                                                    <x-filament::icon icon="heroicon-o-plus" class="w-5 h-5 text-white" />
                                                </button>
                                            </div>

                                            <div class="text-lg font-extrabold text-orange-700">
                                                {{ $this->formatCurrency($item['subtotal']) }}
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <div class="flex flex-col items-center justify-center h-full text-gray-400">
                                <x-filament::icon icon="heroicon-o-shopping-cart" class="w-12 h-12 mb-2" />
                                <h3 class="text-base font-semibold text-gray-700">Cart is empty</h3>
                                <p class="text-sm text-gray-500">Tap products to add.</p>
                            </div>
                        @endif
                    </div>
                </div>

                {{-- Checkout sticky footer --}}
                @if(!empty($this->cartItems))
                    <div class="bg-gradient-to-br from-orange-500 to-orange-600 rounded-xl shadow-lg border-2 border-orange-400 p-3">
                        @if($orderType === 'dine_in' && empty($tableNumber))
                            <div class="mb-3 bg-white/20 rounded-xl p-3 text-white">
                                <div class="text-sm font-bold">Select a table to continue</div>
                                <div class="text-xs text-orange-100">Dine-in orders require a table number.</div>
                            </div>
                        @endif

                        <div class="bg-white/20 backdrop-blur-sm rounded-xl p-3">
                            <div class="flex items-center justify-between text-white">
                                <div class="text-sm font-bold">TOTAL</div>
                                <div class="text-2xl font-extrabold">{{ $this->formatCurrency($totalAmount) }}</div>
                            </div>
                        </div>

                        <button
                            type="button"
                            wire:click="mountAction('placeOrder')"
                            wire:loading.attr="disabled"
                            wire:target="mountAction"
                            {{ $orderType === 'dine_in' && empty($tableNumber) ? 'disabled' : '' }}
                            class="mt-3 w-full bg-white text-orange-700 hover:bg-orange-50 font-extrabold py-4 rounded-xl transition-all flex items-center justify-center text-base shadow-lg touch-manipulation active:scale-[0.99] disabled:opacity-60 disabled:cursor-not-allowed"
                        >
                            <x-filament::icon icon="heroicon-o-shopping-bag" class="w-6 h-6 mr-2" />
                            Send Order
                        </button>

                        <x-filament-actions::modals />
                    </div>
                @endif
            </div>
        </div>
    </div>

    {{-- Variant Selection Modal --}}
    @if($selectedProductForVariant)
        @php
            $selectedProduct = $products->firstWhere('id', $selectedProductForVariant);
        @endphp

        @if($selectedProduct && $selectedProduct->activeVariants->isNotEmpty())
            <div
                x-data="{ show: true }"
                x-show="show"
                x-transition:enter="transition ease-out duration-300"
                x-transition:enter-start="opacity-0"
                x-transition:enter-end="opacity-100"
                x-transition:leave="transition ease-in duration-200"
                x-transition:leave-start="opacity-100"
                x-transition:leave-end="opacity-0"
                class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm"
                wire:key="variant-modal-{{ $selectedProductForVariant }}"
            >
                <div
                    x-show="show"
                    x-transition:enter="transition ease-out duration-300 transform"
                    x-transition:enter-start="opacity-0 scale-95"
                    x-transition:enter-end="opacity-100 scale-100"
                    x-transition:leave="transition ease-in duration-200 transform"
                    x-transition:leave-start="opacity-100 scale-100"
                    x-transition:leave-end="opacity-0 scale-95"
                    @click.away="$wire.closeVariantSelection()"
                    class="bg-white rounded-2xl shadow-2xl max-w-lg w-full overflow-hidden"
                >
                    <div class="bg-gradient-to-r from-orange-500 to-orange-600 p-6 text-white">
                        <div class="flex items-start justify-between">
                            <div class="flex-1">
                                <h3 class="text-xl font-extrabold">{{ $selectedProduct->name }}</h3>
                                <p class="text-sm text-orange-100 mt-1">Choose a variant</p>
                            </div>
                            <button
                                type="button"
                                wire:click="closeVariantSelection"
                                class="text-white/80 hover:text-white transition-colors p-2 hover:bg-white/20 rounded-xl"
                            >
                                <x-filament::icon icon="heroicon-o-x-mark" class="w-6 h-6" />
                            </button>
                        </div>
                    </div>

                    <div class="p-6 max-h-96 overflow-y-auto">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            @foreach($selectedProduct->activeVariants as $variant)
                                <button
                                    type="button"
                                    wire:click="selectVariant({{ $variant->id }})"
                                    wire:key="variant-option-{{ $variant->id }}"
                                    class="group relative bg-gradient-to-br from-white to-gray-50 border-2 border-gray-200 hover:border-orange-400 rounded-2xl p-4 transition-all touch-manipulation active:scale-[0.98] hover:shadow-lg"
                                >
                                    <div class="text-center">
                                        <div class="text-lg font-extrabold text-gray-900">{{ $variant->name }}</div>
                                        <div class="mt-1 text-2xl font-extrabold text-orange-700">{{ $this->formatCurrency($variant->price) }}</div>
                                    </div>

                                    @if($variant->is_default)
                                        <div class="absolute top-2 right-2">
                                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold bg-green-100 text-green-800">
                                                Default
                                            </span>
                                        </div>
                                    @endif
                                </button>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        @endif
    @endif

    <style>
        .scrollbar-hide::-webkit-scrollbar {
            display: none;
        }

        .scrollbar-hide {
            -ms-overflow-style: none;
            scrollbar-width: none;
        }

        .scrollbar-thin::-webkit-scrollbar {
            width: 6px;
            height: 6px;
        }

        .scrollbar-thin::-webkit-scrollbar-track {
            background: rgb(243 244 246);
            border-radius: 9999px;
        }

        .scrollbar-thin::-webkit-scrollbar-thumb {
            background: rgb(253 186 116);
            border-radius: 9999px;
        }

        .scrollbar-thin::-webkit-scrollbar-thumb:hover {
            background: rgb(251 146 60);
        }

        @media (hover: none) and (pointer: coarse) {
            button {
                min-height: 44px;
            }
        }
    </style>
</x-filament-panels::page>
