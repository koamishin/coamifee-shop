<?php

declare(strict_types=1);

namespace App\Filament\Resources\ProductIngredients;

use App\Filament\Resources\ProductIngredients\Pages\CreateProductIngredient;
use App\Filament\Resources\ProductIngredients\Pages\EditProductIngredient;
use App\Filament\Resources\ProductIngredients\Pages\ListProductIngredients;
use App\Filament\Resources\ProductIngredients\Pages\ViewProductIngredient;
use App\Filament\Resources\ProductIngredients\Schemas\ProductIngredientForm;
use App\Filament\Resources\ProductIngredients\Schemas\ProductIngredientInfolist;
use App\Filament\Resources\ProductIngredients\Tables\ProductIngredientsTable;
use App\Models\ProductIngredient;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

final class ProductIngredientResource extends Resource
{
    protected static ?string $model = ProductIngredient::class;

    protected static UnitEnum|string|null $navigationGroup = 'Product Management';

    protected static ?string $navigationLabel = 'Product Ingredients';

    protected static ?string $modelLabel = 'Product Ingredient';

    protected static ?string $pluralModelLabel = 'Product Ingredients';

    protected static ?int $navigationSort = 2;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedLink;

    public static function form(Schema $schema): Schema
    {
        return ProductIngredientForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return ProductIngredientInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ProductIngredientsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListProductIngredients::route('/'),
            'create' => CreateProductIngredient::route('/create'),
            'view' => ViewProductIngredient::route('/{record}'),
            'edit' => EditProductIngredient::route('/{record}/edit'),
        ];
    }
}
