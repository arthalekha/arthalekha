<?php

namespace App\Models;

use App\Data\AccountData\AccountData;
use App\Data\AccountData\CreditCardAccountData;
use App\Data\AccountData\SavingsAccountData;
use App\Enums\AccountType;
use App\Models\Scopes\FamilyUserScope;
use Illuminate\Database\Eloquent\Attributes\ScopedBy;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Date;

#[ScopedBy(FamilyUserScope::class)]
class Account extends Model
{
    /** @use HasFactory<\Database\Factories\AccountFactory> */
    use HasFactory;

    use SoftDeletes;

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

    public function balances(): HasMany
    {
        return $this->hasMany(Balance::class);
    }

    public function previousMonthBalance(): HasOne
    {
        $previousMonthEnd = Date::now()->subMonth()->endOfMonth()->toDateString();

        return $this->hasOne(Balance::class)
            ->whereDate('recorded_until', $previousMonthEnd);
    }

    protected function label(): Attribute
    {
        return Attribute::get(fn () => "{$this->account_type->shortCode()} | {$this->name} {$this->identifier}");
    }

    public function getTypedData(): ?AccountData
    {
        $data = $this->data ?? [];

        return match ($this->account_type) {
            AccountType::Savings => SavingsAccountData::fromArray($data),
            AccountType::CreditCard => CreditCardAccountData::fromArray($data),
            default => null,
        };
    }

    public function setTypedData(AccountData $accountData): void
    {
        $this->data = $accountData->toArray();
    }
}
