<x-layouts.app>
<div class="hero min-h-[60vh]">
    <div class="hero-content text-center">
        <div class="max-w-md">
            <h1 class="text-5xl font-bold">Welcome</h1>
            <p class="py-6">
                You're logged in to {{ config('app.name', 'Laravel') }}.
            </p>
            <a href="{{ route('home') }}" class="btn btn-primary">Go to Dashboard</a>
        </div>
    </div>
</div>
</x-layouts.app>
