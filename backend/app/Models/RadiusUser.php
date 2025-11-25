<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RadiusUser extends Model
{
    protected $fillable = [
        'subscription_id',
        'username',
        'password',
        'rate_limit',
        'framed_ip',
        'vlan_id',
        'status',
    ];

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class);
    }

    public function sessions(): HasMany
    {
        return $this->hasMany(RadiusSession::class);
    }

    public function activeSessions(): HasMany
    {
        return $this->sessions()->whereNull('stop_time');
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function hasActiveSession(): bool
    {
        return $this->activeSessions()->exists();
    }
}
