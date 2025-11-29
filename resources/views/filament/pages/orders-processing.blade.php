<x-filament-panels::page>
    <div class="space-y-4">
        {{-- Status Filter Tabs --}}
         <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4">
             <div class="flex items-center gap-2 overflow-x-auto">
                 <button
                     wire:click="filterByStatus('all')"
                     class="px-4 py-2 rounded-lg font-medium text-sm transition-all whitespace-nowrap {{ $statusFilter === 'all' && $paymentStatusFilter === 'all' ? 'bg-blue-500 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}"
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
                 <button
                     wire:click="filterByPaymentStatus('refunded')"
                     class="px-4 py-2 rounded-lg font-medium text-sm transition-all whitespace-nowrap {{ $paymentStatusFilter === 'refunded' ? 'bg-orange-500 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}"
                 >
                     <span class="inline-flex items-center gap-1">
                         <span class="w-2 h-2 bg-orange-400 rounded-full"></span>
                         Refunded
                     </span>
                 </button>
                 <button
                     wire:click="filterByPaymentStatus('cancelled')"
                     class="px-4 py-2 rounded-lg font-medium text-sm transition-all whitespace-nowrap {{ $paymentStatusFilter === 'cancelled' ? 'bg-red-500 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}"
                 >
                     <span class="inline-flex items-center gap-1">
                         <span class="w-2 h-2 bg-red-400 rounded-full"></span>
                         Cancelled
                     </span>
                 </button>
             </div>
         </div>

        {{-- Orders Grid --}}
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
             @forelse($this->getOrders() as $order)
                 @php
                     $isRefunded = $order->payment_status === 'refunded' || $order->payment_status === 'refund_partial';
                 @endphp
                 <div class="bg-white rounded-xl shadow-sm border-2 {{ match($order->status) {
                 'pending' => 'border-yellow-300',
                 'completed' => 'border-green-300',
                 'cancelled' => 'border-red-300',
                 default => 'border-gray-200'
                 } }} overflow-hidden relative {{ $order->status === 'completed' ? 'bg-green-50' : '' }} {{ $order->status === 'cancelled' ? 'ring-2 ring-red-400 opacity-60 grayscale' : '' }} {{ $isRefunded ? 'border-orange-400 ring-2 ring-orange-300' : '' }}">
                     {{-- Completed Indicator Ribbon --}}
                     @if($order->status === 'completed')
                         <div class="absolute top-0 right-0 z-10">
                             <div class="relative w-32 h-32">
                                 <div class="absolute top-2 right-0 transform rotate-45 origin-top-right w-20 bg-green-500 text-white text-xs font-bold py-1 px-6 text-center shadow-md">
                                     COMPLETED
                                 </div>
                             </div>
                         </div>
                     @endif

                     {{-- Cancelled Indicator Ribbon --}}
                     @if($order->status === 'cancelled')
                         <div class="absolute top-0 right-0 z-10">
                             <div class="relative w-32 h-32">
                                 <div class="absolute top-2 right-0 transform rotate-45 origin-top-right w-20 bg-red-500 text-white text-xs font-bold py-1 px-6 text-center shadow-md">
                                     CANCELLED
                                 </div>
                             </div>
                         </div>
                     @endif

                     {{-- Refunded Indicator Ribbon --}}
                     @if($order->payment_status === 'refunded' || $order->payment_status === 'refund_partial')
                         <div class="absolute top-0 right-0 z-10">
                             <div class="relative w-32 h-32">
                                 <div class="absolute top-2 right-0 transform rotate-45 origin-top-right w-24 bg-orange-500 text-white text-xs font-bold py-1 px-4 text-center shadow-md">
                                     {{ $order->payment_status === 'refunded' ? 'REFUNDED' : 'PARTIAL REFUND' }}
                                 </div>
                             </div>
                         </div>
                     @endif
                {{-- Order Header --}}
                <div class="p-4 {{ match($order->status) {
                    'pending' => 'bg-yellow-50',
                    'completed' => 'bg-green-50',
                    'cancelled' => 'bg-red-50',
                    default => 'bg-gray-50'
                } }} border-b">
                        <div class="flex items-start justify-between">
                            <div>
                                <div class="flex items-center gap-2 flex-wrap">
                                    <h3 class="text-lg font-bold text-gray-900">Order #{{ $order->id }}</h3>
                                    <span class="px-2 py-1 rounded-full text-xs font-bold {{ match($order->status) {
                                        'pending' => 'bg-yellow-500 text-white',
                                        'completed' => 'bg-green-500 text-white',
                                        'cancelled' => 'bg-red-500 text-white',
                                        default => 'bg-gray-500 text-white'
                                    } }}">
                                        {{ match($order->status) {
                                            'pending' => 'In Progress',
                                            'cancelled' => 'Cancelled',
                                            default => ucfirst($order->status)
                                        } }}
                                    </span>
                                    <span class="px-2 py-1 rounded-full text-xs font-bold
                                        {{ match($order->payment_status) {
                                            'paid' => 'bg-green-600 text-white',
                                            'partially_paid' => 'bg-yellow-600 text-white',
                                            'cancelled' => 'bg-gray-600 text-white',
                                            default => 'bg-red-500 text-white'
                                        } }}
                                    ">
                                        {{ match($order->payment_status) {
                                            'paid' => 'Paid',
                                            'partially_paid' => 'Partially Paid',
                                            'cancelled' => 'Cancelled',
                                            default => 'Unpaid'
                                        } }}
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
                    <div class="p-4 space-y-2 max-h-48 overflow-y-auto {{ $order->status === 'completed' ? 'pointer-events-none opacity-75' : '' }}">
                        @foreach($order->items as $item)
                            @php
                                // Use item-level discount if available, otherwise fallback to order-level discount calculation
                                $itemDiscountAmount = (float) ($item->discount_amount ?? $item->discount ?? 0);

                                // Fallback: Calculate discount per item if order has a discount and item doesn't have its own
                                if ($itemDiscountAmount === 0.0 && $order->discount_amount && $order->discount_amount > 0 && $order->subtotal > 0) {
                                    $itemDiscountAmount = ($item->subtotal / $order->subtotal) * $order->discount_amount;
                                }

                                $itemFinalTotal = $item->subtotal - $itemDiscountAmount;
                                // Check if order is refunded
                                $isRefunded = $order->payment_status === 'refunded' || $order->payment_status === 'refund_partial';
                            @endphp
                            <div class="flex items-center justify-between text-sm group hover:bg-gray-50 p-2 rounded transition-colors relative {{ $isRefunded ? 'bg-red-50' : '' }}">
                                {{-- Refunded Badge --}}
                                @if($isRefunded)
                                    <div class="absolute left-0 top-1/2 -translate-y-1/2 -translate-x-2">
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 bg-red-100 text-red-700 text-xs font-semibold rounded-full border border-red-300">
                                            <x-filament::icon icon="heroicon-o-arrow-uturn-left" class="w-3 h-3" />
                                            Refunded
                                        </span>
                                    </div>
                                @endif

                                <div class="flex items-center gap-3 flex-1 {{ $isRefunded ? 'ml-24' : '' }}">
                                    {{-- Served Checkbox --}}
                                     <button
                                         wire:click="toggleServed({{ $item->id }})"
                                         {{ $order->status === 'cancelled' || $isRefunded ? 'disabled' : '' }}
                                         class="flex-shrink-0 w-6 h-6 rounded border-2 flex items-center justify-center transition-all {{ $item->is_served ? 'bg-green-500 border-green-500' : 'border-gray-300 hover:border-green-400' }} {{ ($order->status === 'cancelled' || $isRefunded) ? 'opacity-50 cursor-not-allowed' : '' }}"
                                         title="{{ $item->is_served ? 'Mark as not served' : 'Mark as served' }}"
                                     >
                                        @if($item->is_served)
                                            <x-filament::icon icon="heroicon-o-check" class="w-4 h-4 text-white" />
                                        @endif
                                    </button>

                                    {{-- Quantity Badge --}}
                                    <span class="w-6 h-6 bg-gray-100 rounded-full flex items-center justify-center text-xs font-bold {{ $isRefunded ? 'bg-red-100 text-red-700' : '' }}">
                                        {{ $item->quantity }}
                                    </span>

                                    {{-- Item Name --}}
                                    <div class="flex flex-col">
                                        <div class="flex items-center gap-2">
                                            <span class="font-medium {{ $item->is_served ? 'text-gray-400 line-through' : '' }} {{ $isRefunded ? 'text-red-600 line-through' : 'text-gray-900' }}">
                                                {{ $item->product->name }}
                                            </span>
                                            @if($itemDiscountAmount > 0 && isset($item->discount_percentage) && $item->discount_percentage > 0)
                                                <span class="inline-flex items-center px-1.5 py-0.5 bg-red-100 text-red-700 text-xs font-semibold rounded">
                                                    -{{ number_format($item->discount_percentage, 0) }}%
                                                </span>
                                            @endif
                                        </div>
                                        @if($item->variant_name)
                                            <span class="text-xs {{ $item->is_served ? 'text-gray-400' : '' }} {{ $isRefunded ? 'text-red-500' : 'text-gray-500' }} flex items-center gap-1">
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

                                {{-- Item Price Column --}}
                                <div class="text-right ml-4">
                                    <span class="text-gray-600 block {{ $isRefunded ? 'text-red-600 line-through' : '' }}">{{ $this->formatCurrency($item->subtotal) }}</span>
                                    @if($itemDiscountAmount > 0)
                                        <span class="text-red-600 text-xs block">-{{ $this->formatCurrency($itemDiscountAmount) }}</span>
                                    @endif
                                </div>
                            </div>
                        @endforeach

                         {{-- Subtotal After Item Discounts --}}
                         @php
                             $itemDiscountTotal = $order->items->sum(function($item) {
                                 return (float) ($item->discount_amount ?? $item->discount ?? 0);
                             });
                             $subtotalAfterItemDiscounts = $order->subtotal - $itemDiscountTotal;
                         @endphp

                         @if($itemDiscountTotal > 0 || ($order->discount_amount && $order->discount_amount > 0))
                             <div class="mt-2 pt-2 border-t border-gray-200 space-y-1">
                                 @if($itemDiscountTotal > 0)
                                     <div class="flex items-center justify-between text-sm">
                                         <span class="text-gray-600">Subtotal After Item Discounts:</span>
                                         <span class="text-gray-700 font-medium">{{ $this->formatCurrency($subtotalAfterItemDiscounts) }}</span>
                                     </div>
                                 @endif
                                 @if($order->discount_amount && $order->discount_amount > 0)
                                     <div class="flex items-center justify-between text-sm">
                                         <span class="text-gray-600">Order Discount (-{{ $order->discount_value }}%):</span>
                                         <span class="text-red-600 font-medium">-{{ $this->formatCurrency($order->discount_amount) }}</span>
                                     </div>
                                 @endif
                                 @if($order->add_ons_total && $order->add_ons_total > 0)
                                     <div class="flex items-center justify-between text-sm">
                                         <span class="text-gray-600">Add-ons:</span>
                                         <span class="text-purple-600 font-medium">+{{ $this->formatCurrency($order->add_ons_total) }}</span>
                                     </div>
                                 @endif
                                 <div class="flex items-center justify-between text-sm font-semibold pt-1 border-t border-gray-300">
                                     <span class="text-gray-700">Order Total:</span>
                                     <span class="text-green-600">{{ $this->formatCurrency($order->total) }}</span>
                                 </div>
                             </div>
                         @endif

                         {{-- Add-ons Section --}}
                        @if($order->add_ons && count($order->add_ons) > 0)
                            <div class="mt-3 p-2 bg-purple-50 border border-purple-200 rounded">
                                <div class="flex items-center gap-1 mb-1">
                                    <x-filament::icon icon="heroicon-o-plus-circle" class="w-4 h-4 text-purple-600" />
                                    <strong class="text-purple-700 text-xs">Add-ons:</strong>
                                </div>
                                <div class="space-y-1">
                                    @foreach($order->add_ons as $addOn)
                                        <div class="flex items-center justify-between text-xs">
                                            <span class="text-purple-600">• {{ $addOn['name'] }}</span>
                                            <span class="text-purple-700 font-medium">{{ $this->formatCurrency($addOn['price']) }}</span>
                                        </div>
                                    @endforeach
                                </div>
                                @if($order->add_ons_total > 0)
                                    <div class="mt-2 pt-2 border-t border-purple-200 flex items-center justify-between text-xs">
                                        <span class="text-purple-700 font-semibold">Add-ons Total:</span>
                                        <span class="text-purple-700 font-bold">{{ $this->formatCurrency($order->add_ons_total) }}</span>
                                    </div>
                                @endif
                            </div>
                        @endif

                        @if($order->notes)
                            <div class="mt-3 p-2 bg-blue-50 border border-blue-200 rounded text-xs">
                                <strong class="text-blue-700">Notes:</strong>
                                <p class="text-blue-600 mt-1">{{ $order->notes }}</p>
                            </div>
                        @endif
                    </div>

                    {{-- Order Actions --}}
                    <div class="p-4 bg-gray-50 border-t space-y-2 {{ $order->status === 'cancelled' ? 'pointer-events-none' : '' }}">
                        @if($order->status === 'pending')
                             <div class="grid grid-cols-2 gap-2">
                                 <button
                                     wire:click="mountAction('addProduct', { orderId: {{ $order->id }} })"
                                     {{ $order->status === 'cancelled' ? 'disabled' : '' }}
                                     class="bg-orange-500 hover:bg-orange-600 text-white font-semibold py-2 rounded-lg transition-all flex items-center justify-center gap-2 text-sm {{ $order->status === 'cancelled' ? 'opacity-50 cursor-not-allowed' : '' }}"
                                 >
                                     <x-filament::icon icon="heroicon-o-plus-circle" class="w-4 h-4" />
                                     Add Product
                                 </button>
                                 @if($order->payment_status === 'paid' && $this->canShowRefund($order))
                                     <button
                                         wire:click="mountAction('refund', { orderId: {{ $order->id }} })"
                                         {{ $order->status === 'cancelled' ? 'disabled' : '' }}
                                         class="bg-red-500 hover:bg-red-600 text-white font-semibold py-2 rounded-lg transition-all flex items-center justify-center gap-2 text-sm {{ $order->status === 'cancelled' ? 'opacity-50 cursor-not-allowed' : '' }}"
                                     >
                                         <x-filament::icon icon="heroicon-o-arrow-uturn-left" class="w-4 h-4" />
                                         {{ $this->getRefundLabel($order) }}
                                     </button>
                                 @elseif($this->canShowCancel($order))
                                     <button
                                         wire:click="openCancelModal({{ $order->id }})"
                                         {{ $order->status === 'cancelled' ? 'disabled' : '' }}
                                         class="bg-red-500 hover:bg-red-600 text-white font-semibold py-2 rounded-lg transition-all flex items-center justify-center gap-2 text-sm {{ $order->status === 'cancelled' ? 'opacity-50 cursor-not-allowed' : '' }}"
                                     >
                                         <x-filament::icon icon="heroicon-o-x-mark" class="w-4 h-4" />
                                         Cancel
                                     </button>
                                 @else
                                     <a
                                         href="{{ route('orders.print-kitchen', $order) }}"
                                         target="_blank"
                                         {{ $order->status === 'cancelled' ? 'onclick=return\\ false' : '' }}
                                         class="bg-blue-500 hover:bg-blue-600 text-white font-semibold py-2 rounded-lg transition-all flex items-center justify-center gap-2 text-sm {{ $order->status === 'cancelled' ? 'opacity-50 cursor-not-allowed pointer-events-none' : '' }}"
                                     >
                                         <x-filament::icon icon="heroicon-o-printer" class="w-4 h-4" />
                                         Print
                                     </a>
                                 @endif
                             </div>
                             <div class="grid grid-cols-1 gap-2">
                                 <a
                                     href="{{ route('orders.print-kitchen', $order) }}"
                                     target="_blank"
                                     {{ $order->status === 'cancelled' ? 'onclick=return\\ false' : '' }}
                                     class="bg-blue-500 hover:bg-blue-600 text-white font-semibold py-2 rounded-lg transition-all flex items-center justify-center gap-2 text-sm {{ $order->status === 'cancelled' ? 'opacity-50 cursor-not-allowed pointer-events-none' : '' }}">
                                     <x-filament::icon icon="heroicon-o-printer" class="w-4 h-4" />
                                     Print
                                 </a>
                             </div>
                              <div class="text-center text-xs text-gray-600 mt-2">
                                  <x-filament::icon icon="heroicon-o-clock" class="w-4 h-4 mx-auto mb-1 text-yellow-500" />
                                  <p class="font-medium">Preparing Order</p>
                              </div>
                         @elseif($order->status === 'completed')
                              @if($order->payment_status === 'unpaid' || $order->payment_status === 'partially_paid')
                                  <div class="grid grid-cols-2 gap-2">
                                      <button
                                          wire:click="mountAction('addProduct', { orderId: {{ $order->id }} })"
                                          disabled
                                          class="bg-gray-300 text-gray-500 font-semibold py-2 rounded-lg transition-all flex items-center justify-center gap-2 text-sm opacity-50 cursor-not-allowed"
                                      >
                                         <x-filament::icon icon="heroicon-o-plus-circle" class="w-4 h-4" />
                                         Add Product
                                     </button>
                                     <button
                                         wire:click="mountAction('collectPayment', { orderId: {{ $order->id }} })"
                                         {{ $order->status === 'cancelled' ? 'disabled' : '' }}
                                         class="bg-purple-500 hover:bg-purple-600 text-white font-semibold py-2 rounded-lg transition-all flex items-center justify-center gap-2 text-sm {{ $order->status === 'cancelled' ? 'opacity-50 cursor-not-allowed' : '' }}"
                                     >
                                         <x-filament::icon icon="heroicon-o-credit-card" class="w-4 h-4" />
                                         Collect Payment
                                     </button>
                                 </div>
                             @else
                                 <div class="grid grid-cols-2 gap-2">
                                     <button
                                         wire:click="mountAction('addProduct', { orderId: {{ $order->id }} })"
                                         disabled
                                         class="bg-gray-300 text-gray-500 font-semibold py-2 rounded-lg transition-all flex items-center justify-center gap-2 text-sm opacity-50 cursor-not-allowed"
                                     >
                                         <x-filament::icon icon="heroicon-o-plus-circle" class="w-4 h-4" />
                                         Add Product
                                     </button>
                                    @if($this->canShowRefund($order))
                                        <button
                                            wire:click="mountAction('refund', { orderId: {{ $order->id }} })"
                                            {{ $order->status === 'cancelled' ? 'disabled' : '' }}
                                            class="bg-red-500 hover:bg-red-600 text-white font-semibold py-2 rounded-lg transition-all flex items-center justify-center gap-2 text-sm {{ $order->status === 'cancelled' ? 'opacity-50 cursor-not-allowed' : '' }}"
                                        >
                                            <x-filament::icon icon="heroicon-o-arrow-uturn-left" class="w-4 h-4" />
                                            {{ $this->getRefundLabel($order) }}
                                        </button>
                                    @else
                                        <button
                                            disabled
                                            class="bg-gray-300 text-gray-500 font-semibold py-2 rounded-lg flex items-center justify-center gap-2 text-sm cursor-not-allowed"
                                            title="Cannot refund a completed and fully paid order"
                                        >
                                            <x-filament::icon icon="heroicon-o-arrow-uturn-left" class="w-4 h-4" />
                                            Refund
                                        </button>
                                    @endif
                                </div>
                                <div class="grid grid-cols-2 gap-2">
                                    <button
                                        disabled
                                        class="bg-gray-300 text-gray-500 font-semibold py-1 rounded-lg transition-all flex items-center justify-center gap-2 text-xs opacity-50 cursor-not-allowed"
                                    >
                                        <x-filament::icon icon="heroicon-o-printer" class="w-4 h-4" />
                                        Kitchen
                                    </button>
                                    <a
                                        href="{{ route('orders.print-receipt', $order) }}"
                                        target="_blank"
                                        class="bg-green-500 hover:bg-green-600 text-white font-semibold py-1 rounded-lg transition-all flex items-center justify-center gap-2 text-xs"
                                    >
                                        <x-filament::icon icon="heroicon-o-document-text" class="w-4 h-4" />
                                        Receipt
                                    </a>
                                </div>
                                <div class="flex items-center justify-center gap-2 text-green-600 text-xs mt-2">
                                    <x-filament::icon icon="heroicon-o-check-badge" class="w-4 h-4" />
                                    <span class="font-semibold">Completed & Paid</span>
                                </div>
                                @if($order->payment_method)
                                    <p class="text-xs text-gray-500 text-center mt-1">
                                        {{ ucfirst(str_replace('_', ' ', $order->payment_method)) }}
                                    </p>
                                @endif
                                @endif
                                @elseif($order->status === 'cancelled')
                                    <div class="bg-red-50 rounded-lg p-4 border border-red-200">
                                        <div class="flex items-center justify-center gap-2 text-red-700 mb-3">
                                            <x-filament::icon icon="heroicon-o-x-circle" class="w-5 h-5" />
                                            <span class="font-bold text-sm">Order Cancelled</span>
                                        </div>
                                        <p class="text-xs text-red-600 text-center mb-3">This order is not counted as sales</p>
                                        <div class="grid grid-cols-2 gap-2">
                                            <a
                                                href="{{ route('orders.print-kitchen', $order) }}"
                                                target="_blank"
                                                class="bg-blue-500 hover:bg-blue-600 text-white font-semibold py-1 rounded-lg transition-all flex items-center justify-center gap-2 text-xs"
                                            >
                                                <x-filament::icon icon="heroicon-o-printer" class="w-4 h-4" />
                                                Kitchen
                                            </a>
                                            <a
                                                href="{{ route('orders.print-receipt', $order) }}"
                                                target="_blank"
                                                class="bg-green-500 hover:bg-green-600 text-white font-semibold py-1 rounded-lg transition-all flex items-center justify-center gap-2 text-xs"
                                            >
                                                <x-filament::icon icon="heroicon-o-document-text" class="w-4 h-4" />
                                                Receipt
                                            </a>
                                        </div>
                                    </div>
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

    {{-- Cancel Order Modal --}}
    @if ($cancelOrderId)
        <div
            x-data="{ open: true }"
            @open-cancel-modal.window="open = true"
            @close-cancel-modal.window="open = false"
            x-show="open"
            class="fixed inset-0 z-50 overflow-y-auto"
        >
            <!-- Backdrop -->
            <div
                x-show="open"
                class="fixed inset-0 bg-black/50"
                @click="open = false"
            ></div>

            <!-- Modal -->
            <div class="flex min-h-full items-center justify-center p-4">
                <div
                    x-show="open"
                    class="relative bg-white rounded-lg shadow-xl max-w-md w-full"
                >
                    <!-- Header -->
                    <div class="border-b px-6 py-4">
                        <h2 class="text-lg font-bold text-gray-900">Cancel Order #{{ $cancelOrderId }}</h2>
                    </div>

                    <!-- Body -->
                    <div class="px-6 py-4 space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Admin PIN
                            </label>
                            <input
                                type="password"
                                wire:model="cancelOrderPin"
                                placeholder="Enter your 4-6 digit PIN"
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-red-500"
                            />
                            @error('cancelOrderPin')
                                <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Reason (Optional)
                            </label>
                            <textarea
                                wire:model="cancelOrderReason"
                                placeholder="Why is this order being cancelled?"
                                rows="3"
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-red-500 resize-none"
                            ></textarea>
                        </div>

                        <div class="bg-red-50 border border-red-200 rounded-lg p-3">
                            <p class="text-sm text-red-700">
                                <span class="font-semibold">Warning:</span> This action will cancel the order and it will not be counted as sales.
                            </p>
                        </div>
                    </div>

                    <!-- Footer -->
                    <div class="border-t px-6 py-4 flex gap-2 justify-end">
                        <button
                            @click="open = false"
                            class="px-4 py-2 text-gray-700 border border-gray-300 rounded-lg hover:bg-gray-50"
                        >
                            Cancel
                        </button>
                        <button
                            wire:click="submitCancelOrder"
                            wire:loading.attr="disabled"
                            class="px-4 py-2 bg-red-500 text-white rounded-lg hover:bg-red-600 disabled:opacity-50"
                        >
                            <span wire:loading.remove>Confirm Cancellation</span>
                            <span wire:loading>Processing...</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</x-filament-panels::page>
