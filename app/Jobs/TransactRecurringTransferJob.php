<?php

namespace App\Jobs;

use App\Models\RecurringTransfer;
use App\Models\Transfer;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;

class TransactRecurringTransferJob implements ShouldQueue
{
    use Queueable;

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        RecurringTransfer::query()
            ->where('next_transaction_at', '<=', now())
            ->each(function (RecurringTransfer $recurringTransfer) {
                DB::transaction(function () use ($recurringTransfer) {
                    $this->processRecurringTransfer($recurringTransfer);
                });
            });
    }

    private function processRecurringTransfer(RecurringTransfer $recurringTransfer): void
    {
        if ($recurringTransfer->creditor_id !== null && $recurringTransfer->debtor_id !== null) {
            $transfer = Transfer::create([
                'user_id' => $recurringTransfer->user_id,
                'creditor_id' => $recurringTransfer->creditor_id,
                'debtor_id' => $recurringTransfer->debtor_id,
                'description' => $recurringTransfer->description,
                'amount' => $recurringTransfer->amount,
                'transacted_at' => $recurringTransfer->next_transaction_at,
            ]);

            $transfer->tags()->sync($recurringTransfer->tags->pluck('id'));
        }

        if ($recurringTransfer->remaining_recurrences !== null) {
            $recurringTransfer->remaining_recurrences--;

            if ($recurringTransfer->remaining_recurrences <= 0) {
                $recurringTransfer->delete();

                return;
            }
        }

        $recurringTransfer->next_transaction_at = $recurringTransfer->frequency->addToDate(
            $recurringTransfer->next_transaction_at
        );
        $recurringTransfer->save();
    }
}
