<?php

namespace App\Services;

use App\Models\Account;
use App\Models\Income;
use App\Models\User;
use App\QueryFilters\FromDateFilter;
use App\QueryFilters\TagFilter;
use App\QueryFilters\ToDateFilter;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class IncomeService
{
    /**
     * Get paginated incomes for a user with filters.
     */
    public function getIncomesForUser(User $user, int $perPage = 10): LengthAwarePaginator
    {
        return QueryBuilder::for(Income::class)
            ->where('user_id', $user->id)
            ->with(['account', 'person'])
            ->allowedFilters([
                AllowedFilter::custom('from_date', new FromDateFilter),
                AllowedFilter::custom('to_date', new ToDateFilter),
                AllowedFilter::partial('search', 'description'),
                AllowedFilter::exact('account_id'),
                AllowedFilter::exact('person_id'),
                AllowedFilter::custom('tag_id', new TagFilter),
            ])
            ->latest('transacted_at')
            ->paginate($perPage)
            ->withQueryString();
    }

    /**
     * Create a new income for a user.
     *
     * @param  array<string, mixed>  $data
     */
    public function createIncome(User $user, array $data): Income
    {
        return DB::transaction(function () use ($user, $data) {
            $data['user_id'] = $user->id;
            $tags = $data['tags'] ?? [];
            unset($data['tags']);

            $income = Income::create($data);
            $income->tags()->sync($tags);

            Account::where('id', $income->account_id)->increment('current_balance', $income->amount);

            return $income;
        });
    }

    /**
     * Update an existing income.
     *
     * @param  array<string, mixed>  $data
     */
    public function updateIncome(Income $income, array $data): Income
    {
        return DB::transaction(function () use ($income, $data) {
            $tags = $data['tags'] ?? [];
            unset($data['tags']);

            $oldAccountId = $income->account_id;
            $oldAmount = $income->amount;

            $income->update($data);
            $income->tags()->sync($tags);

            $newAccountId = $income->account_id;
            $newAmount = $income->amount;

            if ($oldAccountId === $newAccountId) {
                $difference = $newAmount - $oldAmount;
                if ($difference != 0) {
                    Account::where('id', $newAccountId)->increment('current_balance', $difference);
                }
            } else {
                Account::where('id', $oldAccountId)->decrement('current_balance', $oldAmount);
                Account::where('id', $newAccountId)->increment('current_balance', $newAmount);
            }

            return $income->fresh();
        });
    }

    /**
     * Delete an income.
     */
    public function deleteIncome(Income $income): bool
    {
        return DB::transaction(function () use ($income) {
            Account::where('id', $income->account_id)->decrement('current_balance', $income->amount);

            return $income->delete();
        });
    }

    /**
     * Check if the user owns the income.
     */
    public function userOwnsIncome(User $user, Income $income): bool
    {
        return $income->user_id === $user->id;
    }
}
