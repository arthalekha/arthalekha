<x-layouts.app>
<div class="max-w-4xl mx-auto">
    <div class="flex justify-between items-center mb-6">
        <div>
            <div class="text-sm breadcrumbs">
                <ul>
                    <li><a href="{{ route('accounts.index') }}">Accounts</a></li>
                    <li><a href="{{ route('accounts.show', $account) }}">{{ $account->name }}</a></li>
                    <li>Balances</li>
                </ul>
            </div>
            <h1 class="text-2xl font-bold">{{ $account->name }} - Historical Balances</h1>
        </div>
        <a href="{{ route('accounts.show', $account) }}" class="btn btn-ghost">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
            </svg>
            Back to Account
        </a>
    </div>

    <div class="card bg-base-100 shadow-xl mb-6">
        <div class="card-body">
            <div class="stats stats-vertical lg:stats-horizontal shadow w-full">
                <div class="stat">
                    <div class="stat-title">Current Balance</div>
                    <div class="stat-value text-lg {{ $account->current_balance >= 0 ? 'text-success' : 'text-error' }}">
                        {{ number_format($account->current_balance, 2) }}
                    </div>
                </div>
                <div class="stat">
                    <div class="stat-title">Total Records</div>
                    <div class="stat-value text-lg">{{ $balances->total() }}</div>
                </div>
            </div>
        </div>
    </div>

    <div class="card bg-base-100 shadow-xl">
        <div class="card-body">
            @if ($balances->isEmpty())
                <div class="text-center py-8">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 mx-auto text-base-content/30 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                    </svg>
                    <p class="text-base-content/70">No historical balances recorded yet.</p>
                    <p class="text-sm text-base-content/50 mt-1">Balances are recorded automatically on the 1st of each month.</p>
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Period Ending</th>
                                <th class="text-right">Balance</th>
                                <th class="text-right">Change</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($balances as $index => $balance)
                                @php
                                    $previousBalance = $balances[$index + 1] ?? null;
                                    $change = $previousBalance ? $balance->balance - $previousBalance->balance : null;
                                @endphp
                                <tr>
                                    <td>
                                        <div class="font-medium">{{ $balance->recorded_until->format('F Y') }}</div>
                                        <div class="text-sm text-base-content/70">{{ $balance->recorded_until->format('M d, Y') }}</div>
                                    </td>
                                    <td class="text-right font-mono {{ $balance->balance >= 0 ? 'text-success' : 'text-error' }}">
                                        {{ number_format($balance->balance, 2) }}
                                    </td>
                                    <td class="text-right font-mono">
                                        @if ($change !== null)
                                            <span class="{{ $change >= 0 ? 'text-success' : 'text-error' }}">
                                                {{ $change >= 0 ? '+' : '' }}{{ number_format($change, 2) }}
                                            </span>
                                        @else
                                            <span class="text-base-content/50">-</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="mt-4">
                    {{ $balances->links() }}
                </div>
            @endif
        </div>
    </div>
</div>
</x-layouts.app>
