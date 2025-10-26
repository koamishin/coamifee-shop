<?php

declare(strict_types=1);

namespace App\Filament\Resources\Products\Schemas;

use App\Filament\Concerns\CurrencyAware;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

final class ProductForm
{
    use CurrencyAware;

    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Product Information')
                ->description('Manage the basic details of your product.')
                ->schema([
                    Grid::make(2)->schema([
                        TextInput::make('name')
                            ->label('Product Name')
                            ->placeholder('Enter product name')
                            ->required()
                            ->maxLength(255)
                            ->live(onBlur: true)
                            ->helperText(
                                'The name of your product as it will appear to customers',
                            ),

                        TextInput::make('sku')
                            ->label('SKU')
                            ->placeholder('e.g., PROD-001')
                            ->helperText(
                                'Stock Keeping Unit for inventory tracking',
                            )
                            ->maxLength(50)
                            ->unique(ignoreRecord: true),

                        Select::make('category_id')
                            ->label('Category')
                            ->relationship('category', 'name')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->helperText(
                                'Select the category this product belongs to',
                            )
                            ->createOptionForm(
                                fn ($form) => $form->schema([
                                    TextInput::make('name')
                                        ->label('Category Name')
                                        ->required(),
                                    TextInput::make('description')->label(
                                        'Description',
                                    ),
                                    Toggle::make('is_active')
                                        ->label('Active')
                                        ->default(true),
                                ]),
                            )
                            ->columnSpanFull(),
                    ]),
                ])
                ->columns(2),

            Section::make('Pricing & Inventory')
                ->description('Set pricing and inventory details.')
                ->schema([
                    Grid::make(2)->schema([
                        TextInput::make('price')
                            ->label('Price')
                            ->prefix(self::getCurrencyPrefix())
                            ->suffix(self::getCurrencySuffix())
                            ->numeric()
                            ->required()
                            ->step(0.01)
                            ->helperText('Current selling price')
                            ->live(onBlur: true),

                        TextInput::make('cost_price')
                            ->label('Cost Price')
                            ->prefix(self::getCurrencyPrefix())
                            ->suffix(self::getCurrencySuffix())
                            ->numeric()
                            ->step(0.01)
                            ->helperText('Your cost for this product')
                            ->default(0),

                        TextInput::make('stock_quantity')
                            ->label('Stock Quantity')
                            ->numeric()
                            ->default(0)
                            ->minValue(0)
                            ->helperText('Available stock quantity')
                            ->live(onBlur: true),

                        TextInput::make('min_stock_level')
                            ->label('Minimum Stock')
                            ->numeric()
                            ->default(5)
                            ->minValue(0)
                            ->helperText(
                                'Alert when stock falls below this level',
                            ),
                    ]),
                ])
                ->columns(1),

            Section::make('Product Details')
                ->description(
                    'Additional product information and presentation.',
                )
                ->schema([
                    Textarea::make('description')
                        ->label('Description')
                        ->placeholder('Enter product description...')
                        ->rows(3)
                        ->maxLength(1000)
                        ->helperText('Detailed description for customers')
                        ->columnSpanFull(),

                    Grid::make(2)->schema([
                        TextInput::make('preparation_time')
                            ->label('Preparation Time')
                            ->numeric()
                            ->default(5)
                            ->minValue(1)
                            ->suffix(' minutes')
                            ->helperText('Average preparation time in minutes'),

                        FileUpload::make('image_url')
                            ->label('Product Image')
                            ->image()
                            ->directory('products')
                            ->maxSize(2048)
                            ->acceptedFileTypes([
                                'image/jpeg',
                                'image/png',
                                'image/webp',
                            ])
                            ->helperText(
                                'Upload a product image (JPEG, PNG, WebP)',
                            )
                            ->columnSpanFull(),
                    ]),
                ])
                ->columns(2),

            Section::make('Status & Settings')
                ->description('Configure product availability and settings.')
                ->schema([
                    Grid::make(2)->schema([
                        Toggle::make('is_active')
                            ->label('Available')
                            ->default(true)
                            ->helperText('Enable this product for sale')
                            ->columnSpan(1),

                        TextInput::make('sort_order')
                            ->label('Sort Order')
                            ->numeric()
                            ->default(0)
                            ->minValue(0)
                            ->helperText(
                                'Display order (lower numbers appear first)',
                            )
                            ->columnSpan(1),
                    ]),
                ])
                ->columns(2),

            Section::make('Recipe & Ingredients')
                ->description(
                    'Manage the ingredients that make up this product.',
                )
                ->schema([
                    Repeater::make('ingredients')
                        ->label('Ingredients')
                        ->relationship('ingredients')
                        ->schema([
                            Grid::make(2)
                                ->schema([
                                    Select::make('ingredient_id')
                                        ->label('Ingredient')
                                        ->relationship('ingredient', 'name')
                                        ->searchable()
                                        ->preload()
                                        ->required()
                                        ->distinct(),

                                    TextInput::make('quantity_required')
                                        ->label('Quantity')
                                        ->numeric()
                                        ->required()
                                        ->minValue(0.01)
                                        ->step(0.01)
                                        ->suffix('g/ml')
                                        ->helperText(
                                            'Amount needed per product',
                                        )
                                        ->live(onBlur: true),
                                ])
                                ->columnSpanFull(),

                            TextInput::make('cost_per_unit')
                                ->label('Cost per Unit')
                                ->prefix(self::getCurrencyPrefix())
                                ->suffix('g/ml')
                                ->numeric()
                                ->step(0.01)
                                ->helperText('Cost of this ingredient per unit')
                                ->default(0)
                                ->columnSpanFull(),
                        ])
                        ->columns(3)
                        ->itemLabel(function (array $state): string {
                            if (
                                isset($state['ingredient_id']) &&
                                $state['ingredient_id']
                            ) {
                                return 'Ingredient Added';
                            }

                            return 'New Ingredient';
                        })
                        ->addActionLabel('Add Ingredient')
                        ->reorderableWithButtons()
                        ->collapsible()
                        ->defaultItems(0)
                        ->helperText(
                            'Define the ingredients that make up this product',
                        ),
                ])
                ->columns(1)
                ->collapsed(fn ($context): bool => $context === 'edit'),
        ]);
    }
}
