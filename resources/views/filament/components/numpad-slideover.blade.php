@props(['orderId'])

<div
    x-data="{
        open: false,
        amount: 0,
        total: 0,
        discountAmount: 0,
        currency: '{{ $currency }}',

        init() {
            this.calculateTotal();
            // Initialize amount from wire (in Filament modal context)
            this.amount = parseFloat(this.getFormData('paidAmount') || 0);
        },

        getFormData(key) {
            // Access Filament modal form data
            const mountedActions = $wire.mountedActionsData || {};
            const actionData = mountedActions[0] || {};
            return actionData[key];
        },

        setFormData(key, value) {
            // Set Filament modal form data
            if (!$wire.mountedActionsData) $wire.mountedActionsData = [{}];
            if (!$wire.mountedActionsData[0]) $wire.mountedActionsData[0] = {};
            $wire.mountedActionsData[0][key] = value;
        },

        calculateTotal() {
            const order = @js($order ?? null);
            if (!order) return;

            // Use the order's total which already has all discounts and add-ons applied
            this.total = parseFloat(order.total || 0);
        },

        appendNumber(num) {
            let current = String(this.amount || '0');
            if (current === '0') {
                this.amount = num;
            } else if (current.includes('.')) {
                // If there's a decimal, just append
                this.amount = parseFloat(current + num);
            } else {
                this.amount = parseFloat(current + num);
            }
        },

        appendDecimal() {
            let str = this.amount.toString();
            if (!str.includes('.')) {
                this.amount = str + '.';
            }
        },

        clear() {
            this.amount = 0;
        },

        backspace() {
            let str = this.amount.toString();
            if (str.length > 1) {
                this.amount = str.slice(0, -1);
                if (this.amount !== '.' && this.amount !== '') {
                    this.amount = parseFloat(this.amount) || 0;
                }
            } else {
                this.amount = 0;
            }
        },

        quickAmount(multiplier) {
            this.amount = Math.ceil(this.total / multiplier) * multiplier;
        },

        setExactAmount() {
            this.amount = this.total;
        },

        get change() {
            return Math.max(0, parseFloat(this.amount || 0) - this.total);
        },

        get isValid() {
            return parseFloat(this.amount || 0) >= this.total;
        }
    }"
    class="relative"
>
    {{-- Trigger Button --}}
    <button
        type="button"
        @click="open = true"
        class="w-full px-4 py-3 bg-gradient-to-r from-orange-500 to-orange-600 hover:from-orange-600 hover:to-orange-700 text-white font-semibold rounded-lg shadow-lg transition-all flex items-center justify-center gap-2"
    >
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
        </svg>
        <span>Open Numpad Calculator</span>
    </button>

    {{-- Amount Display (when closed) --}}
    <div class="mt-2 text-center">
        <div class="text-sm text-gray-600">Cash Received:</div>
        <div class="text-2xl font-bold text-gray-900">
            <span x-text="currency"></span><span x-text="parseFloat(amount || 0).toFixed(2)"></span>
        </div>
        <div x-show="change > 0" class="text-lg font-semibold text-green-600 mt-1">
            Change: <span x-text="currency"></span><span x-text="change.toFixed(2)"></span>
        </div>
    </div>

    {{-- Slide-over Overlay --}}
    <div
        x-show="open"
        x-transition:enter="transition-opacity ease-out duration-300"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition-opacity ease-in duration-200"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        class="fixed inset-0 bg-gray-900/50 backdrop-blur-sm z-[100]"
        @click="open = false"
        style="display: none;"
    ></div>

    {{-- Slide-over Panel --}}
    <div
        x-show="open"
        x-transition:enter="transform transition ease-out duration-300"
        x-transition:enter-start="translate-x-full"
        x-transition:enter-end="translate-x-0"
        x-transition:leave="transform transition ease-in duration-200"
        x-transition:leave-start="translate-x-0"
        x-transition:leave-end="translate-x-full"
        @click.away="open = false"
        class="fixed inset-y-0 right-0 w-full sm:w-96 bg-gradient-to-br from-orange-50 to-amber-50 shadow-2xl z-[101] overflow-y-auto"
        style="display: none;"
    >
        <div class="h-full flex flex-col p-6 space-y-4">
            {{-- Header --}}
            <div class="flex items-center justify-between pb-4 border-b border-orange-200">
                <h3 class="text-xl font-bold text-gray-900">Payment Calculator</h3>
                <button
                    type="button"
                    @click="open = false"
                    class="p-2 hover:bg-orange-100 rounded-lg transition-colors"
                >
                    <svg class="w-6 h-6 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            {{-- Order Summary --}}
            <div class="bg-white rounded-xl shadow-sm p-4 border border-orange-200">
                <h4 class="text-sm font-semibold text-gray-700 mb-3">Order Summary</h4>
                <div class="space-y-2 text-sm">
                    <div class="flex justify-between">
                        <span class="text-gray-600">Subtotal:</span>
                        <span class="font-medium" x-text="currency + ({{ $order->subtotal ?? $order->total ?? 0 }}).toFixed(2)"></span>
                    </div>
                    <div x-show="discountAmount > 0" class="flex justify-between text-green-600">
                        <span>Discount:</span>
                        <span class="font-medium">- <span x-text="currency + discountAmount.toFixed(2)"></span></span>
                    </div>
                    @if(($order->add_ons_total ?? 0) > 0)
                    <div class="flex justify-between text-blue-600">
                        <span>Add-ons:</span>
                        <span class="font-medium">+ <span x-text="currency + ({{ $order->add_ons_total ?? 0 }}).toFixed(2)"></span></span>
                    </div>
                    @endif
                    <div class="flex justify-between text-lg font-bold border-t border-gray-200 pt-2">
                        <span>Total:</span>
                        <span class="text-orange-600" x-text="currency + total.toFixed(2)"></span>
                    </div>
                </div>
            </div>

            {{-- Display --}}
            <div class="bg-gradient-to-br from-gray-900 to-gray-800 rounded-xl p-4 shadow-lg">
                <div class="text-right space-y-2">
                    <div class="text-xs text-gray-400 uppercase tracking-wide">Cash Received</div>
                    <div class="text-3xl font-bold text-orange-400">
                        <span x-text="currency"></span>
                        <span x-text="parseFloat(amount || 0).toFixed(2)"></span>
                    </div>
                    <div x-show="change > 0" class="bg-green-500/20 border border-green-500 rounded-lg p-3 mt-2">
                        <div class="flex justify-between items-center">
                            <span class="text-green-300 font-medium">Change</span>
                            <span class="text-2xl font-bold text-green-300">
                                <span x-text="currency"></span>
                                <span x-text="change.toFixed(2)"></span>
                            </span>
                        </div>
                    </div>
                    <div x-show="amount > 0 && !isValid" class="bg-red-500/20 border border-red-500 rounded-lg p-2 mt-2">
                        <div class="text-xs text-red-300 text-center font-medium">
                            ⚠️ Insufficient amount
                        </div>
                    </div>
                </div>
            </div>

            {{-- Quick Amount Buttons --}}
            <div class="grid grid-cols-4 gap-2">
                <button
                    type="button"
                    @click="quickAmount(10)"
                    class="px-3 py-2 bg-blue-500 hover:bg-blue-600 text-white rounded-lg font-semibold text-sm transition-all active:scale-95 shadow-md"
                >
                    +$10
                </button>
                <button
                    type="button"
                    @click="quickAmount(20)"
                    class="px-3 py-2 bg-blue-500 hover:bg-blue-600 text-white rounded-lg font-semibold text-sm transition-all active:scale-95 shadow-md"
                >
                    +$20
                </button>
                <button
                    type="button"
                    @click="quickAmount(50)"
                    class="px-3 py-2 bg-blue-500 hover:bg-blue-600 text-white rounded-lg font-semibold text-sm transition-all active:scale-95 shadow-md"
                >
                    +$50
                </button>
                <button
                    type="button"
                    @click="quickAmount(100)"
                    class="px-3 py-2 bg-blue-500 hover:bg-blue-600 text-white rounded-lg font-semibold text-sm transition-all active:scale-95 shadow-md"
                >
                    +$100
                </button>
            </div>

            {{-- Numpad --}}
            <div class="grid grid-cols-3 gap-3">
                {{-- Number Buttons --}}
                @foreach([7, 8, 9, 4, 5, 6, 1, 2, 3] as $num)
                    <button
                        type="button"
                        @click="appendNumber({{ $num }})"
                        class="aspect-square bg-white hover:bg-gray-50 border-2 border-gray-300 rounded-xl font-bold text-2xl text-gray-900 transition-all hover:scale-105 active:scale-95 shadow-md touch-manipulation"
                    >
                        {{ $num }}
                    </button>
                @endforeach

                {{-- Bottom Row --}}
                <button
                    type="button"
                    @click="clear()"
                    class="aspect-square bg-red-500 hover:bg-red-600 text-white rounded-xl font-bold text-lg transition-all hover:scale-105 active:scale-95 shadow-md touch-manipulation"
                >
                    CLEAR
                </button>
                <button
                    type="button"
                    @click="appendNumber(0)"
                    class="aspect-square bg-white hover:bg-gray-50 border-2 border-gray-300 rounded-xl font-bold text-2xl text-gray-900 transition-all hover:scale-105 active:scale-95 shadow-md touch-manipulation"
                >
                    0
                </button>
                <button
                    type="button"
                    @click="backspace()"
                    class="aspect-square bg-orange-500 hover:bg-orange-600 text-white rounded-xl font-bold transition-all hover:scale-105 active:scale-95 shadow-md touch-manipulation flex items-center justify-center"
                >
                    <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2M3 12l6.414 6.414a2 2 0 001.414.586H19a2 2 0 002-2V7a2 2 0 00-2-2h-8.172a2 2 0 00-1.414.586L3 12z" />
                    </svg>
                </button>
            </div>

            {{-- Exact Amount Button --}}
            <button
                type="button"
                @click="setExactAmount()"
                class="w-full py-3 bg-gradient-to-r from-purple-500 to-purple-600 hover:from-purple-600 hover:to-purple-700 text-white rounded-xl font-semibold transition-all hover:scale-105 active:scale-95 shadow-lg"
            >
                Exact Amount (<span x-text="currency + total.toFixed(2)"></span>)
            </button>

            {{-- Done Button --}}
            <button
                type="button"
                @click="setFormData('paidAmount', amount); open = false"
                class="w-full py-4 bg-gradient-to-r from-green-500 to-green-600 hover:from-green-600 hover:to-green-700 text-white rounded-xl font-bold text-lg transition-all hover:scale-105 active:scale-95 shadow-lg"
                :disabled="!isValid"
                :class="{ 'opacity-50 cursor-not-allowed': !isValid }"
            >
                ✓ Done
            </button>
        </div>
    </div>
</div>
