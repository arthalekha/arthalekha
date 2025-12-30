<?php

namespace App\Services;

use App\Models\RecurringIncome;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class RecurringIncomeService
{
    /**
     * Get paginated recurring incomes for a user with filters.
     */
    public function getRecurringIncomesForUser(User $user, int $perPage = 10): LengthAwarePaginator
    {
        return QueryBuilder::for(RecurringIncome::class)
            ->where('user_id', $user->id)
            ->with(['account', 'person'])
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
     * Create a new recurring income for a user.
     *
     * @param  array<string, mixed>  $data
     */
    public function createRecurringIncome(User $user, array $data): RecurringIncome
    {
        $data['user_id'] = $user->id;
        $tags = $data['tags'] ?? [];
        unset($data['tags']);

        $recurringIncome = RecurringIncome::create($data);
        $recurringIncome->tags()->sync($tags);

        return $recurringIncome;
    }

    /**
     * Update an existing recurring income.
     *
     * @param  array<string, mixed>  $data
     */
    public function updateRecurringIncome(RecurringIncome $recurringIncome, array $data): RecurringIncome
    {
        $tags = $data['tags'] ?? [];
        unset($data['tags']);

        $recurringIncome->update($data);
        $recurringIncome->tags()->sync($tags);

        return $recurringIncome->fresh();
    }

    /**
     * Delete a recurring income.
     */
    public function deleteRecurringIncome(RecurringIncome $recurringIncome): bool
    {
        return $recurringIncome->delete();
    }

    /**
     * Check if the user owns the recurring income.
     */
    public function userOwnsRecurringIncome(User $user, RecurringIncome $recurringIncome): bool
    {
        return $recurringIncome->user_id === $user->id;
    }
}
