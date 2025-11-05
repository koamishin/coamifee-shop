<div x-data="{
    amount: @entangle('data.paidAmount').live,
    total: {{ $total }},
    currency: '{{ $currency }}',

    appendNumber(num) {
        let current = this.amount.toString();
        if (current === '0' || current === '') {
            this.amount = num.toString();
        } else {
            this.amount = current + num.toString();
        }
        this.amount = parseFloat(this.amount) || 0;
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

    get change() {
        return Math.max(0, parseFloat(this.amount || 0) - this.total);
    },

    get isValid() {
        return parseFloat(this.amount || 0) >= this.total;
    }
}" class="space-y-3">

    {{-- Display --}}
    <div class="bg-gradient-to-br from-gray-900 to-gray-800 rounded-lg p-3 shadow-inner">
        <div class="text-right space-y-2">
            <div class="text-xs text-gray-400 uppercase tracking-wide">Cash Received</div>
            <div class="text-2xl font-bold text-orange-400">
                <span x-text="currency"></span>
                <span x-text="parseFloat(amount || 0).toFixed(2)"></span>
            </div>
            <div x-show="change > 0" class="bg-green-500/20 border border-green-500 rounded p-2 mt-2">
                <div class="flex justify-between items-center text-sm">
                    <span class="text-green-300">Change</span>
                    <span class="text-lg font-bold text-green-300">
                        <span x-text="currency"></span>
                        <span x-text="change.toFixed(2)"></span>
                    </span>
                </div>
            </div>
            <div x-show="amount > 0 && !isValid" class="bg-red-500/20 border border-red-500 rounded p-2 mt-2">
                <div class="text-xs text-red-300 text-center">
                    Insufficient amount
                </div>
            </div>
        </div>
    </div>

    {{-- Quick Amount Buttons --}}
    <div class="grid grid-cols-4 gap-2">
        <button
            type="button"
            @click="quickAmount(10)"
            class="px-2 py-1.5 bg-blue-100 hover:bg-blue-200 text-blue-700 rounded-lg font-semibold text-xs transition-colors"
        >
            +10
        </button>
        <button
            type="button"
            @click="quickAmount(20)"
            class="px-2 py-1.5 bg-blue-100 hover:bg-blue-200 text-blue-700 rounded-lg font-semibold text-xs transition-colors"
        >
            +20
        </button>
        <button
            type="button"
            @click="quickAmount(50)"
            class="px-2 py-1.5 bg-blue-100 hover:bg-blue-200 text-blue-700 rounded-lg font-semibold text-xs transition-colors"
        >
            +50
        </button>
        <button
            type="button"
            @click="quickAmount(100)"
            class="px-2 py-1.5 bg-blue-100 hover:bg-blue-200 text-blue-700 rounded-lg font-semibold text-xs transition-colors"
        >
            +100
        </button>
    </div>

    {{-- Numpad --}}
    <div class="grid grid-cols-3 gap-2">
        {{-- Number Buttons --}}
        @foreach([7, 8, 9, 4, 5, 6, 1, 2, 3] as $num)
            <button
                type="button"
                @click="appendNumber({{ $num }})"
                class="aspect-square bg-white hover:bg-gray-50 border-2 border-gray-200 rounded-lg font-bold text-xl text-gray-900 transition-all hover:scale-105 active:scale-95 shadow-sm touch-manipulation"
            >
                {{ $num }}
            </button>
        @endforeach

        {{-- Bottom Row --}}
        <button
            type="button"
            @click="clear()"
            class="aspect-square bg-red-100 hover:bg-red-200 border-2 border-red-300 rounded-lg font-bold text-sm text-red-700 transition-all hover:scale-105 active:scale-95 shadow-sm touch-manipulation"
        >
            C
        </button>
        <button
            type="button"
            @click="appendNumber(0)"
            class="aspect-square bg-white hover:bg-gray-50 border-2 border-gray-200 rounded-lg font-bold text-xl text-gray-900 transition-all hover:scale-105 active:scale-95 shadow-sm touch-manipulation"
        >
            0
        </button>
        <button
            type="button"
            @click="backspace()"
            class="aspect-square bg-orange-100 hover:bg-orange-200 border-2 border-orange-300 rounded-lg font-bold text-sm text-orange-700 transition-all hover:scale-105 active:scale-95 shadow-sm touch-manipulation flex items-center justify-center"
        >
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2M3 12l6.414 6.414a2 2 0 001.414.586H19a2 2 0 002-2V7a2 2 0 00-2-2h-8.172a2 2 0 00-1.414.586L3 12z" />
            </svg>
        </button>
    </div>

    {{-- Exact Amount Button --}}
    <button
        type="button"
        @click="amount = total"
        class="w-full py-2 bg-purple-100 hover:bg-purple-200 border-2 border-purple-300 rounded-lg font-semibold text-sm text-purple-700 transition-all hover:scale-105 active:scale-95 shadow-sm touch-manipulation"
    >
        Exact Amount (<span x-text="currency"></span><span x-text="total.toFixed(2)"></span>)
    </button>
</div>
