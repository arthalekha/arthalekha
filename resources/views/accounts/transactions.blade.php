<x-layouts.app>
<div class="max-w-6xl mx-auto">
    <div class="flex justify-between items-center mb-6">
        <div>
            <div class="text-sm breadcrumbs">
                <ul>
                    <li><a href="{{ route('accounts.index') }}">Accounts</a></li>
                    <li><a href="{{ route('accounts.show', $account) }}">{{ $account->name }}</a></li>
                    <li>Transactions</li>
                </ul>
            </div>
            <h1 class="text-2xl font-bold">{{ $account->name }} - Transactions</h1>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('incomes.create', ['account_id' => $account->id]) }}" class="btn btn-success btn-sm">
                + Add Income
            </a>
            <a href="{{ route('expenses.create', ['account_id' => $account->id]) }}" class="btn btn-error btn-sm">
                + Add Expense
            </a>
            <a href="{{ route('accounts.show', $account) }}" class="btn btn-ghost btn-sm">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
                Back to Account
            </a>
        </div>
    </div>

    <div class="card bg-base-100 shadow-xl mb-6">
        <div class="card-body">
            <form method="GET" action="{{ route('accounts.transactions', $account) }}">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                    <div class="form-control">
                        <label class="label">
                            <span class="label-text">From Date</span>
                        </label>
                        <input
                            type="date"
                            name="filter[from_date]"
                            value="{{ $filters['from_date'] }}"
                            class="input input-bordered input-sm"
                        >
                    </div>

                    <div class="form-control">
                        <label class="label">
                            <span class="label-text">To Date</span>
                        </label>
                        <input
                            type="date"
                            name="filter[to_date]"
                            value="{{ $filters['to_date'] }}"
                            class="input input-bordered input-sm"
                        >
                    </div>

                    <div class="form-control">
                        <label class="label">
                            <span class="label-text">Search</span>
                        </label>
                        <input
                            type="text"
                            name="filter[search]"
                            value="{{ $filters['search'] }}"
                            placeholder="Search description..."
                            class="input input-bordered input-sm"
                        >
                    </div>

                    <div class="form-control">
                        <label class="label">
                            <span class="label-text">Type</span>
                        </label>
                        <select name="filter[type]" class="select select-bordered select-sm">
                            <option value="">All Types</option>
                            <option value="income" {{ $filters['type'] === 'income' ? 'selected' : '' }}>Income</option>
                            <option value="expense" {{ $filters['type'] === 'expense' ? 'selected' : '' }}>Expense</option>
                            <option value="transfer" {{ $filters['type'] === 'transfer' ? 'selected' : '' }}>Transfer</option>
                        </select>
                    </div>
                </div>

                <div class="flex gap-2 mt-4">
                    <button type="submit" class="btn btn-primary btn-sm">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" />
                        </svg>
                        Filter
                    </button>
                    <a href="{{ route('accounts.transactions', $account) }}" class="btn btn-ghost btn-sm">
                        Clear
                    </a>
                </div>
            </form>
        </div>
    </div>

    <div class="card bg-base-100 shadow-xl">
        <div class="card-body">
            @if ($transactions->isEmpty())
                <div class="text-center py-8">
                    <p class="text-base-content/70">No transactions found for this period.</p>
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Type</th>
                                <th>Description</th>
                                <th>Person / Account</th>
                                <th class="text-right">Amount</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($transactions as $transaction)
                                <tr>
                                    <td class="text-sm">
                                        {{ $transaction['transacted_at']->format('M d, Y') }}
                                    </td>
                                    <td>
                                        @switch($transaction['type'])
                                            @case('income')
                                                <span class="badge badge-success badge-sm">Income</span>
                                                @break
                                            @case('expense')
                                                <span class="badge badge-error badge-sm">Expense</span>
                                                @break
                                            @case('transfer_in')
                                                <span class="badge badge-info badge-sm">Transfer In</span>
                                                @break
                                            @case('transfer_out')
                                                <span class="badge badge-warning badge-sm">Transfer Out</span>
                                                @break
                                        @endswitch
                                    </td>
                                    <td class="font-medium">{{ $transaction['description'] }}</td>
                                    <td class="text-sm text-base-content/70">
                                        @if ($transaction['person'])
                                            {{ $transaction['person']->name }}
                                        @elseif ($transaction['related_account'])
                                            <span class="badge badge-ghost badge-sm">{{ $transaction['related_account']->name }}</span>
                                        @else
                                            -
                                        @endif
                                    </td>
                                    <td class="text-right font-mono">
                                        @if (in_array($transaction['type'], ['income', 'transfer_in']))
                                            <span class="text-success">+{{ number_format($transaction['amount'], 2) }}</span>
                                        @else
                                            <span class="text-error">-{{ number_format($transaction['amount'], 2) }}</span>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="flex justify-end gap-1">
                                            @if ($transaction['type'] === 'income')
                                                <a href="{{ route('incomes.create', ['description' => $transaction['description'], 'amount' => $transaction['amount'], 'account_id' => $transaction['account_id'], 'person_id' => $transaction['person_id'], 'tags' => $transaction['tags']]) }}" class="btn btn-ghost btn-xs tooltip" data-tip="Copy">
                                                    <x-icons.document-duplicate class="size-4" />
                                                </a>
                                                <a href="{{ route('incomes.edit', $transaction['id']) }}" class="btn btn-ghost btn-xs tooltip" data-tip="Edit">
                                                    <x-icons.pencil-square class="size-4" />
                                                </a>
                                            @elseif ($transaction['type'] === 'expense')
                                                <a href="{{ route('expenses.create', ['description' => $transaction['description'], 'amount' => $transaction['amount'], 'account_id' => $transaction['account_id'], 'person_id' => $transaction['person_id'], 'tags' => $transaction['tags']]) }}" class="btn btn-ghost btn-xs tooltip" data-tip="Copy">
                                                    <x-icons.document-duplicate class="size-4" />
                                                </a>
                                                <a href="{{ route('expenses.edit', $transaction['id']) }}" class="btn btn-ghost btn-xs tooltip" data-tip="Edit">
                                                    <x-icons.pencil-square class="size-4" />
                                                </a>
                                            @elseif (in_array($transaction['type'], ['transfer_in', 'transfer_out']))
                                                <a href="{{ route('transfers.create', ['description' => $transaction['description'], 'amount' => $transaction['amount'], 'debtor_id' => $transaction['debtor_id'], 'creditor_id' => $transaction['creditor_id'], 'tags' => $transaction['tags']]) }}" class="btn btn-ghost btn-xs tooltip" data-tip="Copy">
                                                    <x-icons.document-duplicate class="size-4" />
                                                </a>
                                                <a href="{{ route('transfers.edit', $transaction['id']) }}" class="btn btn-ghost btn-xs tooltip" data-tip="Edit">
                                                    <x-icons.pencil-square class="size-4" />
                                                </a>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="mt-4">
                    {{ $transactions->links() }}
                </div>
            @endif
        </div>
    </div>
</div>
</x-layouts.app>
