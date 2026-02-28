<x-layouts.app>
<div class="max-w-2xl mx-auto">
    <div class="card bg-base-100 shadow-xl">
        <div class="card-body">
            <h2 class="card-title text-2xl font-bold mb-4">Profile Settings</h2>

            @if (session('status'))
                <div class="alert alert-success mb-4">
                    <span>{{ session('status') }}</span>
                </div>
            @endif

            <form action="{{ route('profile.update') }}" method="POST">
                @csrf
                @method('PUT')

                <div class="divider">Notifications</div>

                <div class="form-control">
                    <label class="label cursor-pointer flex items-center justify-between">
                        <div class="flex-1">
                            <span class="label-text font-medium">Daily Transaction Reminder</span>
                            <p class="text-sm text-base-content/70">Receive a daily email reminder to log your transactions.</p>
                        </div>
                        <input type="hidden" name="daily_transaction_reminder" value="0">
                        <input type="checkbox" name="daily_transaction_reminder" value="1"
                               class="toggle toggle-primary shrink-0"
                               @checked(old('daily_transaction_reminder', $dailyTransactionReminderEnabled))>
                    </label>
                    @error('daily_transaction_reminder')
                        <label class="label">
                            <span class="label-text-alt text-error">{{ $message }}</span>
                        </label>
                    @enderror
                </div>

                <div class="flex justify-end mt-6">
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>
</x-layouts.app>
