@extends('layouts.app')

@section('content')
<div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <h1 class="text-2xl font-bold">Projected Dashboard - Next 12 Months</h1>
    </div>

    <div class="stats shadow w-full">
        <div class="stat">
            <div class="stat-title">Current Balance</div>
            <div class="stat-value text-info">{{ number_format($currentBalance, 2) }}</div>
        </div>
        <div class="stat">
            <div class="stat-title">Projected Income</div>
            <div class="stat-value text-success">{{ number_format($totalProjectedIncome, 2) }}</div>
        </div>
        <div class="stat">
            <div class="stat-title">Projected Expense</div>
            <div class="stat-value text-error">{{ number_format($totalProjectedExpense, 2) }}</div>
        </div>
        <div class="stat">
            <div class="stat-title">Projected Net Savings</div>
            <div class="stat-value {{ $projectedNetSavings >= 0 ? 'text-success' : 'text-error' }}">
                {{ number_format($projectedNetSavings, 2) }}
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 gap-6">
        <div class="card bg-base-100 shadow-xl">
            <div class="card-body">
                <h2 class="card-title">Monthly Projected Income & Expense</h2>
                <div class="h-96">
                    <canvas id="projectedChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <div class="card bg-base-100 shadow-xl">
        <div class="card-body">
            <h2 class="card-title">Monthly Breakdown</h2>
            <div class="overflow-x-auto">
                <table class="table table-zebra">
                    <thead>
                        <tr>
                            <th>Month</th>
                            <th class="text-right">Income</th>
                            <th class="text-right">Expense</th>
                            <th class="text-right">Net</th>
                            <th class="text-right">Balance</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php $balanceIndex = 0; @endphp
                        @foreach($monthlyProjections as $month => $data)
                        <tr>
                            <td>{{ $month }}</td>
                            <td class="text-right text-success">{{ number_format($data['income'], 2) }}</td>
                            <td class="text-right text-error">{{ number_format($data['expense'], 2) }}</td>
                            <td class="text-right {{ ($data['income'] - $data['expense']) >= 0 ? 'text-success' : 'text-error' }}">
                                {{ number_format($data['income'] - $data['expense'], 2) }}
                            </td>
                            <td class="text-right text-info">{{ number_format($balanceData[$balanceIndex++], 2) }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr class="font-bold">
                            <td>Total</td>
                            <td class="text-right text-success">{{ number_format($totalProjectedIncome, 2) }}</td>
                            <td class="text-right text-error">{{ number_format($totalProjectedExpense, 2) }}</td>
                            <td class="text-right {{ $projectedNetSavings >= 0 ? 'text-success' : 'text-error' }}">
                                {{ number_format($projectedNetSavings, 2) }}
                            </td>
                            <td class="text-right text-info">{{ number_format(end($balanceData), 2) }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
    function initProjectedCharts() {
        const months = @json($months);
        const incomeData = @json($incomeData);
        const expenseData = @json($expenseData);
        const balanceData = @json($balanceData);

        const ctx = document.getElementById('projectedChart').getContext('2d');
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: months,
                datasets: [
                    {
                        label: 'Projected Income',
                        data: incomeData,
                        backgroundColor: 'rgba(34, 197, 94, 0.7)',
                        borderColor: 'rgb(34, 197, 94)',
                        borderWidth: 1,
                        order: 2
                    },
                    {
                        label: 'Projected Expense',
                        data: expenseData,
                        backgroundColor: 'rgba(239, 68, 68, 0.7)',
                        borderColor: 'rgb(239, 68, 68)',
                        borderWidth: 1,
                        order: 3
                    },
                    {
                        label: 'Projected Balance',
                        data: balanceData,
                        type: 'line',
                        borderColor: 'rgb(59, 130, 246)',
                        backgroundColor: 'rgba(59, 130, 246, 0.1)',
                        borderWidth: 2,
                        fill: false,
                        tension: 0.1,
                        pointRadius: 4,
                        pointBackgroundColor: 'rgb(59, 130, 246)',
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
                        beginAtZero: true,
                        position: 'left',
                        title: {
                            display: true,
                            text: 'Income / Expense'
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
                    }
                }
            }
        });
    }

    if (window.Chart) {
        initProjectedCharts();
    } else {
        document.addEventListener('chartjs:ready', initProjectedCharts);
    }
</script>
@endsection
