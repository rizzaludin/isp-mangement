<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MonitoringLog extends Model
{
    protected $fillable = [
        'device_type',
        'device_id',
        'metric_type',
        'value',
        'recorded_at',
    ];

    protected function casts(): array
    {
        return [
            'value' => 'array',
            'recorded_at' => 'datetime',
        ];
    }

    public function device()
    {
        return match($this->device_type) {
            'router' => $this->belongsTo(DeviceRouter::class, 'device_id'),
            'olt' => $this->belongsTo(DeviceOlt::class, 'device_id'),
            'onu' => $this->belongsTo(DeviceOnu::class, 'device_id'),
            default => null,
        };
    }
}
