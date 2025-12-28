<?php

namespace App\Services;

use App\Models\Transfer;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class TransferService
{
    /**
     * Get paginated transfers for a user.
     */
    public function getTransfersForUser(User $user, int $perPage = 10): LengthAwarePaginator
    {
        return Transfer::query()
            ->where('user_id', $user->id)
            ->with(['creditor', 'debtor'])
            ->latest('transacted_at')
            ->paginate($perPage);
    }

    /**
     * Create a new transfer for a user.
     *
     * @param  array<string, mixed>  $data
     */
    public function createTransfer(User $user, array $data): Transfer
    {
        $data['user_id'] = $user->id;

        return Transfer::create($data);
    }

    /**
     * Update an existing transfer.
     *
     * @param  array<string, mixed>  $data
     */
    public function updateTransfer(Transfer $transfer, array $data): Transfer
    {
        $transfer->update($data);

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
