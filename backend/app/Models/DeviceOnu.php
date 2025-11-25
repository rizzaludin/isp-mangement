<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class DeviceOnu extends Model
{
    protected $table = 'devices_onu';

    protected $fillable = [
        'olt_id',
        'customer_id',
        'serial_number',
        'pon_port',
        'onu_index',
        'signal_rx',
        'signal_tx',
        'status',
        'last_online',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'signal_rx' => 'decimal:2',
            'signal_tx' => 'decimal:2',
            'last_online' => 'datetime',
        ];
    }

    public function olt(): BelongsTo
    {
        return $this->belongsTo(DeviceOlt::class, 'olt_id');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function subscription(): HasOne
    {
        return $this->hasOne(Subscription::class, 'onu_id');
    }

    public function isOnline(): bool
    {
        return $this->status === 'online';
    }

    public function isOffline(): bool
    {
        return $this->status === 'offline';
    }

    public function hasGoodSignal(): bool
    {
        return $this->signal_rx !== null 
            && $this->signal_rx >= -27.0 
            && $this->signal_rx <= -8.0;
    }

    public function getSignalQuality(): string
    {
        if ($this->signal_rx === null) {
            return 'unknown';
        }

        if ($this->signal_rx >= -20) {
            return 'excellent';
        } elseif ($this->signal_rx >= -25) {
            return 'good';
        } elseif ($this->signal_rx >= -28) {
            return 'fair';
        } else {
            return 'poor';
        }
    }
}
