@extends('layouts.app')

@section('content')
<div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <h1 class="text-2xl font-bold">Dashboard - {{ $monthName }}</h1>

        <form method="GET" action="{{ route('home') }}" class="flex items-center gap-2">
            <input
                type="month"
                name="month"
                value="{{ $month }}"
                class="input input-bordered input-sm"
                onchange="this.form.submit()"
            >
        </form>
    </div>

    <div class="stats shadow w-full">
        <div class="stat">
            <div class="stat-title">Total Income</div>
            <div class="stat-value text-success">{{ number_format($totalIncome, 2) }}</div>
        </div>
        <div class="stat">
            <div class="stat-title">Total Expense</div>
            <div class="stat-value text-error">{{ number_format($totalExpense, 2) }}</div>
        </div>
        <div class="stat">
            <div class="stat-title">Net Savings</div>
            <div class="stat-value {{ $netSavings >= 0 ? 'text-success' : 'text-error' }}">
                {{ number_format($netSavings, 2) }}
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div class="card bg-base-100 shadow-xl">
            <div class="card-body">
                <h2 class="card-title">Daily Income & Expense</h2>
                <div class="h-80">
                    <canvas id="dailyChart"></canvas>
                </div>
            </div>
        </div>

        <div class="card bg-base-100 shadow-xl">
            <div class="card-body">
                <h2 class="card-title">Income vs Expense</h2>
                <div class="h-80 flex items-center justify-center">
                    <canvas id="pieChart"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    function initCharts() {
        const days = @json($days);
    const incomeData = @json($incomeData);
    const expenseData = @json($expenseData);

    const dailyCtx = document.getElementById('dailyChart').getContext('2d');
    new Chart(dailyCtx, {
        type: 'bar',
        data: {
            labels: days,
            datasets: [
                {
                    label: 'Income',
                    data: incomeData,
                    backgroundColor: 'rgba(34, 197, 94, 0.7)',
                    borderColor: 'rgb(34, 197, 94)',
                    borderWidth: 1
                },
                {
                    label: 'Expense',
                    data: expenseData,
                    backgroundColor: 'rgba(239, 68, 68, 0.7)',
                    borderColor: 'rgb(239, 68, 68)',
                    borderWidth: 1
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true
                }
            },
            plugins: {
                legend: {
                    position: 'top'
                }
            }
        }
    });

    const pieCtx = document.getElementById('pieChart').getContext('2d');
    new Chart(pieCtx, {
        type: 'doughnut',
        data: {
            labels: ['Income', 'Expense'],
            datasets: [{
                data: [{{ $totalIncome }}, {{ $totalExpense }}],
                backgroundColor: [
                    'rgba(34, 197, 94, 0.7)',
                    'rgba(239, 68, 68, 0.7)'
                ],
                borderColor: [
                    'rgb(34, 197, 94)',
                    'rgb(239, 68, 68)'
                ],
                borderWidth: 2
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom'
                }
            }
        }
    });
    }

    if (window.Chart) {
        initCharts();
    } else {
        document.addEventListener('chartjs:ready', initCharts);
    }
</script>
@endsection
