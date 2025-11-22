<?php

declare(strict_types=1);

namespace App\Filament\Resources\Users\Schemas;

use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

final class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('User Information')
                    ->description('Basic user information and credentials')
                    ->schema([
                        TextInput::make('name')
                            ->label('Full Name')
                            ->required()
                            ->maxLength(255)
                            ->autofocus(),

                        TextInput::make('email')
                            ->label('Email Address')
                            ->email()
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255),

                        TextInput::make('password')
                            ->password()
                            ->dehydrateStateUsing(fn ($state) => filled($state) ? Hash::make($state) : null)
                            ->dehydrated(fn ($state) => filled($state))
                            ->required(fn (string $context): bool => $context === 'create')
                            ->rule(Password::defaults())
                            ->revealable()
                            ->autocomplete('new-password')
                            ->helperText('Leave blank to keep current password when editing'),

                        TextInput::make('password_confirmation')
                            ->password()
                            ->same('password')
                            ->requiredWith('password')
                            ->dehydrated(false)
                            ->revealable()
                            ->autocomplete('new-password'),
                    ])
                    ->columns(2),

                Section::make('Roles & Permissions')
                    ->description('Assign roles and permissions to control user access')
                    ->schema([
                        Select::make('roles')
                            ->label('Roles')
                            ->multiple()
                            ->relationship('roles', 'name')
                            ->preload()
                            ->searchable()
                            ->options(Role::all()->pluck('name', 'id'))
                            ->helperText('Assign one or more roles to this user')
                            ->columnSpanFull(),

                        CheckboxList::make('permissions')
                            ->label('Direct Permissions')
                            ->relationship('permissions', 'name')
                            ->options(Permission::all()->pluck('name', 'id'))
                            ->searchable()
                            ->bulkToggleable()
                            ->columns(3)
                            ->gridDirection('row')
                            ->helperText('Grant specific permissions directly to this user (in addition to role permissions)')
                            ->columnSpanFull(),
                    ])
                    ->collapsible(),

                Section::make('Account Status')
                    ->description('Manage account verification and security settings')
                    ->schema([
                        Toggle::make('email_verified')
                            ->label('Email Verified')
                            ->onIcon('heroicon-m-check-circle')
                            ->offIcon('heroicon-m-x-circle')
                            ->onColor('success')
                            ->offColor('danger')
                            ->default(false)
                            ->dehydrated()
                            ->afterStateHydrated(function (Toggle $component, $record) {
                                if ($record) {
                                    $component->state($record->email_verified_at !== null);
                                }
                            })
                            ->afterStateUpdated(function ($state, $set) {
                                if ($state) {
                                    $set('email_verified_at', now());
                                } else {
                                    $set('email_verified_at', null);
                                }
                            })
                            ->helperText('Mark the user\'s email as verified'),

                        DateTimePicker::make('email_verified_at')
                            ->label('Email Verified At')
                            ->displayFormat('M d, Y H:i')
                            ->seconds(false)
                            ->hidden(fn ($get) => ! $get('email_verified'))
                            ->dehydrated(),

                        Toggle::make('two_factor_enabled')
                            ->label('Two-Factor Authentication')
                            ->onIcon('heroicon-m-shield-check')
                            ->offIcon('heroicon-m-shield-exclamation')
                            ->onColor('success')
                            ->offColor('gray')
                            ->disabled()
                            ->dehydrated(false)
                            ->afterStateHydrated(function (Toggle $component, $record) {
                                if ($record) {
                                    $component->state($record->two_factor_confirmed_at !== null);
                                }
                            })
                            ->helperText('Two-factor authentication status (managed by user)'),
                    ])
                    ->columns(2)
                    ->collapsible(),
            ]);
    }
}
