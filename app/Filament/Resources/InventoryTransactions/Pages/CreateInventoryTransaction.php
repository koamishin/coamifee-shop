<?php

declare(strict_types=1);

namespace App\Filament\Resources\InventoryTransactions\Pages;

use App\Filament\Resources\InventoryTransactions\InventoryTransactionResource;
use Filament\Resources\Pages\CreateRecord;

final class CreateInventoryTransaction extends CreateRecord
{
    protected static string $resource = InventoryTransactionResource::class;
}
