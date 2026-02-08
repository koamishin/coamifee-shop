<div class="space-y-4">
    <div class="flex items-center justify-between px-1">
        <h3 class="text-sm font-bold text-gray-900 dark:text-white uppercase tracking-wider">Order Summary</h3>
        <span class="text-xs font-medium text-gray-500 dark:text-gray-400">{{ count($cartItems) }} Items</span>
    </div>

    @if(empty($cartItems))
        <div class="rounded-2xl border border-dashed border-gray-300 dark:border-gray-700 p-8 text-center">
            <x-filament::icon icon="heroicon-o-shopping-cart" class="mx-auto h-10 w-10 text-gray-400 mb-2" />
            <p class="text-sm text-gray-500 dark:text-gray-400">Cart is empty</p>
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

                <div class="group flex items-start gap-4 rounded-2xl bg-gray-50 dark:bg-gray-800 p-4 border border-gray-100 dark:border-gray-700 transition-colors hover:border-gray-300 dark:hover:border-gray-600">
                    {{-- Qty Badge --}}
                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-white dark:bg-gray-700 border border-gray-200 dark:border-gray-600 shadow-sm font-bold text-gray-900 dark:text-white">
                        {{ (int) ($item['quantity'] ?? 1) }}
                    </div>

                    <div class="flex-1 min-w-0">
                        <div class="flex justify-between items-start">
                            <div>
                                <h4 class="font-bold text-gray-900 dark:text-white text-base leading-tight">
                                    {{ $item['name'] ?? 'Item' }}
                                </h4>
                                @if(!empty($item['variant_name']))
                                    <div class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                                        {{ $item['variant_name'] }}
                                    </div>
                                @endif
                            </div>
                            <div class="text-right">
                                <div class="font-bold text-gray-900 dark:text-white">
                                    {{ $this->formatCurrency($finalLineTotal) }}
                                </div>
                                @if($discountPercentage > 0)
                                    <div class="text-xs font-medium text-green-600 dark:text-green-400">
                                        -{{ $this->formatCurrency($discountAmount) }}
                                    </div>
                                @endif
                            </div>
                        </div>

                        {{-- Controls --}}
                        <div class="mt-3 flex items-center justify-between gap-4 border-t border-gray-200 dark:border-gray-700 pt-3 opacity-100">
                            <div class="flex-1">
                                <select
                                    wire:model.live="cartItems.{{ $index }}.discount_type"
                                    class="w-full h-9 rounded-lg border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-xs font-medium text-gray-700 dark:text-gray-200 focus:border-primary-500 focus:ring-primary-500"
                                >
                                    <option value="">No Discount</option>
                                    @foreach($discountOptions ?? [] as $value => $label)
                                        <option value="{{ $value }}">{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                            
                            {{-- Remove Button --}}
                            <button
                                type="button"
                                wire:click="removeFromCart({{ $index }})"
                                class="flex h-9 w-9 items-center justify-center rounded-lg bg-red-50 dark:bg-red-900/20 text-red-600 dark:text-red-400 hover:bg-red-100 dark:hover:bg-red-900/40 transition-colors"
                                title="Remove Item"
                            >
                                <x-filament::icon icon="heroicon-m-trash" class="h-4 w-4" />
                            </button>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>
