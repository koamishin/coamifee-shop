<x-filament-panels::page>
    <div class="mx-auto max-w-2xl">
        <div class="space-y-6">
            {{-- Info Card --}}
            <div class="rounded-lg border border-blue-200 bg-blue-50 p-4">
                <div class="flex gap-3">
                    <div class="flex-shrink-0">
                        <x-filament::icon icon="heroicon-o-information-circle" class="h-5 w-5 text-blue-600" />
                    </div>
                    <div class="text-sm text-blue-800">
                        <p class="font-medium">What is an Admin PIN?</p>
                        <p class="mt-1">Your Admin PIN is a 4-6 digit code required to perform sensitive operations like processing refunds. Only you know this PIN, and it provides an extra layer of security.</p>
                    </div>
                </div>
            </div>

            {{-- Form --}}
            <form wire:submit.prevent="submit" class="space-y-6">
                {{ $this->form }}

                <div class="flex justify-end gap-3">
                    <flux:button variant="primary" type="submit">
                        Set PIN
                    </flux:button>
                </div>
            </form>

            @if(Auth::user()->admin_pin)
                <div class="rounded-lg border border-green-200 bg-green-50 p-4">
                    <div class="flex gap-3">
                        <div class="flex-shrink-0">
                            <x-filament::icon icon="heroicon-o-check-circle" class="h-5 w-5 text-green-600" />
                        </div>
                        <div class="text-sm text-green-800">
                            <p class="font-medium">PIN is Set</p>
                            <p class="mt-1">Your PIN is active and ready to use for refund operations.</p>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>
</x-filament-panels::page>
