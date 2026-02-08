<div class="space-y-6">
    {{-- Order Type Selector --}}
    <div>
        <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-2 uppercase tracking-wide">Order Type</label>
        <div class="grid grid-cols-3 gap-3">
            @foreach(['dine-in' => 'Dine In', 'takeout' => 'Takeout', 'delivery' => 'Delivery'] as $key => $label)
                <button
                    type="button"
                    wire:click="$set('orderType', '{{ $key }}')"
                    class="flex flex-col items-center justify-center p-4 rounded-xl border-2 transition-all {{ $orderType === $key ? 'border-primary-600 bg-primary-50 dark:bg-primary-900/20 text-primary-700 dark:text-primary-400' : 'border-gray-200 dark:border-gray-700 hover:border-gray-300 dark:hover:border-gray-600 bg-white dark:bg-gray-800 text-gray-600 dark:text-gray-300' }}"
                >
                    <div class="mb-2">
                        @if($key === 'dine-in') <x-filament::icon icon="heroicon-o-building-storefront" class="w-8 h-8" />
                        @elseif($key === 'takeout') <x-filament::icon icon="heroicon-o-shopping-bag" class="w-8 h-8" />
                        @elseif($key === 'delivery') <x-filament::icon icon="heroicon-o-truck" class="w-8 h-8" />
                        @endif
                    </div>
                    <span class="font-bold">{{ $label }}</span>
                </button>
            @endforeach
        </div>
    </div>

    {{-- Dine-In: Table Selection --}}
    @if($orderType === 'dine-in')
        <div class="animate-in fade-in slide-in-from-top-4 duration-300">
            <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-2 uppercase tracking-wide">Select Table</label>
            <div class="grid grid-cols-4 sm:grid-cols-6 gap-3">
                @foreach(\App\Enums\TableNumber::cases() as $table)
                    <button
                        type="button"
                        wire:click="$set('tableNumber', '{{ $table->value }}')"
                        class="aspect-square flex items-center justify-center rounded-xl border-2 font-bold text-lg transition-all {{ $tableNumber === $table->value ? 'border-primary-600 bg-primary-600 text-white shadow-lg' : 'border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-600 dark:text-gray-300 hover:border-primary-400 hover:text-primary-600' }}"
                    >
                        {{ str_replace('Table ', '', $table->getLabel()) }}
                    </button>
                @endforeach
            </div>
            @if(!$tableNumber)
                <p class="mt-2 text-sm text-red-500 font-medium animate-pulse">Please select a table to proceed.</p>
            @endif
        </div>
    @endif

    {{-- Delivery: Provider Selection --}}
    @if($orderType === 'delivery')
        <div class="animate-in fade-in slide-in-from-top-4 duration-300">
            <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-2 uppercase tracking-wide">Delivery Provider</label>
            <div class="grid grid-cols-2 gap-4">
                @foreach(['grab' => 'GrabFood', 'food_panda' => 'FoodPanda'] as $key => $label)
                    <button
                        type="button"
                        wire:click="$set('deliveryProvider', '{{ $key }}')"
                        class="flex items-center gap-4 p-4 rounded-xl border-2 transition-all {{ $deliveryProvider === $key ? 'border-primary-600 bg-primary-50 dark:bg-primary-900/20 text-primary-700 dark:text-primary-400' : 'border-gray-200 dark:border-gray-700 hover:border-gray-300 bg-white dark:bg-gray-800 text-gray-600' }}"
                    >
                        <div class="w-10 h-10 rounded-full flex items-center justify-center {{ $key === 'grab' ? 'bg-green-100 text-green-600' : 'bg-pink-100 text-pink-600' }}">
                            <x-filament::icon icon="heroicon-o-truck" class="w-6 h-6" />
                        </div>
                        <span class="font-bold text-lg">{{ $label }}</span>
                    </button>
                @endforeach
            </div>
        </div>
    @endif

    {{-- Customer Selection --}}
    <div>
        <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-2 uppercase tracking-wide">Customer</label>
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
            <button
                type="button"
                wire:click="$set('customerId', null); $set('customerName', '')"
                class="p-3 rounded-lg border-2 text-center text-sm font-bold transition-all {{ is_null($customerId) ? 'border-primary-600 bg-primary-50 text-primary-700' : 'border-gray-200 bg-white text-gray-600 hover:border-gray-300' }}"
            >
                Walk-in
            </button>
            @foreach($customers->take(7) as $customer)
                <button
                    type="button"
                    wire:click="$set('customerId', {{ $customer->id }}); $set('customerName', '{{ $customer->name }}')"
                    class="p-3 rounded-lg border-2 text-center text-sm font-bold truncate transition-all {{ $customerId === $customer->id ? 'border-primary-600 bg-primary-50 text-primary-700' : 'border-gray-200 bg-white text-gray-600 hover:border-gray-300' }}"
                >
                    {{ $customer->name }}
                </button>
            @endforeach
        </div>
    </div>
</div>
