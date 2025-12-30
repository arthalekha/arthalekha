<?php

namespace App\Models;

use App\Enums\Frequency;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RecurringExpense extends Model
{
    /** @use HasFactory<\Database\Factories\RecurringExpenseFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'person_id',
        'account_id',
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

    public function person(): BelongsTo
    {
        return $this->belongsTo(Person::class);
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }
}
