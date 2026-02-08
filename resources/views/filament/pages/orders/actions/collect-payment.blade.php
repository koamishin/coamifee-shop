<div class="flex flex-col h-[calc(100vh-10rem)] -m-6 bg-gray-50 dark:bg-gray-900" x-data="{ paymentMethod: @entangle('paymentMethod') }">
    <div class="flex-1 flex overflow-hidden">
        {{-- Left: Payment Methods & Tips --}}
        <div class="flex-1 p-6 overflow-y-auto">
            <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4 uppercase tracking-wide">Select Payment Method</h3>
            
            <div class="grid grid-cols-2 gap-4 mb-8">
                @foreach($paymentMethods as $value => $label)
                    <button
                        type="button"
                        wire:click="$set('paymentMethod', '{{ $value }}')"
                        class="h-32 rounded-2xl border-2 flex flex-col items-center justify-center gap-3 transition-all active:scale-95"
                        :class="paymentMethod === '{{ $value }}' ? 'border-primary-600 bg-primary-600 text-white shadow-lg' : 'border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-600 dark:text-gray-300 hover:border-primary-400 hover:text-primary-600'"
                    >
                        @if($value === 'cash') <x-filament::icon icon="heroicon-o-banknotes" class="w-10 h-10" />
                        @elseif($value === 'gcash') <x-filament::icon icon="heroicon-o-device-phone-mobile" class="w-10 h-10" />
                        @elseif($value === 'maya') <x-filament::icon icon="heroicon-o-qr-code" class="w-10 h-10" />
                        @elseif($value === 'bank_transfer') <x-filament::icon icon="heroicon-o-building-library" class="w-10 h-10" />
                        @elseif($value === 'grab') <x-filament::icon icon="heroicon-o-truck" class="w-10 h-10" />
                        @elseif($value === 'food_panda') <x-filament::icon icon="heroicon-o-shopping-bag" class="w-10 h-10" />
                        @endif
                        <span class="font-bold text-lg">{{ $label }}</span>
                    </button>
                @endforeach
            </div>

            {{-- Tip Section (Optional Placeholder) --}}
            {{-- 
            <h3 class="text-sm font-bold text-gray-500 mb-3 uppercase tracking-wide">Add Tip</h3>
            <div class="flex gap-3">
                <button class="flex-1 py-3 bg-white dark:bg-gray-800 border rounded-xl font-bold">10%</button>
                <button class="flex-1 py-3 bg-white dark:bg-gray-800 border rounded-xl font-bold">15%</button>
                <button class="flex-1 py-3 bg-white dark:bg-gray-800 border rounded-xl font-bold">20%</button>
                <button class="flex-1 py-3 bg-white dark:bg-gray-800 border rounded-xl font-bold">Custom</button>
            </div>
            --}}
        </div>

        {{-- Right: Totals & Cash Input --}}
        <div class="w-[450px] bg-white dark:bg-gray-800 border-l border-gray-200 dark:border-gray-700 flex flex-col shadow-xl z-10">
            <div class="flex-1 p-6 flex flex-col">
                        {{-- Receipt Preview --}}
                        <div class="space-y-3 mb-6 bg-gray-50 dark:bg-gray-900 p-4 rounded-xl border border-gray-100 dark:border-gray-700">
                            <div class="flex justify-between text-gray-500">
                                @if($order)
                                    <span>Order #{{ $order->id }}</span>
                                    <span>{{ $order->created_at->format('H:i') }}</span>
                                @else
                                    <span>Order details unavailable</span>
                                @endif
                            </div>
                            <div class="h-px bg-gray-200 dark:bg-gray-700 border-t border-dashed"></div>
                            <div class="flex justify-between items-center text-lg font-medium">
                                <span>Total Due</span>
                                <span class="font-black text-2xl text-gray-900 dark:text-white">{{ $this->formatCurrency($total) }}</span>
                            </div>
                        </div>

                {{-- Cash Numpad (Only visible for Cash) --}}
                <div x-show="paymentMethod === 'cash'" class="flex-1 flex flex-col" x-transition>
                    <div class="mb-4">
                        <label class="text-xs font-bold uppercase text-gray-500 tracking-wider mb-1 block">Cash Received</label>
                        <div class="flex items-center gap-2">
                            <div class="flex-1 bg-gray-100 dark:bg-gray-900 rounded-xl px-4 py-3 text-right text-3xl font-mono font-bold tracking-tight text-gray-900 dark:text-white border-2 border-transparent focus-within:border-primary-500 transition-colors">
                                <span class="text-gray-400 text-lg mr-1">{{ $this->getCurrencySymbol() }}</span>
                                {{ number_format((float) $paidAmount, 2) }}
                            </div>
                        </div>
                        
                        {{-- Change Display --}}
                        <div class="mt-2 flex justify-between items-center px-2">
                            <span class="text-sm font-medium text-gray-500">Change:</span>
                            @php 
                                $paid = (float) $paidAmount;
                                $tot = (float) $total;
                                $change = $paid - $tot; 
                            @endphp
                            <span class="text-xl font-bold {{ $change >= 0 ? 'text-green-600' : 'text-red-500' }}">
                                {{ $this->formatCurrency($change >= 0 ? $change : 0) }}
                            </span>
                        </div>
                    </div>

                    {{-- Numpad --}}
                    <div class="grid grid-cols-3 gap-2 flex-1">
                        @foreach([1, 2, 3, 4, 5, 6, 7, 8, 9] as $n)
                            <button
                                wire:click="appendDigit({{ $n }})"
                                class="rounded-xl bg-gray-50 dark:bg-gray-700 hover:bg-gray-100 dark:hover:bg-gray-600 text-2xl font-bold transition-colors active:scale-95 shadow-sm"
                            >
                                {{ $n }}
                            </button>
                        @endforeach
                        <button wire:click="appendDigit('.')" class="rounded-xl bg-gray-50 dark:bg-gray-700 hover:bg-gray-100 text-2xl font-bold">.</button>
                        <button wire:click="appendDigit(0)" class="rounded-xl bg-gray-50 dark:bg-gray-700 hover:bg-gray-100 text-2xl font-bold">0</button>
                        <button wire:click="backspace" class="rounded-xl bg-red-50 dark:bg-red-900/20 text-red-500 hover:bg-red-100 flex items-center justify-center">
                            <x-filament::icon icon="heroicon-m-backspace" class="w-8 h-8" />
                        </button>
                    </div>

                    {{-- Quick Cash Buttons --}}
                    <div class="grid grid-cols-4 gap-2 mt-3">
                        <button wire:click="setExactAmount" class="py-2 bg-primary-50 text-primary-700 font-bold rounded-lg text-xs">Exact</button>
                        <button wire:click="addAmount(50)" class="py-2 bg-gray-100 text-gray-700 font-bold rounded-lg text-xs">+50</button>
                        <button wire:click="addAmount(100)" class="py-2 bg-gray-100 text-gray-700 font-bold rounded-lg text-xs">+100</button>
                        <button wire:click="addAmount(500)" class="py-2 bg-gray-100 text-gray-700 font-bold rounded-lg text-xs">+500</button>
                    </div>
                </div>

                {{-- Non-Cash Message --}}
                <div x-show="paymentMethod !== 'cash'" class="flex-1 flex flex-col items-center justify-center text-center text-gray-500" x-transition>
                    <x-filament::icon icon="heroicon-o-credit-card" class="w-16 h-16 mb-4 text-gray-300" />
                    <p class="text-lg">Process payment on terminal</p>
                    <p class="text-sm">Click "Complete Order" once confirmed.</p>
                </div>
            </div>
        </div>
    </div>
</div>
