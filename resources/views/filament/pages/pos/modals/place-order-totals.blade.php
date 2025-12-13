<div class="rounded-2xl border border-orange-200 bg-gradient-to-r from-orange-50 to-amber-50 p-4">
    <div class="grid grid-cols-2 gap-3">
        <div class="rounded-xl bg-white border border-gray-200 p-3">
            <div class="text-xs font-bold text-gray-500">SUBTOTAL</div>
            <div class="mt-1 text-xl font-extrabold text-gray-900">{{ $formatCurrency($subtotal) }}</div>
        </div>

        <div class="rounded-xl bg-white border border-gray-200 p-3">
            <div class="text-xs font-bold text-gray-500">DISCOUNT</div>
            <div class="mt-1 text-xl font-extrabold {{ $discountAmount > 0 ? 'text-green-700' : 'text-gray-900' }}">
                {{ $discountAmount > 0 ? '−'.$formatCurrency($discountAmount) : $formatCurrency(0) }}
            </div>
        </div>

        <div class="rounded-xl bg-white border border-gray-200 p-3">
            <div class="text-xs font-bold text-gray-500">ADD-ONS</div>
            <div class="mt-1 text-xl font-extrabold {{ $addOnsTotal > 0 ? 'text-blue-700' : 'text-gray-900' }}">
                {{ $addOnsTotal > 0 ? '+'.$formatCurrency($addOnsTotal) : $formatCurrency(0) }}
            </div>
        </div>

        <div class="rounded-xl bg-white border border-gray-200 p-3">
            <div class="text-xs font-bold text-gray-500">TOTAL</div>
            <div class="mt-1 text-2xl font-extrabold text-orange-700">{{ $formatCurrency($finalTotal) }}</div>
        </div>
    </div>

    @if($paymentTiming === 'pay_now' && $paymentMethod === 'cash')
        <div class="mt-4 grid grid-cols-2 gap-3">
            <div class="rounded-xl bg-white border border-gray-200 p-3">
                <div class="text-xs font-bold text-gray-500">CASH RECEIVED</div>
                <div class="mt-1 text-xl font-extrabold text-gray-900">{{ $formatCurrency($paidAmount) }}</div>
            </div>

            <div class="rounded-xl p-3 border-2 {{ $changeAmount >= 0 ? 'bg-green-50 border-green-300' : 'bg-red-50 border-red-300' }}">
                <div class="text-xs font-bold {{ $changeAmount >= 0 ? 'text-green-700' : 'text-red-700' }}">CHANGE</div>
                <div class="mt-1 text-xl font-extrabold {{ $changeAmount >= 0 ? 'text-green-800' : 'text-red-800' }}">
                    {{ $changeAmount >= 0 ? $formatCurrency($changeAmount) : 'Need '.$formatCurrency(abs($changeAmount)) }}
                </div>
            </div>
        </div>
    @endif
</div>
