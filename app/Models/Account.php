<?php

namespace App\Models;

use App\Enums\AccountType;
use App\Observers\AccountObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[ObservedBy(AccountObserver::class)]
class Account extends Model
{
    /** @use HasFactory<\Database\Factories\AccountFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'identifier',
        'account_type',
        'current_balance',
        'initial_date',
        'initial_balance',
        'data',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'account_type' => AccountType::class,
            'current_balance' => 'decimal:2',
            'initial_date' => 'date',
            'initial_balance' => 'decimal:2',
            'data' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    protected function label(): Attribute
    {
        return Attribute::get(fn () => "{$this->account_type->shortCode()} | {$this->name} {$this->identifier}");
    }
}
