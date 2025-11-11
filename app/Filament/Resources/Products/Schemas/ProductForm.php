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
                            ->placeholder('Auto-generated on save')
                            ->helperText(
                                'Automatically generated from product name',
                            )
                            ->maxLength(50)
                            ->disabled()
                            ->dehydrated(false),

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
                ->columns(1),

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
                            ->disk('public')
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
                    Grid::make(1)->schema([
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
                    'Manage the ingredients that make up this product with real-time cost analysis.',
                )
                ->icon('heroicon-o-beaker')
                ->schema([
                    Repeater::make('ingredients')
                        ->label('Product Recipe')
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
                                        ->distinct()
                                        ->reactive()
                                        ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                            // Auto-update quantity when ingredient changes
                                            $set('quantity_required', null);
                                        })
                                        ->helperText('Select ingredient from inventory'),

                                    TextInput::make('quantity_required')
                                        ->label('Quantity')
                                        ->numeric()
                                        ->required()
                                        ->minValue(0.001)
                                        ->step(0.001)
                                        ->placeholder('e.g., 2.5')
                                        ->suffix(function (callable $get) {
                                            $ingredientId = $get('ingredient_id');
                                            if (! $ingredientId) {
                                                return null;
                                            }

                                            $ingredient = \App\Models\Ingredient::find($ingredientId);

                                            return $ingredient->unit_type->getLabel();
                                        })
                                        ->helperText('Amount needed per product')
                                        ->live(onBlur: true),
                                ])
                                ->columnSpanFull(),

                            Section::make('Ingredient Analysis')
                                ->description('Cost and inventory information for this ingredient')
                                ->schema([
                                    Grid::make(2)
                                        ->schema([
                                            TextInput::make('cost_display')
                                                ->label('Cost per Product')
                                                ->formatStateUsing(function ($state, callable $get): string {
                                                    $quantity = (float) ($get('quantity_required') ?? 0);
                                                    $ingredientId = $get('ingredient_id');

                                                    if (! $quantity || ! $ingredientId) {
                                                        return 'Set quantity to calculate';
                                                    }

                                                    $ingredient = \App\Models\Ingredient::find($ingredientId);
                                                    if (! $ingredient) {
                                                        return 'Ingredient not found';
                                                    }

                                                    $unitCost = $ingredient->unit_cost ?? 0;
                                                    $totalCost = $quantity * $unitCost;
                                                    $unitLabel = $ingredient->unit_type->getLabel();

                                                    return self::getCurrencyPrefix().number_format($totalCost, 2)." ({$quantity} {$unitLabel} × ".number_format($unitCost, 2).')';
                                                })
                                                ->disabled()
                                                ->dehydrated(false),

                                            TextInput::make('stock_display')
                                                ->label('Stock Status')
                                                ->formatStateUsing(function ($state, callable $get): string {
                                                    $quantity = (float) ($get('quantity_required') ?? 0);
                                                    $ingredientId = $get('ingredient_id');

                                                    if (! $quantity || ! $ingredientId) {
                                                        return 'Unknown';
                                                    }

                                                    $ingredient = \App\Models\Ingredient::with('inventory')->find($ingredientId);
                                                    if (! $ingredient || ! $ingredient->inventory) {
                                                        return 'No inventory data';
                                                    }

                                                    $currentStock = $ingredient->inventory instanceof \App\Models\IngredientInventory
                                                        ? (float) $ingredient->inventory->getAttribute('current_stock')
                                                        : 0.0;
                                                    $productsPossible = floor($currentStock / $quantity);

                                                    $status = $productsPossible <= 10 ? 'Low Stock' :
                                                             ($productsPossible <= 50 ? 'Limited' : 'Good Stock');

                                                    return "{$productsPossible} possible ({$status})";
                                                })
                                                ->disabled()
                                                ->dehydrated(false),
                                        ]),
                                ])
                                ->collapsible()
                                ->collapsed(),
                        ])
                        ->columns(1)
                        ->itemLabel(function (array $state): string {
                            if (isset($state['ingredient_id']) && $state['ingredient_id']) {
                                $ingredient = \App\Models\Ingredient::find($state['ingredient_id']);
                                if ($ingredient) {
                                    $quantity = $state['quantity_required'] ?? 0;
                                    $unit = $ingredient->unit_type->getLabel();

                                    return "{$ingredient->name} ({$quantity} {$unit})";
                                }
                            }

                            return 'New Ingredient';
                        })
                        ->addActionLabel('Add Ingredient')
                        ->collapsible()
                        ->defaultItems(0)
                        ->helperText('Add all ingredients required to make this product')
                        ->collapsed(fn ($context): bool => $context === 'edit'),
                ])
                ->columns(1)
                ->collapsed(fn ($context): bool => $context === 'edit'),
        ]);
    }
}
