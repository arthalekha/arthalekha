@extends('layouts.app')

@section('content')
<div class="max-w-2xl mx-auto">
    <div class="mb-6">
        <a href="{{ route('accounts.index') }}" class="btn btn-ghost btn-sm">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
            </svg>
            Back to Accounts
        </a>
    </div>

    <div class="card bg-base-100 shadow-xl">
        <div class="card-body">
            <div class="flex justify-between items-start">
                <div>
                    <h2 class="card-title text-2xl font-bold">{{ $account->name }}</h2>
                    <span class="badge badge-ghost mt-1">{{ ucfirst(str_replace('_', ' ', $account->account_type->value)) }}</span>
                </div>
                <div class="flex gap-2">
                    <a href="{{ route('accounts.edit', $account) }}" class="btn btn-ghost btn-sm">Edit</a>
                    <form action="{{ route('accounts.destroy', $account) }}" method="POST" class="inline"
                          onsubmit="return confirm('Are you sure you want to delete this account?');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-ghost btn-sm text-error">Delete</button>
                    </form>
                </div>
            </div>

            <div class="divider"></div>

            <div class="stats stats-vertical lg:stats-horizontal shadow w-full mb-6">
                <div class="stat">
                    <div class="stat-title">Current Balance</div>
                    <div class="stat-value text-lg {{ $account->current_balance >= 0 ? 'text-success' : 'text-error' }}">
                        {{ number_format($account->current_balance, 2) }}
                    </div>
                </div>
                <div class="stat">
                    <div class="stat-title">Initial Balance</div>
                    <div class="stat-value text-lg">{{ number_format($account->initial_balance, 2) }}</div>
                </div>
                @if ($averageBalance !== null)
                    @php
                        $requiredAverageBalance = $account->data['average_balance_amount'] ?? null;
                        $isAboveRequired = $requiredAverageBalance === null || $averageBalance >= $requiredAverageBalance;
                    @endphp
                    <div class="stat">
                        <div class="stat-title">{{ $averageBalanceFrequency->label() }} Avg Balance</div>
                        <div class="stat-value text-lg {{ $isAboveRequired ? 'text-success' : 'text-error' }}">
                            {{ number_format($averageBalance, 2) }}
                        </div>
                        <div class="stat-desc">Required: {{ number_format($requiredAverageBalance, 2) }}</div>
                    </div>
                @endif
            </div>

            <div class="space-y-4">
                @if ($account->identifier)
                    <div>
                        <label class="text-sm font-medium text-base-content/70">Identifier / Account Number</label>
                        <p class="mt-1 font-mono">{{ $account->identifier }}</p>
                    </div>
                @endif

                <div>
                    <label class="text-sm font-medium text-base-content/70">Initial Date</label>
                    <p class="mt-1">{{ $account->initial_date->format('F d, Y') }}</p>
                </div>
            </div>

            <div class="divider"></div>

            <div class="flex flex-wrap gap-2">
                <a href="{{ route('accounts.transactions', $account) }}" class="btn btn-outline btn-sm">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                    </svg>
                    View Transactions
                </a>
                <a href="{{ route('accounts.balances', $account) }}" class="btn btn-outline btn-sm">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                    </svg>
                    View Historical Balances
                </a>
                <a href="{{ route('accounts.projected-balance', $account) }}" class="btn btn-outline btn-sm">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                    </svg>
                    View Projected Balance
                </a>
            </div>

            @if ($account->account_type === \App\Enums\AccountType::Savings && $account->data)
                <div class="divider">Savings Account Details</div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    @if (isset($account->data['rate_of_interest']))
                        <div>
                            <label class="text-sm font-medium text-base-content/70">Rate of Interest</label>
                            <p class="mt-1">{{ $account->data['rate_of_interest'] }}%</p>
                        </div>
                    @endif

                    @if (isset($account->data['interest_frequency']))
                        <div>
                            <label class="text-sm font-medium text-base-content/70">Interest Frequency</label>
                            <p class="mt-1">{{ ucfirst($account->data['interest_frequency']) }}</p>
                        </div>
                    @endif

                    @if (isset($account->data['average_balance_frequency']))
                        <div>
                            <label class="text-sm font-medium text-base-content/70">Average Balance Frequency</label>
                            <p class="mt-1">{{ ucfirst($account->data['average_balance_frequency']) }}</p>
                        </div>
                    @endif

                    @if (isset($account->data['average_balance_amount']))
                        <div>
                            <label class="text-sm font-medium text-base-content/70">Average Balance Amount</label>
                            <p class="mt-1">{{ number_format($account->data['average_balance_amount'], 2) }}</p>
                        </div>
                    @endif
                </div>
            @endif

            @if ($account->account_type === \App\Enums\AccountType::CreditCard && $account->data)
                <div class="divider">Credit Card Details</div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    @if (isset($account->data['rate_of_interest']))
                        <div>
                            <label class="text-sm font-medium text-base-content/70">Rate of Interest</label>
                            <p class="mt-1">{{ $account->data['rate_of_interest'] }}%</p>
                        </div>
                    @endif

                    @if (isset($account->data['interest_frequency']))
                        <div>
                            <label class="text-sm font-medium text-base-content/70">Interest Frequency</label>
                            <p class="mt-1">{{ ucfirst($account->data['interest_frequency']) }}</p>
                        </div>
                    @endif

                    @if (isset($account->data['bill_generated_on']))
                        <div>
                            <label class="text-sm font-medium text-base-content/70">Bill Generated On</label>
                            <p class="mt-1">Day {{ $account->data['bill_generated_on'] }} of each month</p>
                        </div>
                    @endif

                    @if (isset($account->data['repayment_of_bill_after_days']))
                        <div>
                            <label class="text-sm font-medium text-base-content/70">Repayment Due After</label>
                            <p class="mt-1">{{ $account->data['repayment_of_bill_after_days'] }} days</p>
                        </div>
                    @endif
                </div>
            @endif
        </div>
    </div>
</div>
@endsection

