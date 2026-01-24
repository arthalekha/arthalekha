<?php

namespace App\Services;

use App\Models\RecurringTransfer;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class RecurringTransferService
{
    /**
     * Get paginated recurring transfers for a user with filters.
     */
    public function getRecurringTransfersForUser(User $user, int $perPage = 10): LengthAwarePaginator
    {
        return QueryBuilder::for(RecurringTransfer::class)
            ->where('user_id', $user->id)
            ->with(['creditor', 'debtor', 'tags'])
            ->allowedFilters([
                AllowedFilter::partial('search', 'description'),
                AllowedFilter::exact('creditor_id'),
                AllowedFilter::exact('debtor_id'),
                AllowedFilter::exact('frequency'),
            ])
            ->latest('next_transaction_at')
            ->paginate($perPage)
            ->withQueryString();
    }

    /**
     * Create a new recurring transfer for a user.
     *
     * @param  array<string, mixed>  $data
     */
    public function createRecurringTransfer(User $user, array $data): RecurringTransfer
    {
        $data['user_id'] = $user->id;
        $tags = $data['tags'] ?? [];
        unset($data['tags']);

        $recurringTransfer = RecurringTransfer::create($data);
        $recurringTransfer->syncTags($tags);

        return $recurringTransfer;
    }

    /**
     * Update an existing recurring transfer.
     *
     * @param  array<string, mixed>  $data
     */
    public function updateRecurringTransfer(RecurringTransfer $recurringTransfer, array $data): RecurringTransfer
    {
        $tags = $data['tags'] ?? [];
        unset($data['tags']);

        $recurringTransfer->update($data);
        $recurringTransfer->syncTags($tags);

        return $recurringTransfer->fresh();
    }

    /**
     * Delete a recurring transfer.
     */
    public function deleteRecurringTransfer(RecurringTransfer $recurringTransfer): bool
    {
        return $recurringTransfer->delete();
    }
}
