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
    class="space-y-4"
>
    {{-- Trigger Area --}}
    <div class="rounded-3xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 p-5 shadow-sm">
        <div class="flex items-center justify-between gap-4">
            <div>
                <div class="text-[10px] font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">Cash Received</div>
                <div class="mt-1 flex items-baseline gap-1">
                    <span class="text-lg font-semibold text-gray-500 dark:text-gray-400">{{ $currency }}</span>
                    <span class="text-3xl font-black text-gray-900 dark:text-white" x-text="value"></span>
                </div>
            </div>

            <button
                type="button"
                @click="open = true"
                class="h-14 px-8 rounded-2xl bg-gray-900 dark:bg-white text-white dark:text-gray-900 text-lg font-bold shadow-lg hover:scale-105 active:scale-95 transition-all"
            >
                Enter
            </button>
        </div>

        {{-- Quick Presets --}}
        <div class="mt-4 grid grid-cols-4 gap-2">
            <button
                type="button"
                @click="setExact(@js($total ?? 0))"
                class="h-12 rounded-xl border-2 border-primary-100 dark:border-primary-900/30 bg-primary-50 dark:bg-primary-900/10 text-primary-700 dark:text-primary-400 font-bold text-sm hover:border-primary-500 transition-colors"
            >
                Exact
            </button>
            <button
                type="button"
                @click="setNext(@js($total ?? 0), 50)"
                class="h-12 rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-200 font-bold text-sm hover:bg-gray-50 dark:hover:bg-gray-700"
            >
                +50
            </button>
            <button
                type="button"
                @click="setNext(@js($total ?? 0), 100)"
                class="h-12 rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-200 font-bold text-sm hover:bg-gray-50 dark:hover:bg-gray-700"
            >
                +100
            </button>
            <button
                type="button"
                @click="setNext(@js($total ?? 0), 500)"
                class="h-12 rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-200 font-bold text-sm hover:bg-gray-50 dark:hover:bg-gray-700"
            >
                +500
            </button>
        </div>
    </div>

    {{-- Slide-over Numpad --}}
    <div
        x-show="open"
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        class="fixed inset-0 z-[70] backdrop-blur-sm bg-gray-900/50"
        style="display: none;"
    >
        <div class="absolute inset-0" @click="open = false"></div>

        <div
            x-show="open"
            x-transition:enter="transition ease-out duration-300 transform"
            x-transition:enter-start="translate-x-full"
            x-transition:enter-end="translate-x-0"
            x-transition:leave="transition ease-in duration-200 transform"
            x-transition:leave-start="translate-x-0"
            x-transition:leave-end="translate-x-full"
            class="absolute right-0 top-0 h-full w-full sm:w-[400px] bg-white dark:bg-gray-900 shadow-2xl border-l border-gray-200 dark:border-gray-800 flex flex-col"
        >
            {{-- Header --}}
            <div class="p-6 bg-gray-50 dark:bg-gray-950 border-b border-gray-200 dark:border-gray-800 flex items-center justify-between">
                <h3 class="text-xl font-black text-gray-900 dark:text-white">Enter Amount</h3>
                <button type="button" @click="open = false" class="p-2 rounded-full bg-gray-200 dark:bg-gray-800 text-gray-500 hover:text-gray-900 dark:hover:text-white transition-colors">
                    <x-filament::icon icon="heroicon-m-x-mark" class="w-6 h-6" />
                </button>
            </div>

            {{-- Display --}}
            <div class="p-8 text-center bg-white dark:bg-gray-900">
                <div class="inline-flex items-baseline justify-center gap-1 text-5xl font-black text-primary-600 dark:text-primary-500 tracking-tight">
                    <span class="text-2xl text-gray-400 font-bold">{{ $currency }}</span>
                    <span x-text="value"></span>
                </div>
            </div>

            {{-- Keypad --}}
            <div class="flex-1 p-6 bg-gray-50 dark:bg-gray-950 grid grid-cols-3 gap-3">
                <template x-for="n in [1,2,3,4,5,6,7,8,9]" :key="n">
                    <button
                        type="button"
                        @click="tap(n)"
                        class="h-20 rounded-2xl bg-white dark:bg-gray-800 shadow-sm border-b-4 border-gray-200 dark:border-gray-900 active:border-b-0 active:translate-y-1 text-3xl font-bold text-gray-900 dark:text-white transition-all"
                    >
                        <span x-text="n"></span>
                    </button>
                </template>

                <button type="button" @click="dot()" class="h-20 rounded-2xl bg-white dark:bg-gray-800 shadow-sm border-b-4 border-gray-200 dark:border-gray-900 active:border-b-0 active:translate-y-1 text-3xl font-bold text-gray-900 dark:text-white transition-all">.</button>
                <button type="button" @click="tap(0)" class="h-20 rounded-2xl bg-white dark:bg-gray-800 shadow-sm border-b-4 border-gray-200 dark:border-gray-900 active:border-b-0 active:translate-y-1 text-3xl font-bold text-gray-900 dark:text-white transition-all">0</button>
                <button type="button" @click="back()" class="h-20 rounded-2xl bg-red-50 dark:bg-red-900/20 shadow-sm border-b-4 border-red-100 dark:border-red-900 active:border-b-0 active:translate-y-1 text-2xl font-bold text-red-600 dark:text-red-400 transition-all flex items-center justify-center">
                    <x-filament::icon icon="heroicon-m-backspace" class="w-8 h-8" />
                </button>
            </div>

            {{-- Actions --}}
            <div class="p-6 bg-white dark:bg-gray-900 border-t border-gray-200 dark:border-gray-800 grid grid-cols-2 gap-4">
                <button
                    type="button"
                    @click="clear()"
                    class="h-16 rounded-2xl bg-gray-100 dark:bg-gray-800 text-gray-900 dark:text-white font-bold text-lg hover:bg-gray-200 dark:hover:bg-gray-700 transition-colors"
                >
                    Clear
                </button>
                <button
                    type="button"
                    @click="apply()"
                    class="h-16 rounded-2xl bg-primary-600 hover:bg-primary-500 text-white font-bold text-lg shadow-lg shadow-primary-500/30 transition-all active:scale-95"
                >
                    Apply Amount
                </button>
            </div>
        </div>
    </div>
</div>
