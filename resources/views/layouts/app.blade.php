<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Laravel') }}</title>

    <link rel="manifest" href="{{ asset('manifest.json') }}">
    <meta name="theme-color" content="#1a5c2e">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <link rel="apple-touch-icon" href="{{ asset('icons/icon-192x192.png') }}">

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
        <div class="navbar-start">
            @auth
                <div class="dropdown lg:hidden">
                    <div tabindex="0" role="button" class="btn btn-ghost">
                        <x-icons.bars-3 />
                    </div>
                    <ul tabindex="0" class="menu menu-sm dropdown-content bg-base-100 rounded-box z-1 mt-3 w-56 p-2 shadow">
                        @includeUnless(request()->routeIs('family.*'), 'layouts.individual-nav')
                        @includeWhen(request()->routeIs('family.*'), 'layouts.family-nav')
                    </ul>
                </div>
            @endauth
            <a href="{{ url('/') }}" class="btn btn-ghost text-xl">
                <x-logo />
                {{ config('app.name', 'Laravel') }}
            </a>
        </div>
        <div class="navbar-center hidden lg:flex">
            @auth
                <ul class="menu menu-horizontal px-1">
                    @includeUnless(request()->routeIs('family.*'), 'layouts.individual-nav')
                    @includeWhen(request()->routeIs('family.*'), 'layouts.family-nav')
                </ul>
            @endauth
        </div>
        <div class="navbar-end flex items-center gap-2">
            @auth
                <form method="POST" action="{{ route('mode.toggle') }}">
                    @csrf
                    <input type="hidden" name="route" value="{{ request()->route()->getName() }}">
                    <label class="swap btn btn-ghost btn-circle {{ request()->routeIs('family.*') ? 'btn-primary' : '' }}">
                        <input type="checkbox" onclick="this.form.submit()" @checked(request()->routeIs('family.*'))>
                        <svg class="swap-off h-5 w-5 fill-current" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
                            <path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/>
                        </svg>
                        <svg class="swap-on h-5 w-5 fill-current" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
                            <path d="M16 11c1.66 0 2.99-1.34 2.99-3S17.66 5 16 5c-1.66 0-3 1.34-3 3s1.34 3 3 3zm-8 0c1.66 0 2.99-1.34 2.99-3S9.66 5 8 5C6.34 5 5 6.34 5 8s1.34 3 3 3zm0 2c-2.33 0-7 1.17-7 3.5V19h14v-2.5c0-2.33-4.67-3.5-7-3.5zm8 0c-.29 0-.62.02-.97.05 1.16.84 1.97 1.97 1.97 3.45V19h6v-2.5c0-2.33-4.67-3.5-7-3.5z"/>
                        </svg>
                    </label>
                </form>
            @endauth

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
                        <x-icons.user />
                        <span class="hidden sm:inline">{{ Auth::user()->name }}</span>
                        <x-icons.chevron-down />
                    </div>
                    <ul tabindex="0" class="dropdown-content menu bg-base-100 rounded-box z-1 w-52 p-2 shadow">
                        <li>
                            <a href="{{ route('users.index') }}" class="{{ request()->routeIs('users.index') ? 'active' : '' }}">
                                <x-icons.user-group />
                                Manage Users
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('users.invite') }}" class="{{ request()->routeIs('users.invite*') ? 'active' : '' }}">
                                <x-icons.user-plus />
                                Invite User
                            </a>
                        </li>
                        <li>
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit" class="w-full text-left flex items-center gap-2">
                                    <x-icons.arrow-right-start-on-rectangle />
                                    Logout
                                </button>
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

    <script>
        if ('serviceWorker' in navigator) {
            navigator.serviceWorker.register('{{ asset('service-worker.js') }}');
        }
    </script>

    @stack('scripts')
</body>
</html>
