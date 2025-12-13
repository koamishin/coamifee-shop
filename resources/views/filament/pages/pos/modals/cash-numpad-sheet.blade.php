@php
    /** @var string $currency */
@endphp

<div
    x-data="{
        open: false,
        value: String(@js($initial ?? '0')),
        tap(n) {
            if (this.value === '0') {
                this.value = '';
            }

            if (this.value.length >= 9) {
                return;
            }

            this.value = this.value + String(n);
        },
        dot() {
            if (this.value.includes('.')) {
                return;
            }
            this.value = this.value + '.';
        },
        back() {
            this.value = this.value.slice(0, -1);
            if (this.value === '') {
                this.value = '0';
            }
        },
        clear() {
            this.value = '0';
        },
        apply() {
            const parsed = parseFloat(this.value || '0');
            $wire.set('paidAmount', isNaN(parsed) ? 0 : parsed);
            this.open = false;
        },
        setExact(total) {
            this.value = String(total);
            this.apply();
        },
        setNext(total, step) {
            const next = Math.ceil(total / step) * step;
            this.value = String(next);
            this.apply();
        },
    }"
    class="space-y-3"
>
    <div class="rounded-2xl border border-gray-200 bg-white p-4">
        <div class="flex items-center justify-between gap-3">
            <div>
                <div class="text-xs font-bold text-gray-500">CASH RECEIVED</div>
                <div class="mt-1 text-2xl font-extrabold text-gray-900">
                    <span class="text-gray-600">{{ $currency }}</span>
                    <span x-text="value"></span>
                </div>
            </div>

            <button
                type="button"
                @click="open = true"
                class="h-14 px-5 rounded-2xl bg-gradient-to-r from-orange-500 to-orange-600 text-white text-base font-extrabold shadow active:scale-[0.98]"
            >
                Enter
            </button>
        </div>

        <div class="mt-3 grid grid-cols-2 gap-2">
            <button
                type="button"
                @click="setExact(@js($total ?? 0))"
                class="h-12 rounded-xl border-2 border-gray-200 bg-gray-50 text-gray-900 font-bold"
            >
                Exact
            </button>
            <button
                type="button"
                @click="setNext(@js($total ?? 0), 50)"
                class="h-12 rounded-xl border-2 border-gray-200 bg-gray-50 text-gray-900 font-bold"
            >
                Next 50
            </button>
            <button
                type="button"
                @click="setNext(@js($total ?? 0), 100)"
                class="h-12 rounded-xl border-2 border-gray-200 bg-gray-50 text-gray-900 font-bold"
            >
                Next 100
            </button>
            <button
                type="button"
                @click="setNext(@js($total ?? 0), 500)"
                class="h-12 rounded-xl border-2 border-gray-200 bg-gray-50 text-gray-900 font-bold"
            >
                Next 500
            </button>
        </div>
    </div>

    {{-- Side sheet / slide-over --}}
    <div
        x-show="open"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        class="fixed inset-0 z-50"
        style="display: none;"
    >
        <div class="absolute inset-0 bg-black/30" @click="open = false"></div>

        <div
            x-show="open"
            x-transition:enter="transition ease-out duration-200 transform"
            x-transition:enter-start="translate-x-full"
            x-transition:enter-end="translate-x-0"
            x-transition:leave="transition ease-in duration-150 transform"
            x-transition:leave-start="translate-x-0"
            x-transition:leave-end="translate-x-full"
            class="absolute right-0 top-0 h-full w-[min(420px,90vw)] bg-white shadow-2xl border-l border-gray-200"
        >
            <div class="h-full flex flex-col">
                <div class="p-5 border-b border-gray-200 bg-gradient-to-r from-orange-500 to-orange-600 text-white">
                    <div class="flex items-center justify-between">
                        <div>
                            <div class="text-sm font-extrabold">Numpad</div>
                            <div class="text-xs text-orange-100">Tap numbers, then Apply</div>
                        </div>
                        <button type="button" @click="open = false" class="h-10 w-10 rounded-xl bg-white/15 hover:bg-white/20">
                            <x-filament::icon icon="heroicon-o-x-mark" class="w-6 h-6 text-white mx-auto" />
                        </button>
                    </div>

                    <div class="mt-4 rounded-2xl bg-white/15 border border-white/20 p-4">
                        <div class="text-xs font-bold text-orange-100">CASH RECEIVED</div>
                        <div class="mt-1 text-3xl font-extrabold">
                            {{ $currency }} <span x-text="value"></span>
                        </div>
                    </div>
                </div>

                <div class="flex-1 p-5">
                    <div class="grid grid-cols-3 gap-3">
                        <template x-for="n in [1,2,3,4,5,6,7,8,9]" :key="n">
                            <button
                                type="button"
                                @click="tap(n)"
                                class="h-16 rounded-2xl border-2 border-gray-200 bg-white text-2xl font-extrabold text-gray-900 active:scale-[0.98]"
                            >
                                <span x-text="n"></span>
                            </button>
                        </template>

                        <button type="button" @click="dot()" class="h-16 rounded-2xl border-2 border-gray-200 bg-white text-2xl font-extrabold">.</button>
                        <button type="button" @click="tap(0)" class="h-16 rounded-2xl border-2 border-gray-200 bg-white text-2xl font-extrabold">0</button>
                        <button type="button" @click="back()" class="h-16 rounded-2xl border-2 border-gray-200 bg-white text-lg font-extrabold">⌫</button>
                    </div>

                    <div class="mt-4 grid grid-cols-2 gap-3">
                        <button type="button" @click="clear()" class="h-14 rounded-2xl bg-gray-100 border border-gray-200 text-gray-900 font-extrabold">
                            Clear
                        </button>
                        <button type="button" @click="apply()" class="h-14 rounded-2xl bg-gradient-to-r from-orange-500 to-orange-600 text-white font-extrabold shadow active:scale-[0.99]">
                            Apply
                        </button>
                    </div>

                    <div class="mt-4 grid grid-cols-2 gap-3">
                        <button type="button" @click="setExact(@js($total ?? 0))" class="h-12 rounded-xl border-2 border-gray-200 bg-white text-gray-900 font-bold">
                            Exact
                        </button>
                        <button type="button" @click="setNext(@js($total ?? 0), 50)" class="h-12 rounded-xl border-2 border-gray-200 bg-white text-gray-900 font-bold">
                            Next 50
                        </button>
                        <button type="button" @click="setNext(@js($total ?? 0), 100)" class="h-12 rounded-xl border-2 border-gray-200 bg-white text-gray-900 font-bold">
                            Next 100
                        </button>
                        <button type="button" @click="setNext(@js($total ?? 0), 500)" class="h-12 rounded-xl border-2 border-gray-200 bg-white text-gray-900 font-bold">
                            Next 500
                        </button>
                    </div>
                </div>

                <div class="p-5 border-t border-gray-200">
                    <button type="button" @click="open = false" class="w-full h-14 rounded-2xl border border-gray-300 bg-white text-gray-900 font-extrabold">
                        Done
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
