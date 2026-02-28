<x-layouts.app>
<div class="max-w-2xl mx-auto space-y-6">
    <div class="card bg-base-100 shadow-xl">
        <div class="card-body">
            <h2 class="card-title text-2xl font-bold mb-4">Profile Information</h2>

            @if (session('status') === 'profile-information-updated')
                <div class="alert alert-success mb-4">
                    <span>Profile information updated.</span>
                </div>
            @endif

            <form action="{{ route('user-profile-information.update') }}" method="POST">
                @csrf
                @method('PUT')

                <div class="form-control mb-4">
                    <label class="label" for="name">
                        <span class="label-text">Name <span class="text-error">*</span></span>
                    </label>
                    <input type="text" name="name" id="name" value="{{ old('name', $user->name) }}"
                           class="input input-bordered @error('name', 'updateProfileInformation') input-error @enderror"
                           required>
                    @error('name', 'updateProfileInformation')
                        <label class="label">
                            <span class="label-text-alt text-error">{{ $message }}</span>
                        </label>
                    @enderror
                </div>

                <div class="form-control mb-4">
                    <label class="label" for="email">
                        <span class="label-text">Email <span class="text-error">*</span></span>
                    </label>
                    <input type="email" name="email" id="email" value="{{ old('email', $user->email) }}"
                           class="input input-bordered @error('email', 'updateProfileInformation') input-error @enderror"
                           required>
                    @error('email', 'updateProfileInformation')
                        <label class="label">
                            <span class="label-text-alt text-error">{{ $message }}</span>
                        </label>
                    @enderror
                </div>

                <div class="flex justify-end mt-6">
                    <button type="submit" class="btn btn-primary">Update Profile</button>
                </div>
            </form>
        </div>
    </div>

    <div class="card bg-base-100 shadow-xl">
        <div class="card-body">
            <h2 class="card-title text-2xl font-bold mb-4">Preferences</h2>

            @if (session('status') === 'Profile updated.')
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

