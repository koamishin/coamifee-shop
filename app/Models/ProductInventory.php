<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class ProductInventory extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'quantity',
        'location',
        'min_stock_level',
        'max_stock_level',
    ];

    protected $casts = [
        'product_id' => 'integer',
        'quantity' => 'integer',
        'min_stock_level' => 'integer',
        'max_stock_level' => 'integer',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
