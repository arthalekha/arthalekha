<x-layouts.app>
<div class="max-w-6xl mx-auto">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold">Pending Recurring Transactions</h1>
    </div>

    @if (session('success'))
        <div class="alert alert-success mb-4">
            <svg xmlns="http://www.w3.org/2000/svg" class="stroke-current shrink-0 h-6 w-6" fill="none" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <span>{{ session('success') }}</span>
        </div>
    @endif

    @if ($recurringIncomes->isEmpty() && $recurringExpenses->isEmpty())
        <div class="card bg-base-100 shadow-xl">
            <div class="card-body text-center py-12">
                <p class="text-base-content/70">No pending recurring transactions.</p>
                <p class="text-base-content/50 text-sm mt-2">Items without an assigned account will appear here when they are due.</p>
            </div>
        </div>
    @else
        <div class="card bg-base-100 shadow-xl">
            <div class="card-body">
                <div class="overflow-x-auto">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Due Date</th>
                                <th>Description</th>
                                <th>Person</th>
                                <th>Frequency</th>
                                <th>Tags</th>
                                <th class="text-right">Amount</th>
                                <th>Transaction Date</th>
                                <th>Account</th>
                                <th class="text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($recurringIncomes as $recurringIncome)
                                <tr>
                                    <td class="text-sm">{{ $recurringIncome->next_transaction_at->format('M d, Y') }}</td>
                                    <td class="font-medium">{{ $recurringIncome->description }}</td>
                                    <td>{{ $recurringIncome->person?->name ?? '-' }}</td>
                                    <td><span class="badge badge-outline">{{ ucfirst($recurringIncome->frequency->value) }}</span></td>
                                    <td><x-tag-display :tags="$recurringIncome->tags" /></td>
                                    <td class="text-right font-mono text-success">+{{ number_format($recurringIncome->amount, 2) }}</td>
                                    <td>
                                        <input type="datetime-local" name="transacted_at" form="record-income-{{ $recurringIncome->id }}"
                                               value="{{ now()->format('Y-m-d\TH:i') }}"
                                               class="input input-bordered input-sm w-44" required>
                                    </td>
                                    <td>
                                        <form action="{{ route('recurring-incomes.record', $recurringIncome) }}" method="POST" class="flex gap-2 items-center" id="record-income-{{ $recurringIncome->id }}">
                                            @csrf
                                            <select name="account_id" class="select select-bordered select-sm w-40" required>
                                                <option value="">Select Account</option>
                                                @foreach ($accounts as $account)
                                                    <option value="{{ $account->id }}">{{ $account->name }}</option>
                                                @endforeach
                                            </select>
                                        </form>
                                    </td>
                                    <td>
                                        <div class="flex justify-end gap-2">
                                            <button type="submit" form="record-income-{{ $recurringIncome->id }}" class="btn btn-success btn-sm leading-tight text-center">Record<br>Income</button>
                                            <form action="{{ route('recurring-incomes.skip', $recurringIncome) }}" method="POST" class="inline">
                                                @csrf
                                                <button type="submit" class="btn btn-ghost btn-sm">Skip</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                            @foreach ($recurringExpenses as $recurringExpense)
                                <tr>
                                    <td class="text-sm">{{ $recurringExpense->next_transaction_at->format('M d, Y') }}</td>
                                    <td class="font-medium">{{ $recurringExpense->description }}</td>
                                    <td>{{ $recurringExpense->person?->name ?? '-' }}</td>
                                    <td><span class="badge badge-outline">{{ ucfirst($recurringExpense->frequency->value) }}</span></td>
                                    <td><x-tag-display :tags="$recurringExpense->tags" /></td>
                                    <td class="text-right font-mono text-error">-{{ number_format($recurringExpense->amount, 2) }}</td>
                                    <td>
                                        <input type="datetime-local" name="transacted_at" form="record-expense-{{ $recurringExpense->id }}"
                                               value="{{ now()->format('Y-m-d\TH:i') }}"
                                               class="input input-bordered input-sm w-44" required>
                                    </td>
                                    <td>
                                        <form action="{{ route('recurring-expenses.record', $recurringExpense) }}" method="POST" class="flex gap-2 items-center" id="record-expense-{{ $recurringExpense->id }}">
                                            @csrf
                                            <select name="account_id" class="select select-bordered select-sm w-40" required>
                                                <option value="">Select Account</option>
                                                @foreach ($accounts as $account)
                                                    <option value="{{ $account->id }}">{{ $account->name }}</option>
                                                @endforeach
                                            </select>
                                        </form>
                                    </td>
                                    <td>
                                        <div class="flex justify-end gap-2">
                                            <button type="submit" form="record-expense-{{ $recurringExpense->id }}" class="btn btn-error btn-sm leading-tight text-center">Record<br>Expense</button>
                                            <form action="{{ route('recurring-expenses.skip', $recurringExpense) }}" method="POST" class="inline">
                                                @csrf
                                                <button type="submit" class="btn btn-ghost btn-sm">Skip</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @endif
</div>
</x-layouts.app>
