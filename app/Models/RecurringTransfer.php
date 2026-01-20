<?php

namespace App\Models;

use App\Enums\Frequency;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use SourcedOpen\Tags\Traits\HasTags;

class RecurringTransfer extends Model
{
    /** @use HasFactory<\Database\Factories\RecurringTransferFactory> */
    use HasFactory;

    use HasTags;

    protected $fillable = [
        'user_id',
        'creditor_id',
        'debtor_id',
        'description',
        'amount',
        'next_transaction_at',
        'frequency',
        'remaining_recurrences',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'next_transaction_at' => 'datetime',
            'amount' => 'decimal:2',
            'frequency' => Frequency::class,
            'remaining_recurrences' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * The account receiving the money.
     */
    public function creditor(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'creditor_id');
    }

    /**
     * The account sending the money.
     */
    public function debtor(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'debtor_id');
    }
}
