<?php

namespace App\Services;

use App\Models\Account;
use App\Models\Transfer;
use App\Models\User;
use App\QueryFilters\FromDateFilter;
use App\QueryFilters\TagFilter;
use App\QueryFilters\ToDateFilter;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class TransferService
{
    public function __construct(protected BalanceService $balanceService) {}

    /**
     * Get paginated transfers for a user with filters.
     */
    public function getTransfersForUser(User $user, int $perPage = 10): LengthAwarePaginator
    {
        return QueryBuilder::for(Transfer::class)
            ->where('user_id', $user->id)
            ->with(['creditor', 'debtor'])
            ->allowedFilters([
                AllowedFilter::custom('from_date', new FromDateFilter),
                AllowedFilter::custom('to_date', new ToDateFilter),
                AllowedFilter::partial('search', 'description'),
                AllowedFilter::exact('debtor_id'),
                AllowedFilter::exact('creditor_id'),
                AllowedFilter::custom('tag_id', new TagFilter),
            ])
            ->latest('transacted_at')
            ->paginate($perPage)
            ->withQueryString();
    }

    /**
     * Get transfers query for export with filters.
     */
    public function getTransfersQueryForExport(User $user): Builder
    {
        return QueryBuilder::for(Transfer::class)
            ->where('user_id', $user->id)
            ->with(['creditor', 'debtor', 'tags'])
            ->allowedFilters([
                AllowedFilter::custom('from_date', new FromDateFilter),
                AllowedFilter::custom('to_date', new ToDateFilter),
                AllowedFilter::partial('search', 'description'),
                AllowedFilter::exact('debtor_id'),
                AllowedFilter::exact('creditor_id'),
                AllowedFilter::custom('tag_id', new TagFilter),
            ])
            ->latest('transacted_at')
            ->getEloquentBuilder();
    }

    /**
     * Create a new transfer for a user.
     *
     * @param  array<string, mixed>  $data
     */
    public function createTransfer(User $user, array $data): Transfer
    {
        return DB::transaction(function () use ($user, $data) {
            $data['user_id'] = $user->id;
            $tags = $data['tags'] ?? [];
            unset($data['tags']);

            $transfer = Transfer::create($data);
            $transfer->syncTags($tags);

            Account::where('id', $transfer->debtor_id)->decrement('current_balance', $transfer->amount);
            Account::where('id', $transfer->creditor_id)->increment('current_balance', $transfer->amount);
            $this->balanceService->incrementBalance($transfer->creditor_id, $transfer->amount, $transfer->transacted_at);
            $this->balanceService->decrementBalance($transfer->debtor_id, $transfer->amount, $transfer->transacted_at);

            return $transfer;
        });
    }

    /**
     * Update an existing transfer.
     *
     * @param  array<string, mixed>  $data
     */
    public function updateTransfer(Transfer $transfer, array $data): Transfer
    {
        return DB::transaction(function () use ($transfer, $data) {
            $tags = $data['tags'] ?? [];
            unset($data['tags']);

            $oldDebtorId = $transfer->debtor_id;
            $oldCreditorId = $transfer->creditor_id;
            $oldAmount = $transfer->amount;

            // Reverse the old transfer
            Account::where('id', $transfer->debtor_id)->increment('current_balance', $transfer->amount);
            Account::where('id', $transfer->creditor_id)->decrement('current_balance', $transfer->amount);
            $this->balanceService->decrementBalance($transfer->creditor_id, $transfer->amount, $transfer->transacted_at);
            $this->balanceService->incrementBalance($transfer->debtor_id, $transfer->amount, $transfer->transacted_at);

            $transfer->update($data);
            $transfer->syncTags($tags);

            Account::where('id', $transfer->debtor_id)->decrement('current_balance', $transfer->amount);
            Account::where('id', $transfer->creditor_id)->increment('current_balance', $transfer->amount);
            $this->balanceService->incrementBalance($transfer->creditor_id, $transfer->amount, $transfer->transacted_at);
            $this->balanceService->decrementBalance($transfer->debtor_id, $transfer->amount, $transfer->transacted_at);

            return $transfer->fresh();
        });
    }

    /**
     * Delete a transfer.
     */
    public function deleteTransfer(Transfer $transfer): bool
    {
        return DB::transaction(function () use ($transfer) {
            Account::where('id', $transfer->debtor_id)->increment('current_balance', $transfer->amount);
            Account::where('id', $transfer->creditor_id)->decrement('current_balance', $transfer->amount);
            $this->balanceService->decrementBalance($transfer->creditor_id, $transfer->amount, $transfer->transacted_at);
            $this->balanceService->incrementBalance($transfer->debtor_id, $transfer->amount, $transfer->transacted_at);

            return $transfer->delete();
        });
    }
}
