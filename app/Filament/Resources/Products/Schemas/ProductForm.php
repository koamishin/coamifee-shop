<?php

declare(strict_types=1);

namespace App\Filament\Resources\Products\Schemas;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

final class ProductForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required(),
                Textarea::make('description')
                    ->columnSpanFull(),
                TextInput::make('price')
                    ->required()
                    ->numeric()
                    ->prefix('$'),
                Select::make('category_id')
                    ->relationship('category', 'name')
                    ->required(),
                FileUpload::make('image_url')
                    ->image(),
                Toggle::make('is_active')
                    ->required(),
                TextInput::make('sku')
                    ->label('SKU'),
                TextInput::make('preparation_time')
                    ->numeric(),
            ]);
    }
}
