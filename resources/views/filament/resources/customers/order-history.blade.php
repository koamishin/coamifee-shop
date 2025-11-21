@php
use App\Models\Order
@endphp

<div class="space-y-4">
    @if($orders->isEmpty())
        <div class="text-center py-8 text-gray-500">
            <p>No orders found for this customer.</p>
        </div>
    @else
        <div class="space-y-4 max-h-96 overflow-y-auto">
            @foreach($orders as $order)
                <div class="border border-gray-200 rounded-lg p-4 bg-white">
                    <div class="flex justify-between items-start mb-3">
                        <div>
                            <h3 class="font-semibold text-lg">Order #{{ $order->id }}</h3>
                            <p class="text-sm text-gray-600">{{ $order->created_at->format('M d, Y \a\t g:i A') }}</p>
                        </div>
                        <div class="text-right">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                @if($order->status === 'completed') bg-green-100 text-green-800
                                @elseif($order->status === 'pending') bg-yellow-100 text-yellow-800
                                @elseif($order->status === 'cancelled') bg-red-100 text-red-800
                                @else bg-gray-100 text-gray-800
                                @endif">
                                {{ ucfirst($order->status) }}
                            </span>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
                        <div>
                            <span class="text-gray-500">Total:</span>
                            <p class="font-semibold">₱{{ number_format($order->total, 2) }}</p>
                        </div>
                        <div>
                            <span class="text-gray-500">Payment:</span>
                            <p class="font-semibold">{{ ucfirst($order->payment_method) }}</p>
                        </div>
                        <div>
                            <span class="text-gray-500">Type:</span>
                            <p class="font-semibold">{{ ucfirst($order->order_type) }}</p>
                        </div>
                        <div>
                            <span class="text-gray-500">Table:</span>
                            <p class="font-semibold">
                                {{ $order->table_number ? '#' . $order->table_number : 'N/A' }}
                            </p>
                        </div>
                    </div>

                    @if($order->items && $order->items->count() > 0)
                        <div class="mt-3 pt-3 border-t border-gray-100">
                            <p class="text-sm font-medium text-gray-700 mb-2">Items ({{ $order->items->count() }}):</p>
                            <div class="space-y-1">
                                @foreach($order->items->take(3) as $item)
                                    <p class="text-xs text-gray-600">
                                        • {{ $item->quantity }}x {{ $item->product->name ?? 'Unknown Product' }}
                                        @if($item->variant_name)({{ $item->variant_name }})@endif
                                        - ₱{{ number_format($item->price * $item->quantity, 2) }}
                                    </p>
                                @endforeach
                                @if($order->items->count() > 3)
                                    <p class="text-xs text-gray-500 italic">
                                        ... and {{ $order->items->count() - 3 }} more items
                                    </p>
                                @endif
                            </div>
                        </div>
                    @endif

                    @if($order->notes)
                        <div class="mt-3 pt-3 border-t border-gray-100">
                            <p class="text-sm font-medium text-gray-700">Notes:</p>
                            <p class="text-sm text-gray-600">{{ $order->notes }}</p>
                        </div>
                    @endif
                </div>
            @endforeach
        </div>

        <div class="mt-4 pt-4 border-t border-gray-200">
            <div class="flex justify-between items-center text-sm">
                <span class="text-gray-500">Total Orders:</span>
                <span class="font-semibold">{{ $orders->count() }}</span>
            </div>
            <div class="flex justify-between items-center text-sm mt-1">
                <span class="text-gray-500">Total Revenue:</span>
                <span class="font-semibold">₱{{ number_format($orders->sum('total'), 2) }}</span>
            </div>
        </div>
    @endif
</div>