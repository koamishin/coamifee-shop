<div class="space-y-3">
    <div class="flex items-center justify-between">
        <div class="text-sm font-bold text-gray-900">Order Items</div>
        <div class="text-xs font-semibold text-gray-500">Tap + / − to adjust</div>
    </div>

    @if(empty($cartItems))
        <div class="p-4 rounded-xl bg-gray-50 border border-gray-200 text-center text-gray-600">
            Cart is empty.
        </div>
    @else
        <div class="space-y-3">
            @foreach($cartItems as $index => $item)
                @php
                    $discountPercentage = (float) ($item['discount_percentage'] ?? 0);
                    $lineSubtotal = (float) ($item['subtotal'] ?? 0);
                    $discountAmount = $discountPercentage > 0 ? $lineSubtotal * ($discountPercentage / 100) : 0;
                    $finalLineTotal = $lineSubtotal - $discountAmount;
                @endphp

                <div class="rounded-2xl border border-gray-200 bg-gradient-to-r from-white to-orange-50/40 p-4">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0 flex-1">
                            <div class="text-base font-extrabold text-gray-900 leading-tight">
                                {{ $item['name'] ?? 'Item' }}
                            </div>
                            <div class="mt-1 text-sm font-semibold text-gray-700">
                                {{ $formatCurrency((float) ($item['price'] ?? 0)) }}
                            </div>

                            <div class="mt-3 grid grid-cols-1 sm:grid-cols-2 gap-3">
                                <div>
                                    <label class="text-xs font-bold text-gray-500">DISCOUNT</label>
                                    <select
                                        wire:model.live="cartItems.{{ $index }}.discount_type"
                                        class="mt-1 w-full h-12 rounded-xl border border-gray-300 bg-white px-3 text-base font-semibold focus:border-orange-400 focus:ring-orange-200"
                                    >
                                        <option value="">None</option>
                                        @foreach($discountOptions as $value => $label)
                                            <option value="{{ $value }}">{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="rounded-xl border border-gray-200 bg-white p-3">
                                    <div class="text-xs font-bold text-gray-500">LINE TOTAL</div>
                                    @if($discountPercentage > 0)
                                        <div class="mt-1 text-xs font-semibold text-gray-500 line-through">
                                            {{ $formatCurrency($lineSubtotal) }}
                                        </div>
                                        <div class="text-sm font-extrabold text-green-700">
                                            −{{ $formatCurrency($discountAmount) }} ({{ (int) $discountPercentage }}%)
                                        </div>
                                    @endif
                                    <div class="mt-1 text-2xl font-extrabold text-orange-700">
                                        {{ $formatCurrency($finalLineTotal) }}
                                    </div>
                                </div>
                            </div>
                        </div>

                        <button
                            type="button"
                            wire:click="removeFromCart({{ $index }})"
                            class="h-12 w-12 rounded-2xl bg-white border border-gray-300 flex items-center justify-center hover:bg-red-50 hover:border-red-300"
                        >
                            <x-filament::icon icon="heroicon-o-x-mark" class="w-6 h-6 text-red-600" />
                        </button>
                    </div>

                    <div class="mt-4 flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <button
                                type="button"
                                wire:click="updateQuantity({{ $index }}, {{ (int) ($item['quantity'] ?? 1) - 1 }})"
                                class="h-14 w-14 rounded-2xl bg-white border border-gray-300 flex items-center justify-center active:scale-[0.98]"
                            >
                                <x-filament::icon icon="heroicon-o-minus" class="w-6 h-6 text-gray-800" />
                            </button>

                            <div class="w-14 text-center text-2xl font-extrabold text-gray-900">
                                {{ (int) ($item['quantity'] ?? 1) }}
                            </div>

                            <button
                                type="button"
                                wire:click="updateQuantity({{ $index }}, {{ (int) ($item['quantity'] ?? 1) + 1 }})"
                                class="h-14 w-14 rounded-2xl bg-gradient-to-r from-orange-500 to-orange-600 border border-orange-600 flex items-center justify-center active:scale-[0.98]"
                            >
                                <x-filament::icon icon="heroicon-o-plus" class="w-6 h-6 text-white" />
                            </button>
                        </div>

                        <div class="text-xs font-bold text-gray-500">
                            Qty
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>
