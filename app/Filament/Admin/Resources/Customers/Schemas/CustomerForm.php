<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Customers\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

final class CustomerForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('first_name')
                    ->required(),
                TextInput::make('last_name')
                    ->required(),
                TextInput::make('email')
                    ->label('Email address')
                    ->email()
                    ->required(),
                TextInput::make('phone')
                    ->tel(),
                DatePicker::make('birth_date'),
                Textarea::make('address')
                    ->columnSpanFull(),
                TextInput::make('city'),
                TextInput::make('postal_code'),
                Toggle::make('is_loyalty_member')
                    ->required(),
                TextInput::make('loyalty_points')
                    ->required()
                    ->numeric()
                    ->default(0),
                Textarea::make('preferences')
                    ->columnSpanFull(),
                Textarea::make('allergies')
                    ->columnSpanFull(),
                Toggle::make('is_active')
                    ->required(),
                DateTimePicker::make('last_visit_at'),
            ]);
    }
}
