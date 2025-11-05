<div class="space-y-4">
    {{-- Amount to Pay Display --}}
    <div class="bg-gray-50 rounded-lg p-4 border border-gray-200">
        <div class="text-sm text-gray-600 mb-1">Amount to Pay</div>
        <div class="text-2xl font-bold text-gray-900">
            {{ $currency }}{{ number_format($total, 2) }}
        </div>
    </div>

    {{-- Cash Received Input --}}
    <div>
        <label class="block text-sm font-medium text-gray-700 mb-2">Cash Received</label>
        <div class="relative">
            <span class="absolute left-4 top-1/2 -translate-y-1/2 text-lg font-semibold text-gray-600">{{ $currency }}</span>
            <input
                type="number"
                wire:model.live="paidAmount"
                step="0.01"
                min="0"
                placeholder="0.00"
                class="w-full pl-12 pr-4 py-4 text-2xl font-bold border-2 border-gray-300 rounded-lg focus:ring-4 focus:ring-orange-200 focus:border-orange-400 transition-all"
            >
        </div>
    </div>

    {{-- Change Display --}}
    @if($changeAmount > 0)
        <div class="bg-green-50 border-2 border-green-200 rounded-lg p-4">
            <div class="flex justify-between items-center">
                <span class="text-sm font-medium text-green-700">Change</span>
                <span class="text-2xl font-bold text-green-700">
                    {{ $currency }}{{ number_format($changeAmount, 2) }}
                </span>
            </div>
        </div>
    @elseif($amount > 0 && $changeAmount < 0)
        <div class="bg-red-50 border-2 border-red-200 rounded-lg p-3">
            <div class="text-sm text-red-700 text-center font-medium">
                Insufficient amount
            </div>
        </div>
    @endif
</div>
