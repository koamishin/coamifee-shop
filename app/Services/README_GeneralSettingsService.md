# General Settings Service

The `GeneralSettingsService` provides a clean, cached interface for accessing application-wide configuration stored in the `GeneralSetting` model from the Filament General Settings package.

## Features

- **Caching**: Automatically caches settings for 1 hour to improve performance
- **Type Safety**: All methods have proper return type declarations
- **Graceful Defaults**: Returns sensible defaults when settings are missing
- **Convenience Methods**: Grouped methods for related settings
- **Validation Helper Methods**: Quick checks to see if features are configured

## Basic Usage

```php
use App\Services\GeneralSettingsService;

$service = new GeneralSettingsService();

// Get individual settings
$siteName = $service->getSiteName();
$siteDescription = $service->getSiteDescription();
$supportEmail = $service->getSupportEmail();

// Get grouped settings
$branding = $service->getBrandingConfig();
$seo = $service->getSeoConfig();
$analytics = $service->getAnalyticsConfig();
$contact = $service->getContactInfo();
```

## Available Methods

### Site Information
- `getSiteName(): string` - Site name with fallback to app.name
- `getSiteDescription(): ?string` - Site description
- `getSiteLogo(): ?string` - Site logo URL
- `getSiteFavicon(): ?string` - Site favicon URL
- `getThemeColor(): ?string` - Theme color

### Contact Information
- `getSupportEmail(): ?string` - Support email address
- `getSupportPhone(): ?string` - Support phone number
- `getContactInfo(): array` - Array with email and phone

### SEO Configuration
- `getSeoTitle(): ?string` - SEO title
- `getSeoKeywords(): ?string` - SEO keywords
- `getSeoMetadata(): array` - SEO metadata array
- `getSeoConfig(): array` - Complete SEO configuration

### Analytics
- `getGoogleAnalyticsId(): ?string` - Google Analytics tracking ID
- `getPosthogHtmlSnippet(): ?string` - PostHog HTML snippet
- `getAnalyticsConfig(): array` - Complete analytics configuration

### Social Networks
- `getSocialNetworks(): array` - All social network URLs
- `getSocialNetwork(string $platform): ?string` - Get specific platform URL

### Email Configuration
- `getEmailSettings(): array` - Email provider settings
- `getEmailSetting(string $key): mixed` - Get specific email setting
- `getEmailFromAddress(): ?string` - Default from email address
- `getEmailFromName(): ?string` - Default from name

### Additional Configuration
- `getMoreConfigs(): array` - All additional configurations
- `getMoreConfig(string $key): mixed` - Get specific additional config
- `getCurrency(): string` - Get currency (defaults to USD)

### Utility Methods
- `getAllSettings(): array` - Get all settings as array
- `clearCache(): void` - Clear the settings cache
- `hasAnalytics(): bool` - Check if any analytics is configured
- `hasGoogleAnalytics(): bool` - Check if Google Analytics is configured
- `hasPostHog(): bool` - Check if PostHog is configured
- `hasSocialNetworks(): bool` - Check if social networks are configured
- `hasEmailSettings(): bool` - Check if email settings are configured
- `hasContactInfo(): bool` - Check if contact info is available

## Example Usage in Controllers

```php
class SiteController extends Controller
{
    public function __construct(private GeneralSettingsService $settings) {}

    public function home()
    {
        return view('home', [
            'siteName' => $this->settings->getSiteName(),
            'siteDescription' => $this->settings->getSiteDescription(),
            'contactInfo' => $this->settings->getContactInfo(),
            'socialNetworks' => $this->settings->getSocialNetworks(),
        ]);
    }

    public function about()
    {
        return view('about', [
            'branding' => $this->settings->getBrandingConfig(),
            'seo' => $this->settings->getSeoConfig(),
        ]);
    }
}
```

## Example Usage in Blade Templates

```blade
{{-- Display site information --}}
<h1>{{ app(GeneralSettingsService::class)->getSiteName() }}</h1>
<p>{{ app(GeneralSettingsService::class)->getSiteDescription() }}</p>

{{-- Display contact info if available --}}
@if (app(GeneralSettingsService::class)->hasContactInfo())
    @php
        $contact = app(GeneralSettingsService::class)->getContactInfo();
    @endphp
    <div class="contact-info">
        @if ($contact['email'])
            <a href="mailto:{{ $contact['email'] }}">{{ $contact['email'] }}</a>
        @endif
        @if ($contact['phone'])
            <a href="tel:{{ $contact['phone'] }}">{{ $contact['phone'] }}</a>
        @endif
    </div>
@endif

{{-- Display social networks --}}
@if (app(GeneralSettingsService::class)->hasSocialNetworks())
    @php
        $networks = app(GeneralSettingsService::class)->getSocialNetworks();
    @endphp
    <div class="social-links">
        @if ($networks['facebook'])
            <a href="{{ $networks['facebook'] }}">Facebook</a>
        @endif
        @if ($networks['twitter'])
            <a href="{{ $networks['twitter'] }}">Twitter</a>
        @endif
    </div>
@endif

{{-- Display analytics --}}
@if (app(GeneralSettingsService::class)->hasGoogleAnalytics())
    <!-- Global site tag (gtag.js) - Google Analytics -->
    <script async src="https://www.googletagmanager.com/gtag/js?id={{ app(GeneralSettingsService::class)->getGoogleAnalyticsId() }}"></script>
    <script>
        window.dataLayer = window.dataLayer || [];
        function gtag(){dataLayer.push(arguments);}
        gtag('js', new Date());
        gtag('config', '{{ app(GeneralSettingsService::class)->getGoogleAnalyticsId() }}');
    </script>
@endif
```

## Dependency Injection

You can inject the service using Laravel's dependency injection:

```php
class YourController extends Controller
{
    public function __construct(
        private GeneralSettingsService $settings
    ) {}

    public function method()
    {
        $currency = $this->settings->getCurrency();
        // ... your code
    }
}
```

## Caching

The service automatically caches settings for 1 hour. When you update settings through Filament, you may want to clear the cache:

```php
// Clear cache manually
$service = new GeneralSettingsService();
$service->clearCache();

// Or in your service provider
public function boot(GeneralSettingsService $settings)
{
    // Clear cache when settings are updated
    GeneralSetting::updated(function () use ($settings) {
        $settings->clearCache();
    });
}
```

## Current Configuration

Based on your current settings:

- **Site Name**: Good Land cafe
- **Currency**: PHP
- **Analytics**: Not configured
- **Social Networks**: Configured but no URLs set
- **Email Settings**: SMTP provider configured but credentials not set

This service makes it easy to access these values throughout your application while maintaining good performance through caching.