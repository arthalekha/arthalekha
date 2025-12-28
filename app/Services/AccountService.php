<?php

namespace App\Services;

use App\Models\Account;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class AccountService
{
    /**
     * Get paginated accounts for a user.
     */
    public function getAccountsForUser(User $user, int $perPage = 10): LengthAwarePaginator
    {
        return QueryBuilder::for(Account::class)
            ->where('user_id', $user->id)
            ->allowedFilters([
                AllowedFilter::exact('account_type'),
                AllowedFilter::partial('name'),
            ])
            ->defaultSort('-created_at')
            ->paginate($perPage)
            ->appends(request()->query());
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

        return Account::create($data);
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
     * Check if the user owns the account.
     */
    public function userOwnsAccount(User $user, Account $account): bool
    {
        return $account->user_id === $user->id;
    }
}
