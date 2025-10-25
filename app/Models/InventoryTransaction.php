<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class InventoryTransaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'ingredient_id',
        'transaction_type',
        'quantity_change',
        'previous_stock',
        'new_stock',
        'reason',
        'order_item_id',
    ];

    protected $casts = [
        'quantity_change' => 'decimal:3',
        'previous_stock' => 'decimal:3',
        'new_stock' => 'decimal:3',
    ];

    public function ingredient(): BelongsTo
    {
        return $this->belongsTo(Ingredient::class);
    }

    public function orderItem(): BelongsTo
    {
        return $this->belongsTo(OrderItem::class);
    }
}
