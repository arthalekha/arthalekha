<li>
    <a href="{{ route('family.accounts.index') }}" @class(['active' => request()->routeIs('family.accounts.*')])>
        <x-icons.building-library />
        Accounts
    </a>
</li>
<li>
    <a href="{{ route('family.incomes.index') }}" @class(['active' => request()->routeIs('family.incomes.*')])>
        <x-icons.banknotes />
        Incomes
    </a>
</li>
<li>
    <a href="{{ route('family.expenses.index') }}" @class(['active' => request()->routeIs('family.expenses.*')])>
        <x-icons.credit-card />
        Expenses
    </a>
</li>
<li>
    <a href="{{ route('family.transfers.index') }}" @class(['active' => request()->routeIs('family.transfers.*')])>
        <x-icons.arrows-right-left />
        Transfers
    </a>
</li>
<li>
    <a href="{{ route('family.projected-dashboard') }}" @class(['active' => request()->routeIs('family.projected-dashboard')])>
        <x-icons.chart-bar />
        Projections
    </a>
</li>
<li>
    <details>
        <summary @class(['active' => request()->routeIs('family.recurring-*')])>
            <x-icons.arrow-path />
            Recurring
        </summary>
        <ul class="bg-base-100 rounded-t-none p-2 z-1 w-48">
            <li>
                <a href="{{ route('family.recurring-incomes.index') }}" @class(['active' => request()->routeIs('family.recurring-incomes.*')])>
                    <x-icons.banknotes />
                    Incomes
                </a>
            </li>
            <li>
                <a href="{{ route('family.recurring-expenses.index') }}" @class(['active' => request()->routeIs('family.recurring-expenses.*')])>
                    <x-icons.credit-card />
                    Expenses
                </a>
            </li>
            <li>
                <a href="{{ route('family.recurring-transfers.index') }}" @class(['active' => request()->routeIs('family.recurring-transfers.*')])>
                    <x-icons.arrows-right-left />
                    Transfers
                </a>
            </li>
        </ul>
    </details>
</li>
