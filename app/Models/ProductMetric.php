<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class ProductMetric extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'metric_date',
        'orders_count',
        'total_revenue',
        'period_type',
    ];

    protected $casts = [
        'metric_date' => 'date',
        'orders_count' => 'integer',
        'total_revenue' => 'decimal:2',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
