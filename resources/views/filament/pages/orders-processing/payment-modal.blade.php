
<div x-data="{
    paymentMethod: @entangle('paymentState.paymentMethod'),
    paidAmount: @entangle('paymentState.paidAmount'),
    total: {{ $order->total }},
    currency: '{{ $this->currency->getSymbol() }}',
    
    // Calculator Logic
    appendNumber(num) {
        let current = String(this.paidAmount || '0');
        if (current === '0') {
            this.paidAmount = num;
        } else if (current.includes('.')) {
            // Limit to 2 decimal places
            if (current.split('.')[1].length < 2) {
                this.paidAmount = current + num;
            }
        } else {
            this.paidAmount = current + num;
        }
    },
    
    appendDecimal() {
        let str = String(this.paidAmount || '0');
        if (!str.includes('.')) {
            this.paidAmount = str + '.';
        }
    },
    
    clear() {
        this.paidAmount = 0;
    },
    
    backspace() {
        let str = String(this.paidAmount || '0');
        if (str.length > 1) {
            this.paidAmount = str.slice(0, -1);
            if (this.paidAmount === '' || this.paidAmount === '.') {
                this.paidAmount = 0;
            }
        } else {
            this.paidAmount = 0;
        }
    },
    
    setExact() {
        this.paidAmount = this.total;
    },
    
    addAmount(amount) {
        this.paidAmount = (parseFloat(this.paidAmount || 0) + amount).toFixed(2);
    },

    get change() {
        return Math.max(0, (parseFloat(this.paidAmount || 0) - this.total));
    },
    
    get remaining() {
        return Math.max(0, (this.total - parseFloat(this.paidAmount || 0)));
    },
    
    get isSufficient() {
        if (this.paymentMethod !== 'cash') return true;
        return parseFloat(this.paidAmount || 0) >= (this.total - 0.01);
    }
}" class="flex flex-col h-full bg-gray-50 -m-6">

    <div class="flex-1 flex overflow-hidden">
        <!-- LEFT COLUMN: Order Summary (Receipt Style) -->
        <div class="w-1/3 flex flex-col border-r border-gray-200 bg-white shadow-lg z-10">
            <div class="p-4 bg-gray-50 border-b border-gray-200">
                <div class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-1">Order #{{ $order->id }}</div>
                <div class="text-xl font-bold text-gray-900">{{ $order->customer_name }}</div>
                @if($order->table_number)
                    <div class="text-sm text-gray-600">Table {{ $order->table_number }}</div>
                @endif
            </div>

            <div class="flex-1 overflow-y-auto p-4 space-y-3">
                @foreach($order->items as $item)
                    <div class="flex justify-between items-start text-sm">
                        <div class="flex gap-2">
                            <span class="font-bold text-gray-900 w-6">{{ $item->quantity }}x</span>
                            <div>
                                <div class="text-gray-800 font-medium">{{ $item->product->name }}</div>
                                @if($item->variant_name)
                                    <div class="text-xs text-gray-500">{{ $item->variant_name }}</div>
                                @endif
                                @if(($item->discount_amount ?? 0) > 0)
                                    <div class="text-xs text-red-500">
                                        Discount: -{{ $this->formatCurrency($item->discount_amount) }}
                                    </div>
                                @endif
                            </div>
                        </div>
                        <div class="text-right">
                             <div class="font-medium text-gray-900">{{ $this->formatCurrency($item->subtotal - ($item->discount_amount ?? 0)) }}</div>
                             @if(($item->discount_amount ?? 0) > 0)
                                <div class="text-xs text-gray-400 line-through">{{ $this->formatCurrency($item->subtotal) }}</div>
                             @endif
                        </div>
                    </div>
                @endforeach
                
                @if($order->add_ons_total > 0)
                    <div class="border-t border-dashed border-gray-300 pt-2 mt-2">
                        <div class="flex justify-between text-sm text-purple-600">
                            <span>Add-ons Total</span>
                            <span>+{{ $this->formatCurrency($order->add_ons_total) }}</span>
                        </div>
                    </div>
                @endif
            </div>

            <!-- Totals Footer -->
            <div class="p-4 bg-gray-50 border-t border-gray-200 space-y-2">
                <div class="flex justify-between text-sm text-gray-600">
                    <span>Subtotal</span>
                    <span>{{ $this->formatCurrency($order->subtotal) }}</span>
                </div>
                @if(($order->discount_amount ?? 0) > 0)
                    <div class="flex justify-between text-sm text-red-600 font-medium">
                        <span>Discount</span>
                        <span>-{{ $this->formatCurrency($order->discount_amount) }}</span>
                    </div>
                @endif
                <div class="flex justify-between text-2xl font-bold text-gray-900 pt-2 border-t border-gray-300">
                    <span>Total</span>
                    <span class="text-blue-600">{{ $this->formatCurrency($order->total) }}</span>
                </div>
            </div>
        </div>

        <!-- RIGHT COLUMN: Payment Interface -->
        <div class="w-2/3 flex flex-col bg-gray-100">
            <!-- Payment Method Tabs -->
            <div class="p-4 grid grid-cols-2 md:grid-cols-4 gap-3 bg-white border-b border-gray-200 shadow-sm">
                @php
                    $isDelivery = $order->order_type === 'delivery';
                    $methods = $isDelivery 
                        ? [
                            'grab' => ['label' => 'Grab', 'icon' => 'heroicon-o-truck', 'color' => 'text-green-600'],
                            'food_panda' => ['label' => 'Food Panda', 'icon' => 'heroicon-o-shopping-bag', 'color' => 'text-pink-600'],
                        ]
                        : [
                            'cash' => ['label' => 'CASH', 'icon' => 'heroicon-o-banknotes', 'color' => 'text-green-600'],
                            'gcash' => ['label' => 'GCash', 'icon' => 'heroicon-o-qr-code', 'color' => 'text-blue-600'],
                            'maya' => ['label' => 'Maya', 'icon' => 'heroicon-o-credit-card', 'color' => 'text-black'],
                            'bank_transfer' => ['label' => 'Bank', 'icon' => 'heroicon-o-building-library', 'color' => 'text-gray-600'],
                        ];
                @endphp

                @foreach($methods as $value => $method)
                    <button 
                        type="button"
                        @click="paymentMethod = '{{ $value }}'"
                        :class="paymentMethod === '{{ $value }}' 
                            ? 'bg-blue-600 text-white shadow-md ring-2 ring-blue-300 transform scale-105' 
                            : 'bg-white text-gray-600 hover:bg-gray-50 border border-gray-200'"
                        class="flex flex-col items-center justify-center p-4 rounded-xl transition-all duration-200 h-24"
                    >
                        <x-filament::icon :icon="$method['icon']" class="w-8 h-8 mb-2" />
                        <span class="font-bold text-sm">{{ $method['label'] }}</span>
                    </button>
                @endforeach
            </div>

            <!-- Payment Content Area -->
            <div class="flex-1 p-6 overflow-y-auto">
                <!-- CASH PAYMENT UI -->
                <div x-show="paymentMethod === 'cash'" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 translate-y-2">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 h-full">
                        <!-- Display & Status -->
                        <div class="flex flex-col gap-4">
                            <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-200 text-center">
                                <div class="text-sm text-gray-500 uppercase font-semibold mb-1">Cash Received</div>
                                <div class="text-4xl font-bold text-gray-900 flex items-center justify-center gap-1">
                                    <span class="text-2xl text-gray-400">{{ $this->currency->getSymbol() }}</span>
                                    <span x-text="parseFloat(paidAmount || 0).toFixed(2)"></span>
                                </div>
                            </div>

                            <div class="grid grid-cols-2 gap-4">
                                <div class="bg-red-50 p-4 rounded-xl border border-red-100 text-center">
                                    <div class="text-xs text-red-500 font-bold uppercase">Balance Due</div>
                                    <div class="text-xl font-bold text-red-600" x-text="'{{ $this->currency->getSymbol() }}' + remaining.toFixed(2)"></div>
                                </div>
                                <div class="bg-green-50 p-4 rounded-xl border border-green-100 text-center">
                                    <div class="text-xs text-green-500 font-bold uppercase">Change</div>
                                    <div class="text-xl font-bold text-green-600" x-text="'{{ $this->currency->getSymbol() }}' + change.toFixed(2)"></div>
                                </div>
                            </div>

                            <!-- Quick Amount Buttons -->
                            <div class="grid grid-cols-2 gap-2 mt-auto">
                                <button type="button" @click="setExact()" class="py-3 px-4 bg-gray-200 hover:bg-gray-300 rounded-lg font-semibold text-gray-700 transition-colors">Exact Amount</button>
                                <button type="button" @click="addAmount(100)" class="py-3 px-4 bg-blue-50 hover:bg-blue-100 text-blue-700 rounded-lg font-semibold transition-colors">+100</button>
                                <button type="button" @click="addAmount(500)" class="py-3 px-4 bg-blue-50 hover:bg-blue-100 text-blue-700 rounded-lg font-semibold transition-colors">+500</button>
                                <button type="button" @click="addAmount(1000)" class="py-3 px-4 bg-blue-50 hover:bg-blue-100 text-blue-700 rounded-lg font-semibold transition-colors">+1000</button>
                            </div>
                        </div>

                        <!-- Numpad -->
                        <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-4">
                            <div class="grid grid-cols-3 gap-3 h-full">
                                @foreach([1, 2, 3, 4, 5, 6, 7, 8, 9] as $num)
                                    <button 
                                        type="button"
                                        @click="appendNumber('{{ $num }}')"
                                        class="text-2xl font-bold text-gray-700 bg-gray-50 hover:bg-gray-100 active:bg-gray-200 rounded-xl aspect-[4/3] transition-colors"
                                    >
                                        {{ $num }}
                                    </button>
                                @endforeach
                                <button type="button" @click="appendDecimal()" class="text-2xl font-bold text-gray-700 bg-gray-50 hover:bg-gray-100 rounded-xl aspect-[4/3]">.</button>
                                <button type="button" @click="appendNumber('0')" class="text-2xl font-bold text-gray-700 bg-gray-50 hover:bg-gray-100 rounded-xl aspect-[4/3]">0</button>
                                <button type="button" @click="backspace()" class="flex items-center justify-center text-red-500 bg-red-50 hover:bg-red-100 rounded-xl aspect-[4/3]">
                                    <x-filament::icon icon="heroicon-o-backspace" class="w-8 h-8" />
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- DIGITAL PAYMENT UI -->
                <div x-show="paymentMethod !== 'cash'" x-transition:enter="transition ease-out duration-200" class="flex flex-col items-center justify-center h-full text-center space-y-6">
                    <div class="w-24 h-24 bg-blue-50 rounded-full flex items-center justify-center">
                        <x-filament::icon icon="heroicon-o-device-phone-mobile" class="w-12 h-12 text-blue-600" />
                    </div>
                    <div>
                        <h3 class="text-xl font-bold text-gray-900 mb-2">
                            Pay via <span x-text="paymentMethod.replace('_', ' ').toUpperCase()"></span>
                        </h3>
                        <p class="text-gray-500">
                            Please verify the payment of <span class="font-bold text-gray-900">{{ $this->formatCurrency($order->total) }}</span>
                        </p>
                    </div>
                    <div class="bg-yellow-50 text-yellow-800 px-4 py-3 rounded-lg text-sm max-w-sm">
                        Ensure you have received the payment reference or confirmation before completing the order.
                    </div>
                </div>
            </div>

            <!-- Action Bar -->
            <div class="p-4 bg-white border-t border-gray-200">
                <button 
                    type="button"
                    @click="$wire.processPayment({{ $order->id }}, paymentMethod, paidAmount)"
                    :disabled="!isSufficient"
                    :class="isSufficient 
                        ? 'bg-green-600 hover:bg-green-700 shadow-lg hover:shadow-xl transform hover:-translate-y-0.5' 
                        : 'bg-gray-300 cursor-not-allowed opacity-75'"
                    class="w-full py-4 rounded-xl text-white font-bold text-xl transition-all duration-200 flex items-center justify-center gap-2"
                >
                    <x-filament::icon icon="heroicon-o-check-circle" class="w-6 h-6" />
                    <span>COMPLETE PAYMENT</span>
                    <span x-show="isSufficient && paymentMethod === 'cash' && change > 0" class="text-sm font-normal bg-green-700 px-2 py-1 rounded ml-2">
                        (Change: <span x-text="currency + change.toFixed(2)"></span>)
                    </span>
                </button>
            </div>
        </div>
    </div>
</div>

