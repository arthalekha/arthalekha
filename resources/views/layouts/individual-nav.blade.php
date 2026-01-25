<ul class="menu menu-horizontal px-1">
    <li><a href="{{ route('accounts.index') }}" @class(['active' => request()->routeIs('accounts.*')])>Accounts</a></li>
    <li><a href="{{ route('incomes.index') }}" @class(['active' => request()->routeIs('incomes.*')])>Incomes</a></li>
    <li><a href="{{ route('expenses.index') }}" @class(['active' => request()->routeIs('expenses.*')])>Expenses</a></li>
    <li><a href="{{ route('transfers.index') }}" @class(['active' => request()->routeIs('transfers.*')])>Transfers</a></li>
    <li><a href="{{ route('tags.index') }}" @class(['active' => request()->routeIs('tags.*')])>Tags</a></li>
    <li><a href="{{ route('projected-dashboard') }}" @class(['active' => request()->routeIs('projected-dashboard')])>Projections</a></li>
    <li>
        <details>
            <summary @class(['active' => request()->routeIs('recurring-*')])>Recurring</summary>
            <ul class="bg-base-100 rounded-t-none p-2 z-[1] w-48">
                <li><a href="{{ route('recurring-incomes.index') }}" @class(['active' => request()->routeIs('recurring-incomes.*')])>Incomes</a></li>
                <li><a href="{{ route('recurring-expenses.index') }}" @class(['active' => request()->routeIs('recurring-expenses.*')])>Expenses</a></li>
                <li><a href="{{ route('recurring-transfers.index') }}" @class(['active' => request()->routeIs('recurring-transfers.*')])>Transfers</a></li>
            </ul>
        </details>
    </li>
</ul>
