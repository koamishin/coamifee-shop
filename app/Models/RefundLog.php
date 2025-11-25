<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class RefundLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'refunded_by',
        'refund_amount',
        'refund_type',
        'payment_method',
        'refund_date',
    ];

    protected $casts = [
        'refund_amount' => 'decimal:2',
        'refund_date' => 'datetime',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'refunded_by');
    }
}
