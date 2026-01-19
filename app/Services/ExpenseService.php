<?php

namespace App\Services;

use App\Models\Expense;
use App\Models\User;
use App\QueryFilters\FromDateFilter;
use App\QueryFilters\TagFilter;
use App\QueryFilters\ToDateFilter;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class ExpenseService
{
    public function __construct(
        private AccountService $accountService
    ) {}

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
     * Get expenses query for export with filters.
     */
    public function getExpensesQueryForExport(User $user): Builder
    {
        return QueryBuilder::for(Expense::class)
            ->where('user_id', $user->id)
            ->with(['account', 'person', 'tags'])
            ->allowedFilters([
                AllowedFilter::custom('from_date', new FromDateFilter),
                AllowedFilter::custom('to_date', new ToDateFilter),
                AllowedFilter::partial('search', 'description'),
                AllowedFilter::exact('account_id'),
                AllowedFilter::exact('person_id'),
                AllowedFilter::custom('tag_id', new TagFilter),
            ])
            ->latest('transacted_at')
            ->getEloquentBuilder();
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

            $this->accountService->decrementBalance($expense);

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

            if ($oldAccountId === $expense->account_id) {
                // Expense increase = balance decrease (negative adjustment)
                $difference = $oldAmount - $expense->amount;
                $this->accountService->adjustBalance($expense->account_id, $difference);
            } else {
                $this->accountService->adjustBalance($oldAccountId, $oldAmount);
                $this->accountService->decrementBalance($expense);
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
            $this->accountService->incrementBalance($expense);

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
