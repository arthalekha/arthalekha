<?php

namespace App\Services;

use App\Models\Account;
use App\Models\Expense;
use App\Models\Income;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class AccountService
{
    public function __construct(
        protected BalanceService $balanceService,
    ) {}

    /**
     * Get all accounts.
     *
     * @return Collection<int, Account>
     */
    public function getAll(): Collection
    {
        return Account::all();
    }

    /**
     * Get paginated accounts.
     */
    public function getAccounts(int $perPage = 10): LengthAwarePaginator
    {
        return QueryBuilder::for(Account::class)
            ->allowedFilters([
                AllowedFilter::exact('account_type'),
                AllowedFilter::partial('name'),
                AllowedFilter::trashed(),
            ])
            ->defaultSort('-created_at')
            ->paginate($perPage)
            ->withQueryString();
    }

    /**
     * Create a new account for a user.
     *
     * @param  array<string, mixed>  $data
     */
    public function createAccount(User $user, array $data): Account
    {
        $data['user_id'] = $user->id;
        $data['current_balance'] = $data['initial_balance'] ?? 0;

        return DB::transaction(function () use ($data) {
            $account = Account::create($data);

            $this->balanceService->createInitialBalanceEntries($account);

            return $account;
        });
    }

    /**
     * Update an existing account.
     *
     * @param  array<string, mixed>  $data
     */
    public function updateAccount(Account $account, array $data): Account
    {
        $account->update($data);

        return $account->fresh();
    }

    /**
     * Delete an account.
     */
    public function deleteAccount(Account $account): bool
    {
        return $account->delete();
    }

    /**
     * Restore a trashed account.
     */
    public function restoreAccount(Account $account): bool
    {
        return $account->restore();
    }

    /**
     * Increment account balance.
     */
    public function incrementBalance(Expense|Income $transaction): void
    {
        Account::where('id', $transaction->account_id)->increment('current_balance', $transaction->amount);

        $this->balanceService->incrementBalance($transaction->account_id, $transaction->amount, $transaction->transacted_at);
    }

    /**
     * Decrement account balance.
     */
    public function decrementBalance(Expense|Income $transaction): void
    {
        Account::where('id', $transaction->account_id)->decrement('current_balance', $transaction->amount);

        $this->balanceService->decrementBalance($transaction->account_id, $transaction->amount, $transaction->transacted_at);
    }
}
