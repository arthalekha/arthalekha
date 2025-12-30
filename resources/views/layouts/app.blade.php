<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Laravel') }}</title>

    <script>
        (function() {
            const theme = localStorage.getItem('theme') || 'forest-light';
            document.documentElement.setAttribute('data-theme', theme);
        })();
    </script>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-base-200">
    <div class="navbar bg-base-100 shadow-sm">
        <div class="flex-1">
            <a href="{{ url('/') }}" class="btn btn-ghost text-xl">{{ config('app.name', 'Laravel') }}</a>
            @auth
                <ul class="menu menu-horizontal px-1">
                    <li><a href="{{ route('accounts.index') }}" class="{{ request()->routeIs('accounts.*') ? 'active' : '' }}">Accounts</a></li>
                    <li><a href="{{ route('incomes.index') }}" class="{{ request()->routeIs('incomes.*') ? 'active' : '' }}">Incomes</a></li>
                    <li><a href="{{ route('expenses.index') }}" class="{{ request()->routeIs('expenses.*') ? 'active' : '' }}">Expenses</a></li>
                    <li><a href="{{ route('transfers.index') }}" class="{{ request()->routeIs('transfers.*') ? 'active' : '' }}">Transfers</a></li>
                    <li><a href="{{ route('tags.index') }}" class="{{ request()->routeIs('tags.*') ? 'active' : '' }}">Tags</a></li>
                    <li>
                        <details>
                            <summary class="{{ request()->routeIs('recurring-*') ? 'active' : '' }}">Recurring</summary>
                            <ul class="bg-base-100 rounded-t-none p-2 z-[1] w-48">
                                <li><a href="{{ route('recurring-incomes.index') }}" class="{{ request()->routeIs('recurring-incomes.*') ? 'active' : '' }}">Incomes</a></li>
                                <li><a href="{{ route('recurring-expenses.index') }}" class="{{ request()->routeIs('recurring-expenses.*') ? 'active' : '' }}">Expenses</a></li>
                                <li><a href="{{ route('recurring-transfers.index') }}" class="{{ request()->routeIs('recurring-transfers.*') ? 'active' : '' }}">Transfers</a></li>
                            </ul>
                        </details>
                    </li>
                </ul>
            @endauth
        </div>
        <div class="flex-none gap-2">
            <label class="swap swap-rotate btn btn-ghost btn-circle">
                <input type="checkbox" id="theme-toggle" class="theme-controller" value="forest-dark">
                <svg class="swap-off h-5 w-5 fill-current" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
                    <path d="M5.64,17l-.71.71a1,1,0,0,0,0,1.41,1,1,0,0,0,1.41,0l.71-.71A1,1,0,0,0,5.64,17ZM5,12a1,1,0,0,0-1-1H3a1,1,0,0,0,0,2H4A1,1,0,0,0,5,12Zm7-7a1,1,0,0,0,1-1V3a1,1,0,0,0-2,0V4A1,1,0,0,0,12,5ZM5.64,7.05a1,1,0,0,0,.7.29,1,1,0,0,0,.71-.29,1,1,0,0,0,0-1.41l-.71-.71A1,1,0,0,0,4.93,6.34Zm12,.29a1,1,0,0,0,.7-.29l.71-.71a1,1,0,1,0-1.41-1.41L17,5.64a1,1,0,0,0,0,1.41A1,1,0,0,0,17.66,7.34ZM21,11H20a1,1,0,0,0,0,2h1a1,1,0,0,0,0-2Zm-9,8a1,1,0,0,0-1,1v1a1,1,0,0,0,2,0V20A1,1,0,0,0,12,19ZM18.36,17A1,1,0,0,0,17,18.36l.71.71a1,1,0,0,0,1.41,0,1,1,0,0,0,0-1.41ZM12,6.5A5.5,5.5,0,1,0,17.5,12,5.51,5.51,0,0,0,12,6.5Zm0,9A3.5,3.5,0,1,1,15.5,12,3.5,3.5,0,0,1,12,15.5Z"/>
                </svg>
                <svg class="swap-on h-5 w-5 fill-current" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
                    <path d="M21.64,13a1,1,0,0,0-1.05-.14,8.05,8.05,0,0,1-3.37.73A8.15,8.15,0,0,1,9.08,5.49a8.59,8.59,0,0,1,.25-2A1,1,0,0,0,8,2.36,10.14,10.14,0,1,0,22,14.05,1,1,0,0,0,21.64,13Zm-9.5,6.69A8.14,8.14,0,0,1,7.08,5.22v.27A10.15,10.15,0,0,0,17.22,15.63a9.79,9.79,0,0,0,2.1-.22A8.11,8.11,0,0,1,12.14,19.73Z"/>
                </svg>
            </label>

            @auth
                <div class="dropdown dropdown-end">
                    <div tabindex="0" role="button" class="btn btn-ghost">
                        {{ Auth::user()->name }}
                        <svg class="fill-current h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                            <path d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"/>
                        </svg>
                    </div>
                    <ul tabindex="0" class="dropdown-content menu bg-base-100 rounded-box z-[1] w-52 p-2 shadow">
                        <li>
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit" class="w-full text-left">Logout</button>
                            </form>
                        </li>
                    </ul>
                </div>
            @else
                <a href="{{ route('login') }}" class="btn btn-ghost">Login</a>
                @if (Route::has('register'))
                    <a href="{{ route('register') }}" class="btn btn-primary">Register</a>
                @endif
            @endauth
        </div>
    </div>

    <main class="container mx-auto px-4 py-8">
        @yield('content')
    </main>

    <footer class="footer footer-center p-4 bg-base-100 text-base-content mt-auto">
        <aside>
            <p>&copy; {{ date('Y') }} {{ config('app.name', 'Laravel') }}. All rights reserved.</p>
        </aside>
    </footer>

    <script>
        (function() {
            const toggle = document.getElementById('theme-toggle');
            const currentTheme = localStorage.getItem('theme') || 'forest-light';

            toggle.checked = currentTheme === 'forest-dark';

            toggle.addEventListener('change', function() {
                const theme = this.checked ? 'forest-dark' : 'forest-light';
                document.documentElement.setAttribute('data-theme', theme);
                localStorage.setItem('theme', theme);
            });
        })();
    </script>
</body>
</html>
