<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class JournalEntry extends Model
{
    protected $fillable = [
        'date',
        'description',
        'reference_type',
        'reference_id',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
        ];
    }

    public function lines(): HasMany
    {
        return $this->hasMany(JournalLine::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function getTotalDebitAttribute(): float
    {
        return $this->lines()->sum('debit');
    }

    public function getTotalCreditAttribute(): float
    {
        return $this->lines()->sum('credit');
    }

    public function isBalanced(): bool
    {
        return abs($this->total_debit - $this->total_credit) < 0.01;
    }

    public function addLine(int $accountId, float $debit = 0, float $credit = 0): JournalLine
    {
        return $this->lines()->create([
            'account_id' => $accountId,
            'debit' => $debit,
            'credit' => $credit,
        ]);
    }

    public static function createEntry(
        string $date,
        string $description,
        array $lines,
        ?string $referenceType = null,
        ?int $referenceId = null,
        ?int $createdBy = null
    ): self {
        $entry = static::create([
            'date' => $date,
            'description' => $description,
            'reference_type' => $referenceType,
            'reference_id' => $referenceId,
            'created_by' => $createdBy ?? auth()->id(),
        ]);

        foreach ($lines as $line) {
            $entry->addLine(
                $line['account_id'],
                $line['debit'] ?? 0,
                $line['credit'] ?? 0
            );
        }

        if (!$entry->isBalanced()) {
            $entry->delete();
            throw new \Exception('Journal entry is not balanced');
        }

        return $entry;
    }
}
