<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Customer extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'code',
        'name',
        'address',
        'phone',
        'email',
        'id_number',
        'type',
        'status',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'deleted_at' => 'datetime',
        ];
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function activeSubscriptions(): HasMany
    {
        return $this->subscriptions()->where('status', 'active');
    }

    public function unpaidInvoices(): HasMany
    {
        return $this->invoices()->whereIn('status', ['unpaid', 'overdue']);
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public static function generateCode(): string
    {
        $lastCustomer = static::latest('id')->first();
        $nextId = $lastCustomer ? $lastCustomer->id + 1 : 1;
        return 'CST' . str_pad($nextId, 6, '0', STR_PAD_LEFT);
    }
}
