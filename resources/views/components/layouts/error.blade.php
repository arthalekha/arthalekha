@props(['code', 'title', 'message'])

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>{{ $title }} - {{ config('app.name', 'Laravel') }}</title>

    <script>
        (function() {
            const theme = localStorage.getItem('theme') || 'forest-light';
            document.documentElement.setAttribute('data-theme', theme);
        })();
    </script>

    @vite(['resources/css/app.css'])
</head>
<body class="min-h-screen bg-base-200 flex flex-col">
    <div class="navbar bg-base-100 shadow-sm">
        <div class="navbar-start">
            <a href="{{ url('/') }}" class="btn btn-ghost text-xl">
                <x-logo />
                {{ config('app.name', 'Laravel') }}
            </a>
        </div>
    </div>

    <main class="flex-1 flex items-center justify-center px-4 py-8">
        <div class="text-center">
            <p class="text-8xl font-bold text-primary">{{ $code }}</p>
            <h1 class="mt-4 text-3xl font-bold text-base-content">{{ $title }}</h1>
            <p class="mt-2 text-base-content/60">{{ $message }}</p>
            <div class="mt-8">
                <a href="{{ url('/') }}" class="btn btn-primary">Go Home</a>
            </div>
        </div>
    </main>

    <footer class="footer footer-center p-4 bg-base-100 text-base-content mt-auto">
        <aside>
            <p>&copy; {{ date('Y') }} {{ config('app.name', 'Laravel') }}. All rights reserved.</p>
        </aside>
    </footer>
</body>
</html>
