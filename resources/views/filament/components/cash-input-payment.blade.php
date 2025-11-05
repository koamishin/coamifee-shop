<div x-data="{
    amount: @entangle('data.paidAmount').live,
    total: {{ $total }},
    currency: '{{ $currency }}',

    get change() {
        return Math.max(0, parseFloat(this.amount || 0) - this.total);
    },

    get isValid() {
        return parseFloat(this.amount || 0) >= this.total;
    }
}" class="space-y-3">
    {{-- Cash Received Input --}}
    <div>
        <label class="block text-sm font-medium text-gray-700 mb-2">Cash Received</label>
        <div class="relative">
            <span class="absolute left-4 top-1/2 -translate-y-1/2 text-lg font-semibold text-gray-600" x-text="currency"></span>
            <input
                type="number"
                x-model="amount"
                step="0.01"
                min="0"
                placeholder="0.00"
                class="w-full pl-12 pr-4 py-3 text-xl font-bold border-2 border-gray-300 rounded-lg focus:ring-4 focus:ring-orange-200 focus:border-orange-400 transition-all"
            >
        </div>
    </div>

    {{-- Change Display --}}
    <div x-show="change > 0" class="bg-green-50 border-2 border-green-200 rounded-lg p-3">
        <div class="flex justify-between items-center">
            <span class="text-sm font-medium text-green-700">Change</span>
            <span class="text-2xl font-bold text-green-700">
                <span x-text="currency"></span><span x-text="change.toFixed(2)"></span>
            </span>
        </div>
    </div>
    <div x-show="amount > 0 && !isValid" class="bg-red-50 border-2 border-red-200 rounded-lg p-2">
        <div class="text-sm text-red-700 text-center font-medium">
            Insufficient amount
        </div>
    </div>
</div>
