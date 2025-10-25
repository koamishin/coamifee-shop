<?php

declare(strict_types=1);

namespace App\Filament\Resources\ProductIngredients\Pages;

use App\Filament\Resources\ProductIngredients\ProductIngredientResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

final class ListProductIngredients extends ListRecords
{
    protected static string $resource = ProductIngredientResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
