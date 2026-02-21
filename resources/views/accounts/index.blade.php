<x-layouts.app>
<div class="max-w-6xl mx-auto">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold">
            Accounts
            @if (request('filter.trashed') === 'only')
                <span class="text-base font-normal text-base-content/70">(Deleted)</span>
            @elseif (request('filter.trashed') === 'with')
                <span class="text-base font-normal text-base-content/70">(Including Deleted)</span>
            @endif
        </h1>
        <a href="{{ route('accounts.create') }}" class="btn btn-primary">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
            </svg>
            Add Account
        </a>
    </div>

    @if (session('success'))
        <div class="alert alert-success mb-4">
            <svg xmlns="http://www.w3.org/2000/svg" class="stroke-current shrink-0 h-6 w-6" fill="none" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <span>{{ session('success') }}</span>
        </div>
    @endif

    <div class="card bg-base-100 shadow-xl mb-4">
        <div class="card-body py-4">
            <form action="{{ route('accounts.index') }}" method="GET" class="flex flex-wrap gap-4 items-end">
                <div class="form-control">
                    <label class="label py-1">
                        <span class="label-text">Search Name</span>
                    </label>
                    <input type="text" name="filter[name]" value="{{ request('filter.name') }}"
                           class="input input-bordered input-sm w-48" placeholder="Search by name...">
                </div>

                <div class="form-control">
                    <label class="label py-1">
                        <span class="label-text">Account Type</span>
                    </label>
                    <select name="filter[account_type]" class="select select-bordered select-sm w-48">
                        <option value="">All Types</option>
                        @foreach ($accountTypes as $type)
                            <option value="{{ $type->value }}" @selected(request('filter.account_type') === $type->value)>
                                {{ ucfirst(str_replace('_', ' ', $type->value)) }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="form-control">
                    <label class="label py-1">
                        <span class="label-text">Status</span>
                    </label>
                    <select name="filter[trashed]" class="select select-bordered select-sm w-48">
                        <option value="">Active Only</option>
                        <option value="with" @selected(request('filter.trashed') === 'with')>All (Including Deleted)</option>
                        <option value="only" @selected(request('filter.trashed') === 'only')>Deleted Only</option>
                    </select>
                </div>

                <div class="flex gap-2">
                    <button type="submit" class="btn btn-primary btn-sm">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                        Filter
                    </button>
                    @if(request()->hasAny(['filter']))
                        <a href="{{ route('accounts.index') }}" class="btn btn-ghost btn-sm">Clear</a>
                    @endif
                </div>
            </form>
        </div>
    </div>

    <div class="card bg-base-100 shadow-xl">
        <div class="card-body">
            @if ($accounts->isEmpty())
                <div class="text-center py-8">
                    <p class="text-base-content/70">No accounts found.</p>
                    <a href="{{ route('accounts.create') }}" class="btn btn-primary btn-sm mt-4">Create your first account</a>
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Type</th>
                                <th class="text-right">Current Balance</th>
                                <th>{{ request('filter.trashed') ? 'Deleted At' : 'Initial Date' }}</th>
                                <th class="text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($accounts as $account)
                                <tr @class(['bg-error/10' => $account->trashed()])>
                                    <td class="font-medium">
                                        {{ $account->label }}
                                        @if ($account->trashed())
                                            <span class="badge badge-error badge-sm ml-1">Deleted</span>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="badge badge-ghost">{{ ucfirst(str_replace('_', ' ', $account->account_type->value)) }}</span>
                                    </td>
                                    <td class="text-right font-mono {{ $account->current_balance >= 0 ? 'text-success' : 'text-error' }}">
                                        {{ number_format($account->current_balance, 2) }}
                                    </td>
                                    <td class="text-sm text-base-content/70">
                                        @if ($account->trashed())
                                            {{ $account->deleted_at->format('M d, Y H:i') }}
                                        @else
                                            {{ $account->initial_date->format('M d, Y') }}
                                        @endif
                                    </td>
                                    <td>
                                        <div class="flex justify-end gap-2">
                                            @if ($account->trashed())
                                                <a href="{{ route('accounts.show', $account) }}" class="btn btn-ghost btn-sm">
                                                    View
                                                </a>
                                                <form action="{{ route('accounts.restore', $account) }}" method="POST" class="inline">
                                                    @csrf
                                                    <button type="submit" class="btn btn-ghost btn-sm text-success">
                                                        Restore
                                                    </button>
                                                </form>
                                            @else
                                                <a href="{{ route('accounts.transactions', $account) }}" class="btn btn-ghost btn-sm">
                                                    Transactions
                                                </a>
                                                <a href="{{ route('accounts.show', $account) }}" class="btn btn-ghost btn-sm">
                                                    View
                                                </a>
                                                <a href="{{ route('accounts.edit', $account) }}" class="btn btn-ghost btn-sm">
                                                    Edit
                                                </a>
                                                <form action="{{ route('accounts.destroy', $account) }}" method="POST" class="inline"
                                                      onsubmit="return confirm('Are you sure you want to delete this account?');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-ghost btn-sm text-error">
                                                        Delete
                                                    </button>
                                                </form>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="mt-4">
                    {{ $accounts->links() }}
                </div>
            @endif
        </div>
    </div>
</div>
</x-layouts.app>
