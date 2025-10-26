<?php

declare(strict_types=1);

namespace App\Filament\Resources\Categories\Schemas;

use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

final class CategoryForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')
                ->label('Category Name')
                ->placeholder('Enter category name')
                ->required()
                ->maxLength(255)
                ->live(onBlur: true)
                ->afterStateUpdated(
                    fn ($state, callable $set) => $set(
                        'slug',
                        str()->slug($state),
                    ),
                ),

            Textarea::make('description')
                ->label('Category Description')
                ->placeholder('Enter category description...')
                ->rows(3)
                ->maxLength(1000)
                ->columnSpanFull(),

            Toggle::make('is_active')
                ->label('Active')
                ->helperText('Enable this category to be visible in the store')
                ->default(true),

            TextInput::make('sort_order')
                ->label('Sort Order')
                ->helperText('Lower numbers appear first')
                ->numeric()
                ->default(0)
                ->minValue(0),
        ]);
    }
}
