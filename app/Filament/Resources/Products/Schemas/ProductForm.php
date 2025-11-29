<?php

declare(strict_types=1);

namespace App\Filament\Resources\Products\Schemas;

use App\Enums\BeverageVariant;
use App\Enums\UnitType;
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
                            ->reactive()
                            ->live()
                            ->helperText(
                                'Select the category this product belongs to',
                            )
                            ->afterStateUpdated(function ($state, callable $set) {
                                // Update category_has_variants when category changes
                                $categoryId = $state;
                                $hasVariants = false;

                                if ($categoryId) {
                                    $category = \App\Models\Category::find($categoryId);
                                    $hasVariants = $category ? $category->has_variants : false;
                                }

                                $set('category_has_variants', $hasVariants);

                                // Reset has_variants toggle if category doesn't support variants
                                if (! $hasVariants) {
                                    $set('has_variants', false);
                                }
                            })
                            ->debounce('500ms') // Add debounce to prevent excessive calls
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
                                    Toggle::make('has_variants')
                                        ->label('Products in this category have Hot & Cold variants')
                                        ->helperText('Enable to allow products in this category to have different prices for Hot and Cold versions')
                                        ->default(false)
                                        ->reactive()
                                        ->live(),
                                ])
                                    ->model(\App\Models\Category::class)
                                    ->after(function ($get, $set) {
                                        // After creating a new category, update the category_has_variants field
                                        $newCategoryId = $get('category_id');
                                        if ($newCategoryId) {
                                            $newCategory = \App\Models\Category::find($newCategoryId);
                                            if ($newCategory) {
                                                $set('category_has_variants', $newCategory->has_variants);
                                            }
                                        }
                                    }),
                            )
                            ->columnSpanFull(),

                        // Hidden field to track category's has_variants property
                        \Filament\Forms\Components\Hidden::make('category_has_variants')
                            ->default(function ($get) {
                                $categoryId = $get('category_id');
                                if ($categoryId) {
                                    $category = \App\Models\Category::find($categoryId);

                                    return $category ? $category->has_variants : false;
                                }

                                return false;
                            })
                            ->reactive()
                            ->live(),
                        TextInput::make('price')
                            ->label('Product Price')
                            ->prefix(self::getCurrencyPrefix())
                            ->suffix(self::getCurrencySuffix())
                            ->numeric()
                            ->required(fn (callable $get) => ! ($get('category_has_variants') === true && $get('has_variants') === true))
                            ->step(0.01)
                            ->helperText(fn (callable $get) => $get('category_has_variants') === true && $get('has_variants') === true
                                ? 'For products with variants, set prices for Hot and Cold variants below'
                                : 'Set the selling price for this product')
                            ->live(onBlur: true),
                        // ->hidden(fn (callable $get) => $get('category_has_variants') === true && $get('has_variants') === true),
                        // Variant toggle - only visible for categories with variants enabled
                        Toggle::make('has_variants')
                            ->label('This product has Hot & Cold variants')
                            ->helperText('Enable to set different prices for Hot and Cold versions')
                            ->default(fn ($record) => $record ? $record->hasVariants() : false)
                            ->reactive()
                            ->live()
                            ->dehydrated(false)
                            ->visible(fn (callable $get) => $get('category_has_variants') === true)
                            ->columnSpanFull(),
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
                        FileUpload::make('image_url')
                            ->label('Product Image')
                            ->image()
                            ->disk('r2')
                            ->directory('products')
                            ->visibility('public')
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

            // Product Variants Section - only visible when toggle is enabled
            Section::make('Product Variants (Hot & Cold)')
                ->description('Set prices for Hot and Cold versions of this product.')
                ->icon('heroicon-o-fire')
                ->visible(fn (callable $get) => $get('category_has_variants') === true && $get('has_variants') === true)
                ->schema([
                    Repeater::make('variants')
                        ->label('Hot & Cold Prices')
                        ->relationship('variants')
                        ->schema([
                            Grid::make(2)->schema([
                                Select::make('name')
                                    ->label('Variant Type')
                                    ->options(BeverageVariant::getOptions())
                                    ->required()
                                    ->disabled()
                                    ->dehydrated()
                                    ->helperText('Hot or Cold beverage'),

                                TextInput::make('price')
                                    ->label('Price')
                                    ->prefix(self::getCurrencyPrefix())
                                    ->suffix(self::getCurrencySuffix())
                                    ->numeric()
                                    ->required()
                                    ->step(0.01)
                                    ->helperText('Price for this variant')
                                    ->live(onBlur: true),
                            ]),
                        ])
                        ->columns(1)
                        ->itemLabel(function (array $state): string {
                            if (isset($state['name']) && $state['name']) {
                                $price = $state['price'] ?? 0;
                                $icon = $state['name'] === 'Hot' ? '🔥' : '❄️';

                                return "{$icon} {$state['name']} - ".self::getCurrencyPrefix().number_format($price, 2);
                            }

                            return 'New Variant';
                        })
                        ->defaultItems(2)
                        ->default([
                            ['name' => BeverageVariant::HOT->value, 'is_default' => true, 'is_active' => true, 'sort_order' => 0],
                            ['name' => BeverageVariant::COLD->value, 'is_default' => false, 'is_active' => true, 'sort_order' => 1],
                        ])
                        ->addable(false)
                        ->deletable(false)
                        ->reorderable(false)
                        ->collapsible()
                        ->helperText('Set the price for Hot and Cold versions of this beverage'),
                ])
                ->columns(1),

            Section::make('Recipe & Ingredients')
                ->description(
                    'Manage the ingredients that make up this product.',
                )
                ->icon('heroicon-o-beaker')
                ->schema([
                    Repeater::make('ingredients')
                        ->label('Product Recipe')
                        ->relationship('ingredients')
                        ->schema([
                            Grid::make(3)
                                ->schema([
                                    Select::make('ingredient_id')
                                        ->label('Ingredient')
                                        ->relationship('ingredient', 'name')
                                        ->searchable()
                                        ->preload()
                                        ->required()
                                        ->distinct()
                                        ->reactive()
                                        ->live()
                                        ->afterStateUpdated(function (callable $set) {
                                            // Reset quantity when ingredient changes
                                            $set('quantity_required', null);
                                        })
                                        ->helperText('Select ingredient from inventory'),

                                    TextInput::make('quantity_required')
                                        ->label('Quantity')
                                        ->numeric()
                                        ->required()
                                        ->minValue(0.001)
                                        ->step(0.001)
                                        ->placeholder(function (callable $get) {
                                            $ingredientId = $get('ingredient_id');
                                            if (! $ingredientId) {
                                                return 'e.g., 250';
                                            }

                                            $ingredient = \App\Models\Ingredient::find($ingredientId);
                                            if (! $ingredient) {
                                                return 'e.g., 250';
                                            }

                                            return match ($ingredient->unit_type) {
                                                UnitType::MILLILITERS => 'e.g., 250 (ml)',
                                                UnitType::GRAMS => 'e.g., 250 (g)',
                                                UnitType::PIECES => 'e.g., 2',
                                                default => 'e.g., 250'
                                            };
                                        })
                                        ->suffix(function (callable $get) {
                                            $ingredientId = $get('ingredient_id');
                                            if (! $ingredientId) {
                                                return null;
                                            }

                                            $ingredient = \App\Models\Ingredient::find($ingredientId);

                                            return $ingredient->unit_type->getLabel();
                                        })
                                        ->helperText(function (callable $get) {
                                            $ingredientId = $get('ingredient_id');
                                            if (! $ingredientId) {
                                                return 'Amount needed per product';
                                            }

                                            $ingredient = \App\Models\Ingredient::find($ingredientId);
                                            if (! $ingredient) {
                                                return 'Amount needed per product';
                                            }

                                            return match ($ingredient->unit_type) {
                                                UnitType::MILLILITERS => 'Enter quantity in ml (e.g., 250 for 250ml)',
                                                UnitType::GRAMS => 'Enter quantity in grams (e.g., 250 for 250g)',
                                                UnitType::PIECES => 'Enter number of pieces (e.g., 2)',
                                                default => 'Amount needed per product'
                                            };
                                        })
                                        ->live(onBlur: true),

                                    Select::make('stock_status')
                                        ->label('Stock Status')
                                        ->placeholder('Select ingredient first')
                                        ->formatStateUsing(function (callable $get): ?string {
                                            $quantity = (float) ($get('quantity_required') ?? 0);
                                            $ingredientId = $get('ingredient_id');

                                            if (! $quantity || ! $ingredientId) {
                                                return null;
                                            }

                                            $ingredient = \App\Models\Ingredient::with('inventory')->find($ingredientId);
                                            if (! $ingredient || ! $ingredient->inventory) {
                                                return 'no_data';
                                            }

                                            $currentStock = $ingredient->inventory instanceof \App\Models\IngredientInventory
                                                ? (float) $ingredient->inventory->getAttribute('current_stock')
                                                : 0.0;
                                            $productsPossible = floor($currentStock / $quantity);

                                            if ($productsPossible <= 10) {
                                                return 'critical';
                                            }
                                            if ($productsPossible <= 30) {
                                                return 'low';
                                            }
                                            if ($productsPossible <= 50) {
                                                return 'medium';
                                            }

                                            return 'good';
                                        })
                                        ->options([
                                            'critical' => '🔴 Critical Stock',
                                            'low' => '🟡 Low Stock',
                                            'medium' => '🟠 Medium Stock',
                                            'good' => '🟢 Good Stock',
                                            'no_data' => '⚪ No Data',
                                        ])
                                        ->disabled()
                                        ->dehydrated(false)
                                        ->live(),
                                ])
                                ->columnSpanFull(),
                        ])
                        ->columns(1)
                        ->itemLabel(function (array $state): string {
                            if (isset($state['ingredient_id']) && $state['ingredient_id']) {
                                $ingredient = \App\Models\Ingredient::find($state['ingredient_id']);
                                if ($ingredient) {
                                    $quantity = (float) ($state['quantity_required'] ?? 0);

                                    // Display with the ingredient's base unit
                                    $displayUnit = $ingredient->unit_type->getLabel();

                                    return "{$ingredient->name} ({$quantity} {$displayUnit})";
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
                ->columnSpanFull()
                ->collapsed(fn ($context): bool => $context === 'edit'),
        ]);
    }
}
