<li>
    <a href="{{ route('accounts.index') }}" @class(['active' => request()->routeIs('accounts.*')])>
        <x-icons.building-library />
        Accounts
    </a>
</li>
<li>
    <a href="{{ route('incomes.index') }}" @class(['active' => request()->routeIs('incomes.*')])>
        <x-icons.banknotes />
        Incomes
    </a>
</li>
<li>
    <a href="{{ route('expenses.index') }}" @class(['active' => request()->routeIs('expenses.*')])>
        <x-icons.credit-card />
        Expenses
    </a>
</li>
<li>
    <a href="{{ route('transfers.index') }}" @class(['active' => request()->routeIs('transfers.*')])>
        <x-icons.arrows-right-left />
        Transfers
    </a>
</li>
<li>
    <a href="{{ route('tags.index') }}" @class(['active' => request()->routeIs('tags.*')])>
        <x-icons.tag />
        Tags
    </a>
</li>
<li>
    <a href="{{ route('projected-dashboard') }}" @class(['active' => request()->routeIs('projected-dashboard')])>
        <x-icons.chart-bar />
        Projections
    </a>
</li>
<li>
    <details>
        <summary @class(['active' => request()->routeIs('recurring-*')])>
            <x-icons.arrow-path />
            Recurring
        </summary>
        <ul class="bg-base-100 rounded-t-none p-2 z-1 w-48">
            <li>
                <a href="{{ route('recurring-incomes.index') }}" @class(['active' => request()->routeIs('recurring-incomes.*')])>
                    <x-icons.banknotes />
                    Incomes
                </a>
            </li>
            <li>
                <a href="{{ route('recurring-expenses.index') }}" @class(['active' => request()->routeIs('recurring-expenses.*')])>
                    <x-icons.credit-card />
                    Expenses
                </a>
            </li>
            <li>
                <a href="{{ route('recurring-transfers.index') }}" @class(['active' => request()->routeIs('recurring-transfers.*')])>
                    <x-icons.arrows-right-left />
                    Transfers
                </a>
            </li>
        </ul>
    </details>
</li>
