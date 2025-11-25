<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Casts\Attribute;

class DeviceRouter extends Model
{
    protected $table = 'devices_routers';

    protected $fillable = [
        'name',
        'ip_address',
        'type',
        'api_port',
        'api_user',
        'api_password',
        'radius_secret',
        'location',
        'status',
        'notes',
    ];

    protected $hidden = [
        'api_password',
        'radius_secret',
    ];

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class, 'router_id');
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    protected function apiPassword(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => decrypt($value),
            set: fn ($value) => encrypt($value),
        );
    }

    protected function radiusSecret(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => $value ? decrypt($value) : null,
            set: fn ($value) => $value ? encrypt($value) : null,
        );
    }

    public function getConnectionConfig(): array
    {
        return [
            'ip_address' => $this->ip_address,
            'api_port' => $this->api_port,
            'api_user' => $this->api_user,
            'api_password' => $this->api_password,
            'type' => $this->type,
        ];
    }
}
