<?php

namespace App\Http\Controllers;

use App\Http\Requests\InviteUserRequest;
use App\Mail\UserInvitedMail;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class InviteUserController extends Controller
{
    public function create(): View
    {
        return view('users.invite');
    }

    public function store(InviteUserRequest $request): RedirectResponse
    {
        $password = Str::password(12);

        $user = User::create([
            'name' => $request->validated('name'),
            'email' => $request->validated('email'),
            'password' => $password,
        ]);

        Mail::to($user->email)->send(new UserInvitedMail($user, $password));

        return redirect()->route('users.invite')
            ->with('success', 'User invited successfully. An email has been sent with login credentials.');
    }
}
