<?php

declare(strict_types=1);

namespace App\Filament\Resources\ProductMetrics;

use App\Filament\Resources\ProductMetrics\Pages\CreateProductMetric;
use App\Filament\Resources\ProductMetrics\Pages\EditProductMetric;
use App\Filament\Resources\ProductMetrics\Pages\ListProductMetrics;
use App\Filament\Resources\ProductMetrics\Pages\ViewProductMetric;
use App\Filament\Resources\ProductMetrics\Schemas\ProductMetricForm;
use App\Filament\Resources\ProductMetrics\Schemas\ProductMetricInfolist;
use App\Filament\Resources\ProductMetrics\Tables\ProductMetricsTable;
use App\Models\ProductMetric;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

final class ProductMetricResource extends Resource
{
    protected static ?string $model = ProductMetric::class;

    protected static UnitEnum|string|null $navigationGroup = 'Product Management';

    protected static ?string $navigationLabel = 'Product Metrics';

    protected static ?string $modelLabel = 'Product Metric';

    protected static ?string $pluralModelLabel = 'Product Metrics';

    protected static ?int $navigationSort = 3;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChartBar;

    public static function form(Schema $schema): Schema
    {
        return ProductMetricForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return ProductMetricInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ProductMetricsTable::configure($table);
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
            'index' => ListProductMetrics::route('/'),
            'create' => CreateProductMetric::route('/create'),
            'view' => ViewProductMetric::route('/{record}'),
            'edit' => EditProductMetric::route('/{record}/edit'),
        ];
    }
}
