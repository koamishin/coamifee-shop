<?php

declare(strict_types=1);

namespace App\DataTransferObjects;

use App\Models\Product;

final class TopProductDto
{
    public function __construct(
        public ?Product $product,
        public float $quantity_sold,
        public float $revenue
    ) {}
}
