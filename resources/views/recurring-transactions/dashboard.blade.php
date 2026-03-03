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
                                        <div class="flex justify-end gap-2">
                                            <button class="btn btn-success btn-sm" onclick="document.getElementById('modal-income-{{ $recurringIncome->id }}').showModal()">Record Income</button>
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
                                        <div class="flex justify-end gap-2">
                                            <button class="btn btn-error btn-sm" onclick="document.getElementById('modal-expense-{{ $recurringExpense->id }}').showModal()">Record Expense</button>
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

        @foreach ($recurringIncomes as $recurringIncome)
            <dialog id="modal-income-{{ $recurringIncome->id }}" class="modal">
                <div class="modal-box">
                    <h3 class="text-lg font-bold">Record Income</h3>
                    <p class="text-sm text-base-content/70 mt-1">{{ $recurringIncome->description }} &mdash; <span class="text-success font-mono">+{{ number_format($recurringIncome->amount, 2) }}</span></p>
                    <form action="{{ route('recurring-incomes.record', $recurringIncome) }}" method="POST" class="mt-4 flex flex-col gap-4">
                        @csrf
                        <div class="form-control">
                            <label class="label" for="income-transacted-at-{{ $recurringIncome->id }}">
                                <span class="label-text">Transaction Date <span class="text-error">*</span></span>
                            </label>
                            <input type="datetime-local" name="transacted_at" id="income-transacted-at-{{ $recurringIncome->id }}"
                                   value="{{ now()->format('Y-m-d\TH:i') }}"
                                   class="input input-bordered" required>
                        </div>
                        <div class="form-control">
                            <label class="label" for="income-account-{{ $recurringIncome->id }}">
                                <span class="label-text">Account <span class="text-error">*</span></span>
                            </label>
                            <select name="account_id" id="income-account-{{ $recurringIncome->id }}" class="select select-bordered" required>
                                <option value="">Select Account</option>
                                @foreach ($accounts as $account)
                                    <option value="{{ $account->id }}">{{ $account->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="modal-action">
                            <button type="button" class="btn btn-ghost" onclick="this.closest('dialog').close()">Cancel</button>
                            <button type="submit" class="btn btn-success">Record Income</button>
                        </div>
                    </form>
                </div>
                <form method="dialog" class="modal-backdrop">
                    <button>close</button>
                </form>
            </dialog>
        @endforeach

        @foreach ($recurringExpenses as $recurringExpense)
            <dialog id="modal-expense-{{ $recurringExpense->id }}" class="modal">
                <div class="modal-box">
                    <h3 class="text-lg font-bold">Record Expense</h3>
                    <p class="text-sm text-base-content/70 mt-1">{{ $recurringExpense->description }} &mdash; <span class="text-error font-mono">-{{ number_format($recurringExpense->amount, 2) }}</span></p>
                    <form action="{{ route('recurring-expenses.record', $recurringExpense) }}" method="POST" class="mt-4 flex flex-col gap-4">
                        @csrf
                        <div class="form-control">
                            <label class="label" for="expense-transacted-at-{{ $recurringExpense->id }}">
                                <span class="label-text">Transaction Date <span class="text-error">*</span></span>
                            </label>
                            <input type="datetime-local" name="transacted_at" id="expense-transacted-at-{{ $recurringExpense->id }}"
                                   value="{{ now()->format('Y-m-d\TH:i') }}"
                                   class="input input-bordered" required>
                        </div>
                        <div class="form-control">
                            <label class="label" for="expense-account-{{ $recurringExpense->id }}">
                                <span class="label-text">Account <span class="text-error">*</span></span>
                            </label>
                            <select name="account_id" id="expense-account-{{ $recurringExpense->id }}" class="select select-bordered" required>
                                <option value="">Select Account</option>
                                @foreach ($accounts as $account)
                                    <option value="{{ $account->id }}">{{ $account->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="modal-action">
                            <button type="button" class="btn btn-ghost" onclick="this.closest('dialog').close()">Cancel</button>
                            <button type="submit" class="btn btn-error">Record Expense</button>
                        </div>
                    </form>
                </div>
                <form method="dialog" class="modal-backdrop">
                    <button>close</button>
                </form>
            </dialog>
        @endforeach
    @endif
</div>
</x-layouts.app>
