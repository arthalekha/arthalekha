<?php

namespace App\Services;

use App\Models\Account;
use App\Models\Transfer;
use App\Models\User;
use App\QueryFilters\FromDateFilter;
use App\QueryFilters\TagFilter;
use App\QueryFilters\ToDateFilter;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class TransferService
{
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
            $transfer->tags()->sync($tags);

            Account::where('id', $transfer->debtor_id)->decrement('current_balance', $transfer->amount);
            Account::where('id', $transfer->creditor_id)->increment('current_balance', $transfer->amount);

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

            $transfer->update($data);
            $transfer->tags()->sync($tags);

            $newDebtorId = $transfer->debtor_id;
            $newCreditorId = $transfer->creditor_id;
            $newAmount = $transfer->amount;

            // Reverse the old transfer
            Account::where('id', $oldDebtorId)->increment('current_balance', $oldAmount);
            Account::where('id', $oldCreditorId)->decrement('current_balance', $oldAmount);

            // Apply the new transfer
            Account::where('id', $newDebtorId)->decrement('current_balance', $newAmount);
            Account::where('id', $newCreditorId)->increment('current_balance', $newAmount);

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

            return $transfer->delete();
        });
    }

    /**
     * Check if the user owns the transfer.
     */
    public function userOwnsTransfer(User $user, Transfer $transfer): bool
    {
        return $transfer->user_id === $user->id;
    }
}
