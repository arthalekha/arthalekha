@extends('layouts.app')

@section('content')
<div class="max-w-6xl mx-auto">
    <div class="flex justify-between items-center mb-6">
        <div>
            <div class="text-sm breadcrumbs">
                <ul>
                    <li><a href="{{ route('accounts.index') }}">Accounts</a></li>
                    <li><a href="{{ route('accounts.show', $account) }}">{{ $account->name }}</a></li>
                    <li>Projected Balance</li>
                </ul>
            </div>
            <h1 class="text-2xl font-bold">{{ $account->name }} - Projected Balance</h1>
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
            <form method="GET" action="{{ route('accounts.projected-balance', $account) }}">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
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
                </div>

                <div class="flex gap-2 mt-4">
                    <button type="submit" class="btn btn-primary btn-sm">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" />
                        </svg>
                        Filter
                    </button>
                    <a href="{{ route('accounts.projected-balance', $account) }}" class="btn btn-ghost btn-sm">
                        Clear
                    </a>
                </div>
            </form>
        </div>
    </div>

    <div class="stats shadow w-full mb-6">
        <div class="stat">
            <div class="stat-title">Starting Balance</div>
            <div class="stat-value text-lg text-info">{{ number_format($summary['startingBalance'], 2) }}</div>
        </div>
        <div class="stat">
            <div class="stat-title">Total Income</div>
            <div class="stat-value text-lg text-success">{{ number_format($summary['totalIncome'], 2) }}</div>
        </div>
        <div class="stat">
            <div class="stat-title">Total Expense</div>
            <div class="stat-value text-lg text-error">{{ number_format($summary['totalExpense'], 2) }}</div>
        </div>
        <div class="stat">
            <div class="stat-title">Ending Balance</div>
            <div class="stat-value text-lg {{ $summary['endingBalance'] >= 0 ? 'text-success' : 'text-error' }}">
                {{ number_format($summary['endingBalance'], 2) }}
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 gap-6 mb-6">
        <div class="card bg-base-100 shadow-xl">
            <div class="card-body">
                <h2 class="card-title">Daily Projected Income, Expense & Balance</h2>
                <div class="h-96">
                    <canvas id="projectedBalanceChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <div class="card bg-base-100 shadow-xl">
        <div class="card-body">
            <h2 class="card-title">Daily Breakdown</h2>
            <div class="overflow-x-auto">
                <table class="table table-zebra">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th class="text-right">Income</th>
                            <th class="text-right">Expense</th>
                            <th class="text-right">Transfer In</th>
                            <th class="text-right">Transfer Out</th>
                            <th class="text-right">Net</th>
                            <th class="text-right">Balance</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($dailyProjections as $date => $data)
                            @php
                                $net = $data['income'] - $data['expense'] + $data['transfer_in'] - $data['transfer_out'];
                                $hasActivity = $data['income'] > 0 || $data['expense'] > 0 || $data['transfer_in'] > 0 || $data['transfer_out'] > 0;
                            @endphp
                            @if($hasActivity)
                            <tr>
                                <td>{{ \Carbon\Carbon::parse($date)->format('M d, Y') }}</td>
                                <td class="text-right text-success">
                                    {{ $data['income'] > 0 ? number_format($data['income'], 2) : '-' }}
                                </td>
                                <td class="text-right text-error">
                                    {{ $data['expense'] > 0 ? number_format($data['expense'], 2) : '-' }}
                                </td>
                                <td class="text-right text-info">
                                    {{ $data['transfer_in'] > 0 ? number_format($data['transfer_in'], 2) : '-' }}
                                </td>
                                <td class="text-right text-warning">
                                    {{ $data['transfer_out'] > 0 ? number_format($data['transfer_out'], 2) : '-' }}
                                </td>
                                <td class="text-right {{ $net >= 0 ? 'text-success' : 'text-error' }}">
                                    {{ number_format($net, 2) }}
                                </td>
                                <td class="text-right font-medium {{ $data['balance'] >= 0 ? 'text-info' : 'text-error' }}">
                                    {{ number_format($data['balance'], 2) }}
                                </td>
                            </tr>
                            @endif
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr class="font-bold">
                            <td>Total</td>
                            <td class="text-right text-success">{{ number_format($summary['totalIncome'], 2) }}</td>
                            <td class="text-right text-error">{{ number_format($summary['totalExpense'], 2) }}</td>
                            <td class="text-right text-info">{{ number_format($summary['totalTransferIn'], 2) }}</td>
                            <td class="text-right text-warning">{{ number_format($summary['totalTransferOut'], 2) }}</td>
                            <td class="text-right {{ ($summary['totalIncome'] - $summary['totalExpense'] + $summary['totalTransferIn'] - $summary['totalTransferOut']) >= 0 ? 'text-success' : 'text-error' }}">
                                {{ number_format($summary['totalIncome'] - $summary['totalExpense'] + $summary['totalTransferIn'] - $summary['totalTransferOut'], 2) }}
                            </td>
                            <td class="text-right {{ $summary['endingBalance'] >= 0 ? 'text-info' : 'text-error' }}">
                                {{ number_format($summary['endingBalance'], 2) }}
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
    function initProjectedBalanceChart() {
        const dates = @json($dates);
        const incomeData = @json($incomeData);
        const expenseData = @json($expenseData).map(v => -v);
        const transferInData = @json($transferInData);
        const transferOutData = @json($transferOutData).map(v => -v);
        const balanceData = @json($balanceData);

        const ctx = document.getElementById('projectedBalanceChart').getContext('2d');
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: dates,
                datasets: [
                    {
                        label: 'Income',
                        data: incomeData,
                        backgroundColor: 'rgba(34, 197, 94, 0.7)',
                        borderColor: 'rgb(34, 197, 94)',
                        borderWidth: 1,
                        order: 3
                    },
                    {
                        label: 'Expense',
                        data: expenseData,
                        backgroundColor: 'rgba(239, 68, 68, 0.7)',
                        borderColor: 'rgb(239, 68, 68)',
                        borderWidth: 1,
                        order: 4
                    },
                    {
                        label: 'Transfer In',
                        data: transferInData,
                        backgroundColor: 'rgba(59, 130, 246, 0.5)',
                        borderColor: 'rgb(59, 130, 246)',
                        borderWidth: 1,
                        order: 5
                    },
                    {
                        label: 'Transfer Out',
                        data: transferOutData,
                        backgroundColor: 'rgba(251, 191, 36, 0.5)',
                        borderColor: 'rgb(251, 191, 36)',
                        borderWidth: 1,
                        order: 6
                    },
                    {
                        label: 'Balance',
                        data: balanceData,
                        type: 'line',
                        borderColor: 'rgb(99, 102, 241)',
                        backgroundColor: 'rgba(99, 102, 241, 0.1)',
                        borderWidth: 2,
                        fill: false,
                        tension: 0.1,
                        pointRadius: 2,
                        pointBackgroundColor: 'rgb(99, 102, 241)',
                        order: 1,
                        yAxisID: 'y1'
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        position: 'left',
                        title: {
                            display: true,
                            text: 'Income / Expense / Transfers'
                        }
                    },
                    y1: {
                        position: 'right',
                        title: {
                            display: true,
                            text: 'Balance'
                        },
                        grid: {
                            drawOnChartArea: false
                        }
                    }
                },
                plugins: {
                    legend: {
                        position: 'top'
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                let label = context.dataset.label || '';
                                if (label) {
                                    label += ': ';
                                }
                                const value = context.parsed.y;
                                label += Math.abs(value).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2});
                                return label;
                            }
                        }
                    }
                }
            }
        });
    }

    if (window.Chart) {
        initProjectedBalanceChart();
    } else {
        document.addEventListener('chartjs:ready', initProjectedBalanceChart);
    }
</script>
@endsection
