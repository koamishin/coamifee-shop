<x-layouts.auth :title="__('Login - Goodland Cafe POS')">
    <div class="max-w-sm mx-auto">
        <div class="text-center mb-6">
            <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-2">{{ __('Welcome Back') }}</h2>
            <p class="text-gray-600 dark:text-gray-400">{{ __('Sign in to access your POS system') }}</p>
        </div>

        <div class="flex flex-col gap-4">

        <!-- Session Status -->
        <x-auth-session-status class="text-center" :status="session('status')" />

            <form method="POST" action="{{ route('login.store') }}" class="flex flex-col gap-4">
            @csrf

            <!-- Email Address -->
            <flux:input
                name="email"
                :label="__('Email address')"
                type="email"
                required
                autofocus
                autocomplete="email"
                placeholder="email@example.com"
            />

            <!-- Password -->
            <div class="relative">
                <flux:input
                    name="password"
                    :label="__('Password')"
                    type="password"
                    required
                    autocomplete="current-password"
                    :placeholder="__('Password')"
                    viewable
                />

                @if (Route::has('password.request'))
                    <flux:link class="absolute top-0 text-sm end-0 text-amber-600 hover:text-amber-700 dark:text-amber-400 dark:hover:text-amber-300" :href="route('password.request')" wire:navigate>
                        {{ __('Forgot your password?') }}
                    </flux:link>
                @endif
            </div>

            <!-- Remember Me -->
            <flux:checkbox name="remember" :label="__('Remember me')" :checked="old('remember')" />

            <div class="flex items-center justify-end">
                <flux:button type="submit" class="w-full bg-gradient-to-r from-amber-500 to-orange-600 hover:from-amber-600 hover:to-orange-700 shadow-lg hover:shadow-xl transform hover:-translate-y-0.5 transition-all duration-200 text-white font-medium py-2.5 text-sm" data-test="login-button">
                    {{ __('Log in to POS') }}
                </flux:button>
            </div>
        </form>

            @if (Route::has('register'))
                <div class="space-x-1 text-sm text-center rtl:space-x-reverse text-gray-600 dark:text-gray-400 mt-4">
                    <span>{{ __('Don\'t have an account?') }}</span>
                    <flux:link class="text-amber-600 hover:text-amber-700 dark:text-amber-400 dark:hover:text-amber-300 font-medium" :href="route('register')" wire:navigate>{{ __('Create Account') }}</flux:link>
                </div>
            @endif
        </div>
    </div>
</x-layouts.auth>
