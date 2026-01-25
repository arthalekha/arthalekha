<ul class="menu menu-horizontal px-1">
    <li><a href="{{ route('family.accounts.index') }}" @class(['active' => request()->routeIs('family.accounts.*')])>Accounts</a></li>
    <li><a href="{{ route('family.incomes.index') }}" @class(['active' => request()->routeIs('family.incomes.*')])>Incomes</a></li>
    <li><a href="{{ route('family.expenses.index') }}" @class(['active' => request()->routeIs('family.expenses.*')])>Expenses</a></li>
    <li><a href="{{ route('family.transfers.index') }}" @class(['active' => request()->routeIs('family.transfers.*')])>Transfers</a></li>
    <li><a href="{{ route('family.projected-dashboard') }}" @class(['active' => request()->routeIs('family.projected-dashboard')])>Projections</a></li>
    <li>
        <details>
            <summary @class(['active' => request()->routeIs('family.recurring-*')])>Recurring</summary>
            <ul class="bg-base-100 rounded-t-none p-2 z-[1] w-48">
                <li><a href="{{ route('family.recurring-incomes.index') }}" @class(['active' => request()->routeIs('family.recurring-incomes.*')])>Incomes</a></li>
                <li><a href="{{ route('family.recurring-expenses.index') }}" @class(['active' => request()->routeIs('family.recurring-expenses.*')])>Expenses</a></li>
                <li><a href="{{ route('family.recurring-transfers.index') }}" @class(['active' => request()->routeIs('family.recurring-transfers.*')])>Transfers</a></li>
            </ul>
        </details>
    </li>
</ul>
