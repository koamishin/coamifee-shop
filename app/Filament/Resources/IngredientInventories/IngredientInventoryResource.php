<?php

declare(strict_types=1);

namespace App\Filament\Resources\IngredientInventories;

use App\Filament\Resources\IngredientInventories\Pages\CreateIngredientInventory;
use App\Filament\Resources\IngredientInventories\Pages\EditIngredientInventory;
use App\Filament\Resources\IngredientInventories\Pages\ListIngredientInventories;
use App\Filament\Resources\IngredientInventories\Pages\ViewIngredientInventory;
use App\Filament\Resources\IngredientInventories\Schemas\IngredientInventoryForm;
use App\Filament\Resources\IngredientInventories\Schemas\IngredientInventoryInfolist;
use App\Filament\Resources\IngredientInventories\Tables\IngredientInventoriesTable;
use App\Models\IngredientInventory;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

final class IngredientInventoryResource extends Resource
{
    protected static ?string $model = IngredientInventory::class;

    protected static UnitEnum|string|null $navigationGroup = 'Inventory Management';

    protected static ?string $navigationLabel = 'Ingredient Inventory';

    protected static ?string $modelLabel = 'Ingredient Inventory';

    protected static ?string $pluralModelLabel = 'Ingredient Inventory';

    protected static ?int $navigationSort = 1;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArchiveBox;

    public static function form(Schema $schema): Schema
    {
        return IngredientInventoryForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return IngredientInventoryInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return IngredientInventoriesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }





    public static function getNavigationBadgeColor(): ?string
    {
        return 'primary';
    }

    public static function getPages(): array
    {
        return [
            'index' => ListIngredientInventories::route('/'),
            'create' => CreateIngredientInventory::route('/create'),
            'view' => ViewIngredientInventory::route('/{record}'),
            'edit' => EditIngredientInventory::route('/{record}/edit'),
        ];
    }
}
