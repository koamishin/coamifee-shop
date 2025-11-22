<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Joaopaulolndev\FilamentGeneralSettings\Models\GeneralSetting;

final class GeneralSettingsService
{
    private const CACHE_KEY = 'general_settings';

    private const CACHE_TTL = 3600; // 1 hour

    /**
     * Clear the settings cache
     */
    public function clearCache(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    /**
     * Get site name
     */
    public function getSiteName(): string
    {
        return $this->getSettings()->site_name ?? config('app.name', 'My Application');
    }

    /**
     * Get site description
     */
    public function getSiteDescription(): ?string
    {
        return $this->getSettings()->site_description;
    }

    /**
     * Get site logo URL
     */
    public function getSiteLogo(): ?string
    {
        return $this->getSettings()->site_logo;
    }

    /**
     * Get site favicon URL
     */
    public function getSiteFavicon(): ?string
    {
        return $this->getSettings()->site_favicon;
    }

    /**
     * Get theme color
     */
    public function getThemeColor(): ?string
    {
        return $this->getSettings()->theme_color;
    }

    /**
     * Get support email
     */
    public function getSupportEmail(): ?string
    {
        return $this->getSettings()->support_email;
    }

    /**
     * Get support phone
     */
    public function getSupportPhone(): ?string
    {
        return $this->getSettings()->support_phone;
    }

    /**
     * Get Google Analytics ID
     */
    public function getGoogleAnalyticsId(): ?string
    {
        return $this->getSettings()->google_analytics_id;
    }

    /**
     * Get PostHog HTML snippet
     */
    public function getPosthogHtmlSnippet(): ?string
    {
        return $this->getSettings()->posthog_html_snippet;
    }

    /**
     * Get SEO title
     */
    public function getSeoTitle(): ?string
    {
        return $this->getSettings()->seo_title;
    }

    /**
     * Get SEO keywords
     */
    public function getSeoKeywords(): ?string
    {
        return $this->getSettings()->seo_keywords;
    }

    /**
     * Get SEO metadata
     */
    public function getSeoMetadata(): array
    {
        return $this->getSettings()->seo_metadata ?? [];
    }

    /**
     * Get social networks configuration
     */
    public function getSocialNetworks(): array
    {
        return $this->getSettings()->social_network ?? [];
    }

    /**
     * Get specific social network URL
     */
    public function getSocialNetwork(string $platform): ?string
    {
        $networks = $this->getSocialNetworks();

        return $networks[$platform] ?? null;
    }

    /**
     * Get email settings
     */
    public function getEmailSettings(): array
    {
        return $this->getSettings()->email_settings ?? [];
    }

    /**
     * Get specific email setting
     */
    public function getEmailSetting(string $key): mixed
    {
        $settings = $this->getEmailSettings();

        return $settings[$key] ?? null;
    }

    /**
     * Get email from address
     */
    public function getEmailFromAddress(): ?string
    {
        return $this->getSettings()->email_from_address;
    }

    /**
     * Get email from name
     */
    public function getEmailFromName(): ?string
    {
        return $this->getSettings()->email_from_name;
    }

    /**
     * Get additional configurations
     */
    public function getMoreConfigs(): array
    {
        return $this->getSettings()->more_configs ?? [];
    }

    /**
     * Get specific additional configuration
     */
    public function getMoreConfig(string $key): mixed
    {
        $configs = $this->getMoreConfigs();

        return $configs[$key] ?? null;
    }

    /**
     * Get currency configuration
     */
    public function getCurrency(): string
    {
        return $this->getMoreConfig('Currency') ?? 'USD';
    }

    /**
     * Get payment methods configuration
     */
    public function getPaymentMethods(): array
    {
        return $this->getMoreConfig('payment_methods') ?? [
            'cash' => [
                'name' => 'Cash',
                'description' => 'Pay with cash',
                'icon' => 'heroicon-o-banknotes',
                'color' => 'warning',
                'enabled' => true,
            ],
            'gcash' => [
                'name' => 'GCash',
                'description' => 'Pay with GCash e-wallet',
                'icon' => 'heroicon-o-credit-card',
                'color' => 'success',
                'enabled' => true,
            ],
            'maya' => [
                'name' => 'Maya',
                'description' => 'Pay with Maya e-wallet',
                'icon' => 'heroicon-o-device-phone-mobile',
                'color' => 'primary',
                'enabled' => true,
            ],
            'bank_transfer' => [
                'name' => 'Bank Transfer',
                'description' => 'Pay with bank transfer',
                'icon' => 'heroicon-o-building-office',
                'color' => 'info',
                'enabled' => true,
            ],
            'grab' => [
                'name' => 'Grab',
                'description' => 'Pay with Grab e-wallet',
                'icon' => 'heroicon-o-credit-card',
                'color' => 'success',
                'enabled' => true,
            ],
            'food_panda' => [
                'name' => 'Food Panda',
                'description' => 'Pay with Food Panda delivery',
                'icon' => 'heroicon-o-truck',
                'color' => 'warning',
                'enabled' => true,
            ],
        ];
    }

    /**
     * Get specific payment method configuration
     */
    public function getPaymentMethod(string $method): ?array
    {
        $methods = $this->getPaymentMethods();

        return $methods[$method] ?? null;
    }

    /**
     * Get enabled payment methods
     */
    public function getEnabledPaymentMethods(): array
    {
        return array_filter($this->getPaymentMethods(), fn ($method) => $method['enabled'] ?? true);
    }

    /**
     * Get payment method display name
     */
    public function getPaymentMethodDisplayName(string $method): string
    {
        $paymentMethod = $this->getPaymentMethod($method);

        return $paymentMethod['name'] ?? ucfirst(str_replace('_', ' ', $method));
    }

    /**
     * Get payment method icon
     */
    public function getPaymentMethodIcon(string $method): string
    {
        $paymentMethod = $this->getPaymentMethod($method);

        return $paymentMethod['icon'] ?? 'heroicon-o-question-mark-circle';
    }

    /**
     * Get payment method color
     */
    public function getPaymentMethodColor(string $method): string
    {
        $paymentMethod = $this->getPaymentMethod($method);

        return $paymentMethod['color'] ?? 'gray';
    }

    /**
     * Get all settings as array
     */
    public function getAllSettings(): array
    {
        return $this->getSettings()->toArray();
    }

    /**
     * Get contact information (email and phone)
     */
    public function getContactInfo(): array
    {
        return [
            'email' => $this->getSettings()->support_email,
            'phone' => $this->getSettings()->support_phone,
        ];
    }

    /**
     * Get analytics configuration
     */
    public function getAnalyticsConfig(): array
    {
        return [
            'google_analytics_id' => $this->getSettings()->google_analytics_id,
            'posthog_html_snippet' => $this->getSettings()->posthog_html_snippet,
        ];
    }

    /**
     * Get SEO configuration
     */
    public function getSeoConfig(): array
    {
        return [
            'title' => $this->getSettings()->seo_title ?: $this->getSiteName(),
            'description' => $this->getSettings()->site_description ?: $this->getSettings()->seo_title,
            'keywords' => $this->getSettings()->seo_keywords,
            'metadata' => $this->getSeoMetadata(),
        ];
    }

    /**
     * Get branding configuration
     */
    public function getBrandingConfig(): array
    {
        return [
            'site_name' => $this->getSiteName(),
            'site_description' => $this->getSettings()->site_description,
            'site_logo' => $this->getSettings()->site_logo,
            'site_favicon' => $this->getSettings()->site_favicon,
            'theme_color' => $this->getSettings()->theme_color,
        ];
    }

    /**
     * Check if analytics is configured
     */
    public function hasAnalytics(): bool
    {
        return ! empty($this->getSettings()->google_analytics_id) || ! empty($this->getSettings()->posthog_html_snippet);
    }

    /**
     * Check if Google Analytics is configured
     */
    public function hasGoogleAnalytics(): bool
    {
        return ! empty($this->getSettings()->google_analytics_id);
    }

    /**
     * Check if PostHog is configured
     */
    public function hasPostHog(): bool
    {
        return ! empty($this->getSettings()->posthog_html_snippet);
    }

    /**
     * Check if social networks are configured
     */
    public function hasSocialNetworks(): bool
    {
        return array_filter($this->getSocialNetworks()) !== [];
    }

    /**
     * Check if email settings are configured
     */
    public function hasEmailSettings(): bool
    {
        return array_filter($this->getEmailSettings()) !== [];
    }

    /**
     * Check if contact information is available
     */
    public function hasContactInfo(): bool
    {
        return ! empty($this->getSettings()->support_email) || ! empty($this->getSettings()->support_phone);
    }

    /**
     * Get the general settings instance, with caching
     */
    private function getSettings(): GeneralSetting
    {
        return Cache::remember(self::CACHE_KEY, self::CACHE_TTL, fn () => GeneralSetting::query()->firstOrCreate([], [
            'site_name' => config('app.name', 'My Application'),
            'site_description' => null,
            'site_logo' => null,
            'site_favicon' => null,
            'theme_color' => null,
            'support_email' => null,
            'support_phone' => null,
            'google_analytics_id' => null,
            'posthog_html_snippet' => null,
            'seo_title' => null,
            'seo_keywords' => null,
            'seo_metadata' => [],
            'social_network' => [],
            'email_settings' => [],
            'email_from_name' => null,
            'email_from_address' => null,
            'more_configs' => [],
        ]));
    }
}
