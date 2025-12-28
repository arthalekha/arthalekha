<?php

namespace App\Services;

use App\Models\Expense;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ExpenseService
{
    /**
     * Get paginated expenses for a user.
     */
    public function getExpensesForUser(User $user, int $perPage = 10): LengthAwarePaginator
    {
        return Expense::query()
            ->where('user_id', $user->id)
            ->with(['account', 'person'])
            ->latest('transacted_at')
            ->paginate($perPage);
    }

    /**
     * Create a new expense for a user.
     *
     * @param  array<string, mixed>  $data
     */
    public function createExpense(User $user, array $data): Expense
    {
        $data['user_id'] = $user->id;

        return Expense::create($data);
    }

    /**
     * Update an existing expense.
     *
     * @param  array<string, mixed>  $data
     */
    public function updateExpense(Expense $expense, array $data): Expense
    {
        $expense->update($data);

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
