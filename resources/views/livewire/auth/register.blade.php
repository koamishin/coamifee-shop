<x-layouts.auth :title="__('Register - Goodland Cafe POS')">
    <div class="max-w-sm mx-auto">
        <div class="text-center mb-6">
            <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-2">{{ __('Join Goodland Cafe') }}</h2>
            <p class="text-gray-600 dark:text-gray-400">{{ __('Create your account to start managing your POS system') }}</p>
        </div>

        <div class="flex flex-col gap-4">

        <!-- Session Status -->
        <x-auth-session-status class="text-center" :status="session('status')" />

            <form method="POST" action="{{ route('register.store') }}" class="flex flex-col gap-4">
            @csrf

            <!-- Name -->
            <flux:input
                name="name"
                :label="__('Name')"
                type="text"
                required
                autofocus
                autocomplete="name"
                :placeholder="__('Full name')"
            />

            <!-- Email Address -->
            <flux:input
                name="email"
                :label="__('Email address')"
                type="email"
                required
                autocomplete="email"
                placeholder="email@example.com"
            />

            <!-- Password -->
            <flux:input
                name="password"
                :label="__('Password')"
                type="password"
                required
                autocomplete="new-password"
                :placeholder="__('Password')"
                viewable
            />

            <!-- Confirm Password -->
            <flux:input
                name="password_confirmation"
                :label="__('Confirm password')"
                type="password"
                required
                autocomplete="new-password"
                :placeholder="__('Confirm password')"
                viewable
            />

            <div class="flex items-center justify-end">
                <flux:button type="submit" class="w-full bg-gradient-to-r from-amber-500 to-orange-600 hover:from-amber-600 hover:to-orange-700 shadow-lg hover:shadow-xl transform hover:-translate-y-0.5 transition-all duration-200 text-white font-medium py-2.5 text-sm">
                    {{ __('Create Account') }}
                </flux:button>
            </div>
        </form>

            <div class="space-x-1 rtl:space-x-reverse text-center text-sm text-gray-600 dark:text-gray-400 mt-4">
                <span>{{ __('Already have an account?') }}</span>
                <flux:link class="text-amber-600 hover:text-amber-700 dark:text-amber-400 dark:hover:text-amber-300 font-medium" :href="route('login')" wire:navigate>{{ __('Sign In') }}</flux:link>
            </div>
        </div>
    </div>
</x-layouts.auth>
