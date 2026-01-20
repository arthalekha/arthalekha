<?php

namespace App\Services;

use App\Models\Income;
use App\Models\User;
use App\QueryFilters\FromDateFilter;
use App\QueryFilters\TagFilter;
use App\QueryFilters\ToDateFilter;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class IncomeService
{
    public function __construct(
        private AccountService $accountService
    ) {}

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
     * Get incomes query for export with filters.
     */
    public function getIncomesQueryForExport(User $user): Builder
    {
        return QueryBuilder::for(Income::class)
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
            $income->syncTags($tags);

            $this->accountService->incrementBalance($income);

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

            $this->accountService->decrementBalance($income);

            $income->update($data);

            $income->syncTags($tags);

            $this->accountService->incrementBalance($income);

            return $income->fresh();
        });
    }

    /**
     * Delete an income.
     */
    public function deleteIncome(Income $income): bool
    {
        return DB::transaction(function () use ($income) {
            $this->accountService->decrementBalance($income);

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
