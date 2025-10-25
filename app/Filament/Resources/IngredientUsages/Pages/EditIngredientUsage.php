<?php

declare(strict_types=1);

namespace App\Filament\Resources\IngredientUsages\Pages;

use App\Filament\Resources\IngredientUsages\IngredientUsageResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

final class EditIngredientUsage extends EditRecord
{
    protected static string $resource = IngredientUsageResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
