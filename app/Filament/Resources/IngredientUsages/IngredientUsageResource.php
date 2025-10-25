<?php

declare(strict_types=1);

namespace App\Filament\Resources\IngredientUsages;

use App\Filament\Resources\IngredientUsages\Pages\CreateIngredientUsage;
use App\Filament\Resources\IngredientUsages\Pages\EditIngredientUsage;
use App\Filament\Resources\IngredientUsages\Pages\ListIngredientUsages;
use App\Filament\Resources\IngredientUsages\Pages\ViewIngredientUsage;
use App\Filament\Resources\IngredientUsages\Schemas\IngredientUsageForm;
use App\Filament\Resources\IngredientUsages\Schemas\IngredientUsageInfolist;
use App\Filament\Resources\IngredientUsages\Tables\IngredientUsagesTable;
use App\Models\IngredientUsage;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

final class IngredientUsageResource extends Resource
{
    protected static ?string $model = IngredientUsage::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return IngredientUsageForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return IngredientUsageInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return IngredientUsagesTable::configure($table);
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
            'index' => ListIngredientUsages::route('/'),
            'create' => CreateIngredientUsage::route('/create'),
            'view' => ViewIngredientUsage::route('/{record}'),
            'edit' => EditIngredientUsage::route('/{record}/edit'),
        ];
    }
}
