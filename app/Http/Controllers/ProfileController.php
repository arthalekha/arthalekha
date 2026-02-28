<?php

namespace App\Http\Controllers;

use App\Features\DailyTransactionReminder;
use App\Http\Requests\ProfileUpdateRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Laravel\Pennant\Feature;

class ProfileController extends Controller
{
    public function edit(Request $request): View
    {
        return view('profile.edit', [
            'user' => $request->user(),
            'dailyTransactionReminderEnabled' => Feature::for($request->user())->active(DailyTransactionReminder::class),
        ]);
    }

    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        if ($validated['daily_transaction_reminder'] ?? false) {
            Feature::for($request->user())->activate(DailyTransactionReminder::class);
        } else {
            Feature::for($request->user())->deactivate(DailyTransactionReminder::class);
        }

        return redirect()->route('profile.edit')->with('status', 'Profile updated.');
    }
}
