<?php

declare(strict_types=1);

use App\Services\GeneralSettingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Joaopaulolndev\FilamentGeneralSettings\Models\GeneralSetting;

uses(RefreshDatabase::class);

beforeEach(function () {
    // Clear any existing cache
    Cache::forget('general_settings');

    // Create a fresh settings record
    GeneralSetting::create([
        'site_name' => 'Test Cafe',
        'site_description' => 'Test Description',
        'site_logo' => 'logo.png',
        'theme_color' => '#000000',
        'support_email' => 'test@example.com',
        'support_phone' => '+1234567890',
        'google_analytics_id' => 'GA-123456',
        'seo_title' => 'Test SEO Title',
        'seo_keywords' => 'test,keywords',
        'seo_metadata' => ['meta1' => 'value1'],
        'social_network' => [
            'facebook' => 'https://facebook.com/test',
            'twitter' => 'https://twitter.com/test',
        ],
        'email_settings' => [
            'smtp_host' => 'smtp.example.com',
            'smtp_port' => '587',
        ],
        'email_from_address' => 'from@example.com',
        'email_from_name' => 'Test From Name',
        'more_configs' => [
            'Currency' => 'USD',
            'TimeZone' => 'UTC',
        ],
    ]);
});

afterEach(function () {
    Cache::forget('general_settings');
});

test('can get site name', function () {
    $service = new GeneralSettingsService();
    expect($service->getSiteName())->toBe('Test Cafe');
});

test('can get site description', function () {
    $service = new GeneralSettingsService();
    expect($service->getSiteDescription())->toBe('Test Description');
});

test('can get site logo', function () {
    $service = new GeneralSettingsService();
    expect($service->getSiteLogo())->toBe('logo.png');
});

test('can get theme color', function () {
    $service = new GeneralSettingsService();
    expect($service->getThemeColor())->toBe('#000000');
});

test('can get support email and phone', function () {
    $service = new GeneralSettingsService();
    expect($service->getSupportEmail())->toBe('test@example.com');
    expect($service->getSupportPhone())->toBe('+1234567890');
});

test('can get google analytics id', function () {
    $service = new GeneralSettingsService();
    expect($service->getGoogleAnalyticsId())->toBe('GA-123456');
});

test('can get seo configuration', function () {
    $service = new GeneralSettingsService();
    $seoConfig = $service->getSeoConfig();

    expect($seoConfig)->toHaveKey('title');
    expect($seoConfig)->toHaveKey('description');
    expect($seoConfig)->toHaveKey('keywords');
    expect($seoConfig)->toHaveKey('metadata');
    expect($seoConfig['title'])->toBe('Test SEO Title');
    expect($seoConfig['description'])->toBe('Test Description');
    expect($seoConfig['keywords'])->toBe('test,keywords');
    expect($seoConfig['metadata'])->toBe(['meta1' => 'value1']);
});

test('can get social networks', function () {
    $service = new GeneralSettingsService();
    $networks = $service->getSocialNetworks();

    expect($networks)->toHaveKey('facebook');
    expect($networks)->toHaveKey('twitter');
    expect($networks['facebook'])->toBe('https://facebook.com/test');
    expect($networks['twitter'])->toBe('https://twitter.com/test');
});

test('can get specific social network', function () {
    $service = new GeneralSettingsService();
    expect($service->getSocialNetwork('facebook'))->toBe(
        'https://facebook.com/test',
    );
    expect($service->getSocialNetwork('nonexistent'))->toBeNull();
});

test('can get email settings', function () {
    $service = new GeneralSettingsService();
    $settings = $service->getEmailSettings();

    expect($settings)->toHaveKey('smtp_host');
    expect($settings)->toHaveKey('smtp_port');
    expect($settings['smtp_host'])->toBe('smtp.example.com');
    expect($settings['smtp_port'])->toBe('587');
});

test('can get email from address and name', function () {
    $service = new GeneralSettingsService();
    expect($service->getEmailFromAddress())->toBe('from@example.com');
    expect($service->getEmailFromName())->toBe('Test From Name');
});

test('can get more configs', function () {
    $service = new GeneralSettingsService();
    $configs = $service->getMoreConfigs();

    expect($configs)->toHaveKey('Currency');
    expect($configs)->toHaveKey('TimeZone');
    expect($configs['Currency'])->toBe('USD');
    expect($configs['TimeZone'])->toBe('UTC');
});

test('can get currency', function () {
    $service = new GeneralSettingsService();
    expect($service->getCurrency())->toBe('USD');
});

test('can get contact info', function () {
    $service = new GeneralSettingsService();
    $contact = $service->getContactInfo();

    expect($contact)->toHaveKey('email');
    expect($contact)->toHaveKey('phone');
    expect($contact['email'])->toBe('test@example.com');
    expect($contact['phone'])->toBe('+1234567890');
});

test('can get analytics config', function () {
    $service = new GeneralSettingsService();
    $analytics = $service->getAnalyticsConfig();

    expect($analytics)->toHaveKey('google_analytics_id');
    expect($analytics)->toHaveKey('posthog_html_snippet');
    expect($analytics['google_analytics_id'])->toBe('GA-123456');
    expect($analytics['posthog_html_snippet'])->toBeNull();
});

test('can get branding config', function () {
    $service = new GeneralSettingsService();
    $branding = $service->getBrandingConfig();

    expect($branding)->toHaveKey('site_name');
    expect($branding)->toHaveKey('site_description');
    expect($branding)->toHaveKey('site_logo');
    expect($branding)->toHaveKey('site_favicon');
    expect($branding)->toHaveKey('theme_color');
    expect($branding['site_name'])->toBe('Test Cafe');
    expect($branding['site_description'])->toBe('Test Description');
    expect($branding['site_logo'])->toBe('logo.png');
    expect($branding['theme_color'])->toBe('#000000');
});

test('has analytics detection works', function () {
    $service = new GeneralSettingsService();

    expect($service->hasAnalytics())->toBeTrue();
    expect($service->hasGoogleAnalytics())->toBeTrue();
    expect($service->hasPostHog())->toBeFalse();
});

test('has social networks detection works', function () {
    $service = new GeneralSettingsService();
    expect($service->hasSocialNetworks())->toBeTrue();
});

test('has email settings detection works', function () {
    $service = new GeneralSettingsService();
    expect($service->hasEmailSettings())->toBeTrue();
});

test('has contact info detection works', function () {
    $service = new GeneralSettingsService();
    expect($service->hasContactInfo())->toBeTrue();
});

test('cache works correctly', function () {
    $service = new GeneralSettingsService();

    // First call should hit database
    $firstCall = $service->getSiteName();

    // Update database directly
    GeneralSetting::first()->update(['site_name' => 'Updated Name']);

    // Second call should return cached value
    $secondCall = $service->getSiteName();
    expect($secondCall)->toBe($firstCall);

    // Clear cache and check again
    $service->clearCache();
    $thirdCall = $service->getSiteName();
    expect($thirdCall)->toBe('Updated Name');
});

test('handles missing settings gracefully', function () {
    // Delete all settings
    GeneralSetting::query()->delete();
    Cache::forget('general_settings');

    $service = new GeneralSettingsService();

    // Should return defaults or null values
    expect($service->getSiteName())->toBe(config('app.name'));
    expect($service->getSiteDescription())->toBeNull();
    expect($service->getCurrency())->toBe('USD');
    expect($service->hasAnalytics())->toBeFalse();
    expect($service->hasSocialNetworks())->toBeFalse();
});

test('can get all settings as array', function () {
    $service = new GeneralSettingsService();
    $allSettings = $service->getAllSettings();

    expect($allSettings)->toBeArray();
    expect($allSettings)->toHaveKey('site_name');
    expect($allSettings)->toHaveKey('site_description');
    expect($allSettings['site_name'])->toBe('Test Cafe');
});
