<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RadiusSession extends Model
{
    protected $fillable = [
        'radius_user_id',
        'nas_ip',
        'framed_ip',
        'mac_address',
        'start_time',
        'stop_time',
        'bytes_in',
        'bytes_out',
        'terminate_cause',
    ];

    protected function casts(): array
    {
        return [
            'start_time' => 'datetime',
            'stop_time' => 'datetime',
            'bytes_in' => 'integer',
            'bytes_out' => 'integer',
        ];
    }

    public function radiusUser(): BelongsTo
    {
        return $this->belongsTo(RadiusUser::class);
    }

    public function isActive(): bool
    {
        return $this->stop_time === null;
    }

    public function getDurationAttribute(): ?int
    {
        if (!$this->stop_time) {
            return now()->diffInSeconds($this->start_time);
        }
        return $this->stop_time->diffInSeconds($this->start_time);
    }

    public function getTotalBytesAttribute(): int
    {
        return $this->bytes_in + $this->bytes_out;
    }

    public function getFormattedBytesAttribute(): string
    {
        $total = $this->total_bytes;
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        
        for ($i = 0; $total > 1024 && $i < 4; $i++) {
            $total /= 1024;
        }
        
        return round($total, 2) . ' ' . $units[$i];
    }
}
