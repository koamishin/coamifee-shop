<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class Customer extends Model
{
    use HasFactory;

    protected $fillable = [
        'first_name',
        'last_name',
        'email',
        'phone',
        'birth_date',
        'address',
        'city',
        'postal_code',
        'is_loyalty_member',
        'loyalty_points',
        'preferences',
        'allergies',
        'is_active',
        'last_visit_at',
    ];

    protected $casts = [
        'birth_date' => 'date',
        'is_loyalty_member' => 'boolean',
        'loyalty_points' => 'integer',
        'preferences' => 'array',
        'allergies' => 'array',
        'is_active' => 'boolean',
        'last_visit_at' => 'datetime',
    ];

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function getFullNameAttribute(): string
    {
        return "{$this->first_name} {$this->last_name}";
    }

    public function getFormattedPhoneAttribute(): ?string
    {
        if (! $this->phone) {
            return null;
        }

        return preg_replace('/(\d{3})(\d{3})(\d{4})/', '($1) $2-$3', $this->phone);
    }

    public function getTotalSpentAttribute(): float
    {
        return $this->orders->sum('total_amount');
    }

    public function getOrderCountAttribute(): int
    {
        return $this->orders->count();
    }

    public function getAverageOrderValueAttribute(): float
    {
        $orderCount = $this->order_count;

        if ($orderCount === 0) {
            return 0;
        }

        return $this->total_spent / $orderCount;
    }

    public function getAgeAttribute(): ?int
    {
        if (! $this->birth_date) {
            return null;
        }

        return $this->birth_date->age;
    }

    public function isVip(): bool
    {
        return $this->loyalty_points >= 1000 || $this->total_spent >= 500;
    }

    public function addLoyaltyPoints(int $points): void
    {
        $this->loyalty_points += $points;
        $this->save();
    }

    public function redeemLoyaltyPoints(int $points): bool
    {
        if ($this->loyalty_points < $points) {
            return false;
        }

        $this->loyalty_points -= $points;
        $this->save();

        return true;
    }

    public function updateLastVisit(): void
    {
        $this->last_visit_at = now();
        $this->save();
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeLoyaltyMembers($query)
    {
        return $query->where('is_loyalty_member', true);
    }

    public function scopeVips($query)
    {
        return $query->where('loyalty_points', '>=', 1000)
            ->orWhereHas('orders', function ($q) {
                $q->selectRaw('SUM(total_amount)')->groupBy('customer_id')->havingRaw('SUM(total_amount) >= 500');
            });
    }

    public function scopeByCity($query, string $city)
    {
        return $query->where('city', $city);
    }
}
