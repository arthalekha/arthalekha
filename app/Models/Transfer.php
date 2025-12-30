<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

class Transfer extends Model
{
    /** @use HasFactory<\Database\Factories\TransferFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'creditor_id',
        'debtor_id',
        'description',
        'transacted_at',
        'amount',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'transacted_at' => 'datetime',
            'amount' => 'decimal:2',
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

    public function tags(): MorphToMany
    {
        return $this->morphToMany(Tag::class, 'taggable');
    }
}
