<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class Payment extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'payment_method',
        'status',
        'amount',
        'transaction_id',
        'gateway',
        'gateway_response',
        'card_last_four',
        'processed_at',
        'notes',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'processed_at' => 'datetime',
        'gateway_response' => 'array',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function getFormattedAmountAttribute(): string
    {
        return '$'.number_format($this->amount, 2);
    }

    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'pending' => 'gray',
            'processing' => 'blue',
            'completed' => 'green',
            'failed' => 'red',
            'refunded' => 'orange',
            default => 'gray',
        };
    }

    public function getPaymentMethodLabelAttribute(): string
    {
        return match ($this->payment_method) {
            'cash' => 'Cash',
            'card' => 'Credit/Debit Card',
            'mobile' => 'Mobile Payment',
            'gift_card' => 'Gift Card',
            'online' => 'Online Payment',
            default => ucfirst($this->payment_method),
        };
    }

    public function isSuccessful(): bool
    {
        return $this->status === 'completed';
    }

    public function isPending(): bool
    {
        return in_array($this->status, ['pending', 'processing']);
    }

    public function isFailed(): bool
    {
        return $this->status === 'failed';
    }

    public function isRefunded(): bool
    {
        return $this->status === 'refunded';
    }

    public function canBeRefunded(): bool
    {
        return $this->isSuccessful() && ! $this->isRefunded();
    }

    public function markAsCompleted(?string $transactionId = null): void
    {
        $this->status = 'completed';
        $this->processed_at = now();

        if ($transactionId) {
            $this->transaction_id = $transactionId;
        }

        $this->save();
    }

    public function markAsFailed(?string $reason = null): void
    {
        $this->status = 'failed';

        if ($reason) {
            $this->notes = $reason;
        }

        $this->save();
    }

    public function refund(?string $reason = null): bool
    {
        if (! $this->canBeRefunded()) {
            return false;
        }

        $this->status = 'refunded';

        if ($reason) {
            $this->notes = $reason;
        }

        $this->save();

        return true;
    }

    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    public function scopeByPaymentMethod($query, string $paymentMethod)
    {
        return $query->where('payment_method', $paymentMethod);
    }

    public function scopeSuccessful($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopePending($query)
    {
        return $query->whereIn('status', ['pending', 'processing']);
    }

    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    public function scopeRefunded($query)
    {
        return $query->where('status', 'refunded');
    }

    public function scopeToday($query)
    {
        return $query->whereDate('processed_at', today());
    }

    public function scopeThisWeek($query)
    {
        return $query->whereBetween('processed_at', [now()->startOfWeek(), now()->endOfWeek()]);
    }

    public function scopeThisMonth($query)
    {
        return $query->whereMonth('processed_at', now()->month)
            ->whereYear('processed_at', now()->year);
    }
}
