<div class="rounded-3xl bg-gray-50 dark:bg-gray-900 p-6 border border-gray-200 dark:border-gray-800">
    <div class="space-y-4">
        {{-- Subtotal --}}
        <div class="flex items-center justify-between text-base text-gray-500 dark:text-gray-400">
            <span>Subtotal</span>
            <span class="font-semibold text-gray-900 dark:text-white">{{ $formatCurrency($subtotal) }}</span>
        </div>

        {{-- Add-ons --}}
        @if($addOnsTotal > 0)
            <div class="flex items-center justify-between text-base text-blue-600 dark:text-blue-400">
                <span>Add-ons</span>
                <span class="font-bold">+{{ $formatCurrency($addOnsTotal) }}</span>
            </div>
        @endif

        {{-- Discount --}}
        @if($discountAmount > 0)
            <div class="flex items-center justify-between text-base text-green-600 dark:text-green-400">
                <span>Discount</span>
                <span class="font-bold">−{{ $formatCurrency($discountAmount) }}</span>
            </div>
        @endif

        {{-- Divider --}}
        <div class="h-px bg-gray-200 dark:bg-gray-800 border-t border-dashed border-gray-300 dark:border-gray-700"></div>

        {{-- Total --}}
        <div class="flex items-center justify-between">
            <span class="text-lg font-bold text-gray-900 dark:text-white uppercase tracking-wider">Total Amount</span>
            <span class="text-4xl font-black text-primary-600 dark:text-primary-500 tracking-tight">{{ $formatCurrency($finalTotal) }}</span>
        </div>
    </div>

    {{-- Payment Details (if Pay Now) --}}
    @if($paymentTiming === 'pay_now' && $paymentMethod === 'cash')
        <div class="mt-6 pt-6 border-t border-gray-200 dark:border-gray-800">
            <div class="grid grid-cols-2 gap-4">
                <div class="rounded-2xl bg-white dark:bg-gray-800 p-4 border border-gray-100 dark:border-gray-700 shadow-sm">
                    <div class="text-[10px] uppercase tracking-wider font-bold text-gray-400">Cash Received</div>
                    <div class="mt-1 text-2xl font-bold text-gray-900 dark:text-white">{{ $formatCurrency($paidAmount) }}</div>
                </div>

                <div class="rounded-2xl p-4 border {{ $changeAmount >= 0 ? 'bg-green-50/50 dark:bg-green-900/20 border-green-200 dark:border-green-800' : 'bg-red-50/50 dark:bg-red-900/20 border-red-200 dark:border-red-800' }}">
                    <div class="text-[10px] uppercase tracking-wider font-bold {{ $changeAmount >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">Change Due</div>
                    <div class="mt-1 text-2xl font-bold {{ $changeAmount >= 0 ? 'text-green-700 dark:text-green-400' : 'text-red-700 dark:text-red-400' }}">
                        {{ $changeAmount >= 0 ? $formatCurrency($changeAmount) : 'Short ' . $formatCurrency(abs($changeAmount)) }}
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
