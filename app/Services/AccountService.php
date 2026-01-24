<?php

namespace App\Services;

use App\Models\Account;
use App\Models\Expense;
use App\Models\Income;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class AccountService
{
    private const CACHE_TTL = 3600; // 1 hour

    public function __construct(
        protected BalanceService $balanceService,
    ) {}

    /**
     * Get all accounts for a user (cached).
     *
     * @return Collection<int, Account>
     */
    public function getAllForUser(int $userId): Collection
    {
        return Cache::remember(
            $this->getCacheKey($userId),
            self::CACHE_TTL,
            fn () => Account::where('user_id', $userId)->get()
        );
    }

    /**
     * Clear the accounts cache for a user.
     */
    public function clearCache(int $userId): void
    {
        Cache::forget($this->getCacheKey($userId));
    }

    /**
     * Get the cache key for a user's accounts.
     */
    private function getCacheKey(int $userId): string
    {
        return "user.{$userId}.accounts";
    }

    /**
     * Get paginated accounts for a user.
     */
    public function getAccounts(int $perPage = 10): LengthAwarePaginator
    {
        return QueryBuilder::for(Account::class)
            ->allowedFilters([
                AllowedFilter::exact('account_type'),
                AllowedFilter::partial('name'),
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

        $account = DB::transaction(function () use ($data) {
            $account = Account::create($data);

            $this->balanceService->createInitialBalanceEntries($account);

            return $account;
        });

        $this->clearCache($user->id);

        return $account;
    }

    /**
     * Update an existing account.
     *
     * @param  array<string, mixed>  $data
     */
    public function updateAccount(Account $account, array $data): Account
    {
        $account->update($data);

        $this->clearCache($account->user_id);

        return $account->fresh();
    }

    /**
     * Delete an account.
     */
    public function deleteAccount(Account $account): bool
    {
        $userId = $account->user_id;

        $result = $account->delete();

        $this->clearCache($userId);

        return $result;
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
