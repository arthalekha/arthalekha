<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\Expense;
use App\Models\Income;
use App\Models\Transfer;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class AccountTransactionController extends Controller
{
    /**
     * Display all transactions for an account.
     */
    public function __invoke(Request $request, Account $account): View
    {
        $filters = [
            'from_date' => $request->input('filter.from_date', Carbon::now()->startOfMonth()->format('Y-m-d')),
            'to_date' => $request->input('filter.to_date', Carbon::now()->endOfMonth()->format('Y-m-d')),
            'search' => $request->input('filter.search'),
            'type' => $request->input('filter.type'),
        ];

        $transactions = $this->getTransactions($account, $filters);

        $page = $request->input('page', 1);
        $perPage = 15;
        $paginatedTransactions = new LengthAwarePaginator(
            $transactions->forPage($page, $perPage),
            $transactions->count(),
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        return view('accounts.transactions', [
            'account' => $account,
            'transactions' => $paginatedTransactions,
            'filters' => $filters,
        ]);
    }

    /**
     * Get all transactions for an account.
     *
     * @param  array<string, mixed>  $filters
     */
    private function getTransactions(Account $account, array $filters): Collection
    {
        $incomes = $this->getIncomes($account, $filters);
        $expenses = $this->getExpenses($account, $filters);
        $transfers = $this->getTransfers($account, $filters);

        return collect()
            ->merge($incomes)
            ->merge($expenses)
            ->merge($transfers)
            ->sortByDesc('transacted_at')
            ->values();
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function getIncomes(Account $account, array $filters): Collection
    {
        if (! empty($filters['type']) && $filters['type'] !== 'income') {
            return collect();
        }

        $query = Income::query()
            ->where('account_id', $account->id)
            ->with('person');

        $this->applyDateFilters($query, $filters);
        $this->applySearchFilter($query, $filters);

        return collect($query->get())->map(fn ($income) => [
            'id' => $income->id,
            'type' => 'income',
            'description' => $income->description,
            'amount' => $income->amount,
            'transacted_at' => $income->transacted_at,
            'person' => $income->person,
            'related_account' => null,
        ]);
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function getExpenses(Account $account, array $filters): Collection
    {
        if (! empty($filters['type']) && $filters['type'] !== 'expense') {
            return collect();
        }

        $query = Expense::query()
            ->where('account_id', $account->id)
            ->with('person');

        $this->applyDateFilters($query, $filters);
        $this->applySearchFilter($query, $filters);

        return collect($query->get())->map(fn ($expense) => [
            'id' => $expense->id,
            'type' => 'expense',
            'description' => $expense->description,
            'amount' => $expense->amount,
            'transacted_at' => $expense->transacted_at,
            'person' => $expense->person,
            'related_account' => null,
        ]);
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function getTransfers(Account $account, array $filters): Collection
    {
        if (! empty($filters['type']) && $filters['type'] !== 'transfer') {
            return collect();
        }

        // Transfers where this account is creditor (receiving money)
        $creditorQuery = Transfer::query()
            ->where('creditor_id', $account->id)
            ->with('debtor');

        $this->applyDateFilters($creditorQuery, $filters);
        $this->applySearchFilter($creditorQuery, $filters);

        $creditorTransfers = collect($creditorQuery->get())->map(fn ($transfer) => [
            'id' => $transfer->id,
            'type' => 'transfer_in',
            'description' => $transfer->description,
            'amount' => $transfer->amount,
            'transacted_at' => $transfer->transacted_at,
            'person' => null,
            'related_account' => $transfer->debtor,
        ]);

        // Transfers where this account is debtor (sending money)
        $debtorQuery = Transfer::query()
            ->where('debtor_id', $account->id)
            ->with('creditor');

        $this->applyDateFilters($debtorQuery, $filters);
        $this->applySearchFilter($debtorQuery, $filters);

        $debtorTransfers = collect($debtorQuery->get())->map(fn ($transfer) => [
            'id' => $transfer->id,
            'type' => 'transfer_out',
            'description' => $transfer->description,
            'amount' => $transfer->amount,
            'transacted_at' => $transfer->transacted_at,
            'person' => null,
            'related_account' => $transfer->creditor,
        ]);

        return $creditorTransfers->concat($debtorTransfers);
    }

    /**
     * @param  \Illuminate\Database\Eloquent\Builder<\Illuminate\Database\Eloquent\Model>  $query
     * @param  array<string, mixed>  $filters
     */
    private function applyDateFilters($query, array $filters): void
    {
        if (! empty($filters['from_date'])) {
            $query->whereDate('transacted_at', '>=', $filters['from_date']);
        }

        if (! empty($filters['to_date'])) {
            $query->whereDate('transacted_at', '<=', $filters['to_date']);
        }
    }

    /**
     * @param  \Illuminate\Database\Eloquent\Builder<\Illuminate\Database\Eloquent\Model>  $query
     * @param  array<string, mixed>  $filters
     */
    private function applySearchFilter($query, array $filters): void
    {
        if (! empty($filters['search'])) {
            $query->where('description', 'like', '%'.$filters['search'].'%');
        }
    }
}
