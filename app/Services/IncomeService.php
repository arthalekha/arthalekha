<?php

namespace App\Services;

use App\Models\Income;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class IncomeService
{
    /**
     * Get paginated incomes for a user.
     */
    public function getIncomesForUser(User $user, int $perPage = 10): LengthAwarePaginator
    {
        return Income::query()
            ->where('user_id', $user->id)
            ->with(['account', 'person'])
            ->latest('transacted_at')
            ->paginate($perPage);
    }

    /**
     * Create a new income for a user.
     *
     * @param  array<string, mixed>  $data
     */
    public function createIncome(User $user, array $data): Income
    {
        $data['user_id'] = $user->id;

        return Income::create($data);
    }

    /**
     * Update an existing income.
     *
     * @param  array<string, mixed>  $data
     */
    public function updateIncome(Income $income, array $data): Income
    {
        $income->update($data);

        return $income->fresh();
    }

    /**
     * Delete an income.
     */
    public function deleteIncome(Income $income): bool
    {
        return $income->delete();
    }

    /**
     * Check if the user owns the income.
     */
    public function userOwnsIncome(User $user, Income $income): bool
    {
        return $income->user_id === $user->id;
    }
}
