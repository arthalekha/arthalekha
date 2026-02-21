<x-layouts.app>
<div class="flex items-center justify-center min-h-[60vh]">
    <div class="card bg-base-100 shadow-xl w-full max-w-md">
        <div class="card-body">
            <h2 class="card-title text-2xl font-bold justify-center mb-4">Verify Email</h2>

            <p class="text-sm text-base-content/70 mb-4 text-center">
                Thanks for signing up! Before getting started, could you verify your email address by clicking on the link we just emailed to you?
            </p>

            @if (session('status') == 'verification-link-sent')
                <div class="alert alert-success mb-4">
                    <span>A new verification link has been sent to the email address you provided during registration.</span>
                </div>
            @endif

            <div class="flex flex-col gap-4">
                <form method="POST" action="{{ route('verification.send') }}">
                    @csrf
                    <button type="submit" class="btn btn-primary w-full">
                        Resend Verification Email
                    </button>
                </form>

                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="btn btn-ghost w-full">
                        Logout
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
</x-layouts.app>
