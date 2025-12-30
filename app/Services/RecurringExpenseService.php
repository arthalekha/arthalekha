<?php

namespace App\Services;

use App\Models\RecurringExpense;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class RecurringExpenseService
{
    /**
     * Get paginated recurring expenses for a user with filters.
     */
    public function getRecurringExpensesForUser(User $user, int $perPage = 10): LengthAwarePaginator
    {
        return QueryBuilder::for(RecurringExpense::class)
            ->where('user_id', $user->id)
            ->with(['account', 'person', 'tags'])
            ->allowedFilters([
                AllowedFilter::partial('search', 'description'),
                AllowedFilter::exact('account_id'),
                AllowedFilter::exact('person_id'),
                AllowedFilter::exact('frequency'),
            ])
            ->latest('next_transaction_at')
            ->paginate($perPage)
            ->withQueryString();
    }

    /**
     * Create a new recurring expense for a user.
     *
     * @param  array<string, mixed>  $data
     */
    public function createRecurringExpense(User $user, array $data): RecurringExpense
    {
        $data['user_id'] = $user->id;
        $tags = $data['tags'] ?? [];
        unset($data['tags']);

        $recurringExpense = RecurringExpense::create($data);
        $recurringExpense->tags()->sync($tags);

        return $recurringExpense;
    }

    /**
     * Update an existing recurring expense.
     *
     * @param  array<string, mixed>  $data
     */
    public function updateRecurringExpense(RecurringExpense $recurringExpense, array $data): RecurringExpense
    {
        $tags = $data['tags'] ?? [];
        unset($data['tags']);

        $recurringExpense->update($data);
        $recurringExpense->tags()->sync($tags);

        return $recurringExpense->fresh();
    }

    /**
     * Delete a recurring expense.
     */
    public function deleteRecurringExpense(RecurringExpense $recurringExpense): bool
    {
        return $recurringExpense->delete();
    }

    /**
     * Check if the user owns the recurring expense.
     */
    public function userOwnsRecurringExpense(User $user, RecurringExpense $recurringExpense): bool
    {
        return $recurringExpense->user_id === $user->id;
    }
}
