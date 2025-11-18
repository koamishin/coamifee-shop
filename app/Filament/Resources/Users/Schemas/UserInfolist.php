<?php

declare(strict_types=1);

namespace App\Filament\Resources\Users\Schemas;

use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\Split;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Enums\FontWeight;
use Filament\Support\Enums\TextSize;

final class UserInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('User Information')
                    ->schema([
                        
                            TextEntry::make('name')
                                ->label('Full Name')
                                ->size(TextSize::Large)
                                ->weight(FontWeight::Bold)
                                ->icon('heroicon-m-user'),

                            IconEntry::make('email_verified_at')
                                ->label('Email Verified')
                                ->boolean()
                                ->trueIcon('heroicon-o-check-badge')
                                ->falseIcon('heroicon-o-x-circle')
                                ->trueColor('success')
                                ->falseColor('danger')
                                ->grow(false),


                        TextEntry::make('email')
                            ->label('Email Address')
                            ->icon('heroicon-m-envelope')
                            ->copyable()
                            ->copyMessage('Email copied!')
                            ->copyMessageDuration(1500),

                        TextEntry::make('email_verified_at')
                            ->label('Email Verified At')
                            ->dateTime('M d, Y H:i')
                            ->icon('heroicon-m-calendar')
                            ->placeholder('Not verified')
                            ->color(fn ($state) => $state ? 'success' : 'danger'),
                    ])
                    ->columns(2),

                Section::make('Roles & Permissions')
                    ->schema([
                        TextEntry::make('roles.name')
                            ->label('Assigned Roles')
                            ->badge()
                            ->color('primary')
                            ->separator(',')
                            ->icon('heroicon-m-shield-check')
                            ->placeholder('No roles assigned')
                            ->columnSpanFull(),

                        TextEntry::make('permissions.name')
                            ->label('Direct Permissions')
                            ->badge()
                            ->color('info')
                            ->separator(',')
                            ->icon('heroicon-m-key')
                            ->placeholder('No direct permissions')
                            ->columnSpanFull()
                            ->limit(10),
                            // ->limitedRemainingText(isSeparate: true),

                        TextEntry::make('all_permissions')
                            ->label('Total Permissions (via Roles)')
                            ->formatStateUsing(fn ($record) => $record?->getAllPermissions()->count() ?? 0)
                            ->icon('heroicon-m-check-circle')
                            ->color('success'),
                    ])
                    ->collapsible(),

                Section::make('Security & Account Status')
                    ->schema([
                        IconEntry::make('two_factor_confirmed_at')
                            ->label('Two-Factor Authentication')
                            ->boolean()
                            ->trueIcon('heroicon-o-shield-check')
                            ->falseIcon('heroicon-o-shield-exclamation')
                            ->trueColor('success')
                            ->falseColor('gray'),

                        TextEntry::make('two_factor_confirmed_at')
                            ->label('2FA Enabled At')
                            ->dateTime('M d, Y H:i')
                            ->icon('heroicon-m-calendar')
                            ->placeholder('Not enabled')
                            ->hidden(fn ($state) => ! $state),

                        TextEntry::make('created_at')
                            ->label('Account Created')
                            ->dateTime('M d, Y H:i')
                            ->icon('heroicon-m-calendar-days')
                            ->since()
                            ->dateTimeTooltip(),

                        TextEntry::make('updated_at')
                            ->label('Last Updated')
                            ->dateTime('M d, Y H:i')
                            ->icon('heroicon-m-clock')
                            ->since()
                            ->dateTimeTooltip(),
                    ])
                    ->columns(2)
                    ->collapsible(),
            ]);
    }
}
