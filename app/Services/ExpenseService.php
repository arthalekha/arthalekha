<?php

namespace App\Services;

use App\Models\Account;
use App\Models\Expense;
use App\Models\User;
use App\QueryFilters\FromDateFilter;
use App\QueryFilters\TagFilter;
use App\QueryFilters\ToDateFilter;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class ExpenseService
{
    /**
     * Get paginated expenses for a user with filters.
     */
    public function getExpensesForUser(User $user, int $perPage = 10): LengthAwarePaginator
    {
        return QueryBuilder::for(Expense::class)
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
     * Create a new expense for a user.
     *
     * @param  array<string, mixed>  $data
     */
    public function createExpense(User $user, array $data): Expense
    {
        return DB::transaction(function () use ($user, $data) {
            $data['user_id'] = $user->id;
            $tags = $data['tags'] ?? [];
            unset($data['tags']);

            $expense = Expense::create($data);
            $expense->tags()->sync($tags);

            Account::where('id', $expense->account_id)->decrement('current_balance', $expense->amount);

            return $expense;
        });
    }

    /**
     * Update an existing expense.
     *
     * @param  array<string, mixed>  $data
     */
    public function updateExpense(Expense $expense, array $data): Expense
    {
        return DB::transaction(function () use ($expense, $data) {
            $tags = $data['tags'] ?? [];
            unset($data['tags']);

            $oldAccountId = $expense->account_id;
            $oldAmount = $expense->amount;

            $expense->update($data);
            $expense->tags()->sync($tags);

            $newAccountId = $expense->account_id;
            $newAmount = $expense->amount;

            if ($oldAccountId === $newAccountId) {
                $difference = $newAmount - $oldAmount;
                if ($difference != 0) {
                    Account::where('id', $newAccountId)->decrement('current_balance', $difference);
                }
            } else {
                Account::where('id', $oldAccountId)->increment('current_balance', $oldAmount);
                Account::where('id', $newAccountId)->decrement('current_balance', $newAmount);
            }

            return $expense->fresh();
        });
    }

    /**
     * Delete an expense.
     */
    public function deleteExpense(Expense $expense): bool
    {
        return DB::transaction(function () use ($expense) {
            Account::where('id', $expense->account_id)->increment('current_balance', $expense->amount);

            return $expense->delete();
        });
    }

    /**
     * Check if the user owns the expense.
     */
    public function userOwnsExpense(User $user, Expense $expense): bool
    {
        return $expense->user_id === $user->id;
    }
}
