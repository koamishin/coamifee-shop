<?php

declare(strict_types=1);

use App\Enums\Currency;
use Joaopaulolndev\FilamentGeneralSettings\Enums\TypeFieldEnum;

return [
    'show_application_tab' => true,
    'show_logo_and_favicon' => true,
    'show_analytics_tab' => true,
    'show_seo_tab' => true,
    'show_email_tab' => true,
    'show_social_networks_tab' => true,
    'expiration_cache_config_time' => 60,
    'show_custom_tabs' => true,
    'custom_tabs' => [
        'more_configs' => [
            'label' => 'Shop Configs',
            'icon' => 'heroicon-o-plus-circle',
            'columns' => 1,
            'fields' => [
                'Currency' => [
                    'type' => TypeFieldEnum::Select->value,
                    'label' => 'Currency',
                    'placeholder' => 'Select Currency',
                    'required' => true,
                    'rules' => 'required|string|max:255',
                    'options' => Currency::getSelectOptions(),
                ],
            ],
        ],
    ],
];
