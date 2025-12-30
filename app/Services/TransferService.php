<?php

namespace App\Services;

use App\Models\Transfer;
use App\Models\User;
use App\QueryFilters\FromDateFilter;
use App\QueryFilters\TagFilter;
use App\QueryFilters\ToDateFilter;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
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
        $data['user_id'] = $user->id;
        $tags = $data['tags'] ?? [];
        unset($data['tags']);

        $transfer = Transfer::create($data);
        $transfer->tags()->sync($tags);

        return $transfer;
    }

    /**
     * Update an existing transfer.
     *
     * @param  array<string, mixed>  $data
     */
    public function updateTransfer(Transfer $transfer, array $data): Transfer
    {
        $tags = $data['tags'] ?? [];
        unset($data['tags']);

        $transfer->update($data);
        $transfer->tags()->sync($tags);

        return $transfer->fresh();
    }

    /**
     * Delete a transfer.
     */
    public function deleteTransfer(Transfer $transfer): bool
    {
        return $transfer->delete();
    }

    /**
     * Check if the user owns the transfer.
     */
    public function userOwnsTransfer(User $user, Transfer $transfer): bool
    {
        return $transfer->user_id === $user->id;
    }
}
