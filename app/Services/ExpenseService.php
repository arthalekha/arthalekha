<?php

namespace App\Services;

use App\Models\Expense;
use App\Models\User;
use App\QueryFilters\FromDateFilter;
use App\QueryFilters\ToDateFilter;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
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
        $data['user_id'] = $user->id;
        $tags = $data['tags'] ?? [];
        unset($data['tags']);

        $expense = Expense::create($data);
        $expense->tags()->sync($tags);

        return $expense;
    }

    /**
     * Update an existing expense.
     *
     * @param  array<string, mixed>  $data
     */
    public function updateExpense(Expense $expense, array $data): Expense
    {
        $tags = $data['tags'] ?? [];
        unset($data['tags']);

        $expense->update($data);
        $expense->tags()->sync($tags);

        return $expense->fresh();
    }

    /**
     * Delete an expense.
     */
    public function deleteExpense(Expense $expense): bool
    {
        return $expense->delete();
    }

    /**
     * Check if the user owns the expense.
     */
    public function userOwnsExpense(User $user, Expense $expense): bool
    {
        return $expense->user_id === $user->id;
    }
}
