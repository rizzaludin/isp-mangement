<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Subscription extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'customer_id',
        'service_id',
        'router_id',
        'olt_id',
        'onu_id',
        'username_pppoe',
        'password_pppoe',
        'vlan_id',
        'ip_static',
        'ip_type',
        'status',
        'start_date',
        'end_date',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    public function router(): BelongsTo
    {
        return $this->belongsTo(DeviceRouter::class, 'router_id');
    }

    public function olt(): BelongsTo
    {
        return $this->belongsTo(DeviceOlt::class, 'olt_id');
    }

    public function onu(): BelongsTo
    {
        return $this->belongsTo(DeviceOnu::class, 'onu_id');
    }

    public function radiusUser(): HasOne
    {
        return $this->hasOne(RadiusUser::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isSuspended(): bool
    {
        return $this->status === 'suspended';
    }

    public function isTerminated(): bool
    {
        return $this->status === 'terminated';
    }

    public static function generateUsername(Customer $customer): string
    {
        $cleanName = preg_replace('/[^a-zA-Z0-9]/', '', strtolower($customer->name));
        $username = substr($cleanName, 0, 10) . rand(100, 999);
        
        while (static::where('username_pppoe', $username)->exists()) {
            $username = substr($cleanName, 0, 10) . rand(100, 999);
        }
        
        return $username;
    }

    public static function generatePassword(): string
    {
        return substr(str_shuffle('abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'), 0, 12);
    }
}
