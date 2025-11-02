<?php

declare(strict_types=1);

namespace App\Filament\Pages\Auth;

use Filament\Auth\Pages\Login as BaseLogin;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Schema;

final class Login extends BaseLogin
{
    public function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                $this->getEmailFormComponent(),
                $this->getPasswordFormComponent(),
                $this->getRememberFormComponent(),
            ])
            ->statePath('data');
    }

    protected function getEmailFormComponent(): Component
    {
        $component = parent::getEmailFormComponent();

        // Prefill email in demo mode
        if (config('app.env') === 'demo') {
            $component->default('test@example.com');
        }

        return $component;
    }

    protected function getPasswordFormComponent(): Component
    {
        $component = parent::getPasswordFormComponent();

        // Prefill password in demo mode
        if (config('app.env') === 'demo') {
            $component->default('password');
        }

        return $component;
    }

    protected function getRememberFormComponent(): Component
    {
        $component = parent::getRememberFormComponent();

        // Prefill password in demo mode
        if (config('app.env') === 'demo') {
            $component->default('password');
        }

        return $component;
    }
}
