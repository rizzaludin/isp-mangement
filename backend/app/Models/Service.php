<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Service extends Model
{
    protected $fillable = [
        'name',
        'code',
        'speed_up',
        'speed_down',
        'price',
        'billing_cycle',
        'description',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'speed_up' => 'integer',
            'speed_down' => 'integer',
            'price' => 'decimal:2',
        ];
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function getRateLimitAttribute(): string
    {
        return "{$this->speed_up}M/{$this->speed_down}M";
    }

    public function getFormattedPriceAttribute(): string
    {
        return 'Rp ' . number_format($this->price, 0, ',', '.');
    }
}
