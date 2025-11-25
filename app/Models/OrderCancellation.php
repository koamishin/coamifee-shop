<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class OrderCancellation extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'cancelled_by',
        'cancellation_amount',
        'reason',
        'cancelled_at',
    ];

    protected $casts = [
        'cancellation_amount' => 'decimal:2',
        'cancelled_at' => 'datetime',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cancelled_by');
    }
}
