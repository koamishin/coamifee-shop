<?php

declare(strict_types=1);

namespace App\Filament\Resources\Customers\Pages;

use App\Filament\Resources\Customers\CustomerResource;
use Filament\Resources\Pages\CreateRecord;

final class CreateCustomer extends CreateRecord
{
    protected static string $resource = CustomerResource::class;
}
