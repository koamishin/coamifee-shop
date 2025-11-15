<x-filament-panels::page>
    <div class="space-y-4">
        {{-- Status Filter Tabs --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4">
            <div class="flex items-center gap-2 overflow-x-auto">
                <button
                    wire:click="filterByStatus('all')"
                    class="px-4 py-2 rounded-lg font-medium text-sm transition-all whitespace-nowrap {{ $statusFilter === 'all' ? 'bg-blue-500 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}"
                >
                    All Orders
                </button>
                <button
                    wire:click="filterByStatus('pending')"
                    class="px-4 py-2 rounded-lg font-medium text-sm transition-all whitespace-nowrap {{ $statusFilter === 'pending' ? 'bg-yellow-500 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}"
                >
                    <span class="inline-flex items-center gap-1">
                        <span class="w-2 h-2 bg-yellow-400 rounded-full"></span>
                        In Progress
                    </span>
                </button>
                <button
                    wire:click="filterByStatus('completed')"
                    class="px-4 py-2 rounded-lg font-medium text-sm transition-all whitespace-nowrap {{ $statusFilter === 'completed' ? 'bg-green-500 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}"
                >
                    <span class="inline-flex items-center gap-1">
                        <span class="w-2 h-2 bg-green-400 rounded-full"></span>
                        Completed
                    </span>
                </button>
            </div>
        </div>

        {{-- Orders Grid --}}
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            @forelse($this->getOrders() as $order)
                <div class="bg-white rounded-xl shadow-sm border-2 {{ match($order->status) {
                    'pending' => 'border-yellow-300',
                    'completed' => 'border-green-300',
                    default => 'border-gray-200'
                } }} overflow-hidden">
                    {{-- Order Header --}}
                    <div class="p-4 {{ match($order->status) {
                        'pending' => 'bg-yellow-50',
                        'completed' => 'bg-green-50',
                        default => 'bg-gray-50'
                    } }} border-b">
                        <div class="flex items-start justify-between">
                            <div>
                                <div class="flex items-center gap-2 flex-wrap">
                                    <h3 class="text-lg font-bold text-gray-900">Order #{{ $order->id }}</h3>
                                    <span class="px-2 py-1 rounded-full text-xs font-bold {{ match($order->status) {
                                        'pending' => 'bg-yellow-500 text-white',
                                        'completed' => 'bg-green-500 text-white',
                                        default => 'bg-gray-500 text-white'
                                    } }}">
                                        {{ $order->status === 'pending' ? 'In Progress' : ucfirst($order->status) }}
                                    </span>
                                    <span class="px-2 py-1 rounded-full text-xs font-bold {{ $order->payment_status === 'paid' ? 'bg-green-600 text-white' : 'bg-red-500 text-white' }}">
                                        {{ $order->payment_status === 'paid' ? 'Paid' : 'Unpaid' }}
                                    </span>
                                </div>
                                <p class="text-sm text-gray-600 mt-1">
                                    <span class="font-medium">{{ $order->customer_name }}</span>
                                    @if($order->table_number)
                                        · Table {{ $order->table_number }}
                                    @endif
                                </p>
                                <p class="text-xs text-gray-500 mt-1">
                                    {{ $order->created_at->diffForHumans() }}
                                </p>
                                @if($order->status === 'pending')
                                    @php
                                        $totalItems = $order->items->count();
                                        $completedItems = $order->items->where('is_served', true)->count();
                                    @endphp
                                    <div class="mt-2">
                                        <div class="flex items-center gap-2 text-xs">
                                            <span class="text-gray-600">Progress:</span>
                                            <span class="font-semibold {{ $completedItems === $totalItems ? 'text-green-600' : 'text-yellow-600' }}">
                                                {{ $completedItems }}/{{ $totalItems }} items
                                            </span>
                                        </div>
                                        <div class="mt-1 w-full bg-gray-200 rounded-full h-1.5">
                                            <div class="bg-yellow-500 h-1.5 rounded-full transition-all" style="width: {{ $totalItems > 0 ? ($completedItems / $totalItems * 100) : 0 }}%"></div>
                                        </div>
                                    </div>
                                @endif
                            </div>
                            <div class="text-right">
                                <div class="text-xl font-bold text-gray-900">
                                    {{ $this->formatCurrency($order->total) }}
                                </div>
                                <div class="text-xs text-gray-500">
                                    {{ $order->order_type }}
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Order Items --}}
                    <div class="p-4 space-y-2 max-h-48 overflow-y-auto">
                        @foreach($order->items as $item)
                            <div class="flex items-center justify-between text-sm group hover:bg-gray-50 p-2 rounded transition-colors">
                                <div class="flex items-center gap-3 flex-1">
                                    {{-- Served Checkbox --}}
                                    <button
                                        wire:click="toggleServed({{ $item->id }})"
                                        class="flex-shrink-0 w-6 h-6 rounded border-2 flex items-center justify-center transition-all {{ $item->is_served ? 'bg-green-500 border-green-500' : 'border-gray-300 hover:border-green-400' }}"
                                        title="{{ $item->is_served ? 'Mark as not served' : 'Mark as served' }}"
                                    >
                                        @if($item->is_served)
                                            <x-filament::icon icon="heroicon-o-check" class="w-4 h-4 text-white" />
                                        @endif
                                    </button>

                                    {{-- Quantity Badge --}}
                                    <span class="w-6 h-6 bg-gray-100 rounded-full flex items-center justify-center text-xs font-bold">
                                        {{ $item->quantity }}
                                    </span>

                                    {{-- Item Name --}}
                                    <div class="flex flex-col">
                                        <span class="font-medium {{ $item->is_served ? 'text-gray-400 line-through' : 'text-gray-900' }}">
                                            {{ $item->product->name }}
                                        </span>
                                        @if($item->variant_name)
                                            <span class="text-xs {{ $item->is_served ? 'text-gray-400' : 'text-gray-500' }} flex items-center gap-1">
                                                @if(strtolower($item->variant_name) === 'hot')
                                                    <x-filament::icon icon="heroicon-o-fire" class="w-3 h-3 text-orange-500" />
                                                @elseif(strtolower($item->variant_name) === 'cold' || strtolower($item->variant_name) === 'iced')
                                                    <svg class="w-3 h-3 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                                                    </svg>
                                                @endif
                                                <span>({{ $item->variant_name }})</span>
                                            </span>
                                        @endif
                                    </div>
                                </div>

                                {{-- Item Subtotal --}}
                                <span class="text-gray-600">${{ number_format($item->subtotal, 2) }}</span>
                            </div>
                        @endforeach

                        @if($order->notes)
                            <div class="mt-3 p-2 bg-blue-50 border border-blue-200 rounded text-xs">
                                <strong class="text-blue-700">Notes:</strong>
                                <p class="text-blue-600 mt-1">{{ $order->notes }}</p>
                            </div>
                        @endif
                    </div>

                    {{-- Order Actions --}}
                    <div class="p-4 bg-gray-50 border-t">
                        @if($order->status === 'pending')
                            <div class="text-center text-sm text-gray-600">
                                <x-filament::icon icon="heroicon-o-clock" class="w-5 h-5 mx-auto mb-1 text-yellow-500" />
                                <p class="font-medium">Preparing Order</p>
                                <p class="text-xs mt-1">Check off items as they're completed</p>
                            </div>
                        @elseif($order->status === 'completed')
                            @if($order->payment_status === 'unpaid')
                                <button
                                    wire:click="mountAction('collectPayment', { orderId: {{ $order->id }} })"
                                    class="w-full bg-purple-500 hover:bg-purple-600 text-white font-semibold py-2 rounded-lg transition-all flex items-center justify-center gap-2"
                                >
                                    <x-filament::icon icon="heroicon-o-credit-card" class="w-5 h-5" />
                                    Collect Payment
                                </button>
                            @else
                                <div class="flex items-center justify-center gap-2 text-green-600">
                                    <x-filament::icon icon="heroicon-o-check-badge" class="w-5 h-5" />
                                    <span class="font-semibold text-sm">Completed & Paid</span>
                                </div>
                                @if($order->payment_method)
                                    <p class="text-xs text-gray-500 text-center mt-1">
                                        Payment: {{ ucfirst($order->payment_method) }}
                                    </p>
                                @endif
                            @endif
                        @endif
                    </div>
                </div>
            @empty
                <div class="col-span-full">
                    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-12 text-center">
                        <x-filament::icon icon="heroicon-o-inbox" class="w-16 h-16 text-gray-300 mx-auto mb-4" />
                        <h3 class="text-lg font-semibold text-gray-900 mb-2">No orders found</h3>
                        <p class="text-sm text-gray-500">
                            @if($statusFilter === 'all')
                                There are no orders yet.
                            @else
                                There are no {{ $statusFilter }} orders at the moment.
                            @endif
                        </p>
                    </div>
                </div>
            @endforelse
        </div>
    </div>

    <x-filament-actions::modals />
</x-filament-panels::page>
