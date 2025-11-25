<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Casts\Attribute;

class DeviceOlt extends Model
{
    protected $table = 'devices_olt';

    protected $fillable = [
        'name',
        'ip_address',
        'vendor',
        'mgmt_type',
        'mgmt_port',
        'mgmt_user',
        'mgmt_password',
        'snmp_community',
        'location',
        'status',
        'notes',
    ];

    protected $hidden = [
        'mgmt_password',
        'snmp_community',
    ];

    public function onus(): HasMany
    {
        return $this->hasMany(DeviceOnu::class, 'olt_id');
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class, 'olt_id');
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    protected function mgmtPassword(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => decrypt($value),
            set: fn ($value) => encrypt($value),
        );
    }

    protected function snmpCommunity(): Attribute
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
            'vendor' => $this->vendor,
            'mgmt_type' => $this->mgmt_type,
            'mgmt_port' => $this->mgmt_port,
            'mgmt_user' => $this->mgmt_user,
            'mgmt_password' => $this->mgmt_password,
        ];
    }
}
